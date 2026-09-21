@props(['message', 'receiptId' => null])
@if($message)
@php $msg = is_string($message) ? str_replace('✅','', $message) : $message; @endphp
<div x-data="{ open: true }" x-show="open" x-transition
     x-init="$nextTick(()=> $refs.closeBtn?.focus())"
     @keydown.escape.window="open=false"
     x-cloak
     class="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="success-popup-title">
  <div @click="open=false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
  <div class="relative bg-white border border-[#e0e0e0] w-full max-w-md overflow-hidden" style="border-left:4px solid #24a148" @click.stop>
    <div class="bg-[#defbe6] border-b border-[#a7f0ba] px-5 py-4 flex items-start gap-3">
      <span class="flex h-8 w-8 items-center justify-center bg-[#0e6027] text-white shrink-0"><span class="material-symbols-outlined text-[18px]">check_circle</span></span>
      <div class="flex-1 min-w-0">
        <h2 id="success-popup-title" class="text-sm font-semibold text-[#0e6027]">Success</h2>
        <p class="text-sm text-[#0e6027] mt-0.5 leading-5" style="font-family:'IBM Plex Sans',sans-serif">{{ $msg }}</p>
      </div>
      <button x-ref="closeBtn" @click="open=false" type="button" aria-label="Close notification" class="flex h-7 w-7 items-center justify-center text-[#0e6027] hover:bg-[#a7f0ba]/40 shrink-0">
        <span class="material-symbols-outlined text-[18px]">close</span>
      </button>
    </div>
    <div class="px-5 py-4 flex justify-end gap-2 bg-white">
      @if($receiptId)
        <a href="{{ route('sales.invoices.receipt', $receiptId) }}" target="_blank" class="inline-flex items-center gap-1.5 border border-[#0f62fe] bg-white px-4 py-2 text-sm font-semibold text-[#0f62fe] hover:bg-[#edf5ff]">Print receipt</a>
      @endif
      <button @click="open=false" type="button" class="bg-[#0f62fe] hover:bg-[#0353e9] text-white px-5 py-2 text-sm font-semibold border border-[#0f62fe]">Done</button>
    </div>
  </div>
</div>
@endif
