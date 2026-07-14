<?php

namespace App\DTOs;

class ArtifactDto
{
    public function __construct(
        public readonly string $source,
        public readonly string $source_id,
        public readonly ?string $title,
        public readonly ?string $artist,
        public readonly ?string $culture,
        public readonly ?string $period,
        public readonly ?string $dynasty,
        public readonly ?string $reign,
        public readonly ?string $country,
        public readonly ?string $region,
        public readonly ?int $object_begin_date,
        public readonly ?int $object_end_date,
        public readonly ?string $classification,
        public readonly ?string $date_display,
        public readonly ?string $medium,
        public readonly ?string $dimensions,
        public readonly ?string $description,
        public readonly ?string $primary_image_url,
        public readonly ?array $additional_images,
        public readonly ?string $source_url,
        public readonly ?array $raw_metadata,
    ) {}

    /**
     * Create a DTO from the Met API object response.
     */
    public static function fromMetApi(array $data, string $source): self
    {
        return new self(
            source: $source,
            source_id: (string) ($data['objectID'] ?? ''),
            title: $data['title'] ?? null,
            artist: $data['artistDisplayName'] ?? null,
            culture: $data['culture'] ?? null,
            period: $data['period'] ?? null,
            dynasty: $data['dynasty'] ?? null,
            reign: $data['reign'] ?? null,
            country: $data['country'] ?? null,
            region: $data['region'] ?? null,
            object_begin_date: $data['objectBeginDate'] ?? null,
            object_end_date: $data['objectEndDate'] ?? null,
            classification: $data['classification'] ?? null,
            date_display: $data['objectDate'] ?? null,
            medium: $data['medium'] ?? null,
            dimensions: $data['dimensions'] ?? null,
            description: $data['description'] ?? null,
            primary_image_url: $data['primaryImage'] ?? null,
            additional_images: $data['additionalImages'] ?? null,
            source_url: $data['objectURL'] ?? null,
            raw_metadata: $data,
        );
    }

    /**
     * Convert DTO to array for mass assignment.
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'source_id' => $this->source_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'culture' => $this->culture,
            'period' => $this->period,
            'dynasty' => $this->dynasty,
            'reign' => $this->reign,
            'country' => $this->country,
            'region' => $this->region,
            'object_begin_date' => $this->object_begin_date,
            'object_end_date' => $this->object_end_date,
            'classification' => $this->classification,
            'date_display' => $this->date_display,
            'medium' => $this->medium,
            'dimensions' => $this->dimensions,
            'description' => $this->description,
            'primary_image_url' => $this->primary_image_url,
            'additional_images' => $this->additional_images,
            'source_url' => $this->source_url,
            'raw_metadata' => $this->raw_metadata,
        ];
    }
}