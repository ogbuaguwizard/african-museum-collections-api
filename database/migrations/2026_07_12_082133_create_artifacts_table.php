<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table) {
            // UUID Primary Key
            $table->uuid('id')->primary();
            
            // Source tracking (for the import engine)
            $table->string('source'); // e.g., 'met', 'smithsonian'
            $table->string('source_id'); // Original ID from the museum
            
            // Core descriptive fields
            $table->text('title')->nullable();
            $table->string('artist')->nullable();
            $table->string('culture')->nullable()->index();
            $table->string('period')->nullable()->index();
            $table->string('dynasty')->nullable()->index(); // CRITICAL for African Kingdoms
            $table->string('reign')->nullable();
            $table->string('country')->nullable()->index();
            $table->string('region')->nullable();
            $table->integer('object_begin_date')->nullable();
            $table->integer('object_end_date')->nullable();
            $table->string('classification')->nullable();
            $table->string('date_display')->nullable();
            $table->string('medium')->nullable();
            $table->text('dimensions')->nullable();
            $table->text('description')->nullable();
            
            // Media & References
            $table->string('primary_image_url')->nullable();
            $table->json('additional_images')->nullable();
            $table->string('source_url')->nullable();
            
            // The raw payload (for debugging/future re-normalization)
            $table->json('raw_metadata')->nullable();
            
            // Search optimization (compatible with SQLite & PostgreSQL)
            // We'll use Laravel Scout for real full-text search later
            $table->index(['title', 'description', 'artist', 'culture', 'period', 'dynasty', 'country']);
            
            $table->timestamps();
            
            // Ensure we don't import the same record twice from the same source
            $table->unique(['source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};