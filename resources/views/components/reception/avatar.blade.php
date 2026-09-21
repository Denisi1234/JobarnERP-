@props(['name' => 'Guest', 'src' => null, 'size' => 'md', 'status' => null])

@php
$map = ['sm' => 'w-9 h-9 text-[13px]', 'md' => 'w-11 h-11 text-[15px]', 'lg' => 'w-12 h-12 text-[16px]'];
$cls = $map[$size] ?? $map['md'];
$initials = collect(explode(' ', $name))->map(fn($p)=>mb_substr($p,0,1))->take(2)->implode('');
$statusColor = ['available'=>'bg-tertiary','away'=>'bg-secondary','in_meeting'=>'bg-secondary','dnd'=>'bg-error','offline'=>'bg-outline'][$status] ?? null;
@endphp

<div class="relative shrink-0 {{ $cls }} rounded-lg overflow-hidden bg-primary-container text-on-primary flex items-center justify-center font-semibold shadow-sm {{ $attributes->get('class') }}">
  @if($src)
    <img src="{{ $src }}" alt="{{ $name }}" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
    <span class="hidden w-full h-full items-center justify-center">{{ $initials }}</span>
  @else
    <span>{{ $initials }}</span>
  @endif
  @if($statusColor)
    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full {{ $statusColor }} ring-2 ring-surface-container-lowest"></span>
  @endif
</div>
