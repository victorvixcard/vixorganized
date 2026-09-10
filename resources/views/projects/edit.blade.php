@extends('layouts.app')
@section('title', 'Editar · '.$project->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="mb-4 text-2xl font-semibold tracking-tight">Editar projeto</h1>

    <form method="post" action="{{ route('projects.update', $project) }}" class="card space-y-4 p-6">
        @method('PUT')
        @include('projects._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('projects.show', $project) }}" class="btn-ghost">Cancelar</a>
            <button class="btn-primary" type="submit">Salvar</button>
        </div>
    </form>

    <form method="post" action="{{ route('projects.destroy', $project) }}" class="mt-6 text-right"
          onsubmit="return confirm('Excluir o projeto {{ addslashes($project->name) }} e todas as fases e passos? Não dá para desfazer.')">
        @csrf @method('DELETE')
        <button class="btn-danger" type="submit">Excluir projeto</button>
    </form>
</div>
@endsection
