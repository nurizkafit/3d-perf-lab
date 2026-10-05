<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stress Test budgets are evaluated at each instance step (BENCHMARK.md §4.3). Decision D37.
// Null for Comparison and Headroom, and for the Stress summary items (file size, texture memory, instances at budget).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_evaluations', function (Blueprint $table) {
            $table->unsignedInteger('step_instances')->nullable()->after('asset_id');
            $table->index(['test_run_id', 'asset_id', 'step_instances']);
        });
    }

    public function down(): void
    {
        Schema::table('budget_evaluations', function (Blueprint $table) {
            $table->dropIndex(['test_run_id', 'asset_id', 'step_instances']);
            $table->dropColumn('step_instances');
        });
    }
};
