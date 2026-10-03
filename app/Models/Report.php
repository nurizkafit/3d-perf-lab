<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['test_session_id', 'title', 'question', 'conclusion', 'limitations', 'status', 'public_slug', 'published_at', 'published_by', 'created_by'])]
class Report extends Model
{
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
