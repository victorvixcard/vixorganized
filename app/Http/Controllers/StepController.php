<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\Project;
use App\Models\Step;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StepController extends Controller
{
    public function store(Request $request, Project $project, Phase $phase): RedirectResponse
    {
        abort_unless($phase->project_id === $project->id, 404);
        $data = $request->validate(['title' => ['required', 'string', 'max:200']]);

        $position = (int) $phase->steps()->max('position') + 1;
        $step = $phase->steps()->create(['title' => $data['title'], 'position' => $position]);
        $project->log('passo', "Passo adicionado em {$phase->name}: {$step->title}");

        // Fase que estava concluída ganhou um passo novo -> reabre.
        if ($phase->syncCompletion()) {
            $project->syncCurrentPhase();
        }

        return back();
    }

    /** CHECK / UNCHECK de um passo. Responde JSON para o toggle sem recarregar. */
    public function toggle(Request $request, Project $project, Step $step): JsonResponse|RedirectResponse
    {
        $phase = $step->phase;
        abort_unless($phase && $phase->project_id === $project->id, 404);

        $step->is_done = ! $step->is_done;
        $step->done_at = $step->is_done ? now() : null;
        $step->done_by = $step->is_done ? auth()->id() : null;
        $step->save();

        $project->log('check', ($step->is_done ? '✔ ' : '↩ ').$phase->name.': '.$step->title);

        $phaseChanged = $phase->syncCompletion();
        if ($phaseChanged) {
            $project->log('fase', ($phase->isDone() ? 'Fase concluída: ' : 'Fase reaberta: ').$phase->name);
        }
        $project->syncCurrentPhase();
        $project->refresh()->loadCount([
            'steps',
            'steps as done_steps_count' => fn ($q) => $q->where('is_done', true),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'step' => [
                    'id' => $step->id,
                    'is_done' => $step->is_done,
                    'done_at' => $step->done_at?->format('d/m H:i'),
                    'done_by' => $step->doneBy?->name,
                ],
                'phase' => [
                    'id' => $phase->id,
                    'is_done' => $phase->isDone(),
                    'done_count' => $phase->steps()->where('is_done', true)->count(),
                    'total' => $phase->steps()->count(),
                ],
                'project' => [
                    'progress' => $project->progress(),
                    'status' => $project->status,
                    'status_label' => $project->statusLabel(),
                    'current_phase_id' => $project->current_phase_id,
                    'current_phase' => $project->currentPhase?->name,
                ],
            ]);
        }

        return back();
    }

    public function update(Request $request, Project $project, Step $step): RedirectResponse
    {
        abort_unless($step->phase?->project_id === $project->id, 404);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $step->update($data);

        return back();
    }

    public function destroy(Project $project, Step $step): RedirectResponse
    {
        $phase = $step->phase;
        abort_unless($phase && $phase->project_id === $project->id, 404);

        $title = $step->title;
        $step->delete();
        $project->log('passo', "Passo removido de {$phase->name}: {$title}");

        if ($phase->syncCompletion()) {
            $project->syncCurrentPhase();
        }

        return back();
    }

    public function reorder(Request $request, Project $project, Phase $phase): JsonResponse
    {
        abort_unless($phase->project_id === $project->id, 404);
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach (array_values($data['order']) as $i => $id) {
            $phase->steps()->whereKey($id)->update(['position' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
