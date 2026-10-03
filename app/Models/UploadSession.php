<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['created_by', 'status', 'completed_at'])]
class UploadSession extends Model
{
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
