<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function evaluations(): HasMany
    {
        return $this->hasMany(BudgetEvaluation::class);
    }

    /** A run expires config('lab.run_expiry_minutes') after start (BENCHMARK.md §5). */
    public function isExpired(): bool
    {
        return $this->started_at !== null && $this->started_at->addMinutes(config('lab.run_expiry_minutes'))->isPast();
    }
}
