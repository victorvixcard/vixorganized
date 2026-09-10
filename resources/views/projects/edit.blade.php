@extends('layouts.app')
@section('title', 'Editar · '.$project->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> {{ $project->name }}
    </a>
    <h1 class="mt-2 mb-5 font-display text-2xl font-bold tracking-tight">Editar projeto</h1>

    <form method="post" action="{{ route('projects.update', $project) }}" class="space-y-5">
        @method('PUT')
        <div class="panel space-y-5 p-6">
            @include('projects._form')
        </div>
        <div class="sticky bottom-0 -mx-4 flex items-center justify-end gap-2 border-t border-border bg-background/95 px-4 py-3 backdrop-blur lg:-mx-6 lg:px-6">
            <a href="{{ route('projects.show', $project) }}" class="btn-ghost">Cancelar</a>
            <button class="btn-primary" type="submit">Salvar</button>
        </div>
    </form>

    <div class="panel mt-8 flex flex-col gap-3 border-destructive/30 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium">Excluir projeto</p>
            <p class="help mt-0.5">Remove fases, passos e histórico. Não dá para desfazer.</p>
        </div>
        <form method="post" action="{{ route('projects.destroy', $project) }}"
              onsubmit="return confirm('Excluir o projeto {{ addslashes($project->name) }} e todas as fases e passos? Não dá para desfazer.')">
            @csrf @method('DELETE')
            <button class="btn-danger border border-destructive/30" type="submit">
                <i data-lucide="trash-2" class="h-4 w-4"></i> Excluir projeto
            </button>
        </form>
    </div>
</div>
@endsection
