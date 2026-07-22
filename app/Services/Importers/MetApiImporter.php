<?php

namespace App\Services\Importers;

use App\DTOs\ArtifactDto;
use App\Models\Artifact;
use App\Models\ImportProgress;
use App\Services\AfricanHeritageFilter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetApiImporter
{
    protected string $baseUrl;
    protected string $source = 'met';
    protected int $rateLimitDelay = 500000; // 0.5 sec
    protected AfricanHeritageFilter $filter;
    protected ?ImportProgress $progress = null;
    protected int $africanDepartmentId = 5;

    public function __construct(AfricanHeritageFilter $filter)
    {
        $this->filter = $filter;
        $this->baseUrl = config('services.met_api.base_url', 'https://collectionapi.metmuseum.org/public/collection/v1');
    }

    public function importAll(?int $batchSize = 50, ?int $limit = null, ?int $offset = 0): array
    {
        $this->progress = ImportProgress::where('source', $this->source)
            ->whereIn('status', ['pending', 'running'])
            ->first();

        if (!$this->progress) {
            $objectIds = $this->searchAfricanObjects();
            if (empty($objectIds)) {
                Log::warning('No African objects found.');
                return ['imported' => 0, 'skipped' => 0, 'failed' => 0];
            }
            $objectIds = array_values($objectIds);
            $this->progress = ImportProgress::create([
                'source' => $this->source,
                'total_objects' => count($objectIds),
                'processed_objects' => 0,
                'imported_objects' => 0,
                'skipped_objects' => 0,
                'failed_objects' => 0,
                'search_terms_used' => $this->filter->getSearchTerms(),
                'processed_ids' => $objectIds,
                'status' => 'pending',
            ]);
            Log::info("New import progress: " . count($objectIds) . " objects.");
        }

        if ($this->progress->status === 'completed') {
            return $this->getStats();
        }

        $this->progress->markAsRunning();

        $stats = [
            'imported' => $this->progress->imported_objects,
            'skipped' => $this->progress->skipped_objects,
            'failed' => $this->progress->failed_objects,
        ];

        try {
            $totalToProcess = $limit ?? $this->progress->total_objects;
            $processed = $offset;

            while ($processed < $totalToProcess) {
                $batch = $this->progress->getNextBatch($batchSize);
                if (empty($batch)) {
                    break;
                }

                Log::info(sprintf("Batch: %d-%d of %d", $processed+1, min($processed+count($batch), $totalToProcess), $totalToProcess));

                $batchResults = $this->processBatch($batch);
                $stats['imported'] += $batchResults['imported'];
                $stats['skipped'] += $batchResults['skipped'];
                $stats['failed'] += $batchResults['failed'];

                $this->progress->increment('imported_objects', $batchResults['imported']);
                $this->progress->increment('skipped_objects', $batchResults['skipped']);
                $this->progress->increment('failed_objects', $batchResults['failed']);

                $processed += count($batch);
                $this->progress->markAsProcessed(count($batch));
                $this->progress->refresh();

                if ($limit && $stats['imported'] >= $limit) {
                    Log::info("Reached limit of $limit imported.");
                    break;
                }

                if ($processed % ($batchSize * 10) == 0) {
                    Log::info(sprintf("Progress: %.1f%% (%d/%d)", $this->getProgressPercentage(), $processed, $totalToProcess));
                }
            }

            if ($processed >= $this->progress->total_objects) {
                $this->progress->markAsCompleted();
                Log::info('Import completed.');
            }

        } catch (\Exception $e) {
            $this->progress->markAsFailed($e->getMessage());
            Log::error('Import failed: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Process a batch – strict filters: must have images AND be public domain.
     */
    protected function processBatch(array $objectIds): array
    {
        $stats = ['imported' => 0, 'skipped' => 0, 'failed' => 0];
        foreach ($objectIds as $id) {
            try {
                $data = $this->fetchObject($id);
                if (!$data) {
                    $stats['skipped']++;
                    continue;
                }

                // ✅ REQUIRE: has images (primary or additional)
                $hasPrimaryImage = !empty($data['primaryImage']);
                $hasAdditionalImages = !empty($data['additionalImages']) && is_array($data['additionalImages']) && count($data['additionalImages']) > 0;
                $hasAnyImage = $hasPrimaryImage || $hasAdditionalImages;

                if (!$hasAnyImage) {
                    Log::debug("Skipping $id: No images available.");
                    $stats['skipped']++;
                    continue;
                }

                // ✅ REQUIRE: must be public domain
                $isPublicDomain = $data['isPublicDomain'] ?? false;
                if (!$isPublicDomain) {
                    Log::debug("Skipping $id: Not public domain.");
                    $stats['skipped']++;
                    continue;
                }

                // ✅ REQUIRE: must be African (scoring filter)
                $result = $this->filter->analyze($data);
                if (!$result['is_african']) {
                    Log::debug("Skipping $id: " . $result['reason']);
                    $stats['skipped']++;
                    continue;
                }

                $dto = ArtifactDto::fromMetApi($data, $this->source);
                if ($this->save($dto)) {
                    $stats['imported']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                Log::error("Error on $id: " . $e->getMessage());
                $stats['failed']++;
            }
            usleep($this->rateLimitDelay);
        }
        return $stats;
    }

    /**
     * Multi-strategy search for African artifacts – always require images.
     */
    protected function searchAfricanObjects(): array
    {
        $allIds = [];

        // STRATEGY 1: Direct department search (most reliable)
        $this->searchDepartment($allIds);

        // STRATEGY 2: Search with artistOrCulture parameter
        $this->searchByCulture($allIds);

        // STRATEGY 3: Search with geoLocation parameter
        $this->searchByGeoLocation($allIds);

        // Deduplicate
        $uniqueIds = array_unique($allIds);
        sort($uniqueIds);

        // Remove already imported
        $existing = Artifact::where('source', $this->source)->pluck('source_id')->map(fn($id) => (int)$id)->toArray();
        $uniqueIds = array_diff($uniqueIds, $existing);

        Log::info("Found " . count($uniqueIds) . " unique African objects after combining strategies.");

        return array_values($uniqueIds);
    }

    /**
     * STRATEGY 1: Search within the African Art department.
     */
    protected function searchDepartment(array &$allIds): void
    {
        $artTerms = ['mask', 'figure', 'statue', 'sculpture', 'textile', 'ceramic', 'beadwork', 'vessel', 'weapon', 'staff', 'throne'];

        foreach ($artTerms as $term) {
            $params = [
                'q' => $term,
                'departmentId' => $this->africanDepartmentId,
                'hasImages' => 'true', // ✅ REQUIRES IMAGES AT SEARCH LEVEL
            ];
            $ids = $this->searchWithParams($params);
            $allIds = array_merge($allIds, $ids);
            Log::debug("Department {$this->africanDepartmentId} + q='$term' found " . count($ids) . " objects.");
            usleep(50000);
        }

        $params = [
            'q' => 'art',
            'departmentId' => $this->africanDepartmentId,
            'hasImages' => 'true', // ✅ REQUIRES IMAGES AT SEARCH LEVEL
        ];
        $ids = $this->searchWithParams($params);
        $allIds = array_merge($allIds, $ids);
        Log::debug("Department {$this->africanDepartmentId} + q='art' found " . count($ids) . " objects.");
        usleep(50000);
    }

    /**
     * STRATEGY 2: Search with artistOrCulture=true using high-confidence terms.
     */
    protected function searchByCulture(array &$allIds): void
    {
        $cultureTerms = [
            'Yoruba', 'Benin', 'Igbo', 'Akan', 'Kongo', 'Zulu', 'Asante',
            'Dahomey', 'Nok', 'Ife', 'Mali', 'Songhai', 'Kanem', 'Bornu',
            'Nubia', 'Kush', 'Axum', 'Great Zimbabwe', 'Luba', 'Lunda',
            'Chokwe', 'Fon', 'Dogon', 'Tuareg', 'Berber', 'Swahili',
            'Ashanti', 'Oyo', 'Ile-Ife', 'Meroe', 'Kerma'
        ];

        foreach ($cultureTerms as $term) {
            $params = [
                'q' => $term,
                'artistOrCulture' => 'true',
                'hasImages' => 'true', // ✅ REQUIRES IMAGES AT SEARCH LEVEL
            ];
            $ids = $this->searchWithParams($params);
            $allIds = array_merge($allIds, $ids);
            Log::debug("artistOrCulture + '$term' found " . count($ids) . " objects.");
            usleep(50000);
        }
    }

    /**
     * STRATEGY 3: Search with geoLocation for African countries.
     */
    protected function searchByGeoLocation(array &$allIds): void
    {
        $africanCountries = [
            'Nigeria', 'Ghana', 'Benin', 'Togo', 'Mali', 'Burkina Faso',
            'Senegal', 'Cameroon', 'Ethiopia', 'Kenya', 'Tanzania',
            'Zimbabwe', 'South Africa', 'Namibia', 'Botswana', 'Mozambique',
            'Angola', 'Sudan', 'Egypt', 'Morocco', 'Algeria', 'Tunisia',
            'Libya', 'Congo', 'Ivory Coast', 'Guinea', 'Sierra Leone',
            'Liberia', 'Niger', 'Chad', 'Gabon', 'Rwanda', 'Burundi',
            'Malawi', 'Zambia', 'Lesotho', 'Madagascar', 'Somalia'
        ];

        $artTerms = ['figure', 'mask', 'sculpture', 'textile', 'vessel'];

        foreach ($africanCountries as $country) {
            foreach ($artTerms as $artTerm) {
                $params = [
                    'q' => $artTerm,
                    'geoLocation' => $country,
                    'hasImages' => 'true', // ✅ REQUIRES IMAGES AT SEARCH LEVEL
                ];
                $ids = $this->searchWithParams($params);
                $allIds = array_merge($allIds, $ids);
                usleep(50000);
            }

            $params = [
                'q' => $country,
                'geoLocation' => $country,
                'hasImages' => 'true', // ✅ REQUIRES IMAGES AT SEARCH LEVEL
            ];
            $ids = $this->searchWithParams($params);
            $allIds = array_merge($allIds, $ids);
            usleep(50000);
        }
    }

    /**
     * Helper to perform search with arbitrary parameters.
     */
    protected function searchWithParams(array $params): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/search", $params);
            if ($response->failed()) {
                Log::warning("Search failed with params: " . json_encode($params) . " - status: " . $response->status());
                return [];
            }
            $data = $response->json();
            return $data['objectIDs'] ?? [];
        } catch (\Exception $e) {
            Log::warning("Search exception: " . $e->getMessage() . " with params: " . json_encode($params));
            return [];
        }
    }

    protected function fetchObject(int $id): ?array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/objects/{$id}");
            if ($response->failed()) return null;
            return $response->json();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function save(ArtifactDto $dto): bool
    {
        if (Artifact::where('source', $dto->source)->where('source_id', $dto->source_id)->exists()) {
            return false;
        }
        Artifact::create($dto->toArray());
        return true;
    }

    public function getStats(): array
    {
        if (!$this->progress) {
            $this->progress = ImportProgress::where('source', $this->source)->orderBy('id', 'desc')->first();
        }
        if (!$this->progress) {
            return ['status' => 'no_import', 'total' => 0, 'processed' => 0, 'imported' => 0, 'skipped' => 0, 'failed' => 0];
        }
        return [
            'status' => $this->progress->status,
            'total' => $this->progress->total_objects,
            'processed' => $this->progress->processed_objects,
            'imported' => $this->progress->imported_objects,
            'skipped' => $this->progress->skipped_objects,
            'failed' => $this->progress->failed_objects,
            'started_at' => $this->progress->started_at,
            'completed_at' => $this->progress->completed_at,
        ];
    }

    public function getProgressPercentage(): float
    {
        if (!$this->progress || $this->progress->total_objects === 0) return 0;
        return ($this->progress->processed_objects / $this->progress->total_objects) * 100;
    }
}