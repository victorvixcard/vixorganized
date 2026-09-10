<?php

namespace App\Services;

use App\Models\Project;

class ProjectTemplate
{
    /**
     * Aplica o pipeline padrão (config/vix.php) a um projeto recém-criado.
     *
     * @param  array<string, string[]>|null  $template  fases => passos
     */
    public static function apply(Project $project, ?array $template = null): void
    {
        $template ??= config('vix.template', []);
        $pos = 1;

        foreach ($template as $phaseName => $steps) {
            $phase = $project->phases()->create(['name' => $phaseName, 'position' => $pos++]);
            foreach (array_values($steps) as $i => $title) {
                $phase->steps()->create(['title' => $title, 'position' => $i + 1]);
            }
        }

        $project->syncCurrentPhase();
    }
}
