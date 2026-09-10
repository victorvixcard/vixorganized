<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);

        $position = (int) $project->phases()->max('position') + 1;
        $phase = $project->phases()->create(['name' => $data['name'], 'position' => $position]);
        $project->log('fase', "Fase adicionada: {$phase->name}");
        $project->syncCurrentPhase();

        return back()->with('ok', 'Fase adicionada.');
    }

    public function update(Request $request, Project $project, Phase $phase): RedirectResponse
    {
        $this->guard($project, $phase);
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $phase->update($data);

        return back();
    }

    public function destroy(Project $project, Phase $phase): RedirectResponse
    {
        $this->guard($project, $phase);
        $name = $phase->name;
        $phase->delete();
        $project->log('fase', "Fase removida: {$name}");
        $project->syncCurrentPhase();

        return back()->with('ok', 'Fase removida.');
    }

    /** Marca/desmarca a fase inteira (marca todos os passos junto). */
    public function toggle(Project $project, Phase $phase): RedirectResponse
    {
        $this->guard($project, $phase);
        $done = ! $phase->isDone();

        $phase->steps()->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'done_by' => $done ? auth()->id() : null,
        ]);
        $phase->completed_at = $done ? now() : null;
        $phase->save();

        $project->log('fase', ($done ? 'Fase concluída: ' : 'Fase reaberta: ').$phase->name);
        $project->syncCurrentPhase();

        return back();
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach (array_values($data['order']) as $i => $id) {
            $project->phases()->whereKey($id)->update(['position' => $i + 1]);
        }
        $project->syncCurrentPhase();

        return response()->json(['ok' => true]);
    }

    private function guard(Project $project, Phase $phase): void
    {
        abort_unless($phase->project_id === $project->id, 404);
    }
}
