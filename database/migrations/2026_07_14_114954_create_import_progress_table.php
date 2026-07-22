<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_progress', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // 'met', 'smithsonian', etc.
            $table->integer('total_objects')->default(0);
            $table->integer('processed_objects')->default(0);
            $table->integer('imported_objects')->default(0);
            $table->integer('skipped_objects')->default(0);
            $table->integer('failed_objects')->default(0);
            $table->json('search_terms_used')->nullable();
            $table->json('processed_ids')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_progress');
    }
};