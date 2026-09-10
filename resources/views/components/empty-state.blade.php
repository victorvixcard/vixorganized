@props(['icon' => 'info', 'title'])
{{-- Estado vazio é tela de trabalho: diz o que a tela faz e oferece a ação que popula. --}}
<div {{ $attributes->merge(['class' => 'panel flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="grid h-10 w-10 place-items-center rounded-full bg-muted text-muted-foreground">
        <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
    </span>
    <p class="mt-3 font-medium">{{ $title }}</p>
    @if (trim($slot))
        <div class="mt-1 max-w-md text-sm text-muted-foreground">{{ $slot }}</div>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
