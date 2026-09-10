@extends('layouts.app')
@section('title', 'Projetos')

@section('content')
@php $overWip = $kpi['em_andamento'] > $wipLimit; @endphp

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight">Fila de projetos</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Posição 1 é a prioridade máxima. Arraste pela alça ou use as setas para reordenar.
        </p>
    </div>
    @if ($kpi['concluido'] > 0)
        <a class="btn-outline" href="{{ route('projects.index', $showDone ? [] : ['concluidos' => 1]) }}">
            {{ $showDone ? 'Ocultar concluídos' : 'Ver concluídos' }}
        </a>
    @endif
</div>

{{-- KPIs em layout puro, separados por 1px. Sem card dentro de card. --}}
<div class="panel mb-5 grid grid-cols-2 divide-y divide-border sm:grid-cols-4 sm:divide-x sm:divide-y-0">
    <div class="px-4 py-3 {{ $overWip ? 'bg-destructive/5' : '' }}">
        <div class="text-xs text-muted-foreground">Em andamento</div>
        <div class="mt-0.5 flex items-baseline gap-1 font-display text-2xl font-bold tabular-nums {{ $overWip ? 'text-destructive' : '' }}">
            <span class="font-mono">{{ $kpi['em_andamento'] }}</span>
            <span class="font-mono text-sm font-normal text-muted-foreground">/ {{ $wipLimit }}</span>
        </div>
    </div>
    <div class="px-4 py-3">
        <div class="text-xs text-muted-foreground">Aguardando</div>
        <div class="mt-0.5 font-display font-mono text-2xl font-bold tabular-nums">{{ $kpi['aguardando'] }}</div>
    </div>
    <div class="px-4 py-3">
        <div class="text-xs text-muted-foreground">Atrasados</div>
        <div class="mt-0.5 font-display font-mono text-2xl font-bold tabular-nums {{ $kpi['atrasados'] > 0 ? 'text-destructive' : '' }}">{{ $kpi['atrasados'] }}</div>
    </div>
    <div class="px-4 py-3">
        <div class="text-xs text-muted-foreground">Concluídos</div>
        <div class="mt-0.5 font-display font-mono text-2xl font-bold tabular-nums">{{ $kpi['concluido'] }}</div>
    </div>
</div>

@if ($overWip)
    <div class="mb-5 flex gap-3 rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm" role="alert">
        <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 text-destructive"></i>
        <p><strong>Limite de trabalho em andamento estourado.</strong> São {{ $kpi['em_andamento'] }} projetos em andamento para um limite de {{ $wipLimit }}. Se tudo está em andamento, nada está. Pause ou mande para Aguardando.</p>
    </div>
@endif

@if ($projects->isEmpty())
    <x-empty-state icon="list-checks" title="{{ $showDone ? 'Nenhum projeto cadastrado.' : 'Nenhum projeto ativo.' }}">
        Crie o primeiro projeto. Ele já nasce com o pipeline padrão de fases e passos.
        <x-slot:action>
            <a href="{{ route('projects.create') }}" class="btn-primary"><i data-lucide="plus" class="h-4 w-4"></i> Novo projeto</a>
        </x-slot:action>
    </x-empty-state>
