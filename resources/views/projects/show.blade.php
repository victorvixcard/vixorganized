@extends('layouts.app')
@section('title', $project->name)

@section('content')
<div x-data="board()" @keydown.escape.window="closeSheet()" class="space-y-5">
    {{-- Cabeçalho --}}
    <div class="panel p-4 sm:p-5">
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Fila de projetos
            </a>
            {{-- Navegação entre projetos --}}
            <div class="flex items-center gap-1 text-xs text-muted-foreground">
                <span class="hidden font-mono sm:inline">{{ $position }}</span>
                <a @if ($prevProject) href="{{ route('projects.show', $prevProject) }}" title="Anterior: {{ $prevProject->name }} (atalho: [)" @else aria-disabled="true" @endif
                   class="btn-ghost btn-icon {{ $prevProject ? '' : 'pointer-events-none opacity-40' }}" data-shortcut="[" aria-label="Projeto anterior">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </a>
                <a @if ($nextProject) href="{{ route('projects.show', $nextProject) }}" title="Próximo: {{ $nextProject->name }} (atalho: ])" @else aria-disabled="true" @endif
                   class="btn-ghost btn-icon {{ $nextProject ? '' : 'pointer-events-none opacity-40' }}" data-shortcut="]" aria-label="Próximo projeto">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </a>
            </div>
        </div>

        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-primary px-2 py-0.5 font-mono text-xs font-semibold text-primary-foreground" title="Posição na fila">#{{ $project->rank }}</span>
                    <h1 class="font-display text-xl font-bold tracking-tight sm:text-2xl">{{ $project->name }}</h1>
                    <x-status-badge :status="$project->status" live />
                    @if ($project->isOverdue())
                        <span class="pill-danger"><i data-lucide="clock" class="h-3 w-3"></i> Atrasado</span>
                    @endif
                </div>
                @if ($project->description)
                    <p class="wrap-any mt-2 max-w-3xl text-sm whitespace-pre-line text-muted-foreground">{{ $project->description }}</p>
                @endif
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-muted-foreground">
                    <span>Fase atual: <span class="font-medium text-foreground" x-text="currentPhase ?? 'Todas concluídas'">{{ $project->currentPhase?->name ?? 'Todas concluídas' }}</span></span>
                    <span class="inline-flex items-center gap-1"><i data-lucide="user" class="h-3 w-3"></i>{{ $project->owner?->name ?? 'Sem responsável' }}</span>
                    <span class="inline-flex items-center gap-1"><i data-lucide="calendar-days" class="h-3 w-3"></i><span class="font-mono">{{ $project->due_date?->format('d/m/Y') ?? 'Sem prazo' }}</span></span>
                </div>
            </div>
            <a href="{{ route('projects.edit', $project) }}" class="btn-outline"><i data-lucide="pencil" class="h-4 w-4"></i> Editar</a>
        </div>

        {{-- Stepper de fases --}}
        @if ($project->phases->isNotEmpty())
            <ol class="mt-5 flex items-center gap-2" aria-label="Fases do projeto">
                @foreach ($project->phases as $i => $phase)
                    <li class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-semibold transition-colors"
                              :class="phases[{{ $phase->id }}].done ? 'bg-success text-white' : (currentPhaseId === {{ $phase->id }} ? 'bg-primary text-primary-foreground pulse-ring' : 'bg-muted text-muted-foreground')">
                            <i data-lucide="check" class="h-3.5 w-3.5" x-show="phases[{{ $phase->id }}].done" x-cloak></i>
                            <span class="font-mono" x-show="!phases[{{ $phase->id }}].done">{{ $i + 1 }}</span>
                        </span>
                        <span class="hidden truncate text-xs xl:block" :class="currentPhaseId === {{ $phase->id }} ? 'font-medium text-foreground' : 'text-muted-foreground'" title="{{ $phase->name }}">{{ $phase->name }}</span>
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
    <div class="-mx-4 overflow-x-auto px-4 pb-3 lg:-mx-8 lg:px-8">
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
                            <li data-id="{{ $step->id }}" class="flex min-h-11 items-start gap-0.5 rounded-md pr-1 transition-colors hover:bg-muted/60"
                                :class="sheet.id === {{ $step->id }} && 'bg-primary/10'">
                                <span data-step-handle class="grid h-11 w-5 shrink-0 place-items-center text-muted-foreground/40 hover:text-foreground" aria-label="Arrastar passo">
                                    <i data-lucide="grip-vertical" class="h-3.5 w-3.5"></i>
                                </span>
                                <button type="button" @click="toggle({{ $step->id }})"
                                        class="grid h-11 w-9 shrink-0 place-items-center"
                                        :aria-pressed="steps[{{ $step->id }}].done"
                                        :aria-label="steps[{{ $step->id }}].done ? 'Desmarcar passo' : 'Marcar passo como concluído'">
                                    <span class="grid h-[18px] w-[18px] place-items-center rounded border transition-colors"
                                          :class="steps[{{ $step->id }}].done ? 'border-success bg-success text-white' : 'border-input bg-card text-transparent hover:border-primary'">
                                        <i data-lucide="check" class="h-3 w-3"></i>
                                    </span>
                                </button>
                                {{-- Clicar no passo abre o painel de detalhe --}}
                                <button type="button" @click="openStep({{ $step->id }}, $el)" class="min-w-0 flex-1 py-2.5 text-left" title="Abrir passo">
                                    <span class="wrap-any block text-sm leading-snug" :class="steps[{{ $step->id }}].done && 'text-muted-foreground line-through'" x-text="steps[{{ $step->id }}].title">{{ $step->title }}</span>
                                    <span class="wrap-any mt-0.5 line-clamp-2 block text-xs text-muted-foreground" x-show="steps[{{ $step->id }}].notes" x-text="steps[{{ $step->id }}].notes">{{ $step->notes }}</span>
                                    <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-[11px]">
                                        <span class="text-success" x-show="steps[{{ $step->id }}].done && steps[{{ $step->id }}].meta" x-cloak x-text="steps[{{ $step->id }}].meta"></span>
                                        <span class="inline-flex items-center gap-1 text-muted-foreground" x-show="steps[{{ $step->id }}].comments > 0" x-cloak>
                                            <i data-lucide="message-square" class="h-3 w-3"></i><span x-text="steps[{{ $step->id }}].comments"></span>
                                        </span>
                                    </span>
                                </button>
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

    {{-- Histórico do projeto --}}
    <div class="panel">
        <div class="flex items-center justify-between border-b border-border px-4 py-3 sm:px-5">
            <h2 class="text-sm font-semibold">Histórico</h2>
            <span class="text-xs text-muted-foreground">Últimos <span class="font-mono">{{ $project->activities->count() }}</span> registros</span>
        </div>
        @if ($project->activities->isEmpty())
            <p class="px-5 py-6 text-center text-sm text-muted-foreground">Nada registrado ainda. Marque um passo e ele aparece aqui.</p>
        @else
            <ul class="divide-y divide-border text-sm">
                @foreach ($project->activities as $a)
                    <li class="flex gap-3 px-4 py-2 sm:gap-4 sm:px-5">
                        <span class="w-20 shrink-0 font-mono text-xs tabular-nums text-muted-foreground sm:w-24">{{ $a->created_at->format('d/m H:i') }}</span>
                        <span class="hidden w-20 shrink-0 truncate text-muted-foreground sm:block" title="{{ $a->user?->name ?? 'sistema' }}">{{ $a->user?->name ?? 'sistema' }}</span>
                        <span class="wrap-any min-w-0 flex-1"><span class="text-muted-foreground sm:hidden">{{ $a->user?->name ?? 'sistema' }}: </span>{{ $a->description }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Painel lateral: detalhe do passo --}}
    <template x-teleport="body">
        <div x-show="sheet.open" x-cloak class="fixed inset-0 z-[55]" role="dialog" aria-modal="true" aria-labelledby="sheet-title">
            <div class="absolute inset-0 bg-foreground/40 backdrop-blur-[2px]" @click="closeSheet()"></div>
            <div class="sheet-enter absolute inset-x-0 bottom-0 flex max-h-[92dvh] flex-col rounded-t-xl border border-border bg-card shadow-2xl sm:inset-y-0 sm:right-0 sm:left-auto sm:w-[440px] sm:max-h-none sm:rounded-none sm:border-y-0 sm:border-r-0"
                 @keydown.escape.stop="closeSheet()">
                <header class="flex items-start gap-2 border-b border-border px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase" x-text="sheet.data?.phase ?? 'Passo'"></p>
                        <h2 id="sheet-title" class="wrap-any mt-0.5 text-base font-semibold" x-text="sheet.data?.title ?? 'Carregando'"></h2>
                    </div>
                    <button type="button" class="btn-ghost btn-icon" @click="closeSheet()" aria-label="Fechar painel">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-4 py-4" x-show="sheet.loading">
                    <div class="space-y-3">
                        <div class="h-10 animate-pulse rounded-md bg-muted"></div>
                        <div class="h-24 animate-pulse rounded-md bg-muted"></div>
                        <div class="h-16 animate-pulse rounded-md bg-muted"></div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto" x-show="!sheet.loading && sheet.data">
                    {{-- Status --}}
                    <div class="flex items-center gap-3 border-b border-border px-4 py-3">
                        <button type="button" @click="toggle(sheet.id)" class="flex items-center gap-2.5 text-sm" :aria-pressed="steps[sheet.id]?.done">
                            <span class="grid h-5 w-5 place-items-center rounded border transition-colors"
                                  :class="steps[sheet.id]?.done ? 'border-success bg-success text-white' : 'border-input text-transparent hover:border-primary'">
                                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                            </span>
                            <span x-text="steps[sheet.id]?.done ? 'Concluído' : 'Marcar como concluído'"></span>
                        </button>
                        <span class="ml-auto font-mono text-[11px] text-success" x-show="steps[sheet.id]?.done" x-text="steps[sheet.id]?.meta"></span>
                    </div>

                    {{-- Edição --}}
                    <form class="space-y-4 border-b border-border px-4 py-4" @submit.prevent="saveStep()">
                        <div>
                            <label class="label" for="sheet-title-input">Título</label>
                            <input id="sheet-title-input" x-ref="sheetTitle" x-model="sheet.form.title" class="field" maxlength="200" required>
                        </div>
                        <div>
                            <label class="label" for="sheet-notes">Observação</label>
                            <textarea id="sheet-notes" x-model="sheet.form.notes" class="field" rows="4" maxlength="2000" placeholder="Contexto, links, o que precisa acontecer."></textarea>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-[11px] text-muted-foreground">Criado em <span x-text="sheet.data?.created_at"></span></span>
                            <button type="submit" class="btn-primary" :disabled="sheet.saving || !sheetDirty()">
                                <span x-text="sheet.saving ? 'Salvando' : 'Salvar'"></span>
                            </button>
                        </div>
                    </form>

                    {{-- Registro --}}
                    <div class="px-4 py-4">
                        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
                            <i data-lucide="message-square" class="h-4 w-4 text-muted-foreground"></i> Registro
                            <span class="font-mono text-xs font-normal text-muted-foreground" x-text="(sheet.data?.comments?.length ?? 0)"></span>
                        </h3>

                        <p class="rounded-md border border-dashed border-input px-3 py-3 text-center text-xs text-muted-foreground" x-show="!sheet.data?.comments?.length">
                            Nenhum registro ainda. Anote o que foi feito, com quem falou, o que travou.
                        </p>

                        <ol class="space-y-3" x-show="sheet.data?.comments?.length">
                            <template x-for="c in sheet.data?.comments ?? []" :key="c.id">
                                <li class="flex gap-3">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-primary/10 font-display text-xs font-bold text-primary" x-text="c.initial"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-baseline gap-x-2">
                                            <span class="text-sm font-medium" x-text="c.user"></span>
                                            <span class="font-mono text-[11px] text-muted-foreground" x-text="c.at"></span>
                                        </div>
                                        <p class="wrap-any mt-0.5 text-sm whitespace-pre-line text-foreground/90" x-text="c.body"></p>
                                    </div>
                                </li>
                            </template>
                        </ol>

                        <form class="mt-4" @submit.prevent="addComment()">
                            <label class="sr-only" for="sheet-comment">Novo registro</label>
                            <textarea id="sheet-comment" x-model="sheet.comment" class="field" rows="3" maxlength="4000" placeholder="Escreva o registro. Ctrl+Enter envia."
                                      @keydown.ctrl.enter.prevent="addComment()" @keydown.meta.enter.prevent="addComment()"></textarea>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[11px] text-muted-foreground">Fica salvo com seu nome, data e hora.</span>
                                <button type="submit" class="btn-outline" :disabled="sheet.posting || !sheet.comment.trim()">
                                    <i data-lucide="send" class="h-3.5 w-3.5"></i> <span x-text="sheet.posting ? 'Enviando' : 'Registrar'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function board() {
        const R = {
            show:    @json(route('steps.show', [$project, '__ID__'])),
            toggle:  @json(route('steps.toggle', [$project, '__ID__'])),
            update:  @json(route('steps.update', [$project, '__ID__'])),
            comment: @json(route('steps.comment', [$project, '__ID__'])),
        };
        const url = (k, id) => R[k].replace('__ID__', id);
        const err = (msg) => Alpine.store('toast').add(msg, 'error', { label: 'Recarregar', action: () => location.reload() });

        return {
            ...@json($board),
            busy: false,
            sheet: { open: false, loading: false, saving: false, posting: false, id: null, data: null, form: { title: '', notes: '' }, comment: '', trigger: null },

            async toggle(id) {
                if (this.busy) return;
                this.busy = true;
                const wasDone = this.steps[id].done;
                try {
                    const r = await vixPost(url('toggle', id));
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
                    console.error(e); err('Não consegui salvar o passo.');
                } finally {
                    this.busy = false;
                }
            },

            async openStep(id, trigger) {
                this.sheet.trigger = trigger ?? null;
                this.sheet.id = id;
                this.sheet.open = true;
                this.sheet.loading = true;
                this.sheet.comment = '';
                this.sheet.data = null;
                try {
                    const r = await vixGet(url('show', id));
                    this.sheet.data = r.data;
                    this.sheet.form = { title: r.data.title, notes: r.data.notes ?? '' };
                    this.steps[id].comments = r.data.comments.length;
                    this.$nextTick(() => { window.vixIcons(); this.$refs.sheetTitle?.focus(); });
                } catch (e) {
                    console.error(e); err('Não consegui abrir o passo.'); this.closeSheet();
                } finally {
                    this.sheet.loading = false;
                }
            },

            closeSheet() {
                if (!this.sheet.open) return;
                this.sheet.open = false;
                const t = this.sheet.trigger;
                this.sheet.id = null;
                this.$nextTick(() => t?.focus());
            },

            sheetDirty() {
                const d = this.sheet.data;
                return !!d && (this.sheet.form.title !== d.title || (this.sheet.form.notes ?? '') !== (d.notes ?? ''));
            },

            async saveStep() {
                if (!this.sheetDirty() || this.sheet.saving) return;
                this.sheet.saving = true;
                try {
                    const r = await vixPost(url('update', this.sheet.id), { title: this.sheet.form.title, notes: this.sheet.form.notes }, 'PUT');
                    this.sheet.data = r.data;
                    this.steps[this.sheet.id].title = r.data.title;
                    this.steps[this.sheet.id].notes = r.data.notes;
                    Alpine.store('toast').add('Passo salvo.');
                } catch (e) {
                    console.error(e); err('Não consegui salvar.');
                } finally {
                    this.sheet.saving = false;
                }
            },

            async addComment() {
                const body = this.sheet.comment.trim();
                if (!body || this.sheet.posting) return;
                this.sheet.posting = true;
                try {
                    const r = await vixPost(url('comment', this.sheet.id), { body });
                    this.sheet.data.comments.push(r.comment);
                    this.steps[this.sheet.id].comments = this.sheet.data.comments.length;
                    this.sheet.comment = '';
                    this.$nextTick(() => window.vixIcons());
                } catch (e) {
                    console.error(e); err('Não consegui registrar.');
                } finally {
                    this.sheet.posting = false;
                }
            },
        };
    }
</script>
@endsection
