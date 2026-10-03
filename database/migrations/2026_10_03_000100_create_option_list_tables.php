<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Admin-managed option lists (PRD.md §11).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('option_presets', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['camera', 'lighting', 'background', 'resolution', 'instance_steps']);
            $table->string('name');
            $table->json('values_json');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['type', 'name']);
            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::create('device_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('category', ['desktop', 'laptop', 'tablet', 'phone']);
            $table->string('platform', 32);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
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

        // A preset can serve several device classes (decision D22).
        Schema::create('budget_preset_device_class', function (Blueprint $table) {
            $table->foreignId('budget_preset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_class_id')->constrained()->restrictOnDelete();
            $table->primary(['budget_preset_id', 'device_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_preset_device_class');
        Schema::dropIfExists('budget_presets');
        Schema::dropIfExists('device_classes');
        Schema::dropIfExists('option_presets');
        Schema::dropIfExists('tools');
    }
};
