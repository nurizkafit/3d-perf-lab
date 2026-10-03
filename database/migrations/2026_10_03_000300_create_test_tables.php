<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Performance Lab sessions, runs, results (PRD.md §12, BENCHMARK.md §5).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('method', ['comparison', 'stress', 'headroom']);
            $table->enum('scenario', ['repeated', 'mixed'])->nullable();
            $table->foreignId('tool_constraint_id')->nullable()->constrained('tools')->restrictOnDelete();
            $table->json('configuration_json');
            $table->json('budget_json');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['method', 'created_at']);
        });

        Schema::create('test_session_assets', function (Blueprint $table) {
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->primary(['test_session_id', 'asset_id']);
        });

        // Separate from sessions so a session can have many runs (Device Matrix, BENCHMARK.md §8.2).
        Schema::create('test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_class_id')->constrained()->restrictOnDelete();
            $table->json('environment_json')->nullable();
            $table->enum('status', ['running', 'completed', 'invalid', 'cancelled'])->default('running');
            $table->string('invalid_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'started_at']);
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('step_instances')->nullable();
            $table->unsignedTinyInteger('repetition');
            $table->json('metrics_json');
            $table->json('samples_json')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('budget_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('item_key', 64);
            $table->decimal('limit_value', 14, 3);
            $table->decimal('measured_value', 14, 3)->nullable();
            $table->enum('outcome', ['pass', 'fail', 'not_measured']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_evaluations');
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('test_runs');
        Schema::dropIfExists('test_session_assets');
        Schema::dropIfExists('test_sessions');
    }
};
