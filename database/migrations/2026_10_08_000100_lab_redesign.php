<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test redesign (LOG.md D39–D54, PROJECT.md M4.2): Objects, Targets, and the Side-by-Side / Crowd result shape.
 * Replaces budget presets, budget evaluations, and the instance-step list. The old test rows were backed up and
 * deleted before this migration (decision D40); the test tables are rebuilt empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Objects: what an asset depicts, from the research plan (D41, D52).
        Schema::create('objects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Required for new uploads (validation). Nullable here only so the column can be added to a filled table.
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('object_id')->nullable()->after('tool_id')->constrained('objects')->restrictOnDelete();
        });

        // Targets replace budget presets (BENCHMARK.md §6).
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('max_load_s', 6, 2);
            $table->unsignedSmallInteger('target_fps');
            $table->decimal('max_file_size_mb', 8, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('device_class_target', function (Blueprint $table) {
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_class_id')->constrained()->restrictOnDelete();
            $table->primary(['target_id', 'device_class_id']);
        });

        Schema::dropIfExists('budget_preset_device_class');
        Schema::dropIfExists('budget_presets');

        DB::table('option_presets')->where('type', 'instance_steps')->delete();
        Schema::table('option_presets', function (Blueprint $table) {
            $table->enum('type', ['camera', 'lighting', 'background', 'resolution'])->change();
        });

        // Test tables in the new shape (PRD.md §12). Empty after D40. `reports` refers to test_sessions; it is empty
        // too, and its foreign key resolves to the new table by name.
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('budget_evaluations');
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('test_runs');
        Schema::dropIfExists('test_session_assets');
        Schema::dropIfExists('test_sessions');

        Schema::create('test_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('method', ['side_by_side', 'crowd']);
            $table->enum('scenario', ['per_asset', 'per_tool'])->nullable();
            $table->json('configuration_json');
            $table->json('target_json');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['method', 'created_at']);
        });

        Schema::create('test_session_assets', function (Blueprint $table) {
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->foreignId('group_tool_id')->nullable()->constrained('tools')->restrictOnDelete();
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
            $table->foreignId('group_tool_id')->nullable()->constrained('tools')->restrictOnDelete();
            $table->enum('motion', ['static', 'spin', 'physics'])->nullable(); // null: load rows
            $table->unsignedInteger('probe_copies')->nullable(); // Crowd probes
            $table->unsignedTinyInteger('repetition');
            $table->json('metrics_json');
            $table->json('samples_json')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('target_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('group_tool_id')->nullable()->constrained('tools')->restrictOnDelete();
            $table->enum('motion', ['static', 'spin', 'physics'])->nullable();
            $table->enum('check_key', ['load', 'smooth', 'file_size', 'smooth_copies']);
            $table->decimal('limit_value', 14, 3)->nullable();
            $table->decimal('measured_value', 14, 3)->nullable();
            $table->enum('label', ['smooth', 'mostly_smooth', 'choppy'])->nullable();
            $table->enum('outcome', ['pass', 'fail', 'not_measured'])->nullable();
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Restores the old structure (empty). Rows deleted under D40 come back only from the JSON backup in
     * storage/app/private/backups/2026-10-07-redesign/.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('target_checks');
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('test_runs');
        Schema::dropIfExists('test_session_assets');
        Schema::dropIfExists('test_sessions');

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
            $table->unsignedInteger('step_instances')->nullable();
            $table->string('item_key', 64);
            $table->decimal('limit_value', 14, 3);
            $table->decimal('measured_value', 14, 3)->nullable();
            $table->enum('outcome', ['pass', 'fail', 'not_measured']);
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();

        Schema::table('option_presets', function (Blueprint $table) {
            $table->enum('type', ['camera', 'lighting', 'background', 'resolution', 'instance_steps'])->change();
        });

        Schema::create('budget_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('items_json');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('budget_preset_device_class', function (Blueprint $table) {
            $table->foreignId('budget_preset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_class_id')->constrained()->restrictOnDelete();
            $table->primary(['budget_preset_id', 'device_class_id']);
        });

        Schema::dropIfExists('device_class_target');
        Schema::dropIfExists('targets');

        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('object_id');
        });
        Schema::dropIfExists('objects');
    }
};