@else
    <div class="panel overflow-hidden">
        <div class="hidden grid-cols-[2.5rem_3.25rem_minmax(0,1fr)_11rem_9rem_2.5rem] items-center gap-3 border-b border-border px-2 py-2 text-xs font-medium text-muted-foreground md:grid">
            <span></span>
            <span class="text-center">Pos.</span>
            <span>Projeto</span>
            <span>Fases</span>
            <span class="text-right">Progresso</span>
            <span></span>
        </div>

        <ol id="project-list" class="divide-y divide-border"
            x-data
            x-init="vixSortable($el, '{{ route('projects.reorder') }}', { savedMessage: 'Prioridade salva.', onSaved: () => {
                [...$el.querySelectorAll('[data-rank]')].forEach((n, i) => n.textContent = i + 1);
            } })">
            @foreach ($projects as $project)
                @php $progress = $project->progress(); @endphp
                <li data-id="{{ $project->id }}" class="flex items-start gap-2 px-2 py-3 hover:bg-muted/40 md:grid md:grid-cols-[2.5rem_3.25rem_minmax(0,1fr)_11rem_9rem_2.5rem] md:items-center md:gap-3">
                    <div data-handle class="flex h-11 w-8 shrink-0 items-center justify-center rounded-md md:w-auto text-muted-foreground/60 hover:bg-muted hover:text-foreground" title="Arrastar para reordenar" aria-label="Arrastar">
                        <i data-lucide="grip-vertical" class="h-4 w-4"></i>
                    </div>

                    <div class="flex w-9 shrink-0 flex-col items-center md:w-auto">
                        <span data-rank class="font-mono text-xl font-semibold tabular-nums leading-none">{{ $project->rank }}</span>
                        <div class="mt-1 flex">
                            <form method="post" action="{{ route('projects.move', $project) }}">@csrf<input type="hidden" name="dir" value="up">
                                <button class="btn-ghost h-6 w-6 px-0" aria-label="Subir prioridade" title="Subir" {{ $loop->first ? 'disabled' : '' }}><i data-lucide="chevron-up" class="h-3.5 w-3.5"></i></button></form>
                            <form method="post" action="{{ route('projects.move', $project) }}">@csrf<input type="hidden" name="dir" value="down">
                                <button class="btn-ghost h-6 w-6 px-0" aria-label="Descer prioridade" title="Descer" {{ $loop->last ? 'disabled' : '' }}><i data-lucide="chevron-down" class="h-3.5 w-3.5"></i></button></form>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('projects.show', $project) }}" class="truncate font-medium hover:underline" title="{{ $project->name }}">{{ $project->name }}</a>
                            <x-status-badge :status="$project->status" />
                            @if ($project->isOverdue())
                                <span class="pill-danger"><i data-lucide="clock" class="h-3 w-3"></i> Atrasado</span>
                            @endif
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            <span>Fase atual: <span class="text-foreground">{{ $project->currentPhase?->name ?? 'Nenhuma' }}</span></span>
                            <span class="inline-flex items-center gap-1"><i data-lucide="user" class="h-3 w-3"></i>{{ $project->owner?->name ?? 'Sem responsável' }}</span>
                            <span class="inline-flex items-center gap-1"><i data-lucide="calendar-days" class="h-3 w-3"></i><span class="font-mono">{{ $project->due_date?->format('d/m/Y') ?? 'Sem prazo' }}</span></span>
                        </div>
                        {{-- Mobile: fases e progresso entram aqui embaixo --}}
                        <div class="mt-2 flex items-center gap-3 md:hidden">
                            <x-phase-dots :phases="$project->phases" :current-id="$project->current_phase_id" />
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full bg-primary" style="width: {{ $progress }}%"></div></div>
                            <span class="font-mono text-xs tabular-nums">{{ $progress }}%</span>
                        </div>
                    </div>

                    <div class="hidden md:block">
                        <x-phase-dots :phases="$project->phases" :current-id="$project->current_phase_id" />
                        <div class="mt-1 font-mono text-[11px] text-muted-foreground">{{ $project->done_steps_count }}/{{ $project->steps_count }} passos</div>
                    </div>

                    <div class="hidden items-center gap-2 md:flex">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $progress }}%"></div>
                        </div>
                        <span class="w-10 text-right font-mono text-sm tabular-nums">{{ $progress }}%</span>
                    </div>

                    <div x-data="{ open: false }" class="relative shrink-0 md:justify-self-center" @keydown.escape="open = false">
                        <button type="button" @click="open = !open" @click.outside="open = false" class="btn-ghost btn-icon" :aria-expanded="open" aria-label="Ações do projeto {{ $project->name }}" title="Ações">
                            <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                        </button>
                        <div x-show="open" x-cloak class="menu">
                            <div class="px-2 pt-1 pb-1.5 text-[11px] font-medium tracking-wide text-muted-foreground uppercase">Mudar status</div>
                            @foreach (config('vix.statuses') as $key => $st)
                                @if ($key !== $project->status)
                                    <form method="post" action="{{ route('projects.status', $project) }}">
                                        @csrf<input type="hidden" name="status" value="{{ $key }}">
                                        <button class="menu-item"><span class="h-2 w-2 rounded-full" style="background: hsl(var(--status-{{ $key }}))"></span>{{ $st['label'] }}</button>
                                    </form>
                                @endif
                            @endforeach
                            <div class="menu-sep"></div>
                            <a href="{{ route('projects.edit', $project) }}" class="menu-item"><i data-lucide="pencil" class="h-3.5 w-3.5"></i> Editar</a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="flex items-center justify-between border-t border-border px-4 py-2 text-xs text-muted-foreground">
            <span>Exibindo <span class="font-mono">{{ $projects->count() }}</span> de <span class="font-mono">{{ $kpi['total'] }}</span> projetos</span>
            <span class="hidden items-center gap-1.5 sm:inline-flex"><span class="kbd">N</span> novo projeto</span>
        </div>
    </div>
@endif
@endsection
