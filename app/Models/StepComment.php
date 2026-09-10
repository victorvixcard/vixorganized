<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepComment extends Model
{
    protected $fillable = ['step_id', 'user_id', 'body'];

    public function step(): BelongsTo
    {
        return $this->belongsTo(Step::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
