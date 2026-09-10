@props(['phases', 'currentId'])
{{-- Mini stepper das fases de um projeto, para a linha da fila. --}}
<div class="flex items-center gap-1" aria-label="Fases">
    @foreach ($phases as $phase)
        @php
            $done = ! is_null($phase->completed_at);
            $current = $phase->id === $currentId;
        @endphp
        <span class="block h-2 w-2 rounded-full {{ $done ? 'bg-success' : ($current ? 'bg-primary ring-2 ring-primary/30' : 'border border-input bg-transparent') }}"
              title="{{ $phase->name }}: {{ $done ? 'concluída' : ($current ? 'fase atual' : 'pendente') }}"></span>
    @endforeach
</div>
