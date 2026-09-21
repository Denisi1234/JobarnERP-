@props(['label','value','icon' => null,'trend' => null,'trendTone' => 'tertiary','footer' => null,'footerRight' => null])

<div class="bg-surface-container-lowest rounded-xl p-4 shadow-sm flex flex-col justify-between border border-transparent hover:border-outline-variant/20 transition-colors">
    <div class="flex items-center justify-between mb-2">
        <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider font-semibold">{{ $label }}</span>
        @if($icon)
            <span class="p-1 rounded-lg bg-surface-container text-primary">
                <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
            </span>
        @endif
    </div>
    <div class="flex items-baseline justify-between">
        <span class="font-metric-val text-metric-val text-on-surface">{{ $value }}</span>
        @if($trend)
            <span class="font-label-md text-label-md px-2 py-0.5 rounded-full
                @if($trendTone === 'success') bg-tertiary-fixed text-on-tertiary-fixed-variant
                @elseif($trendTone === 'warning') bg-amber-100 text-amber-700 border border-amber-200
                @elseif($trendTone === 'danger') bg-error-container text-on-error-container border border-error/20
                @else bg-surface-container text-primary @endif
            ">{{ $trend }}</span>
        @endif
    </div>
    @if($footer || $footerRight)
    <div class="mt-2 pt-2 flex items-center justify-between text-on-surface-variant font-body-sm text-body-sm">
        <span>{{ $footer }}</span>
        @if($footerRight)<span class="w-1 h-1 rounded-full bg-outline-variant"></span><span class="text-on-surface font-medium">{{ $footerRight }}</span>@endif
    </div>
    @endif
</div>
