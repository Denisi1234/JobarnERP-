<div class="login-page-root w-full min-h-screen">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">

<style>
html, body {
    height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #f4f4f4 !important;
    font-family: 'IBM Plex Sans', system-ui, sans-serif !important;
    overflow: hidden !important;
}
* { font-family: 'IBM Plex Sans', sans-serif !important; }
.mono { font-family: 'IBM Plex Mono', monospace !important; }
.login-split-wrapper {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 9999 !important;
    display: flex !important;
    flex-direction: row !important;
    background: #f4f4f4 !important;
    overflow: hidden !important;
}
.login-left-half {
    width: 50% !important;
    height: 100% !important;
    background: #ffffff !important;
    border-right: 1px solid #e0e0e0 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 2.5rem !important;
}
.login-right-half {
    width: 50% !important;
    height: 100% !important;
    background: #f4f4f4 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    align-items: center !important;
    padding: 2rem !important;
    overflow-y: auto !important;
}
@media (max-width: 900px) {
    html, body { overflow: auto !important; }
    .login-split-wrapper { position: relative !important; flex-direction: column !important; height: auto !important; min-height: 100vh !important; overflow-y: auto !important; }
    .login-left-half { width: 100% !important; height: auto !important; padding: 2rem 1.5rem !important; border-right: none !important; border-bottom: 1px solid #e0e0e0 !important; }
    .login-right-half { width: 100% !important; height: auto !important; padding: 2rem 1.5rem !important; }
}
/* IBM field labels */
.fi-fo-field-wrp { margin-bottom: 1rem !important; }
.fi-fo-field-wrp-label, .fi-fo-field-wrp-label span, .fi-fo-field-wrp-label label,
label[for="data.email"] span, label[for="data.role"] span, label[for="data.password"] span, label[for="data.remember"] span {
    color: #161616 !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.04em !important;
    text-transform: uppercase !important;
    opacity: 1 !important;
    font-family: 'IBM Plex Sans', sans-serif !important;
}
label[for="data.remember"] { display: inline-flex !important; align-items: center !important; gap: 0.5rem !important; cursor: pointer !important; margin-top: 0.25rem !important; }
label[for="data.remember"] span { font-weight: 400 !important; color: #525252 !important; font-size: 0.875rem !important; text-transform: none !important; letter-spacing: 0 !important; }
/* IBM inputs — square, border #8d8d8d, focus #0f62fe */
.fi-input-wrp {
    border-radius: 0 !important;
    border: 1px solid #8d8d8d !important;
    box-shadow: none !important;
    background: #ffffff !important;
}
.fi-input-wrp:focus-within {
    border-color: #0f62fe !important;
    box-shadow: none !important;
    outline: 1px solid #0f62fe !important;
}
.fi-input, .fi-select-input {
    padding: 0.7rem 0.85rem !important;
    font-size: 0.875rem !important;
    color: #161616 !important;
    background-color: transparent !important;
    font-family: 'IBM Plex Sans', sans-serif !important;
    border-radius: 0 !important;
}
.fi-select-input { cursor: pointer !important; }
/* IBM primary button — square, flat */
.fi-btn {
    background: #0f62fe !important;
    border-radius: 0 !important;
    border: 1px solid #0f62fe !important;
    padding: 0.85rem 1.25rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    color: #ffffff !important;
    box-shadow: none !important;
    width: 100% !important;
    font-family: 'IBM Plex Sans', sans-serif !important;
    letter-spacing: 0.02em !important;
}
.fi-btn:hover { background: #0353e9 !important; border-color: #0353e9 !important; box-shadow: none !important; transform: none !important; }
.fi-btn:active { transform: none !important; }
.fi-checkbox-input { border-radius: 0 !important; border: 1px solid #8d8d8d !important; color: #0f62fe !important; }
.fi-checkbox-input:checked { background-color: #0f62fe !important; border-color: #0f62fe !important; }
.cds--tile, .cds--btn { border-radius: 0 !important; }
</style>

<div class="login-split-wrapper">
    {{-- LEFT 50% — IBM Branding --}}
    <div class="login-left-half">
        <div style="align-self:flex-start; display:flex; align-items:center; gap:0.5rem;">
            <span style="width:2rem; height:2rem; background:#0f62fe; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.875rem;">J</span>
            <span style="font-size:0.875rem; font-weight:600; letter-spacing:-0.02em; color:#161616;">JOBARN <span style="font-weight:400; color:#525252;">ERP</span></span>
            <span class="hidden sm:inline-flex" style="margin-left:0.5rem; font-size:0.6875rem; color:#8d8d8d; border:1px solid #e0e0e0; background:#f4f4f4; padding:0.125rem 0.375rem;">FrontDesk OS</span>
        </div>
        <div class="flex flex-col items-center justify-center w-full max-w-[440px] px-6">
            <img src="{{ asset('images/jobarn-logo-clean.png') }}" alt="Jobarn General Trading Company Ltd" class="w-full max-w-[340px] h-auto object-contain" style="filter: grayscale(0.1);" />
            <div class="mt-6 text-center">
                <p class="text-sm font-semibold tracking-tight" style="color:#161616;">FrontDesk OS</p>
                <p class="mono text-xs mt-1" style="color:#525252;">Reception · IT · Sales · Manager — one secure workspace</p>
            </div>
        </div>
        <div class="text-center mono text-[11px]" style="color:#8d8d8d;">
            © {{ date('Y') }} Jobarn General Trading Company Ltd · IBM Carbon
        </div>
    </div>

    {{-- RIGHT 50% — IBM Sign-in Tile --}}
    <div class="login-right-half">
        <div class="w-full max-w-[420px]">
            <div class="bg-white border border-[#e0e0e0] border-t-[3px] border-t-[#0f62fe] p-6 sm:p-7">
                <div class="mb-6">
                    <h1 class="text-[22px] font-semibold tracking-tight" style="color:#161616; letter-spacing:-0.02em">Sign in</h1>
                    <p class="mono text-xs mt-1.5" style="color:#525252;">Enter your credentials to access your portal</p>
                </div>

                @if($errors->any())
                    <div class="mb-4 flex items-start gap-2 border border-[#ffd7d9] bg-[#fff1f1] px-3 py-3 text-sm text-[#580b0b]" style="border-left:3px solid #da1e28">
                        <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5" style="color:#da1e28">error</span>
                        <span style="font-family:'IBM Plex Sans',sans-serif">{{ $errors->first() }}</span>
                    </div>
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.before') }}

                <x-filament-panels::form id="form" wire:submit="authenticate">
                    {{ $this->form }}
                    <div class="mt-5">
                        <x-filament-panels::form.actions
                            :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()"
                        />
                    </div>
                </x-filament-panels::form>

                {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.after') }}
            </div>
            <div class="mt-4 flex items-center justify-between mono text-[11px]" style="color:#8d8d8d;">
                <span>Secure Portals</span>
                <span class="border border-[#e0e0e0] bg-white px-2 py-0.5">4 portals · role-isolated</span>
            </div>
        </div>
    </div>
</div>
</div>
