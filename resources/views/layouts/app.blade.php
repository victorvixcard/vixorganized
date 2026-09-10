<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Projetos') · VIXORGANIZE</title>
    {{-- Aplica o tema antes do primeiro paint para não piscar. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('vix-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] bg-background font-sans text-foreground antialiased">
<div x-data="{ nav: false }" @keydown.escape.window="nav = false" class="flex min-h-[100dvh]">

    {{-- Overlay do drawer (mobile) --}}
    <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-40 bg-foreground/40 backdrop-blur-[2px] lg:hidden"></div>

    {{-- Sidebar: fixa no desktop, drawer no mobile --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-[272px] -translate-x-full flex-col border-r border-border bg-card transition-transform duration-200 lg:translate-x-0"
           :class="nav && 'translate-x-0'" aria-label="Navegação">
        <div class="flex h-14 items-center justify-between border-b border-border px-4">
            <a href="{{ route('projects.index') }}" class="flex items-center gap-2.5 rounded-md">
                <span class="grid h-7 w-7 place-items-center rounded-md bg-primary text-primary-foreground">
                    <i data-lucide="kanban" class="h-4 w-4"></i>
                </span>
                <span class="font-display text-[15px] font-bold tracking-tight">VIXORGANIZE</span>
            </a>
            <button type="button" class="btn-ghost btn-icon lg:hidden" @click="nav = false" aria-label="Fechar menu">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        @auth
        <nav class="flex-1 overflow-y-auto px-3 py-3">
            <a href="{{ route('projects.index') }}"
               class="flex h-9 items-center gap-2.5 rounded-md px-2.5 text-sm font-medium {{ request()->routeIs('projects.index') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                <i data-lucide="list-ordered" class="h-4 w-4"></i> Fila de projetos
            </a>
            <a href="{{ route('projects.create') }}" data-shortcut="n" title="Novo projeto (atalho: N)"
               class="mt-0.5 flex h-9 items-center gap-2.5 rounded-md px-2.5 text-sm font-medium {{ request()->routeIs('projects.create') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                <i data-lucide="plus" class="h-4 w-4"></i> Novo projeto
                <span class="kbd ml-auto">N</span>
            </a>

            <div class="mt-5 mb-1.5 flex items-center justify-between px-2.5">
                <span class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Projetos</span>
                <span class="font-mono text-[11px] text-muted-foreground">{{ $navProjects->count() }}</span>
            </div>
            @forelse ($navProjects as $p)
                @php $active = request()->route('project')?->id === $p->id; @endphp
                <a href="{{ route('projects.show', $p) }}" title="{{ $p->name }}"
                   class="group flex h-9 items-center gap-2.5 rounded-md px-2.5 text-sm {{ $active ? 'bg-primary/10 font-medium text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                    <span class="w-5 shrink-0 text-right font-mono text-xs {{ $active ? 'text-primary' : 'text-muted-foreground/70' }}">{{ $p->rank }}</span>
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background: hsl(var(--status-{{ $p->status }}))" title="{{ config("vix.statuses.{$p->status}.label") }}"></span>
                    <span class="truncate">{{ $p->name }}</span>
                    @if ($p->due_date && $p->due_date->isPast())
                        <i data-lucide="clock" class="ml-auto h-3.5 w-3.5 shrink-0 text-destructive" title="Atrasado"></i>
                    @endif
                </a>
            @empty
                <p class="px-2.5 py-2 text-xs text-muted-foreground">Nenhum projeto ativo.</p>
            @endforelse
            @if ($navDoneCount > 0)
                <a href="{{ route('projects.index', ['concluidos' => 1]) }}" class="mt-1 flex h-8 items-center gap-2.5 rounded-md px-2.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground">
                    <i data-lucide="circle-check" class="h-3.5 w-3.5"></i> Concluídos <span class="ml-auto font-mono">{{ $navDoneCount }}</span>
                </a>
            @endif
        </nav>

        <div class="flex items-center gap-1 border-t border-border p-3">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary/10 font-display text-sm font-bold text-primary">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="min-w-0 flex-1 truncate px-1 text-sm">{{ auth()->user()->name }}</span>
            <button type="button" class="btn-ghost btn-icon" @click="$store.theme.toggle()"
                    :aria-label="$store.theme.dark ? 'Mudar para tema claro' : 'Mudar para tema escuro'" title="Alternar tema">
                <i data-lucide="sun" class="h-4 w-4" x-show="$store.theme.dark" x-cloak></i>
                <i data-lucide="moon" class="h-4 w-4" x-show="!$store.theme.dark"></i>
            </button>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn-ghost btn-icon" type="submit" aria-label="Sair" title="Sair">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                </button>
            </form>
        </div>
        @endauth
    </aside>

    <div class="flex min-w-0 flex-1 flex-col lg:pl-[272px]">
        {{-- Topbar só no mobile --}}
        <header class="sticky top-0 z-30 flex h-14 items-center gap-2 border-b border-border bg-background/85 px-3 backdrop-blur lg:hidden">
            <button type="button" class="btn-ghost btn-icon" @click="nav = true" aria-label="Abrir menu">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>
            <span class="min-w-0 flex-1 truncate text-sm font-semibold">@yield('title', 'Projetos')</span>
            <a href="{{ route('projects.create') }}" class="btn-primary btn-icon" aria-label="Novo projeto"><i data-lucide="plus" class="h-4 w-4"></i></a>
        </header>

        <main class="flex-1 px-4 py-5 lg:px-8 lg:py-7">
            <div class="mx-auto max-w-[1500px]">
                @if ($errors->any())
                    <div class="mb-5 flex gap-3 rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
                        <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4"></i>
                        <ul class="space-y-0.5">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>

    {{-- Toasts --}}
    <div x-data class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6" aria-live="polite">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-md border bg-popover px-3 py-2.5 text-sm shadow-lg"
                 :class="t.type === 'error' ? 'border-destructive/40' : 'border-border'">
                <span class="h-2 w-2 shrink-0 rounded-full" :class="t.type === 'error' ? 'bg-destructive' : 'bg-success'"></span>
                <span class="flex-1" x-text="t.message"></span>
                <button x-show="t.action" type="button" class="btn-outline h-7 px-2 text-xs" @click="t.action.action(); $store.toast.remove(t.id)" x-text="t.action?.label"></button>
                <button type="button" class="btn-ghost h-7 w-7 px-0" @click="$store.toast.remove(t.id)" aria-label="Fechar aviso">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </div>
        </template>
    </div>

    @if (session('ok'))
        <script>document.addEventListener('alpine:initialized', () => Alpine.store('toast').add(@json(session('ok'))));</script>
    @endif
    @if (session('err'))
        <script>document.addEventListener('alpine:initialized', () => Alpine.store('toast').add(@json(session('err')), 'error'));</script>
    @endif
</body>
</html>
