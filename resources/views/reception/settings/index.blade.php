@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<div class="flex flex-col w-full" style="max-width:1480px; margin-inline:auto">
<x-success-popup :message="session('success')" />

<div class="bg-white border border-[#e0e0e0] border-t-[3px] border-t-[#0f62fe] p-5 mb-6">
  <nav class="flex items-center gap-1.5 text-xs text-[#525252] mb-2"><a href="{{ $backUrl ?? route('reception.dashboard') }}" class="hover:text-[#161616] hover:underline">Back</a><span class="text-[#8d8d8d]">/</span><span class="font-semibold text-[#161616]">Settings</span></nav>
  <h1 class="text-[22px] font-semibold tracking-tight text-[#161616]" style="letter-spacing:-0.02em; font-family:'IBM Plex Sans',sans-serif">{{ __('reception.system_settings') }}</h1>
  <p class="mono text-xs mt-1" style="color:#525252">{{ now()->format('l, d F Y') }} — {{ auth()->user()->getRoleLabel() }} · {{ auth()->user()->email }}</p>
</div>



 {{-- Main Language Selection Card --}}
 <div class="bg-surface-container-lowest p-space-xl border border-outline-variant/30 max-w-2xl mx-auto w-full">
 <div class="text-center mb-space-lg">
 <span class="w-12 h-12 bg-primary/10 text-primary flex items-center justify-center mx-auto mb-3">
 <span class="material-symbols-outlined text-[24px]">language</span>
 </span>
 <h2 class="font-headline-md text-headline-md text-on-surface font-bold">{{ __('reception.select_language') }}</h2>
 <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('reception.select_language_sub') }}</p>
 </div>

 @php $current = session('locale', app()->getLocale()); @endphp

 <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md w-full">
 <a href="{{ route('reception.language.switch', 'en') }}"
 class="group relative flex flex-col items-center gap-2 p-space-lg border-2 transition-all {{ $current==='en' ? 'border-primary bg-primary/5 ' : 'border-outline-variant/40 bg-surface-container-lowest hover:border-primary/50' }}">
 @if($current==='en')
 <span class="absolute top-3 right-3 w-6 h-6 rounded-full bg-primary text-on-primary flex items-center justify-center">
 <span class="material-symbols-outlined text-[16px]">check</span>
 </span>
 @endif
 <span class="text-4xl">🇬🇧</span>
 <span class="font-headline-sm text-headline-sm text-on-surface font-semibold">English</span>
 <span class="font-body-sm text-body-sm text-on-surface-variant">Default System Language</span>
 </a>

 <a href="{{ route('reception.language.switch', 'sw') }}"
 class="group relative flex flex-col items-center gap-2 p-space-lg border-2 transition-all {{ $current==='sw' ? 'border-primary bg-primary/5 ' : 'border-outline-variant/40 bg-surface-container-lowest hover:border-primary/50' }}">
 @if($current==='sw')
 <span class="absolute top-3 right-3 w-6 h-6 rounded-full bg-primary text-on-primary flex items-center justify-center">
 <span class="material-symbols-outlined text-[16px]">check</span>
 </span>
 @endif
 <span class="text-4xl">🇹🇿</span>
 <span class="font-headline-sm text-headline-sm text-on-surface font-semibold">Kiswahili</span>
 <span class="font-body-sm text-body-sm text-on-surface-variant">Lugha ya Kiswahili</span>
 </a>
 </div>

  <div class="mt-space-lg pt-space-md border-t border-outline-variant/30 text-center">
  <p class="font-label-mono text-label-mono text-on-surface-variant text-xs">
  Active Locale: <strong class="text-primary font-bold">{{ strtoupper($current) }}</strong> · {{ $current==='sw' ? 'Kiswahili' : 'English' }} · Timezone: EAT (UTC+3)
  </p>
  </div>
  </div>

  {{-- Password Change — IBM, available on all 4 portals after login --}}
  <div class="bg-white border border-[#e0e0e0] max-w-2xl mx-auto w-full mt-6">
    <div class="px-6 py-4 border-b border-[#e0e0e0] bg-[#f4f4f4] flex items-center gap-2">
      <span class="flex h-7 w-7 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[16px]">lock</span></span>
      <div>
        <h2 class="text-sm font-semibold tracking-tight" style="color:#161616">Change password</h2>
        <p class="mono text-[11px]" style="color:#525252">Available on Reception, IT, Sales and Manager after login</p>
      </div>
    </div>
    <form method="POST" action="{{ route('settings.password.update') }}" class="p-6 space-y-4">
      @csrf
      <div>
        <label class="block text-xs font-semibold tracking-wide uppercase" style="color:#525252">Current password <span style="color:#da1e28">*</span></label>
        <input type="password" name="current_password" required autocomplete="current-password" placeholder="Enter current password" class="mt-1 w-full h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none" style="font-family:'IBM Plex Sans',sans-serif">
        @error('current_password')<p class="text-xs mt-1" style="color:#da1e28">{{ $message }}</p>@enderror
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold tracking-wide uppercase" style="color:#525252">New password <span style="color:#da1e28">*</span></label>
          <input type="password" name="password" required autocomplete="new-password" placeholder="Min 8 characters" class="mt-1 w-full h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none">
          @error('password')<p class="text-xs mt-1" style="color:#da1e28">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-xs font-semibold tracking-wide uppercase" style="color:#525252">Confirm new password <span style="color:#da1e28">*</span></label>
          <input type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat new password" class="mt-1 w-full h-10 px-3 border border-[#8d8d8d] bg-white text-sm focus:border-[#0f62fe] focus:outline-none">
        </div>
      </div>
      <div class="flex justify-end">
        <button type="submit" class="bg-[#0f62fe] hover:bg-[#0353e9] text-white px-6 py-2.5 text-sm font-semibold border border-[#0f62fe]">Update password</button>
      </div>
    </form>
  </div>

 </div>
@endsection
