<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'status', 'rank', 'owner_id', 'due_date', 'current_phase_id', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (! $project->slug) {
                $project->slug = static::uniqueSlug($project->name);
            }
            if (! $project->rank) {
                $project->rank = (int) static::max('rank') + 1;
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'projeto';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class)->orderBy('position');
    }

    public function currentPhase(): BelongsTo
    {
        return $this->belongsTo(Phase::class, 'current_phase_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function steps(): HasManyThrough
    {
        return $this->hasManyThrough(Step::class, Phase::class);
    }

    /** Percentual de passos concluídos (0..100). */
    public function progress(): int
    {
        $total = (int) ($this->steps_count ?? $this->steps()->count());
        if ($total === 0) {
            return 0;
        }
        $done = (int) ($this->done_steps_count ?? $this->steps()->where('is_done', true)->count());

        return (int) round($done * 100 / $total);
    }

    /** Recalcula a fase atual: a primeira fase ainda não concluída. */
    public function syncCurrentPhase(): void
    {
        $this->load('phases');
        $current = $this->phases->first(fn (Phase $p) => is_null($p->completed_at));
        $this->current_phase_id = $current?->id;

        if (is_null($current) && $this->phases->isNotEmpty()) {
            // Todas as fases concluídas -> projeto concluído.
            $this->status = 'concluido';
            $this->completed_at = $this->completed_at ?? now();
        } elseif ($this->status === 'concluido') {
            // Reabriu alguma fase -> volta para em andamento.
            $this->status = 'em_andamento';
            $this->completed_at = null;
        }

        $this->save();
    }

    public function log(string $action, string $description, ?int $userId = null): Activity
    {
        return $this->activities()->create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => Str::limit($description, 490),
        ]);
    }

    public function statusLabel(): string
    {
        return config("vix.statuses.{$this->status}.label", $this->status);
    }

    public function statusColor(): string
    {
        return config("vix.statuses.{$this->status}.color", 'slate');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->status !== 'concluido' && $this->due_date->isPast();
    }

    /** Renumera rank 1..N sem buracos, preservando a ordem atual. */
    public static function normalizeRanks(): void
    {
        static::orderBy('rank')->orderBy('id')->get()->values()->each(function (Project $p, int $i) {
            if ($p->rank !== $i + 1) {
                $p->forceFill(['rank' => $i + 1])->saveQuietly();
            }
        });
    }
}
