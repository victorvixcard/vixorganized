<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Projetos') · VIXORGANIZE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('projects.index') }}" class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-md bg-ink text-sm font-bold text-white">V</span>
                <span class="text-base font-semibold tracking-tight">VIXORGANIZE</span>
            </a>
            <nav class="flex items-center gap-2 text-sm">
                @auth
                    <a href="{{ route('projects.index') }}" class="btn-ghost">Projetos</a>
                    <a href="{{ route('projects.create') }}" class="btn-primary">+ Novo projeto</a>
                    <span class="ml-2 hidden text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-ghost" type="submit">Sair</button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @if (session('ok'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('ok') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
