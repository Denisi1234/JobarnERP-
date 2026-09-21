@extends('reception.layout')

@section('content')
<div class="w-full space-y-6" x-data="{ tab: 'overview', showSerialAdd: false, showDeleteModal: false, openDeleteModal(){ this.showDeleteModal=true; this.$nextTick(()=>{ this.$refs.cancelDeleteBtn && this.$refs.cancelDeleteBtn.focus(); }); }, closeDeleteModal(){ this.showDeleteModal=false; } }">
    {{-- Back --}}
    <a href="{{ route('sales.inventory') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-900">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to Inventory
    </a>

    @if(session('success'))
    <x-success-popup :message="session('success')" />
    @endif

    {{-- Header — professional flat card --}}
    <div class="bg-white rounded-none border border-slate-200 p-6">
        <div class="flex flex-wrap gap-6">
            <div class="h-20 w-20 rounded-none bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 shrink-0">
                <span class="material-symbols-outlined text-[32px]">{{ $product->icon ?: 'inventory_2' }}</span>
            </div>
            <div class="flex-1 min-w-[260px]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ $product->name }}</h1>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <span class="inline-flex rounded-none border border-slate-900 bg-slate-900 px-2 py-0.5 text-[11px] font-mono font-bold text-white">{{ $product->sku }}</span>
                            @if($product->barcode)<span class="inline-flex rounded-none border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-mono text-slate-600">{{ $product->barcode }}</span>@endif
                            <span class="inline-flex rounded-none border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $product->category }}</span>
                            @if($product->brand)<span class="inline-flex rounded-none border border-slate-200 bg-white px-2 py-0.5 text-[11px] text-slate-600">{{ $product->brand }} {{ $product->model }}</span>@endif
                            @if($product->is_service)<span class="inline-flex rounded-none border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px]">Service</span>@else<span class="inline-flex rounded-none border px-2 py-0.5 text-[11px] font-medium {{ $product->tracking_method==='serial' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-slate-200 text-slate-600' }}">{{ ucfirst($product->tracking_method ?? 'quantity') }}</span>@endif
                        </div>
                        @if($product->description)<p class="text-xs text-slate-500 mt-2 max-w-2xl">{{ $product->description }}</p>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('sales.products.edit', $product->id) }}" class="rounded-none border border-slate-200 bg-white px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Edit</a>
                        <button @click="showSerialAdd=true" class="rounded-none bg-slate-900 px-4 py-2.5 text-xs font-medium text-white hover:bg-black">+ Serials</button>
                    </div>
                </div>
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-xs">
                    <div><dt class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Selling Price</dt><dd class="text-sm font-semibold font-mono text-slate-900 mt-1">TZS {{ number_format($product->price) }}</dd></div>
                    <div><dt class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Cost Price</dt><dd class="text-sm font-mono text-slate-700 mt-1">TZS {{ number_format($product->cost_price ?? 0) }}</dd></div>
                    <div><dt class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Margin</dt><dd class="text-sm font-bold mt-1 {{ $margin >=30 ? 'text-emerald-700' : ($margin !== null ? 'text-slate-700' : 'text-slate-400') }}">{{ $margin !== null ? $margin.'%' : '—' }}</dd></div>
                    <div><dt class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Supplier</dt><dd class="text-xs font-medium text-slate-900 mt-1">{{ $product->supplier?->name ?? '—' }}<span class="block text-[11px] text-slate-400 font-mono">{{ $product->supplier?->phone ?? '' }}</span></dd></div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-none border border-slate-200 bg-white px-3 py-1 text-xs font-mono"><span class="h-1.5 w-1.5 rounded-none {{ $onHand>0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span> {{ $onHand }} on hand</span>
                    <span class="inline-flex rounded-none border border-slate-200 bg-slate-50 px-3 py-1 text-xs">Retail value TZS {{ number_format($retailValue) }}</span>
                    <span class="inline-flex rounded-none border border-slate-200 bg-slate-50 px-3 py-1 text-xs">Cost value TZS {{ number_format($costValue) }}</span>
                    @if($product->tracking_method==='serial')<span class="inline-flex rounded-none border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">{{ $units->count() }} serial units</span>@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stock status overview --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-none border border-slate-200 p-4 text-center">
            <div class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Available</div>
            <div class="text-xl font-semibold text-emerald-700 mt-1">{{ $availableUnits }}</div>
            <div class="text-[11px] text-slate-500">Ready to sell</div>
        </div>
        <div class="bg-white rounded-none border border-amber-200 p-4 text-center">
            <div class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Reserved</div>
            <div class="text-xl font-semibold text-amber-700 mt-1">{{ $reservedUnits }}</div>
            <div class="text-[11px] text-slate-500">Hold for orders</div>
        </div>
        <div class="bg-white rounded-none border border-slate-200 p-4 text-center">
            <div class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Sold</div>
            <div class="text-xl font-semibold text-slate-900 mt-1">{{ $soldUnits }}</div>
            <div class="text-[11px] text-slate-500">Delivered</div>
        </div>
        <div class="bg-white rounded-none border border-slate-200 p-4 text-center">
            <div class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Reorder Point</div>
            <div class="text-xl font-semibold text-slate-900 mt-1">{{ $product->min_stock_alert ?? 5 }}</div>
            <div class="text-[11px] {{ $onHand <= ($product->min_stock_alert ?? 5) ? 'text-rose-600 font-medium' : 'text-slate-500' }}">{{ $onHand <= ($product->min_stock_alert ?? 5) ? 'Reorder needed' : 'Stock OK' }}</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 text-xs font-medium border-b border-slate-200 overflow-x-auto">
        @foreach([['overview','Overview'],['specs','Specifications'],['units','Serial Units'],['movements','Stock Movements'],['warranty','Warranty'],['sales','Sales History']] as [$k,$l])
            <button @click="tab='{{ $k }}'" :class="tab==='{{ $k }}' ? 'text-slate-900 border-slate-900' : 'text-slate-500 border-transparent hover:text-slate-900'" class="px-4 py-2.5 border-b-2 -mb-px whitespace-nowrap">{{ $l }}</button>
        @endforeach
    </div>

    {{-- Overview --}}
    <div x-show="tab==='overview'" class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-none border border-slate-200 p-5">
                <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Product Details</h3>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
                    <div><dt class="text-slate-400">SKU</dt><dd class="font-mono font-medium text-slate-900">{{ $product->sku }}</dd></div>
                    <div><dt class="text-slate-400">Barcode</dt><dd class="font-mono text-slate-900">{{ $product->barcode ?? '—' }} @if($product->barcode)<span class="ml-1 text-[10px] border border-slate-200 px-1">scan</span>@endif</dd></div>
                    <div><dt class="text-slate-400">Brand / Model</dt><dd class="font-medium text-slate-900">{{ $product->brand ?? '—' }} {{ $product->model ? '/ '.$product->model : '' }}</dd></div>
                    <div><dt class="text-slate-400">Category</dt><dd class="font-medium text-slate-900">{{ $product->category }}</dd></div>
                    <div><dt class="text-slate-400">Tracking Method</dt><dd class="font-medium text-slate-900">{{ ucfirst($product->tracking_method ?? 'quantity') }} <span class="text-slate-400 font-normal">{{ $product->isSerialized() ? '(mandatory serial)' : '' }}</span></dd></div>
                    <div><dt class="text-slate-400">Warehouse</dt><dd class="font-medium text-slate-900">{{ $product->warehouse?->name ?? '—' }}</dd></div>
                </dl>
                @if($product->description)
                <div class="mt-4 border-t border-slate-100 pt-3"><p class="text-xs text-slate-600 whitespace-pre-line">{{ $product->description }}</p></div>
                @endif
            </div>
            <div class="bg-white rounded-none border border-slate-200 p-5">
                <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Inventory Units Summary</h3>
                <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                    <div class="border border-slate-200 p-3"><div class="text-lg font-semibold text-slate-900">{{ $units->count() }}</div><div class="text-[11px] text-slate-500">Total units</div></div>
                    <div class="border border-emerald-200 bg-emerald-50 p-3"><div class="text-lg font-semibold text-emerald-700">{{ $availableUnits }}</div><div class="text-[11px] text-slate-500">Available</div></div>
                    <div class="border border-slate-200 p-3"><div class="text-lg font-semibold text-slate-900">{{ $onHand }}</div><div class="text-[11px] text-slate-500">On hand qty</div></div>
                </div>
                @if($product->tracking_method==='quantity')
                <p class="text-[11px] text-slate-400 mt-2">Non-serialized: managed by quantity. Serialized products (laptops, printers, CCTV) use individual serial rows.</p>
                @endif
            </div>
        </div>
        <div class="space-y-6">
            <div class="bg-white rounded-none border border-slate-200 p-5">
                <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Supplier</h3>
                @if($product->supplier)
                    <div class="mt-3 flex items-center gap-3">
                        <div class="h-9 w-9 rounded-none bg-slate-900 text-white flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">local_shipping</span></div>
                        <div>
                            <div class="text-sm font-medium text-slate-900">{{ $product->supplier->name }}</div>
                            <div class="text-[11px] text-slate-500">{{ $product->supplier->phone ?? '' }} · {{ $product->supplier->email ?? '' }}</div>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-600">{{ $product->supplier->address ?? '' }}</div>
                @else
                    <p class="text-xs text-slate-400 mt-2">No supplier linked.</p>
                @endif
            </div>
            <div class="bg-white rounded-none border border-slate-200 p-5">
                <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Quick Actions</h3>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button @click="tab='units'; $nextTick(()=> document.getElementById('units-section')?.scrollIntoView({behavior:'smooth'}))" class="rounded-none border border-slate-900 bg-slate-900 px-3 py-2.5 text-xs font-medium text-white hover:bg-black">View Serials</button>
                    <a href="{{ route('sales.products.edit', $product->id) }}" class="rounded-none border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 text-center">Edit product</a>
                </div>
                <form id="delete-product-form" action="{{ route('sales.products.destroy', $product->id) }}" method="POST" class="mt-2">
                    @csrf @method('DELETE')
                    <button type="button" @click="openDeleteModal()" class="w-full rounded-none border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Delete product</button>
                </form>
                {{-- Delete Carbon modal --}}
                <div x-show="showDeleteModal" x-cloak @keydown.escape.window="closeDeleteModal()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
                  <div x-trap.noscroll="showDeleteModal" role="dialog" aria-modal="true" aria-labelledby="delete-product-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeDeleteModal()">
                    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
                      <h3 id="delete-product-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Delete “{{ $product->name }}”?</h3>
                      <button @click="closeDeleteModal()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
                    </div>
                    <div style="padding:1.25rem; background:#fff;">
                      <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">This will permanently delete <span style="font-weight:600; color:#161616;">{{ $product->name }}</span> and remove all serial units. This action cannot be undone.</p>
                    </div>
                    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
                      <button x-ref="cancelDeleteBtn" @click="closeDeleteModal()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
                      <button @click="document.getElementById('delete-product-form').submit()" type="button" style="height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Delete</button>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Specifications --}}
    <div x-show="tab==='specs'" x-cloak class="bg-white rounded-none border border-slate-200 p-6">
        <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Technical Specifications — Structured Attributes</h3>
        <p class="text-xs text-slate-500 mt-1">Different categories have different specs (CPU/RAM/Storage/GPU/Screen/OS/Ports/Color). Stored as JSON `specs`.</p>
        @if($product->specs && is_array($product->specs))
            <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($product->specs as $k => $v)
                    <div class="border border-slate-200 p-3 bg-slate-50">
                        <dt class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">{{ $k }}</dt>
                        <dd class="text-xs font-medium text-slate-900 mt-1">{{ is_array($v) ? json_encode($v) : $v }}</dd>
                    </div>
                @endforeach
            </dl>
        @elseif($product->specs)
            <pre class="mt-3 bg-slate-50 border border-slate-200 p-3 text-xs font-mono whitespace-pre-wrap">{{ is_string($product->specs) ? $product->specs : json_encode($product->specs, JSON_PRETTY_PRINT) }}</pre>
        @else
            <p class="text-xs text-slate-400 mt-3 border border-dashed border-slate-200 p-4 text-center">No specs recorded. Edit product and add JSON: <code class="font-mono">{"cpu":"i7-1355U","ram":"16GB DDR4","storage":"512GB NVMe","gpu":"Iris Xe","screen":"14\" FHD"}</code></p>
        @endif
    </div>

    {{-- Serial Units --}}
    <div x-show="tab==='units'" x-cloak id="units-section" class="bg-white rounded-none border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50">
            <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Serial Units — Device Level</h3>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-mono text-slate-500">{{ $units->count() }} units</span>
                <button @click="showSerialAdd=true" class="rounded-none bg-slate-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-black">+ Add Serials</button>
            </div>
        </div>
        <div class="p-4 border-b border-slate-100" x-show="showSerialAdd" x-cloak>
            <form action="{{ route('sales.products.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}">
                <input type="hidden" name="is_serial_receive" value="1">
                <div class="grid sm:grid-cols-3 gap-3">
                    <select name="warehouse_id" class="rounded-none border border-slate-200 bg-white px-3 py-2 text-xs">
                        <option value="">Warehouse — {{ $product->warehouse?->name ?? 'Main Store' }}</option>
                        @foreach($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>@endforeach
                    </select>
                    <input type="text" name="purchase_order" placeholder="PO-00231" class="rounded-none border border-slate-200 px-3 py-2 text-xs font-mono">
                    <input type="number" name="cost" placeholder="Cost per unit TZS" class="rounded-none border border-slate-200 px-3 py-2 text-xs font-mono">
                </div>
                <textarea name="serial_numbers" rows="4" required placeholder="5CD123001&#10;5CD123002&#10;5CD123003&#10;5CD123004" class="w-full rounded-none border border-slate-200 px-3 py-2 text-xs font-mono"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showSerialAdd=false" class="rounded-none border border-slate-200 bg-white px-4 py-2 text-xs font-medium">Cancel</button>
                    <button type="submit" class="rounded-none bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-700">Receive Serials</button>
                </div>
                <p class="text-[11px] text-slate-400">Duplicate serial prevention active — system blocks <code>serial already exists</code>.</p>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-medium text-slate-500"><tr><th class="px-5 py-3">Serial</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Warehouse</th><th class="px-5 py-3">Cost</th><th class="px-5 py-3">Warranty</th><th class="px-5 py-3">Customer</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($units as $unit)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono font-bold text-slate-900">{{ $unit->serial_number }}</td>
                        <td class="px-5 py-3"><span class="inline-flex rounded-none border px-2 py-0.5 text-[11px] font-medium {{ $unit->status==='available' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : ($unit->status==='reserved' ? 'bg-amber-50 border-amber-200 text-amber-700' : ($unit->status==='sold' ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-600')) }}">{{ ucfirst(str_replace('_',' ',$unit->status)) }}</span></td>
                        <td class="px-5 py-3 text-[11px]">{{ $unit->warehouse?->name ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono">TZS {{ number_format($unit->cost ?? 0) }}</td>
                        <td class="px-5 py-3 text-[11px] font-mono {{ $unit->warranty_end && $unit->warranty_end->isPast() ? 'text-rose-600' : 'text-emerald-600' }}">{{ $unit->warranty_end?->format('d M Y') ?? '—' }} <span class="text-slate-400">{{ $unit->warranty_provider ?? $unit->warranty_type ?? '' }}</span></td>
                        <td class="px-5 py-3 text-[11px]">{{ $unit->customer_name ?? '—' }}<span class="block font-mono text-slate-400">{{ $unit->invoice_id ? 'INV #'.$unit->invoice_id : '' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-xs text-slate-400">No serialized units yet — serial tracking mandatory for this product category. Add via + Add Serials.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Stock Movements --}}
    <div x-show="tab==='movements'" x-cloak class="bg-white rounded-none border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Stock Movements — Audit Trail</h3>
            <span class="text-[11px] font-mono text-slate-400">{{ $logs->count() }} entries</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-medium text-slate-500"><tr><th class="px-5 py-3">Time</th><th class="px-5 py-3">Change</th><th class="px-5 py-3">Balance</th><th class="px-5 py-3">Reason</th><th class="px-5 py-3">By</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono text-[11px] text-slate-500">{{ $log->created_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono font-bold {{ ($log->quantity_change ?? 0)>0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ ($log->quantity_change ?? 0)>0 ? '+' : '' }}{{ $log->quantity_change }}</td>
                        <td class="px-5 py-3 font-mono text-[11px]">{{ $log->quantity_before }} → <span class="font-bold text-slate-900">{{ $log->quantity_after }}</span> @if($log->serial_number)<span class="ml-1 border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono">{{ $log->serial_number }}</span>@endif</td>
                        <td class="px-5 py-3 truncate max-w-[280px]">{{ $log->reason }} @if($log->reference)<span class="font-mono text-[11px] text-slate-400">[{{ $log->reference }}]</span>@endif</td>
                        <td class="px-5 py-3 text-[11px] text-slate-500">{{ $log->user?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-xs text-slate-400">No movements recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Warranty --}}
    <div x-show="tab==='warranty'" x-cloak class="bg-white rounded-none border border-slate-200 p-5">
        <h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Warranty — Claims & Status</h3>
        <div class="mt-3 grid sm:grid-cols-3 gap-3">
            <div class="border border-slate-200 p-3 text-center"><div class="text-lg font-semibold text-slate-900">{{ $units->filter(fn($u)=> $u->warranty_end && $u->warranty_end->isFuture())->count() }}</div><div class="text-[11px] text-slate-500">Active warranties</div></div>
            <div class="border border-amber-200 bg-amber-50 p-3 text-center"><div class="text-lg font-semibold text-amber-700">{{ $units->filter(fn($u)=> $u->warranty_end && $u->warranty_end->between(now(), now()->addDays(30)))->count() }}</div><div class="text-[11px] text-slate-500">Expiring 30d</div></div>
            <div class="border border-slate-200 p-3 text-center"><div class="text-lg font-semibold text-slate-900">{{ $warrantyClaims->count() }}</div><div class="text-[11px] text-slate-500">Claims</div></div>
        </div>
        <div class="mt-4 border border-slate-200">
            <div class="px-4 py-2 bg-slate-50 border-b border-slate-200 text-[11px] font-medium text-slate-500">Recent Warranty Claims</div>
            @forelse($warrantyClaims as $claim)
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between text-xs hover:bg-slate-50">
                    <div>
                        <div class="font-medium text-slate-900">{{ $claim->serial_number }} · {{ $claim->customer_name }}</div>
                        <div class="text-slate-500">{{ Str::limit($claim->issue_description, 60) }}</div>
                    </div>
                    <span class="inline-flex rounded-none border px-2 py-0.5 text-[11px] {{ $claim->status==='resolved' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-amber-50 border-amber-200 text-amber-700' }}">{{ ucfirst($claim->status) }}</span>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-xs text-slate-400">No claims — warranty lifecycle: Received → Inspection → Diagnosis → Repair → Resolved → Returned.</p>
            @endforelse
        </div>
    </div>

    {{-- Sales History --}}
    <div x-show="tab==='sales'" x-cloak class="bg-white rounded-none border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50"><h3 class="text-[11px] font-semibold tracking-widest text-slate-500 uppercase">Sales History — Invoices containing this product</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] text-slate-500"><tr><th class="px-5 py-3">Receipt</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3 text-right">Amount</th><th class="px-5 py-3">Date</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentInvoices as $inv)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono font-medium">{{ $inv->receipt_number ?? 'INV-'.$inv->id }}</td>
                        <td class="px-5 py-3">{{ $inv->customer_name ?? 'Walk-in' }}</td>
                        <td class="px-5 py-3 text-right font-mono font-semibold">TZS {{ number_format($inv->amount) }}</td>
                        <td class="px-5 py-3 font-mono text-[11px] text-slate-500">{{ $inv->created_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-xs text-slate-400">No sales linked yet. Flow: Quotation → Order → Reserve → Payment → Goods Issue → Serial assigned → Invoice → Warranty activated.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
