@extends('layouts.app')
@section('title', 'Projetos')

@section('content')
@php $overWip = $inProgress > $wipLimit; @endphp

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Fila de projetos</h1>
        <p class="mt-1 text-sm text-slate-500">
            Posição 1 é a prioridade máxima. Arraste pela alça <span class="font-mono">⋮⋮</span> para reordenar.
        </p>
    </div>
    <div class="flex items-center gap-3 text-sm">
        <span class="{{ $overWip ? 'pill-red' : 'pill-blue' }}" title="Limite de projetos em andamento ao mesmo tempo">
            Em andamento: {{ $inProgress }}/{{ $wipLimit }}
        </span>
        @if ($doneCount > 0)
            <a class="btn-ghost" href="{{ route('projects.index', $showDone ? [] : ['concluidos' => 1]) }}">
                {{ $showDone ? 'Ocultar concluídos' : "Ver concluídos ({$doneCount})" }}
            </a>
        @endif
    </div>
</div>

@if ($overWip)
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
        Tem {{ $inProgress }} projetos em andamento e o limite é {{ $wipLimit }}. Se tudo está em andamento, nada está. Pause ou mande para "Aguardando".
    </div>
@endif

@if ($projects->isEmpty())
    <div class="card p-10 text-center">
        <p class="text-slate-600">Nenhum projeto {{ $showDone ? '' : 'ativo' }} ainda.</p>
        <a href="{{ route('projects.create') }}" class="btn-primary mt-4">+ Criar o primeiro projeto</a>
    </div>
@else
    <ol id="project-list" class="space-y-2"
        x-data
        x-init="vixSortable($el, '{{ route('projects.reorder') }}', { onSaved: () => {
            [...$el.querySelectorAll('[data-rank]')].forEach((n, i) => n.textContent = i + 1);
        } })">
        @foreach ($projects as $project)
            @php
                $progress = $project->progress();
                $color = $project->statusColor();
            @endphp
            <li data-id="{{ $project->id }}" class="card flex items-stretch gap-0 overflow-hidden">
                <div data-handle class="flex w-10 shrink-0 items-center justify-center bg-slate-50 text-slate-400 hover:bg-slate-100" title="Arrastar">
                    <span class="font-mono text-lg leading-none">⋮⋮</span>
                </div>

                <div class="flex w-14 shrink-0 flex-col items-center justify-center border-r border-slate-100">
                    <span data-rank class="text-2xl font-bold tabular-nums text-ink">{{ $project->rank }}</span>
                    <div class="flex gap-0.5">
                        <form method="post" action="{{ route('projects.move', $project) }}">@csrf<input type="hidden" name="dir" value="up">
                            <button class="px-1 text-xs text-slate-400 hover:text-ink" title="Subir" {{ $loop->first ? 'disabled' : '' }}>▲</button></form>
                        <form method="post" action="{{ route('projects.move', $project) }}">@csrf<input type="hidden" name="dir" value="down">
                            <button class="px-1 text-xs text-slate-400 hover:text-ink" title="Descer" {{ $loop->last ? 'disabled' : '' }}>▼</button></form>
                    </div>
                </div>

                <div class="flex min-w-0 flex-1 flex-col gap-2 p-4 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('projects.show', $project) }}" class="truncate text-base font-semibold hover:underline">{{ $project->name }}</a>
                            <span class="pill-{{ $color }}">{{ $project->statusLabel() }}</span>
                            @if ($project->isOverdue())
                                <span class="pill-red">Atrasado</span>
                            @endif
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                            <span>Fase atual: <strong class="text-slate-700">{{ $project->currentPhase?->name ?? '—' }}</strong></span>
                            @if ($project->owner)<span>Resp.: {{ $project->owner->name }}</span>@endif
                            @if ($project->due_date)<span>Prazo: {{ $project->due_date->format('d/m/Y') }}</span>@endif
                            <span>{{ $project->done_steps_count }}/{{ $project->steps_count }} passos</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 sm:w-64">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-{{ $color === 'slate' ? 'slate-400' : $color.'-500' }}" style="width: {{ $progress }}%"></div>
                        </div>
                        <span class="w-10 text-right text-sm font-semibold tabular-nums">{{ $progress }}%</span>
                    </div>

                    <div x-data="{ open: false }" class="relative sm:ml-2">
                        <button @click="open = !open" @click.outside="open = false" class="btn-ghost px-2" title="Mudar status">⋯</button>
                        <div x-show="open" x-cloak class="absolute right-0 z-10 mt-1 w-44 rounded-md border border-slate-200 bg-white p-1 shadow-lg">
                            @foreach (config('vix.statuses') as $key => $st)
                                @if ($key !== $project->status)
                                    <form method="post" action="{{ route('projects.status', $project) }}">
                                        @csrf<input type="hidden" name="status" value="{{ $key }}">
                                        <button class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-slate-100">{{ $st['label'] }}</button>
                                    </form>
                                @endif
                            @endforeach
                            <a href="{{ route('projects.edit', $project) }}" class="block rounded border-t border-slate-100 px-2 py-1.5 text-sm hover:bg-slate-100">Editar</a>
                        </div>
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
@endif
@endsection
