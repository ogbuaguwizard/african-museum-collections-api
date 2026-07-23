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

    protected function writeToStdout(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    public function importAll(?int $batchSize = 50, ?int $limit = null, ?int $offset = 0): array
    {
        $this->progress = ImportProgress::where('source', $this->source)
            ->whereIn('status', ['pending', 'running'])
            ->first();

        if (!$this->progress) {
            $objectIds = $this->searchAfricanObjects();
            if (empty($objectIds)) {
                $this->writeToStdout('No African objects found.');
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
            $this->writeToStdout("New import progress: " . count($objectIds) . " objects.");
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

                $this->writeToStdout(sprintf("Batch: %d-%d of %d", $processed+1, min($processed+count($batch), $totalToProcess), $totalToProcess));

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
                    $this->writeToStdout("Reached limit of $limit imported.");
                    break;
                }

                if ($processed % ($batchSize * 10) == 0) {
                    $this->writeToStdout(sprintf("Progress: %.1f%% (%d/%d)", $this->getProgressPercentage(), $processed, $totalToProcess));
                }
            }

            if ($processed >= $this->progress->total_objects) {
                $this->progress->markAsCompleted();
                $this->writeToStdout('Import completed.');
            }

        } catch (\Exception $e) {
            $this->progress->markAsFailed($e->getMessage());
            $this->writeToStdout('Import failed: ' . $e->getMessage());
            Log::error('Import failed: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    protected function processBatch(array $objectIds): array
    {
        $stats = ['imported' => 0, 'skipped' => 0, 'failed' => 0];
        foreach ($objectIds as $id) {
            try {
                $data = $this->fetchObject($id);
                if (!$data) {
                    $this->writeToStdout("SKIP $id: No data fetched.");
                    $stats['skipped']++;
                    continue;
                }

                // REQUIRE: has images (primary or additional)
                $hasPrimaryImage = !empty($data['primaryImage']);
                $hasAdditionalImages = !empty($data['additionalImages']) && is_array($data['additionalImages']) && count($data['additionalImages']) > 0;
                $hasAnyImage = $hasPrimaryImage || $hasAdditionalImages;

                if (!$hasAnyImage) {
                    $this->writeToStdout("SKIP $id: No images available.");
                    $stats['skipped']++;
                    continue;
                }

                // REQUIRE: must be public domain
                $isPublicDomain = $data['isPublicDomain'] ?? false;
                if (!$isPublicDomain) {
                    $this->writeToStdout("SKIP $id: Not public domain.");
                    $stats['skipped']++;
                    continue;
                }

                // REQUIRE: must be African (scoring filter)
                $result = $this->filter->analyze($data);
                if (!$result['is_african']) {
                    $this->writeToStdout("SKIP $id: African filter failed. Score: {$result['score']}, Reason: {$result['reason']}");
                    $stats['skipped']++;
                    continue;
                }

                $dto = ArtifactDto::fromMetApi($data, $this->source);
                if ($this->save($dto)) {
                    $stats['imported']++;
                    $this->writeToStdout("IMPORT $id: Success.");
                } else {
                    $this->writeToStdout("SKIP $id: Duplicate.");
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                $this->writeToStdout("ERROR $id: " . $e->getMessage());
                Log::error("ERROR $id: " . $e->getMessage());
                $stats['failed']++;
            }
            usleep($this->rateLimitDelay);
        }
        return $stats;
    }

    /**
     * Fetch object with proper User-Agent header.
     */
    protected function fetchObject(int $id): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'African-Museum-Collection-API/1.0 (contact@example.com)',
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/objects/{$id}");
            
            if ($response->failed()) {
                $status = $response->status();
                $body = substr($response->body(), 0, 200);
                $this->writeToStdout("ERROR fetching $id: HTTP $status - $body");
                return null;
            }
            
            return $response->json();
            
        } catch (\Exception $e) {
            $this->writeToStdout("EXCEPTION fetching $id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search with proper headers.
     */
    protected function searchWithParams(array $params): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'African-Museum-Collection-API/1.0 (contact@example.com)',
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/search", $params);
            if ($response->failed()) {
                Log::warning("Search failed: " . json_encode($params) . " - status: " . $response->status());
                return [];
            }
            return $response->json('objectIDs') ?? [];
        } catch (\Exception $e) {
            Log::warning("Search exception: " . $e->getMessage());
            return [];
        }
    }

    protected function searchAfricanObjects(): array
    {
        $allIds = [];

        $this->searchDepartment($allIds);
        $this->searchByCulture($allIds);
        $this->searchByGeoLocation($allIds);

        $uniqueIds = array_unique($allIds);
        sort($uniqueIds);

        $existing = Artifact::where('source', $this->source)->pluck('source_id')->map(fn($id) => (int)$id)->toArray();
        $uniqueIds = array_diff($uniqueIds, $existing);

        $this->writeToStdout("Found " . count($uniqueIds) . " unique African objects after combining strategies.");

        return array_values($uniqueIds);
    }

    protected function searchDepartment(array &$allIds): void
    {
        $artTerms = ['mask', 'figure', 'statue', 'sculpture', 'textile', 'ceramic', 'beadwork', 'vessel', 'weapon', 'staff', 'throne'];
        foreach ($artTerms as $term) {
            $params = ['q' => $term, 'departmentId' => $this->africanDepartmentId, 'hasImages' => 'true'];
            $ids = $this->searchWithParams($params);
            $allIds = array_merge($allIds, $ids);
            usleep(50000);
        }
        $params = ['q' => 'art', 'departmentId' => $this->africanDepartmentId, 'hasImages' => 'true'];
        $ids = $this->searchWithParams($params);
        $allIds = array_merge($allIds, $ids);
        usleep(50000);
    }

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
            $params = ['q' => $term, 'artistOrCulture' => 'true', 'hasImages' => 'true'];
            $ids = $this->searchWithParams($params);
            $allIds = array_merge($allIds, $ids);
            usleep(50000);
        }
    }

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
                $params = ['q' => $artTerm, 'geoLocation' => $country, 'hasImages' => 'true'];
                $ids = $this->searchWithParams($params);
                $allIds = array_merge($allIds, $ids);
                usleep(50000);
            }
            $params = ['q' => $country, 'geoLocation' => $country, 'hasImages' => 'true'];
            $ids = $this->searchWithParams($params);
            $allIds = array_merge($allIds, $ids);
            usleep(50000);
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