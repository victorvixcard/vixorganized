@extends('layouts.app')
@section('title', $project->name)

@section('content')
@php $color = $project->statusColor(); @endphp

<div x-data="board()" class="space-y-5">
    {{-- Cabeçalho --}}
    <div class="card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('projects.index') }}" class="text-xs text-slate-500 hover:underline">← Fila de projetos</a>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <span class="rounded bg-ink px-2 py-0.5 text-xs font-bold text-white">#{{ $project->rank }}</span>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ $project->name }}</h1>
                    <span class="pill-{{ $color }}" x-text="statusLabel">{{ $project->statusLabel() }}</span>
                    @if ($project->isOverdue())<span class="pill-red">Atrasado</span>@endif
                </div>
                @if ($project->description)
                    <p class="mt-2 max-w-3xl text-sm whitespace-pre-line text-slate-600">{{ $project->description }}</p>
                @endif
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span>Fase atual: <strong class="text-slate-700" x-text="currentPhase">{{ $project->currentPhase?->name ?? '—' }}</strong></span>
                    @if ($project->owner)<span>Responsável: {{ $project->owner->name }}</span>@endif
                    @if ($project->due_date)<span>Prazo: {{ $project->due_date->format('d/m/Y') }}</span>@endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.edit', $project) }}" class="btn-ghost">Editar</a>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-brand transition-all" :style="`width: ${progress}%`" style="width: {{ $project->progress() }}%"></div>
            </div>
            <span class="w-12 text-right text-sm font-semibold tabular-nums" x-text="progress + '%'">{{ $project->progress() }}%</span>
        </div>
    </div>

    {{-- Kanban: uma coluna por fase --}}
    <div class="overflow-x-auto pb-3">
        <div id="phase-list" class="flex min-w-max items-start gap-3"
             x-init="vixSortable($el, '{{ route('phases.reorder', $project) }}', { handle: '[data-phase-handle]' })">
            @foreach ($project->phases as $phase)
                @php
                    $total = $phase->steps->count();
                    $done = $phase->steps->where('is_done', true)->count();
                    $isCurrent = $project->current_phase_id === $phase->id;
                @endphp
                <section data-id="{{ $phase->id }}" class="w-72 shrink-0 rounded-lg border bg-white shadow-sm transition"
                         :class="{
                            'border-emerald-300 bg-emerald-50/40': phases[{{ $phase->id }}].done,
                            'border-brand ring-2 ring-brand/20': currentPhaseId === {{ $phase->id }},
                            'border-slate-200': !phases[{{ $phase->id }}].done && currentPhaseId !== {{ $phase->id }}
                         }">
                    <header class="flex items-center gap-2 border-b border-slate-100 px-3 py-2">
                        <span data-phase-handle class="font-mono text-slate-300 hover:text-slate-500" title="Arrastar fase">⋮⋮</span>
                        <form method="post" action="{{ route('phases.toggle', [$project, $phase]) }}" title="{{ $phase->isDone() ? 'Reabrir fase' : 'Concluir fase inteira' }}">
                            @csrf
                            <button type="submit" class="grid h-5 w-5 place-items-center rounded border text-xs"
                                    :class="phases[{{ $phase->id }}].done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 text-transparent hover:border-slate-500'">✓</button>
                        </form>
                        <div x-data="{ edit: false }" class="min-w-0 flex-1">
                            <button x-show="!edit" @dblclick="edit = true; $nextTick(() => $refs.in.focus())" class="block w-full truncate text-left text-sm font-semibold" title="Duplo clique para renomear">
                                {{ $loop->iteration }}. {{ $phase->name }}
                            </button>
                            <form x-show="edit" x-cloak method="post" action="{{ route('phases.update', [$project, $phase]) }}">
                                @csrf @method('PUT')
                                <input x-ref="in" name="name" value="{{ $phase->name }}" class="field py-1" @keydown.escape="edit = false" @blur="edit = false" maxlength="80">
                            </form>
                        </div>
                        <span class="text-xs tabular-nums text-slate-500" x-text="`${phases[{{ $phase->id }}].done_count}/${phases[{{ $phase->id }}].total}`">{{ $done }}/{{ $total }}</span>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.outside="open = false" class="px-1 text-slate-400 hover:text-ink">⋯</button>
                            <div x-show="open" x-cloak class="absolute right-0 z-10 mt-1 w-40 rounded-md border border-slate-200 bg-white p-1 shadow-lg">
                                <form method="post" action="{{ route('phases.destroy', [$project, $phase]) }}" onsubmit="return confirm('Remover a fase {{ addslashes($phase->name) }} e seus passos?')">
                                    @csrf @method('DELETE')
                                    <button class="block w-full rounded px-2 py-1.5 text-left text-sm text-red-700 hover:bg-red-50">Remover fase</button>
                                </form>
                            </div>
                        </div>
                    </header>

                    <ul class="space-y-1 p-2" x-init="vixSortable($el, '{{ route('steps.reorder', [$project, $phase]) }}', { handle: '[data-step-handle]' })">
                        @foreach ($phase->steps as $step)
                            <li data-id="{{ $step->id }}" class="group flex items-start gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50"
                                :class="steps[{{ $step->id }}].done && 'opacity-70'">
                                <span data-step-handle class="mt-0.5 font-mono text-xs text-slate-300 opacity-0 group-hover:opacity-100">⋮⋮</span>
                                <button type="button" @click="toggle({{ $step->id }}, '{{ route('steps.toggle', [$project, $step]) }}')"
                                        class="mt-0.5 grid h-4.5 w-4.5 shrink-0 place-items-center rounded border text-[10px] transition"
                                        :class="steps[{{ $step->id }}].done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 text-transparent hover:border-brand'"
                                        title="Marcar como concluído">✓</button>
                                <div x-data="{ edit: false }" class="min-w-0 flex-1">
                                    <div x-show="!edit" @dblclick="edit = true; $nextTick(() => $refs.t.focus())" class="cursor-text text-sm leading-snug" :class="steps[{{ $step->id }}].done && 'line-through text-slate-500'" title="Duplo clique para editar">
                                        {{ $step->title }}
                                        @if ($step->notes)<span class="block text-xs text-slate-500">{{ $step->notes }}</span>@endif
                                    </div>
                                    <div x-show="steps[{{ $step->id }}].done && steps[{{ $step->id }}].meta" x-cloak class="text-[11px] text-emerald-700" x-text="steps[{{ $step->id }}].meta"></div>
                                    <form x-show="edit" x-cloak method="post" action="{{ route('steps.update', [$project, $step]) }}" class="space-y-1">
                                        @csrf @method('PUT')
                                        <input x-ref="t" name="title" value="{{ $step->title }}" class="field py-1 text-sm" maxlength="200" @keydown.escape="edit = false">
                                        <input name="notes" value="{{ $step->notes }}" class="field py-1 text-xs" placeholder="Observação (opcional)" maxlength="2000" @keydown.escape="edit = false">
                                        <div class="flex gap-1"><button class="btn-primary py-0.5 text-xs">Salvar</button><button type="button" @click="edit = false" class="btn-ghost py-0.5 text-xs">Cancelar</button></div>
                                    </form>
                                </div>
                                <form method="post" action="{{ route('steps.destroy', [$project, $step]) }}" onsubmit="return confirm('Remover este passo?')" class="opacity-0 group-hover:opacity-100">
                                    @csrf @method('DELETE')
                                    <button class="px-1 text-xs text-slate-400 hover:text-red-600" title="Remover">✕</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>

                    <form method="post" action="{{ route('steps.store', [$project, $phase]) }}" class="border-t border-slate-100 p-2">
                        @csrf
                        <input name="title" class="field py-1 text-sm" placeholder="+ Adicionar passo e Enter" maxlength="200" required>
                    </form>
                </section>
            @endforeach

            <form method="post" action="{{ route('phases.store', $project) }}" class="w-64 shrink-0 rounded-lg border border-dashed border-slate-300 p-3">
                @csrf
                <label class="label">Nova fase</label>
                <input name="name" class="field py-1 text-sm" placeholder="Nome da fase e Enter" maxlength="80" required>
            </form>
        </div>
    </div>

    {{-- Histórico --}}
    <div class="card p-5">
        <h2 class="mb-3 text-sm font-semibold tracking-wide text-slate-600 uppercase">Histórico</h2>
        @if ($project->activities->isEmpty())
            <p class="text-sm text-slate-500">Nada registrado ainda.</p>
        @else
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($project->activities as $a)
                    <li class="flex gap-3 py-1.5">
                        <span class="w-28 shrink-0 tabular-nums text-slate-400">{{ $a->created_at->format('d/m H:i') }}</span>
                        <span class="w-20 shrink-0 text-slate-500">{{ $a->user?->name ?? 'sistema' }}</span>
                        <span class="text-slate-700">{{ $a->description }}</span>
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
                try {
                    const r = await vixPost(url);
                    this.steps[r.step.id].done = r.step.is_done;
                    this.steps[r.step.id].meta = r.step.is_done ? `${r.step.done_by ?? ''} ${r.step.done_at ?? ''}`.trim() : '';
                    this.phases[r.phase.id].done = r.phase.is_done;
                    this.phases[r.phase.id].done_count = r.phase.done_count;
                    this.phases[r.phase.id].total = r.phase.total;
                    this.progress = r.project.progress;
                    this.statusLabel = r.project.status_label;
                    this.currentPhase = r.project.current_phase ?? '—';
                    this.currentPhaseId = r.project.current_phase_id;
                } catch (e) {
                    console.error(e);
                    alert('Não consegui salvar. Recarregue a página.');
                } finally {
                    this.busy = false;
                }
            },
        };
    }
</script>
@endsection
