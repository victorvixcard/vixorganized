@extends('layouts.app')
@section('title', 'Novo projeto')

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Fila de projetos
    </a>
    <h1 class="mt-2 mb-5 font-display text-2xl font-bold tracking-tight">Novo projeto</h1>

    <form method="post" action="{{ route('projects.store') }}" class="space-y-5">
        <div class="panel space-y-5 p-6">
            @include('projects._form')
        </div>

        <div x-data="{ tpl: true }" class="panel p-5">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="hidden" name="use_template" value="0">
                <input type="checkbox" name="use_template" value="1" x-model="tpl" class="mt-0.5 h-4 w-4 rounded border-input accent-[hsl(var(--primary))]">
                <span>
                    <span class="block text-sm font-medium">Aplicar pipeline padrão</span>
                    <span class="help mt-0.5 block">Cria as fases abaixo com seus passos. Tudo pode ser renomeado, reordenado ou removido depois.</span>
                </span>
            </label>
            <div x-show="tpl" class="mt-4 grid gap-px overflow-hidden rounded-md border border-border bg-border sm:grid-cols-3">
                @foreach ($template as $phase => $steps)
                    <div class="bg-card p-3">
                        <div class="mb-1.5 flex items-center gap-2 text-sm font-semibold">
                            <span class="font-mono text-xs text-muted-foreground">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $phase }}
                        </div>
                        <ul class="space-y-1 text-xs text-muted-foreground">
                            @foreach ($steps as $s)
                                <li class="flex gap-1.5"><span class="mt-[5px] h-1 w-1 shrink-0 rounded-full bg-muted-foreground/50"></span>{{ $s }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Ação que fecha o fluxo sempre visível. --}}
        <div class="sticky bottom-0 -mx-4 flex items-center justify-end gap-2 border-t border-border bg-background/95 px-4 py-3 backdrop-blur lg:-mx-6 lg:px-6">
            <a href="{{ route('projects.index') }}" class="btn-ghost">Cancelar</a>
            <button class="btn-primary" type="submit">Criar projeto</button>
        </div>
    </form>
</div>
@endsection
