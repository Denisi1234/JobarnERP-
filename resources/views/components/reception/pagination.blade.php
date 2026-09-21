@props(['total' => 24, 'perPage' => 6, 'current' => 1])

@php
$pages = max(1, (int)ceil($total / $perPage));
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-between font-body-sm text-body-sm text-on-surface-variant']) }}>
  <span class="font-label-mono text-label-mono">Showing <strong class="text-on-surface font-semibold">{{ min($perPage, $total) }}</strong> of <strong class="text-on-surface font-semibold">{{ $total }}</strong> • Page <strong class="text-on-surface">{{ $current }}</strong>/{{ $pages }}</span>
  <div class="flex items-center gap-1">
    <button class="w-7 h-7 rounded-lg bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant disabled:opacity-40" @if($current<=1) disabled @endif type="button"><span class="material-symbols-outlined text-[16px]">chevron_left</span></button>
    <span class="font-label-mono text-label-mono px-2 text-on-surface font-semibold">{{ $current }} / {{ $pages }}</span>
    <button class="w-7 h-7 rounded-lg bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant disabled:opacity-40" @if($current>=$pages) disabled @endif type="button"><span class="material-symbols-outlined text-[16px]">chevron_right</span></button>
  </div>
</div>
