<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\{Fillable, Scope};
use Illuminate\Database\Eloquent\Builder;

#[Fillable([
    'id',
    'source',
    'source_id',
    'title',
    'artist',
    'culture',
    'period',
    'dynasty',
    'reign',
    'country',
    'region',
    'object_begin_date',
    'object_end_date',
    'classification',
    'date_display',
    'medium',
    'dimensions',
    'description',
    'primary_image_url',
    'additional_images',
    'source_url',
    'raw_metadata',
])]

class Artifact extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    // Casts
    protected function casts(): array
    {
        return [
            'additional_images' => 'array',
            'raw_metadata' => 'array',
            'object_begin_date' => 'integer',
            'object_end_date' => 'integer',
        ];
    }

    // Scopes
    #[Scope]
    public function fromSource(Builder $query, string $source): void
    {
        $query->where('source', $source);
    }

    #[Scope]
    public function culture(Builder $query, string $culture): void
    {
        $query->where('culture', 'LIKE', "%{$culture}%");
    }

    // Accessors
    protected function heritageContext(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->dynasty ?? $this->period ?? $this->culture ?? 'Unspecified'
        );
    }

    protected function fullLocation(): Attribute
    {
        return Attribute::make(
            get: fn() => trim(($this->country ?? '') . ($this->country && $this->region ? ', ' : '') . ($this->region ?? ''))
        );
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->title ?? 'Untitled'
        );
    }

    protected function displayArtist(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->artist ?? 'Unknown artist'
        );
    }

    protected function sourceLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => strtoupper($this->source)
        );
    }

    protected function hasImages(): Attribute
    {
        return Attribute::make(
            get: fn() => !is_null($this->primary_image_url) || !empty($this->additional_images)
        );
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->primary_image_url ?? null
        );
    }

    protected function additionalImageList(): Attribute
    {
        return Attribute::make(
            get: fn() => is_array($this->additional_images) ? array_slice($this->additional_images, 0, 4) : []
        );
    }
}