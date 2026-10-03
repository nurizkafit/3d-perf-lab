<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reports and public asset tokens (PRD.md §10, §12).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('question')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('limitations')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('public_slug')->nullable()->unique();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('report_public_assets', function (Blueprint $table) {
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('created_at')->nullable();
            $table->primary(['report_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_public_assets');
        Schema::dropIfExists('reports');
    }
};
