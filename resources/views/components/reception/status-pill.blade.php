@props(['tone' => 'neutral', 'label'])

@php
$map = [
  'success' => 'bg-[#ECFDF5] text-[#059669] border-[#A7F3D0]',
  'warning' => 'bg-[#FFFBEB] text-[#D97706] border-[#FDE68A]',
  'danger'  => 'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]',
  'neutral' => 'bg-surface-container-low text-on-surface-variant border-outline-variant/30',
  'info'    => 'bg-surface-container text-primary border-primary/20',
  'vip'     => 'bg-secondary-fixed text-on-secondary-fixed border-secondary-fixed-dim',
];
$classes = $map[$tone] ?? $map['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-semibold uppercase tracking-wider $classes"]) }}>
    {{ $label }}
</span>
