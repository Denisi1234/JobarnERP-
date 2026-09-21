@props(['code' => 'JOB-20241024-043', 'tone' => 'neutral', 'qr' => false, 'size' => 'sm'])

@php
$tones = [
  'success' => 'bg-[#ECFDF5] text-[#059669] border-[#A7F3D0]',
  'warning' => 'bg-[#FFFBEB] text-[#92400E] border-[#FDE68A]',
  'danger'  => 'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]',
  'neutral' => 'bg-surface-container text-on-surface-variant border-outline-variant/30',
  'vip'     => 'bg-[#FFFBEB] text-[#92400E] border-[#FDE68A]',
];
$cls = $tones[$tone] ?? $tones['neutral'];
$sizeCls = $size === 'pill' ? 'px-2 py-0.5 rounded-full text-[11px]' : 'px-2 py-0.5 rounded text-[11px]';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 $sizeCls font-label-mono font-semibold tabular-nums border $cls"]) }}>
  @if($qr)<span class="material-symbols-outlined text-[12px]">qr_code_2</span>@endif
  {{ $code }}
</span>
