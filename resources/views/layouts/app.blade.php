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
    <header class="sticky top-0 z-40 border-b border-border bg-background/85 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-[1400px] items-center justify-between gap-4 px-4 lg:px-6">
            <a href="{{ route('projects.index') }}" class="flex items-center gap-2.5 rounded-md">
                <span class="grid h-7 w-7 place-items-center rounded-md bg-primary text-primary-foreground">
                    <i data-lucide="kanban" class="h-4 w-4"></i>
                </span>
                <span class="font-display text-[15px] font-bold tracking-tight">VIXORGANIZE</span>
            </a>

            <nav class="flex items-center gap-1" aria-label="Principal">
                @auth
                    <a href="{{ route('projects.index') }}" class="btn-ghost hidden sm:inline-flex {{ request()->routeIs('projects.index') ? 'bg-muted text-foreground' : '' }}">Projetos</a>
                    <a href="{{ route('projects.create') }}" class="btn-primary ml-1 max-sm:btn-icon" data-shortcut="n" title="Novo projeto (atalho: N)" aria-label="Novo projeto">
                        <i data-lucide="plus" class="h-4 w-4"></i><span class="hidden sm:inline">Novo projeto</span>
                    </a>
                    <span class="mx-2 hidden h-5 w-px bg-border sm:block"></span>
                    <button type="button" class="btn-ghost btn-icon" @click="$store.theme.toggle()" x-data
                            :aria-label="$store.theme.dark ? 'Mudar para tema claro' : 'Mudar para tema escuro'" title="Alternar tema">
                        <i data-lucide="sun" class="h-4 w-4" x-show="$store.theme.dark" x-cloak></i>
                        <i data-lucide="moon" class="h-4 w-4" x-show="!$store.theme.dark"></i>
                    </button>
                    <span class="hidden items-center gap-1.5 px-2 text-sm text-muted-foreground sm:inline-flex">
                        <i data-lucide="user" class="h-4 w-4"></i>{{ auth()->user()->name }}
                    </span>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-ghost btn-icon" type="submit" aria-label="Sair" title="Sair">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                        </button>
                    </form>
                @else
                    <button type="button" class="btn-ghost btn-icon" @click="$store.theme.toggle()" x-data aria-label="Alternar tema">
                        <i data-lucide="sun" class="h-4 w-4" x-show="$store.theme.dark" x-cloak></i>
                        <i data-lucide="moon" class="h-4 w-4" x-show="!$store.theme.dark"></i>
                    </button>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-[1400px] px-4 py-6 lg:px-6 lg:py-8">
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
    </main>

    {{-- Toasts --}}
    <div x-data class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
         aria-live="polite">
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
        <script>
            document.addEventListener('alpine:initialized', () => Alpine.store('toast').add(@json(session('ok'))));
        </script>
    @endif
    @if (session('err'))
        <script>
            document.addEventListener('alpine:initialized', () => Alpine.store('toast').add(@json(session('err')), 'error'));
        </script>
    @endif
</body>
</html>
