<?php

namespace App\Models;

use App\Support\RunEstimate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['test_session_id', 'device_class_id', 'environment_json', 'status', 'invalid_reason', 'started_at', 'completed_at', 'created_by'])]
class TestRun extends Model
{
    protected function casts(): array
    {
        return [
            'environment_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }

    public function deviceClass(): BelongsTo
    {
        return $this->belongsTo(DeviceClass::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(TargetCheck::class);
    }

    /** Estimated run time in seconds (Crowd: the maximum) from the test configuration and this device's category (BENCHMARK.md §4.1). */
    public function estimatedSeconds(): int
    {
        $test = $this->session;

        $category = $this->deviceClass?->category;

        return match ($test->method) {
            'side_by_side' => RunEstimate::sideBySideSeconds($test->configuration_json, $test->assets()->count(), $category),
            // The expiry uses the maximum (decision D62).
            'crowd' => RunEstimate::crowdSeconds($test->configuration_json, ...[...$test->crowdShape(), $category])['max'],
            default => 0,
        };
    }

    /** A run expires 30 minutes after start, or after its estimate plus 15 minutes when longer (BENCHMARK.md §5, D59). */
    public function expiresAt(): ?Carbon
    {
        return $this->started_at?->copy()->addMinutes(RunEstimate::expiryMinutes($this->estimatedSeconds()));
    }

    public function isExpired(): bool
    {
        return $this->expiresAt()?->isPast() ?? false;
    }
}
