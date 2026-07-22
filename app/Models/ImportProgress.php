<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'source',
    'total_objects',
    'processed_objects',
    'imported_objects',
    'skipped_objects',
    'failed_objects',
    'search_terms_used',
    'processed_ids',
    'status',
    'started_at',
    'completed_at',
    'error_message',
])]
class ImportProgress extends Model
{
    use HasFactory;

    // Casts
    protected function casts(): array
    {
        return [
            'search_terms_used' => 'array',
            'processed_ids' => 'array',
            'total_objects' => 'integer',
            'processed_objects' => 'integer',
            'imported_objects' => 'integer',
            'skipped_objects' => 'integer',
            'failed_objects' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Status management methods
    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get the next batch of IDs to process.
     * Uses the offset (processed_objects) to slice the array.
     */
    public function getNextBatch(int $batchSize = 50): array
    {
        $allIds = $this->processed_ids ?? [];
        $offset = $this->processed_objects;
        return array_slice($allIds, $offset, $batchSize);
    }

    /**
     * Mark a batch as processed by incrementing the counter.
     * 
     * @param int $count Number of items processed in this batch
     */
    public function markAsProcessed(int $count): void
    {
        $this->increment('processed_objects', $count);
    }

    public function isComplete(): bool
    {
        return $this->processed_objects >= $this->total_objects;
    }
}