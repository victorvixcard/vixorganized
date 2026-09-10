<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Step extends Model
{
    protected $fillable = ['phase_id', 'title', 'notes', 'position', 'is_done', 'done_at', 'done_by'];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
