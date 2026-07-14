<?php

namespace App\Services\Importers;

use App\DTOs\ArtifactDto;
use App\Models\Artifact;
use App\Services\AfricanHeritageFilter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetApiImporter
{
    protected string $baseUrl;
    protected string $source = 'met';
    protected int $rateLimitDelay = 100000; // 0.1 second
    protected AfricanHeritageFilter $filter;

    public function __construct(AfricanHeritageFilter $filter)
    {
        $this->filter = $filter;
        $this->baseUrl = config('services.met_api.base_url', 'https://collectionapi.metmuseum.org/public/collection/v1');
    }

    public function import(?int $limit = null, ?int $departmentId = null, ?int $offset = 0, bool $skipFiltering = false): array
    {
        $stats = [
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
            'skipped_by_filter' => 0,
        ];

        // 1. SEARCH for African objects instead of fetching all
        $objectIds = $this->searchAfricanObjects();
        
        if (empty($objectIds)) {
            Log::warning('No African objects found in Met API.');
            return $stats;
        }

        $total = count($objectIds);
        $objectIds = array_slice($objectIds, $offset, $limit);

        Log::info(sprintf("Found %d African objects. Processing %d objects.", $total, count($objectIds)));

        // 2. Fetch each object
        foreach ($objectIds as $index => $objectId) {
            Log::debug(sprintf("Processing %d/%d: ID %d", $index + 1, count($objectIds), $objectId));

            try {
                $data = $this->fetchObject($objectId);
                if (empty($data)) {
                    $stats['skipped']++;
                    continue;
                }

                // Double-check with the filter (optional, but keeps it safe)
                if (!$skipFiltering) {
                    $result = $this->filter->analyze($data);
                    
                    Log::debug(sprintf(
                        "Object %d - African: %s | Confidence: %s | Reason: %s",
                        $objectId,
                        $result['is_african'] ? 'YES' : 'NO',
                        $result['confidence'],
                        $result['reason']
                    ));

                    if (!$result['is_african']) {
                        $stats['skipped_by_filter']++;
                        Log::debug(sprintf("Skipping object %d: %s", $objectId, $result['reason']));
                        continue;
                    }
                }

                $dto = ArtifactDto::fromMetApi($data, $this->source);
                $this->save($dto);
                $stats['imported']++;

            } catch (\Exception $e) {
                Log::error(sprintf("Error importing object %d: %s", $objectId, $e->getMessage()));
                $stats['failed']++;
            }

            usleep($this->rateLimitDelay);
        }

        Log::info(sprintf(
            "Import complete. Imported: %d, Skipped: %d, Skipped by filter: %d, Failed: %d",
            $stats['imported'],
            $stats['skipped'],
            $stats['skipped_by_filter'],
            $stats['failed']
        ));

        return $stats;
    }

    /**
     * Search Met API for African objects using African terms.
     * This is the key method that makes it work!
     */
    protected function searchAfricanObjects(): array
    {
        $allIds = [];
        $searchTerms = array_merge(
            $this->filter->getHighConfidenceTerms(),
            array_slice($this->filter->getGeneralTerms(), 0, 20) // Limit general terms to avoid too many requests
        );

        Log::info(sprintf("Searching Met API with %d African terms...", count($searchTerms)));

        foreach ($searchTerms as $term) {
            // Skip very short terms (like "Benin" is fine, but "Ga" might match too many)
            if (strlen($term) < 3) {
                continue;
            }

            $ids = $this->search($term);
            $allIds = array_merge($allIds, $ids);
            
            Log::debug(sprintf("Term '%s' found %d objects", $term, count($ids)));
            
            // Rate limiting - Met allows 80/sec, we're being conservative
            usleep(50000); // 50ms
        }

        // Remove duplicates
        $uniqueIds = array_unique($allIds);
        
        Log::info(sprintf("Found %d unique object IDs from %d search terms.", count($uniqueIds), count($searchTerms)));

        return $uniqueIds;
    }

    /**
     * Perform a single search query on the Met API.
     */
    protected function search(string $query): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/search", [
                'q' => $query,
                'hasImages' => 'true', // Only get objects with images
            ]);

            if (!$response->successful()) {
                Log::warning(sprintf("Met API search failed for query: %s", $query));
                return [];
            }

            return $response->json('objectIDs') ?? [];

        } catch (\Exception $e) {
            Log::warning(sprintf("Met API search exception for query '%s': %s", $query, $e->getMessage()));
            return [];
        }
    }

    /**
     * Fetch a single object from Met API.
     */
    protected function fetchObject(int $objectId): ?array
    {
        try {
            $url = $this->baseUrl . '/objects/' . $objectId;
            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                Log::warning(sprintf("Failed to fetch object %d: %d", $objectId, $response->status()));
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::warning(sprintf("Failed to fetch object %d: %s", $objectId, $e->getMessage()));
            return null;
        }
    }

    /**
     * Save DTO to database (skip duplicates).
     */
    protected function save(ArtifactDto $dto): void
    {
        $exists = Artifact::where('source', $dto->source)
                          ->where('source_id', $dto->source_id)
                          ->exists();

        if ($exists) {
            Log::info(sprintf("Skipping duplicate: %s", $dto->source_id));
            return;
        }

        Artifact::create($dto->toArray());
    }
}