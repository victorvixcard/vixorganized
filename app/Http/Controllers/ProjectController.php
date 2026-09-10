<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $showDone = $request->boolean('concluidos');

        $projects = Project::query()
            ->with(['owner', 'currentPhase', 'phases:id,project_id,name,position,completed_at'])
            ->withCount([
                'steps',
                'steps as done_steps_count' => fn ($q) => $q->where('is_done', true),
            ])
            ->when(! $showDone, fn ($q) => $q->where('status', '!=', 'concluido'))
            ->orderBy('rank')
            ->get();

        $byStatus = Project::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $kpi = [
            'total' => (int) $byStatus->sum(),
            'em_andamento' => (int) ($byStatus['em_andamento'] ?? 0),
            'aguardando' => (int) ($byStatus['aguardando'] ?? 0),
            'pausado' => (int) ($byStatus['pausado'] ?? 0),
            'concluido' => (int) ($byStatus['concluido'] ?? 0),
            'atrasados' => Project::where('status', '!=', 'concluido')->whereDate('due_date', '<', today())->count(),
        ];

        return view('projects.index', [
            'projects' => $projects,
            'kpi' => $kpi,
            'wipLimit' => config('vix.wip_limit'),
            'showDone' => $showDone,
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'users' => User::orderBy('name')->get(),
            'template' => config('vix.template'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $useTemplate = $request->boolean('use_template', true);

        $project = DB::transaction(function () use ($data, $useTemplate) {
            $project = Project::create($data);
            if ($useTemplate) {
                ProjectTemplate::apply($project);
            }
            $project->log('criou', "Projeto criado: {$project->name}");

            return $project;
        });

        return redirect()->route('projects.show', $project)->with('ok', 'Projeto criado.');
    }

    public function show(Project $project): View
    {
        $project->load([
            'owner',
            'currentPhase',
            'phases.steps.doneBy',
            'activities' => fn ($q) => $q->with('user')->limit(30),
        ]);

        // Estado inicial do board para o Alpine (atualizado via JSON nos toggles).
        $board = [
            'progress' => $project->progress(),
            'status' => $project->status,
            'statusLabel' => $project->statusLabel(),
            'currentPhase' => $project->currentPhase?->name,
            'currentPhaseId' => $project->current_phase_id,
            'phases' => (object) $project->phases->mapWithKeys(fn ($p) => [$p->id => [
                'done' => $p->isDone(),
                'done_count' => $p->steps->where('is_done', true)->count(),
                'total' => $p->steps->count(),
            ]])->all(),
            'steps' => (object) $project->phases->flatMap->steps->mapWithKeys(fn ($s) => [$s->id => [
                'done' => $s->is_done,
                'meta' => $s->is_done ? trim(($s->doneBy?->name ?? '').' '.($s->done_at?->format('d/m H:i') ?? '')) : '',
            ]])->all(),
        ];

        return view('projects.show', ['project' => $project, 'board' => $board]);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);
        $old = $project->status;
        $project->update($data);

        if ($old !== $project->status) {
            $project->log('status', "Status: {$project->statusLabel()}");
        }

        return redirect()->route('projects.show', $project)->with('ok', 'Projeto atualizado.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();
        Project::normalizeRanks();

        return redirect()->route('projects.index')->with('ok', 'Projeto excluído.');
    }

    /** Muda o status direto da lista (aguardando / em andamento / pausado). */
    public function status(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('vix.statuses')))],
        ]);

        $project->status = $data['status'];
        $project->completed_at = $data['status'] === 'concluido' ? ($project->completed_at ?? now()) : null;
        $project->save();
        $project->log('status', "Status: {$project->statusLabel()}");

        return back()->with('ok', "{$project->name}: {$project->statusLabel()}.");
    }

    /**
     * Reordena prioridades. Recebe a lista completa de IDs na nova ordem
     * (drag-and-drop) e grava rank 1..N.
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:projects,id'],
        ]);

        DB::transaction(function () use ($data) {
            // Projetos fora da lista enviada (ex.: concluídos ocultos) vão para o fim, mantendo ordem.
            $rest = Project::whereNotIn('id', $data['order'])->orderBy('rank')->pluck('id');
            $ids = array_values(array_merge($data['order'], $rest->all()));

            foreach ($ids as $i => $id) {
                Project::whereKey($id)->update(['rank' => $i + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /** Sobe ou desce uma posição (botões, útil no celular). */
    public function move(Request $request, Project $project): RedirectResponse
    {
        $dir = $request->input('dir') === 'up' ? 'up' : 'down';
        Project::normalizeRanks();
        $project->refresh();

        $neighbor = $dir === 'up'
            ? Project::where('rank', '<', $project->rank)->orderByDesc('rank')->first()
            : Project::where('rank', '>', $project->rank)->orderBy('rank')->first();

        if ($neighbor) {
            [$a, $b] = [$project->rank, $neighbor->rank];
            $project->forceFill(['rank' => $b])->saveQuietly();
            $neighbor->forceFill(['rank' => $a])->saveQuietly();
        }

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(config('vix.statuses')))],
            'owner_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
