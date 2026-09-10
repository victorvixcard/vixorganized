@extends('layouts.app')
@section('title', 'Novo projeto')

@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="mb-4 text-2xl font-semibold tracking-tight">Novo projeto</h1>

    <form method="post" action="{{ route('projects.store') }}" class="card space-y-4 p-6">
        @include('projects._form')

        <div x-data="{ tpl: true }" class="rounded-md border border-slate-200 bg-slate-50 p-4">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="use_template" value="0">
                <input type="checkbox" name="use_template" value="1" x-model="tpl" class="rounded"> Aplicar pipeline padrão
            </label>
            <p class="mt-1 text-xs text-slate-500">Cria as fases abaixo com seus passos. Você pode renomear, adicionar ou remover depois.</p>
            <div x-show="tpl" class="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                @foreach ($template as $phase => $steps)
                    <div class="rounded border border-slate-200 bg-white p-2">
                        <div class="mb-1 font-semibold">{{ $loop->iteration }}. {{ $phase }}</div>
                        <ul class="list-disc pl-4 text-slate-600">
                            @foreach ($steps as $s)<li>{{ $s }}</li>@endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('projects.index') }}" class="btn-ghost">Cancelar</a>
            <button class="btn-primary" type="submit">Criar projeto</button>
        </div>
    </form>
</div>
@endsection
