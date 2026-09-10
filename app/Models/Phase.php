<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    protected $fillable = ['project_id', 'name', 'position', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('position');
    }

    public function isDone(): bool
    {
        return ! is_null($this->completed_at);
    }

    /** A fase fica concluída quando tem ao menos um passo e todos estão marcados. */
    public function syncCompletion(): bool
    {
        $steps = $this->steps()->get(['is_done']);
        $allDone = $steps->isNotEmpty() && $steps->every(fn ($s) => $s->is_done);
        $changed = false;

        if ($allDone && ! $this->completed_at) {
            $this->completed_at = now();
            $changed = true;
        } elseif (! $allDone && $this->completed_at) {
            $this->completed_at = null;
            $changed = true;
        }

        if ($changed) {
            $this->save();
        }

        return $changed;
    }
}
