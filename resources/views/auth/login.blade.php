@extends('layouts.app')
@section('title', 'Entrar')

@section('content')
<div class="mx-auto mt-10 w-full max-w-sm sm:mt-16">
    <div class="mb-6 text-center">
        <span class="mx-auto grid h-11 w-11 place-items-center rounded-lg bg-primary text-primary-foreground">
            <i data-lucide="kanban" class="h-5 w-5"></i>
        </span>
        <h1 class="mt-4 font-display text-2xl font-bold tracking-tight">Entrar</h1>
        <p class="mt-1 text-sm text-muted-foreground">Fila de projetos e kanban de fases.</p>
    </div>

    <form method="post" action="{{ route('login') }}" class="panel space-y-4 p-6" novalidate>
        @csrf
        <div>
            <label class="label" for="email">E-mail</label>
            <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" inputmode="email">
        </div>
        <div>
            <label class="label" for="password">Senha</label>
            <input class="field" id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <label class="flex items-center gap-2 text-sm text-muted-foreground">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-input accent-[hsl(var(--primary))]"> Manter conectado
        </label>
        <button class="btn-primary h-10 w-full" type="submit">Entrar</button>
    </form>
</div>
@endsection
