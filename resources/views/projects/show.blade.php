@extends('layouts.app')
@section('title', $project->name)

@section('content')
<div x-data="board()" class="space-y-5">
    {{-- Cabeçalho --}}
    <div class="panel p-5">
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Fila de projetos
        </a>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-primary px-2 py-0.5 font-mono text-xs font-semibold text-primary-foreground" title="Posição na fila">#{{ $project->rank }}</span>
                    <h1 class="font-display text-2xl font-bold tracking-tight">{{ $project->name }}</h1>
                    <x-status-badge :status="$project->status" live />
                    @if ($project->isOverdue())
                        <span class="pill-danger"><i data-lucide="clock" class="h-3 w-3"></i> Atrasado</span>
                    @endif
                </div>
                @if ($project->description)
                    <p class="mt-2 max-w-3xl text-sm whitespace-pre-line text-muted-foreground">{{ $project->description }}</p>
                @endif
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-muted-foreground">
                    <span>Fase atual: <span class="font-medium text-foreground" x-text="currentPhase ?? 'Todas concluídas'">{{ $project->currentPhase?->name ?? 'Todas concluídas' }}</span></span>
                    <span class="inline-flex items-center gap-1"><i data-lucide="user" class="h-3 w-3"></i>{{ $project->owner?->name ?? 'Sem responsável' }}</span>
                    <span class="inline-flex items-center gap-1"><i data-lucide="calendar-days" class="h-3 w-3"></i><span class="font-mono">{{ $project->due_date?->format('d/m/Y') ?? 'Sem prazo' }}</span></span>
                </div>
            </div>
            <a href="{{ route('projects.edit', $project) }}" class="btn-outline"><i data-lucide="pencil" class="h-4 w-4"></i> Editar</a>
        </div>

        {{-- Stepper de fases (componente canônico de workflow) --}}
        @if ($project->phases->isNotEmpty())
            <ol class="mt-5 flex items-center gap-2" aria-label="Fases do projeto">
                @foreach ($project->phases as $i => $phase)
                    <li class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-semibold transition-colors"
                              :class="phases[{{ $phase->id }}].done ? 'bg-success text-white' : (currentPhaseId === {{ $phase->id }} ? 'bg-primary text-primary-foreground pulse-ring' : 'bg-muted text-muted-foreground')">
                            <i data-lucide="check" class="h-3.5 w-3.5" x-show="phases[{{ $phase->id }}].done" x-cloak></i>
                            <span class="font-mono" x-show="!phases[{{ $phase->id }}].done">{{ $i + 1 }}</span>
                        </span>
                        <span class="hidden truncate text-xs lg:block" :class="currentPhaseId === {{ $phase->id }} ? 'font-medium text-foreground' : 'text-muted-foreground'" title="{{ $phase->name }}">{{ $phase->name }}</span>
                        @if (! $loop->last)
                            <span class="h-px flex-1 rounded-full" :class="phases[{{ $phase->id }}].done ? 'bg-success' : 'bg-border'"></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="mt-4 flex items-center gap-3">
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                <div class="h-full rounded-full bg-primary transition-[width] duration-300" :style="`width: ${progress}%`" style="width: {{ $project->progress() }}%"></div>
            </div>
            <span class="w-12 text-right font-mono text-sm font-semibold tabular-nums" x-text="progress + '%'">{{ $project->progress() }}%</span>
        </div>
    </div>

    {{-- Kanban: uma coluna por fase --}}
    <div class="-mx-4 overflow-x-auto px-4 pb-3 lg:-mx-6 lg:px-6">
        <div id="phase-list" class="flex min-w-max items-start gap-3"
             x-init="vixSortable($el, '{{ route('phases.reorder', $project) }}', { handle: '[data-phase-handle]', savedMessage: 'Ordem das fases salva.' })">
            @foreach ($project->phases as $phase)
                @php
                    $total = $phase->steps->count();
                    $done = $phase->steps->where('is_done', true)->count();
                @endphp
                <section data-id="{{ $phase->id }}" class="flex w-[19rem] shrink-0 flex-col rounded-lg border bg-card transition-colors"
                         :class="phases[{{ $phase->id }}].done ? 'border-success/40' : (currentPhaseId === {{ $phase->id }} ? 'border-primary ring-2 ring-primary/15' : 'border-border')"
                         aria-labelledby="phase-{{ $phase->id }}-title">
                    <header class="flex items-center gap-1 border-b border-border px-2 py-1.5">
                        <span data-phase-handle class="grid h-8 w-6 place-items-center text-muted-foreground/50 hover:text-foreground" title="Arrastar fase" aria-label="Arrastar fase">
                            <i data-lucide="grip-vertical" class="h-4 w-4"></i>
                        </span>
                        <form method="post" action="{{ route('phases.toggle', [$project, $phase]) }}">
                            @csrf
                            <button type="submit" class="grid h-8 w-8 place-items-center rounded-md hover:bg-muted"
                                    :aria-label="phases[{{ $phase->id }}].done ? 'Reabrir fase' : 'Concluir fase inteira'"
                                    :title="phases[{{ $phase->id }}].done ? 'Reabrir fase' : 'Concluir fase inteira'">
                                <span class="grid h-[18px] w-[18px] place-items-center rounded border transition-colors"
                                      :class="phases[{{ $phase->id }}].done ? 'border-success bg-success text-white' : 'border-input text-transparent hover:border-foreground'">
                                    <i data-lucide="check" class="h-3 w-3"></i>
                                </span>
                            </button>
                        </form>

                        <div x-data="{ edit: false }" class="min-w-0 flex-1" @keydown.escape.stop="edit = false" x-on:rename.window="if ($event.detail === {{ $phase->id }}) { edit = true; $nextTick(() => $refs.in.select()) }">
                            <h2 id="phase-{{ $phase->id }}-title" x-show="!edit" class="truncate text-sm font-semibold" title="{{ $phase->name }}">
                                <span class="mr-1 font-mono text-xs text-muted-foreground">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $phase->name }}
                            </h2>
                            <form x-show="edit" x-cloak method="post" action="{{ route('phases.update', [$project, $phase]) }}">
                                @csrf @method('PUT')
                                <input x-ref="in" name="name" value="{{ $phase->name }}" class="field h-8" maxlength="80" aria-label="Nome da fase" @blur="edit = false">
                            </form>
                        </div>

                        <span class="font-mono text-xs tabular-nums text-muted-foreground" x-text="`${phases[{{ $phase->id }}].done_count}/${phases[{{ $phase->id }}].total}`">{{ $done }}/{{ $total }}</span>

                        <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                            <button type="button" @click="open = !open" @click.outside="open = false" class="btn-ghost h-8 w-8 px-0" :aria-expanded="open" aria-label="Ações da fase {{ $phase->name }}">
                                <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                            </button>
                            <div x-show="open" x-cloak class="menu">
                                <button type="button" class="menu-item" @click="open = false; $dispatch('rename', {{ $phase->id }})"><i data-lucide="pencil" class="h-3.5 w-3.5"></i> Renomear</button>
                                <div class="menu-sep"></div>
                                <form method="post" action="{{ route('phases.destroy', [$project, $phase]) }}" onsubmit="return confirm('Remover a fase {{ addslashes($phase->name) }} e seus passos?')">
                                    @csrf @method('DELETE')
                                    <button class="menu-item menu-item-danger"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Remover fase</button>
                                </form>
                            </div>
                        </div>
                    </header>

                    <ul class="flex-1 p-1.5" x-init="vixSortable($el, '{{ route('steps.reorder', [$project, $phase]) }}', { handle: '[data-step-handle]', savedMessage: 'Ordem dos passos salva.' })">
                        @forelse ($phase->steps as $step)
                            <li data-id="{{ $step->id }}" class="flex min-h-11 items-start gap-0.5 rounded-md pr-1 hover:bg-muted/60">
                                <span data-step-handle class="grid h-11 w-5 shrink-0 place-items-center text-muted-foreground/40 hover:text-foreground" aria-label="Arrastar passo">
                                    <i data-lucide="grip-vertical" class="h-3.5 w-3.5"></i>
                                </span>
                                {{-- Alvo de toque de 44px para o CHECK --}}
                                <button type="button" @click="toggle({{ $step->id }}, '{{ route('steps.toggle', [$project, $step]) }}')"
                                        class="grid h-11 w-9 shrink-0 place-items-center"
                                        :aria-pressed="steps[{{ $step->id }}].done"
                                        :aria-label="steps[{{ $step->id }}].done ? 'Desmarcar passo' : 'Marcar passo como concluído'">
                                    <span class="grid h-[18px] w-[18px] place-items-center rounded border transition-colors"
                                          :class="steps[{{ $step->id }}].done ? 'border-success bg-success text-white' : 'border-input bg-card text-transparent hover:border-primary'">
                                        <i data-lucide="check" class="h-3 w-3"></i>
                                    </span>
                                </button>
                                <div x-data="{ edit: false }" class="min-w-0 flex-1 py-2.5" @keydown.escape.stop="edit = false">
                                    <div x-show="!edit">
                                        <button type="button" @click="edit = true; $nextTick(() => $refs.t.select())" class="block w-full text-left text-sm leading-snug" :class="steps[{{ $step->id }}].done && 'text-muted-foreground line-through'" title="Editar passo">{{ $step->title }}</button>
                                        @if ($step->notes)<p class="mt-0.5 text-xs text-muted-foreground">{{ $step->notes }}</p>@endif
                                        <p x-show="steps[{{ $step->id }}].done && steps[{{ $step->id }}].meta" x-cloak class="mt-0.5 font-mono text-[11px] text-success" x-text="steps[{{ $step->id }}].meta"></p>
                                    </div>
                                    <form x-show="edit" x-cloak method="post" action="{{ route('steps.update', [$project, $step]) }}" class="space-y-1.5">
                                        @csrf @method('PUT')
                                        <input x-ref="t" name="title" value="{{ $step->title }}" class="field h-8 text-sm" maxlength="200" aria-label="Título do passo">
                                        <input name="notes" value="{{ $step->notes }}" class="field h-8 text-xs" placeholder="Observação (opcional)" maxlength="2000" aria-label="Observação">
                                        <div class="flex gap-1"><button class="btn-primary h-7 px-2 text-xs">Salvar</button><button type="button" @click="edit = false" class="btn-ghost h-7 px-2 text-xs">Cancelar</button></div>
                                    </form>
                                </div>
                                {{-- Remover sempre visível (touch não tem hover) --}}
                                <form method="post" action="{{ route('steps.destroy', [$project, $step]) }}" onsubmit="return confirm('Remover este passo?')" class="shrink-0">
                                    @csrf @method('DELETE')
                                    <button class="grid h-11 w-8 place-items-center text-muted-foreground/40 hover:text-destructive" aria-label="Remover passo" title="Remover passo">
                                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                    </button>
                                </form>
                            </li>
                        @empty
                            <li class="px-2 py-4 text-center text-xs text-muted-foreground">Nenhum passo nesta fase. Adicione o primeiro abaixo.</li>
                        @endforelse
                    </ul>

                    <form method="post" action="{{ route('steps.store', [$project, $phase]) }}" class="border-t border-border p-1.5">
                        @csrf
                        <div class="relative">
                            <i data-lucide="plus" class="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"></i>
                            <input name="title" class="field h-9 pl-8 text-sm" placeholder="Adicionar passo e Enter" maxlength="200" required aria-label="Novo passo em {{ $phase->name }}">
                        </div>
                    </form>
                </section>
            @endforeach

            <form method="post" action="{{ route('phases.store', $project) }}" class="w-64 shrink-0 rounded-lg border border-dashed border-input p-3">
                @csrf
                <label class="label" for="new-phase">Nova fase</label>
                <input id="new-phase" name="name" class="field h-9 text-sm" placeholder="Nome da fase e Enter" maxlength="80" required>
            </form>
        </div>
    </div>

    {{-- Histórico --}}
    <div class="panel">
        <div class="flex items-center justify-between border-b border-border px-5 py-3">
            <h2 class="text-sm font-semibold">Histórico</h2>
            <span class="text-xs text-muted-foreground">Últimos <span class="font-mono">{{ $project->activities->count() }}</span> registros</span>
        </div>
        @if ($project->activities->isEmpty())
            <p class="px-5 py-6 text-center text-sm text-muted-foreground">Nada registrado ainda. Marque um passo e ele aparece aqui.</p>
        @else
            <ul class="divide-y divide-border text-sm">
                @foreach ($project->activities as $a)
                    <li class="flex gap-4 px-5 py-2">
                        <span class="w-24 shrink-0 font-mono text-xs tabular-nums text-muted-foreground">{{ $a->created_at->format('d/m H:i') }}</span>
                        <span class="w-20 shrink-0 truncate text-muted-foreground" title="{{ $a->user?->name ?? 'sistema' }}">{{ $a->user?->name ?? 'sistema' }}</span>
                        <span class="min-w-0 flex-1">{{ $a->description }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<script>
    function board() {
        return {
            ...@json($board),
            busy: false,

            async toggle(id, url) {
                if (this.busy) return;
                this.busy = true;
                const wasDone = this.steps[id].done;
                try {
                    const r = await vixPost(url);
                    this.steps[r.step.id].done = r.step.is_done;
                    this.steps[r.step.id].meta = r.step.is_done ? `${r.step.done_by ?? ''} ${r.step.done_at ?? ''}`.trim() : '';
                    this.phases[r.phase.id].done = r.phase.is_done;
                    this.phases[r.phase.id].done_count = r.phase.done_count;
                    this.phases[r.phase.id].total = r.phase.total;
                    this.progress = r.project.progress;
                    this.status = r.project.status;
                    this.statusLabel = r.project.status_label;
                    this.currentPhase = r.project.current_phase;
                    this.currentPhaseId = r.project.current_phase_id;
                    if (r.phase.is_done && !wasDone) {
                        Alpine.store('toast').add('Fase concluída. Fase atual: ' + (r.project.current_phase ?? 'todas concluídas') + '.');
                    }
                } catch (e) {
                    console.error(e);
                    Alpine.store('toast').add('Não consegui salvar o passo.', 'error', { label: 'Recarregar', action: () => location.reload() });
                } finally {
                    this.busy = false;
                }
            },
        };
    }
</script>
@endsection
