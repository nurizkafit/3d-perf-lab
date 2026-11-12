<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Production time is stored in minutes instead of hours. Existing values are multiplied by 60. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('production_time_hours', 10, 2)->change();
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->renameColumn('production_time_hours', 'production_time_minutes');
        });
        DB::table('assets')->update(['production_time_minutes' => DB::raw('production_time_minutes * 60')]);
    }

    public function down(): void
    {
        DB::table('assets')->update(['production_time_minutes' => DB::raw('production_time_minutes / 60')]);
        Schema::table('assets', function (Blueprint $table) {
            $table->renameColumn('production_time_minutes', 'production_time_hours');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('production_time_hours', 8, 2)->change();
        });
    }
};
