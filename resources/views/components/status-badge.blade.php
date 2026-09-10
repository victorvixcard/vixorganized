@props(['status', 'label' => null, 'live' => false])
@php
    $label ??= config("vix.statuses.{$status}.label", $status);
    $active = $status === 'em_andamento';
@endphp
<span {{ $attributes->merge(['class' => 'pill-base']) }}
      style="color: hsl(var(--status-{{ $status }})); border-color: hsl(var(--status-{{ $status }}) / 0.35); background: hsl(var(--status-{{ $status }}) / 0.10);"
      @if ($live) :style="`color: hsl(var(--status-${status})); border-color: hsl(var(--status-${status}) / 0.35); background: hsl(var(--status-${status}) / 0.10)`" @endif>
    <span class="h-1.5 w-1.5 rounded-full {{ $active ? 'animate-pulse' : '' }}"
          style="background: hsl(var(--status-{{ $status }}))"
          @if ($live) :style="`background: hsl(var(--status-${status}))`" :class="status === 'em_andamento' && 'animate-pulse'" @endif></span>
    <span @if ($live) x-text="statusLabel" @endif>{{ $label }}</span>
</span>
