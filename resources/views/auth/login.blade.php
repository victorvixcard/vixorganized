@extends('layouts.app')
@section('title', 'Entrar')

@section('content')
<div class="mx-auto mt-12 max-w-sm">
    <div class="card p-6">
        <h1 class="mb-1 text-lg font-semibold">Entrar</h1>
        <p class="mb-5 text-sm text-slate-500">Acesse a fila de projetos.</p>

        <form method="post" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="email">E-mail</label>
                <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label class="label" for="password">Senha</label>
                <input class="field" id="password" name="password" type="password" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded"> Manter conectado
            </label>
            <button class="btn-primary w-full justify-center" type="submit">Entrar</button>
        </form>
    </div>
</div>
@endsection
