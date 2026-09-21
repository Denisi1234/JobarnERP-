@extends('reception.layout')

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- Header — compact, aligned --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('sales.inventory') }}" class="h-8 w-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:text-slate-900 hover:border-slate-300">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        </a>
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-semibold tracking-tight text-slate-900 leading-none">{{ isset($product) && $product ? 'Edit product' : 'New product' }}</h1>
                @if(isset($product) && $product)
                    <span class="hidden sm:inline-flex rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-mono text-slate-600">{{ $product->sku }}</span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1 truncate">{{ isset($product) && $product ? 'Update details and stock — audited.' : 'Add to catalog for POS. Stock tracked; services are billed without deduction.' }}</p>
        </div>
    </div>

    @if(isset($errors) && $errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 flex gap-2">
        <span class="material-symbols-outlined text-rose-600 text-[18px]">error</span>
        <ul class="text-xs text-rose-700 list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('sales.products.store') }}" method="POST" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        @csrf
        @if(isset($product) && $product)<input type="hidden" name="id" value="{{ $product->id }}">@endif

        {{-- Identity — broken, not full column for name --}}
        <div class="px-5 py-5">
            <h2 class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Identity</h2>
            <div class="mt-3 grid grid-cols-12 gap-3">
                <div class="col-span-12 sm:col-span-8">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Product name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required placeholder="HDMI Cable 4K (3m)"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Category <span class="text-rose-500">*</span></label>
                    <select name="category" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        @php $cats = ['Hardware','Accessories','Services','Software','Consumables','Networking','General']; $sel = old('category', $product->category ?? 'Hardware'); @endphp
                        @foreach($cats as $c)<option value="{{ $c }}" @selected($sel===$c)>{{ $c }}</option>@endforeach
                        @foreach($categories ?? [] as $cat) @if(!in_array($cat, $cats))<option value="{{ $cat }}" @selected($sel===$cat)>{{ $cat }}</option>@endif @endforeach
                    </select>
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-700 mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="Auto"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono text-slate-700 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Supplier</label>
                    <select name="supplier_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="">— No supplier —</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" @selected(old('supplier_id', $product->supplier_id ?? '') == $sup->id)>{{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-12 sm:col-span-6">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description', $product->description ?? '') }}" placeholder="Specs, warranty"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
            </div>
        </div>

        <div class="h-px bg-slate-100"></div>

        {{-- Pricing — compact broken --}}
        <div class="px-5 py-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Pricing</h2>
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600">Margin <span id="marginDisplay" class="font-mono font-semibold text-slate-400">—</span></span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Selling (TZS) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-400">TZS</span>
                        <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" required min="0" step="1"
                               class="w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 py-2 text-sm font-semibold text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" oninput="updateMargin()">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Cost (TZS)</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-400">TZS</span>
                        <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price ?? '') }}" min="0" step="1"
                               class="w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 py-2 text-sm font-medium text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" oninput="updateMargin()">
                    </div>
                </div>
            </div>
        </div>

        <div class="h-px bg-slate-100"></div>

        {{-- Inventory — compact --}}
        <div class="px-5 py-5">
            <h2 class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Inventory</h2>
            <label class="mt-3 flex gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 cursor-pointer">
                <input type="checkbox" name="is_service" value="1" id="is_service" @checked(old('is_service', isset($product) ? $product->is_service : false))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900" onchange="toggleService()">
                <span class="text-xs leading-tight"><span class="font-medium text-slate-900">Service — no stock</span><span class="block text-slate-500">Excluded from stock deductions.</span></span>
            </label>
            <div id="stockFields" class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">On hand</label>
                    <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 10) }}" min="0"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Reorder at</label>
                    <input type="number" name="min_stock_alert" value="{{ old('min_stock_alert', $product->min_stock_alert ?? 5) }}" min="1"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
            </div>
            @if(isset($product) && !$product->is_service)
                <p class="text-xs text-slate-500 mt-2">Current <span class="font-medium text-slate-900">{{ $product->stock_quantity }} units</span> • Retail <span class="font-mono">TZS {{ number_format(($product->stock_quantity ?? 0) * ($product->price ?? 0)) }}</span></p>
            @endif
        </div>

        {{-- Actions — sticky bottom bar --}}
        <div class="sticky bottom-0 z-10 -mx-px rounded-b-xl px-5 py-3 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 border-t border-slate-200 flex items-center justify-between shadow-[0_-8px_24px_rgba(0,0,0,0.04)]">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="hidden sm:inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                <span class="hidden sm:inline">{{ isset($product) && $product ? 'Editing' : 'Creating' }} • Auto-saved in audit log</span>
                <a href="{{ route('sales.inventory') }}" class="sm:hidden text-xs font-medium text-slate-600 hover:text-slate-900">Cancel</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.inventory') }}" class="hidden sm:inline-flex rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900">Cancel</a>
                <button type="reset" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Reset</button>
                <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 shadow-sm">{{ isset($product) && $product ? 'Save changes' : 'Create product' }}</button>
            </div>
        </div>
    </form>

    @if(isset($product) && $product)
    <div class="mt-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3">
        <span class="text-xs text-slate-500">Delete this product permanently</span>
        <form action="{{ route('sales.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($product->name) }}?');">
            @csrf @method('DELETE')
            <button class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">Delete</button>
        </form>
    </div>
    @endif
</div>

<script>
function updateMargin(){
    const p = parseFloat(document.querySelector('input[name="price"]')?.value)||0;
    const c = parseFloat(document.getElementById('cost_price')?.value)||0;
    const el = document.getElementById('marginDisplay');
    if(!el) return;
    if(!p || !c || c>=p){ el.textContent='—'; el.className='font-mono font-semibold text-slate-400'; return; }
    const m = Math.round((p-c)/p*100);
    el.textContent=m+'%';
    el.className='font-mono font-semibold '+(m>=30?'text-emerald-700':m>=15?'text-slate-700':'text-amber-700');
}
function toggleService(){
    const v = document.getElementById('is_service')?.checked;
    const f = document.getElementById('stockFields');
    if(f) f.style.display = v ? 'none' : 'grid';
}
document.addEventListener('DOMContentLoaded', ()=>{ updateMargin(); toggleService(); });
</script>
@endsection
