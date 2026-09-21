@props(['message'])
@if($message)
<div x-data="{ show: true, paused: false, timer: null, init() { this.start(); } , start(){ this.timer = setTimeout(()=> { if(!this.paused) this.show=false }, 5000) }, pause(){ this.paused=true; clearTimeout(this.timer) }, resume(){ this.paused=false; this.start() } }" x-show="show" x-transition
     @mouseenter="pause()" @mouseleave="resume()"
     role="alert" aria-live="polite"
     class="mb-6 flex items-start gap-3 border border-[#a7f0ba] bg-[#defbe6] px-4 py-3 text-sm text-[#0e6027]" style="border-left:4px solid #24a148">
  <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5" aria-hidden="true">check_circle</span>
  <span class="flex-1 leading-5" style="font-family:'IBM Plex Sans',sans-serif">{{ $message }}</span>
  <button @click="show=false" type="button" aria-label="Close notification" class="ml-auto flex h-6 w-6 items-center justify-center text-[#0e6027] hover:bg-[#a7f0ba]/50 shrink-0">
    <span class="material-symbols-outlined text-[16px]">close</span>
  </button>
</div>
@endif
