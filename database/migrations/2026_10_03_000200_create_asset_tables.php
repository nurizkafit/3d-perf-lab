<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Assets and bulk upload (PRD.md §7, §12).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('tool_id')->constrained()->restrictOnDelete();
            $table->string('author');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('production_time_hours', 8, 2);
            $table->enum('format', ['glb', 'gltf']);
            $table->string('web_file_path');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->char('sha256', 64)->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index('name');
            $table->index('author');
        });

        Schema::create('asset_metrics', function (Blueprint $table) {
            $table->foreignId('asset_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('vertices')->nullable();
            $table->unsignedInteger('triangles')->nullable();
            $table->unsignedInteger('meshes')->nullable();
            $table->unsignedInteger('materials')->nullable();
            $table->unsignedInteger('textures')->nullable();
            $table->json('texture_dimensions_json')->nullable();
            $table->unsignedBigInteger('texture_memory_estimate_bytes')->nullable();
            $table->timestamps();
        });

        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['open', 'completed', 'expired'])->default('open');
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
        });

        Schema::create('upload_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->string('staged_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->enum('format', ['glb', 'gltf'])->nullable();
            $table->json('metrics_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->enum('status', ['uploading', 'staged', 'invalid', 'registered', 'failed', 'removed'])->default('uploading');
            $table->json('error_json')->nullable();
            $table->timestamps();

            $table->index(['upload_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_items');
        Schema::dropIfExists('upload_sessions');
        Schema::dropIfExists('asset_metrics');
        Schema::dropIfExists('assets');
    }
};
