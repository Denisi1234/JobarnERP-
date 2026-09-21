@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  [x-cloak]{display:none!important}
  .cds--tile, .cds--data-table-container, .cds--btn, .cds--tag, .cds--search-input { border-radius:0 !important; }
  .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
  @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
  .ibm-hgrid-4 { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem; }
  @media(max-width:66rem){ .ibm-hgrid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media(max-width:42rem){ .ibm-hgrid-4 { grid-template-columns: 1fr; } }
</style>

<div class="cds--content" style="background:#f4f4f4; min-height:100vh; margin:-1.5rem -1.5rem 0 -1.5rem; padding:0 0 2rem 0;" x-data="{
    currentView: 'all',
    searchQuery: '',
    selectedCategory: 'All',
    showProductModal: false,
    editingProduct: { id: null, name: '', sku: '', category: 'Hardware', price: '', cost_price: '', stock_quantity: 10, min_stock_alert: 5, is_service: false, description: '', supplier_id: '', tracking_method: 'quantity', brand: '', model: '', specs: '' },
    showMessageModal: false,
    messageSupplier: { id: null, name: '', phone: '', email: '' },
    messageBody: '', messageChannel: 'sms',
    openMessage(supplier) { this.messageSupplier = supplier; this.messageBody = 'Habari ' + supplier.name + ', tunahitaji kuagiza stock ya duka la JOBARN. Tafadhali tujulishe upatikanaji na bei. Asante!'; this.messageChannel = supplier.phone ? 'sms' : 'email'; this.showMessageModal = true; },
    showStockAdjustModal: false,
    adjustAction: 'stock_in',
    stockItem: { id: null, name: '', sku: '', stock_quantity: 0 },
    adjustQty: 1,
    adjustReason: 'Supplier PO Delivery / Restock',
    adjustReference: '',
    showSerialModal: false,
    serialProduct: { id: null, name: '', sku: '' },
    serialNumbers: '',
    showDeleteConfirm: false,
    pendingDeleteProduct: { id: null, name: '' },
    openAdd() { this.editingProduct = { id: null, name: '', sku: '', category: 'Hardware', price: '', cost_price: '', stock_quantity: 10, min_stock_alert: 5, is_service: false, description: '', supplier_id: '', tracking_method: 'quantity', brand: '', model: '', specs: '' }; this.showProductModal = true; },
    openEdit(prod) { this.editingProduct = { id: prod.id, name: prod.name, sku: prod.sku, category: prod.category, price: prod.price, cost_price: prod.cost_price || '', stock_quantity: prod.stock_quantity ?? 10, min_stock_alert: prod.min_stock_alert ?? 5, is_service: !!prod.is_service, description: prod.description || '', supplier_id: prod.supplier_id || '', tracking_method: prod.tracking_method || 'quantity', brand: prod.brand || '', model: prod.model || '', specs: prod.specs ? JSON.stringify(prod.specs) : '' }; this.showProductModal = true; },
    openStockAdjust(prod, action) { this.stockItem = { id: prod.id, name: prod.name, sku: prod.sku, stock_quantity: prod.stock_quantity ?? 0 }; this.adjustAction = action || 'stock_in'; this.adjustQty = 1; this.adjustReason = this.adjustAction === 'stock_in' ? 'Supplier PO Delivery / Restock' : 'Damaged / Internal Use'; this.adjustReference = ''; this.showStockAdjustModal = true; },
    openSerialReceive(prod){ this.serialProduct = { id: prod.id, name: prod.name, sku: prod.sku }; this.serialNumbers = ''; this.showSerialModal = true; },
    openDeleteConfirm(prod){ this.pendingDeleteProduct = { id: prod.id, name: prod.name }; this.showDeleteConfirm = true; this.$nextTick(()=>{ this.$refs.cancelDeleteProductBtn && this.$refs.cancelDeleteProductBtn.focus(); }); },
    closeDeleteConfirm(){ this.showDeleteConfirm = false; },
    confirmDeleteProduct(){ if(this.pendingDeleteProduct.id){ let f=document.getElementById('delete-product-form-'+this.pendingDeleteProduct.id); if(f) f.submit(); } }
}">

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0;">
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:0;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('sales.index') }}" style="color:#0f62fe; text-decoration:none;">Sales</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Inventory</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; padding-bottom:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">Inventory</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">Stock control — IT equipment, hardware, accessories and services</p>
      </div>
      <div style="display:flex; gap:0.625rem; flex-wrap:wrap; align-items:center;">
        <a href="{{ route('sales.inventory.export') }}" class="cds--btn cds--btn--tertiary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; background:#ffffff; color:#161616; border:1px solid #8d8d8d; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
          <svg width="16" height="16" viewBox="0 0 32 32" fill="#525252"><path d="M26 24v4H6v-4H4v4a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2v-4zm0-10l-1.41-1.41L17 20.17V2h-2v18.17l-7.59-7.58L6 14l10 10l10-10z"/></svg> Export
        </a>
        <button @click="openAdd()" type="button" class="cds--btn cds--btn--primary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; display:inline-flex; align-items:center; gap:0.5rem;">
          <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M17 15V8h-2v7H8v2h7v7h2v-7h7v-2z"/></svg> Add product
        </button>
      </div>
    </div>
  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  @if(session('success'))
  <x-success-popup :message="session('success')" />
  @endif
  @if($errors->any())
  <div class="cds--inline-notification cds--inline-notification--low-contrast cds--inline-notification--error" role="alert" style="margin-bottom:1rem; padding:1rem; background:#fff1f1; border:1px solid #ffb3b8; border-left:4px solid #da1e28;">
    <ul style="margin:0; padding-left:1.25rem; font-size:0.875rem; color:#750e13;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
  @endif

  {{-- IBM KPI Tiles — 4 --}}
  <div class="ibm-hgrid-4">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Inventory Value</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#161616;">TZS {{ number_format($stockValuationRetail) }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem; font-family:'IBM Plex Sans',sans-serif;">Cost TZS {{ number_format($stockValuationCost) }} • {{ $products->count() }} SKUs</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Available</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ number_format($totalItemsInStock) }} <span style="font-size:0.875rem; font-weight:400; color:#525252;">units</span></div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">{{ $availableDevices ?? 0 }} serialized • {{ $reservedDevices ?? 0 }} reserved</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Attention</div>
      <div style="display:flex; align-items:baseline; gap:0.5rem; margin-top:0.75rem;">
        <span style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:{{ $lowStockCount>0 ? '#e9730c' : '#161616' }};">{{ $lowStockCount }}</span><span style="font-size:0.75rem; color:#525252;">low</span>
        <span style="color:#e0e0e0;">/</span>
        <span style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:{{ $outOfStockCount>0 ? '#da1e28' : '#161616' }};">{{ $outOfStockCount }}</span><span style="font-size:0.75rem; color:#525252;">out</span>
      </div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">{{ $underRepairDevices ?? 0 }} repair • {{ $warrantyClaims ?? 0 }} claims</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Catalog</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $physicalProducts->count() }} <span style="font-size:0.875rem; font-weight:400; color:#525252;">goods</span> • {{ $services->count() }} <span style="font-size:0.875rem; font-weight:400; color:#525252;">services</span></div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">{{ $serialProducts ?? 0 }} serialized • {{ $categories->count() }} categories</div>
    </div>
  </div>

  {{-- IBM Filter Bar --}}
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-bottom:1rem;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem; padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; background:#f4f4f4;">
      <div style="display:flex; align-items:center; gap:0.375rem; flex-wrap:wrap;">
        <button @click="currentView='all'" :style="currentView==='all' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">All <span style="margin-left:0.25rem; font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; opacity:0.8;">{{ $products->count() }}</span></button>
        <button @click="currentView='physical'" :style="currentView==='physical' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">Physical <span style="margin-left:0.25rem; font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; opacity:0.8;">{{ $physicalProducts->count() }}</span></button>
        <button @click="currentView='services'" :style="currentView==='services' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">Services <span style="margin-left:0.25rem; font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; opacity:0.8;">{{ $services->count() }}</span></button>
        <button @click="currentView='low_stock'" :style="currentView==='low_stock' ? 'background:#da1e28; color:#fff; border:1px solid #da1e28;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">Low <span style="margin-left:0.25rem; font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; opacity:0.8;">{{ $lowStockCount + $outOfStockCount }}</span></button>
        <button @click="currentView='logs'" :style="currentView==='logs' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">Movements</button>
      </div>
      <div style="display:flex; align-items:center; gap:0.75rem; margin-left:auto; flex-wrap:wrap;">
        <div style="position:relative;">
          <svg style="position:absolute; left:0.625rem; top:50%; transform:translateY(-50%); width:1rem; height:1rem; fill:#525252;" viewBox="0 0 32 32"><path d="M14 4A10 10 0 1 0 24 14A10 10 0 0 0 14 4zm0 18A8 8 0 1 1 22 14A8 8 0 0 1 14 22z"/><path d="M26.7 24.7L21.3 19.3L20 20.7l5.4 5.4z"/></svg>
          <input type="text" x-model="searchQuery" placeholder="Search SKU, name, brand..." style="height:2rem; padding:0 0.75rem 0 2rem; width:16rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
        </div>
        <select x-model="selectedCategory" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; min-width:140px;">
          <option value="All">All categories</option>
          @foreach($categories as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
        </select>
      </div>
    </div>

    {{-- IBM Data Table --}}
    <div x-show="currentView !== 'logs'" style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
        <thead>
          <tr style="background:#e0e0e0;">
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; font-family:'IBM Plex Sans',sans-serif;">SKU / Barcode</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Product</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Supplier</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Tracking</th>
            <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Price</th>
            <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Stock</th>
            <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($products as $prod)
          <tr x-show="(
                  (currentView === 'all') ||
                  (currentView === 'physical' && !{{ $prod->is_service ? 'true' : 'false' }}) ||
                  (currentView === 'services' && {{ $prod->is_service ? 'true' : 'false' }}) ||
                  (currentView === 'low_stock' && !{{ $prod->is_service ? 'true' : 'false' }} && {{ ($prod->stock_quantity ?? 0) <= ($prod->min_stock_alert ?? 5) ? 'true' : 'false' }})
              ) &&
              (selectedCategory === 'All' || selectedCategory === '{{ $prod->category }}') &&
              ('{{ strtolower($prod->name) }} {{ strtolower($prod->sku) }} {{ strtolower($prod->barcode ?? '') }} {{ strtolower($prod->brand ?? '') }}'.includes(searchQuery.toLowerCase()))"
              style="border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
            <td style="padding:0.75rem 1rem;">
              <div style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; font-weight:600; color:#161616;">{{ $prod->sku }}</div>
              @if($prod->barcode)<div style="font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; color:#525252; margin-top:0.125rem;">{{ $prod->barcode }}</div>@endif
              @if($prod->brand)<div style="font-size:0.75rem; color:#525252; margin-top:0.125rem;">{{ $prod->brand }} {{ $prod->model ?? '' }}</div>@endif
            </td>
            <td style="padding:0.75rem 1rem;">
              <div style="display:flex; align-items:center; gap:0.75rem;">
                <a href="{{ route('sales.products.show', $prod->id) }}" style="width:2rem; height:2rem; background:#e0e0e0; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; text-decoration:none; flex-shrink:0;">
                  <span class="material-symbols-outlined" style="font-size:16px;">{{ $prod->icon ?: ($prod->is_service ? 'build' : 'inventory_2') }}</span>
                </a>
                <div style="min-width:0;">
                  <a href="{{ route('sales.products.show', $prod->id) }}" style="font-size:0.875rem; font-weight:600; color:#0f62fe; text-decoration:none; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:14rem; font-family:'IBM Plex Sans',sans-serif;">{{ $prod->name }}</a>
                  <div style="display:flex; align-items:center; gap:0.375rem; margin-top:0.25rem;">
                    <span style="background:#e0e0e0; color:#525252; font-size:0.6875rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif;">{{ $prod->category }}</span>
                    @if($prod->specs)<span style="font-size:0.75rem; color:#525252; max-width:8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ is_array($prod->specs) ? implode(' • ', array_map(fn($k,$v)=>"$k:$v", array_keys($prod->specs), $prod->specs)) : Str::limit($prod->specs,28) }}</span>@endif
                  </div>
                </div>
              </div>
            </td>
            <td style="padding:0.75rem 1rem;">
              @if($prod->supplier)
                <span style="background:#e0e0e0; color:#393939; font-size:0.75rem; padding:0.25rem 0.5rem; font-family:'IBM Plex Sans',sans-serif;">{{ Str::limit($prod->supplier->name, 14) }}</span>
              @else
                <span style="font-size:0.75rem; color:#8d8d8d;">—</span>
              @endif
            </td>
            <td style="padding:0.75rem 1rem;">
              @if($prod->is_service)
                <span style="background:#f4f4f4; border:1px solid #e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Sans',sans-serif;">Service</span>
              @else
                <span style="background:{{ $prod->tracking_method==='serial' ? '#defbe6' : ($prod->tracking_method==='batch' ? '#fff8e1' : '#ffffff') }}; border:1px solid {{ $prod->tracking_method==='serial' ? '#a7f0ba' : ($prod->tracking_method==='batch' ? '#f1c21b' : '#e0e0e0') }}; color:{{ $prod->tracking_method==='serial' ? '#044317' : ($prod->tracking_method==='batch' ? '#684e00' : '#525252') }}; font-size:0.6875rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; letter-spacing:0.02em;">{{ $prod->tracking_method ?? 'quantity' }}</span>
              @endif
            </td>
            <td style="padding:0.75rem 1rem; text-align:right; font-family:'IBM Plex Mono',monospace; font-size:0.875rem; font-weight:600; color:#161616;">TZS {{ number_format($prod->price) }}</td>
            <td style="padding:0.75rem 1rem; text-align:center;">
              @if($prod->is_service)
                <span style="background:#f4f4f4; border:1px solid #e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem;">—</span>
              @elseif(($prod->stock_quantity ?? 0)===0)
                <span style="background:#ffd7d9; border:1px solid #ffb3b8; color:#750e13; font-size:0.75rem; padding:0.125rem 0.375rem; font-weight:600;">0</span>
              @elseif(($prod->stock_quantity ?? 0) <= ($prod->min_stock_alert ?? 5))
                <span style="background:#fff8e1; border:1px solid #f1c21b; color:#684e00; font-size:0.75rem; padding:0.125rem 0.375rem; font-weight:600;">{{ $prod->stock_quantity }}</span>
              @else
                <span style="background:#ffffff; border:1px solid #e0e0e0; color:#161616; font-size:0.75rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Mono',monospace;">{{ $prod->stock_quantity }}</span>
              @endif
            </td>
            <td style="padding:0.75rem 1rem;">
              <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.25rem;">
                @if(!$prod->is_service)
                <button @click="openStockAdjust(@js($prod), 'stock_in')" style="height:1.75rem; padding:0 0.5rem; background:#fff; border:1px solid #e0e0e0; font-size:0.75rem; font-weight:600; color:#161616; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">+ In</button>
                <button @click="openStockAdjust(@js($prod), 'stock_out')" style="height:1.75rem; padding:0 0.5rem; background:#fff; border:1px solid #e0e0e0; font-size:0.75rem; font-weight:600; color:#525252; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">− Out</button>
                @endif
                <a href="{{ route('sales.products.show', $prod->id) }}" style="width:1.75rem; height:1.75rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; text-decoration:none;"><span class="material-symbols-outlined" style="font-size:14px;">visibility</span></a>
                <button @click="openEdit(@js($prod))" style="width:1.75rem; height:1.75rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;"><span class="material-symbols-outlined" style="font-size:14px;">edit</span></button>
                <form id="delete-product-form-{{ $prod->id }}" action="{{ route('sales.products.destroy', $prod->id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')<button type="button" @click="openDeleteConfirm(@js($prod))" style="width:1.75rem; height:1.75rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#da1e28; cursor:pointer;"><span class="material-symbols-outlined" style="font-size:14px;">delete</span></button></form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="7" style="padding:2rem; text-align:center;"><div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">No products</div><div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Add your first product</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Movements — IBM --}}
    <div x-show="currentView==='logs'" x-cloak style="border-top:1px solid #e0e0e0;">
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
          <thead>
            <tr style="background:#e0e0e0;">
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Time</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Product</th>
              <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Change</th>
              <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Balance</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Reason</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">By</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentStockLogs as $log)
            <tr style="border-bottom:1px solid #e0e0e0; background:#fff;">
              <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252;">{{ $log->created_at?->format('d M H:i') ?? '—' }}</td>
              <td style="padding:0.75rem 1rem;"><span style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $log->product?->name ?? '—' }}</span><span style="margin-left:0.25rem; font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252;">{{ $log->product?->sku }}</span></td>
              <td style="padding:0.75rem 1rem; text-align:center; font-family:'IBM Plex Mono',monospace; font-size:0.875rem; font-weight:600; color:{{ ($log->quantity_change ?? 0) >0 ? '#24a148' : '#da1e28' }};">{{ ($log->quantity_change ?? 0) >0 ? '+' : '' }}{{ $log->quantity_change }}</td>
              <td style="padding:0.75rem 1rem; text-align:center; font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252;">{{ $log->quantity_before }} <span style="color:#8d8d8d;">→</span> <span style="font-weight:600; color:#161616;">{{ $log->quantity_after }}</span></td>
              <td style="padding:0.75rem 1rem; font-size:0.875rem; color:#525252; max-width:16rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $log->reason }} @if($log->reference)<span style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#8d8d8d;">[{{ $log->reference }}]</span>@endif</td>
              <td style="padding:0.75rem 1rem; font-size:0.75rem; color:#525252;">{{ $log->user?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem;">No movements recorded</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Modals — IBM Carbon --}}
  <div x-show="showProductModal" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div @click.away="showProductModal = false" style="background:#fff; width:100%; max-width:42rem; max-height:92vh; display:flex; flex-direction:column; border:1px solid #e0e0e0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
        <div>
          <h3 style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;" x-text="editingProduct.id ? 'Edit product' : 'New product'"></h3>
          <p style="font-size:0.75rem; color:#525252; margin-top:0.125rem; font-family:'IBM Plex Mono',monospace;" x-text="editingProduct.sku || 'SKU auto • tracking: ' + (editingProduct.tracking_method||'quantity')"></p>
        </div>
        <button @click="showProductModal=false" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;"><span class="material-symbols-outlined" style="font-size:16px;">close</span></button>
      </div>
      <form action="{{ route('sales.products.store') }}" method="POST" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
        @csrf
        <input type="hidden" name="id" :value="editingProduct.id">
        <div style="flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:1rem; background:#f4f4f4;">
          <div style="display:grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap:0.75rem;">
            <div style="grid-column: span 7 / span 7;"><label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Product name *</label><input type="text" name="name" x-model="editingProduct.name" required placeholder="HP ProBook 440 G10" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;"></div>
            <div style="grid-column: span 2 / span 2;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">Brand</label><input type="text" name="brand" x-model="editingProduct.brand" placeholder="HP" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"></div>
            <div style="grid-column: span 3 / span 3;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">Model</label><input type="text" name="model" x-model="editingProduct.model" placeholder="440 G10" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"></div>
            <div style="grid-column: span 4 / span 4;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">SKU</label><input type="text" name="sku" x-model="editingProduct.sku" placeholder="Auto" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></div>
            <div style="grid-column: span 4 / span 4;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">Barcode</label><input type="text" name="barcode" placeholder="Scan" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></div>
            <div style="grid-column: span 4 / span 4;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">Tracking</label><select name="tracking_method" x-model="editingProduct.tracking_method" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"><option value="quantity">Quantity</option><option value="serial">Serial</option><option value="batch">Batch</option><option value="none">None</option></select></div>
            <div style="grid-column: span 5 / span 5;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">Category *</label><select name="category" x-model="editingProduct.category" required style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"><option>Hardware</option><option>Accessories</option><option>Services</option><option>Software</option><option>Consumables</option><option>Networking</option><option>CCTV</option></select></div>
            <div style="grid-column: span 4 / span 4;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">Supplier</label><select name="supplier_id" x-model="editingProduct.supplier_id" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"><option value="">— None —</option>@foreach($suppliers as $sup)<option value="{{ $sup->id }}">{{ $sup->name }}</option>@endforeach</select></div>
            <div style="grid-column: span 12 / span 12;"><label style="font-size:0.75rem; font-weight:600; color:#525252;">Specs JSON</label><input type="text" name="specs" x-model="editingProduct.specs" placeholder='{"cpu":"i7-1355U","ram":"16GB"}' style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></div>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
            <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">Selling TZS *</label><div style="position:relative; margin-top:0.25rem;"><span style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); font-size:0.75rem; color:#525252;">TZS</span><input type="number" name="price" x-model="editingProduct.price" required min="0" style="width:100%; height:2rem; padding:0 0.75rem 0 2.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-weight:600; font-family:'IBM Plex Mono',monospace;"></div></div>
            <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">Cost TZS</label><div style="position:relative; margin-top:0.25rem;"><span style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); font-size:0.75rem; color:#525252;">TZS</span><input type="number" name="cost_price" x-model="editingProduct.cost_price" min="0" style="width:100%; height:2rem; padding:0 0.75rem 0 2.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace;"></div></div>
          </div>
          <div x-show="!editingProduct.is_service" style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
            <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">On hand</label><input type="number" name="stock_quantity" x-model="editingProduct.stock_quantity" min="0" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></div>
            <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">Reorder at</label><input type="number" name="min_stock_alert" x-model="editingProduct.min_stock_alert" min="1" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></div>
          </div>
          <label style="display:flex; gap:0.75rem; padding:0.75rem; background:#fff; border:1px solid #e0e0e0; cursor:pointer;"><input type="checkbox" name="is_service" x-model="editingProduct.is_service" value="1" style="margin-top:0.125rem;"><span style="font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"><span style="font-weight:600; color:#161616;">Service — no stock</span><span style="display:block; font-size:0.75rem; color:#525252;">Tracked as service, not inventory.</span></span></label>
        </div>
        <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#fff; display:flex; justify-content:space-between; align-items:center;">
          <button type="button" @click="showProductModal=false" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;" x-text="editingProduct.id ? 'Save changes' : 'Create product'"></button>
        </div>
      </form>
    </div>
  </div>

  <div x-show="showStockAdjustModal" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div @click.away="showStockAdjustModal=false" style="background:#fff; width:100%; max-width:24rem; border:1px solid #e0e0e0;">
      <form :action="'/sales/products/' + stockItem.id + '/adjust'" method="POST">
        @csrf
        <input type="hidden" name="action" :value="adjustAction">
        <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
          <h3 style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;" x-text="adjustAction==='stock_in' ? 'Stock In' : 'Stock Out'"></h3>
          <button type="button" @click="showStockAdjustModal=false" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;"><span class="material-symbols-outlined" style="font-size:16px;">close</span></button>
        </div>
        <div style="padding:1rem; display:flex; flex-direction:column; gap:0.75rem;">
          <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;" x-text="stockItem.name"></div>
          <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">Quantity *</label><input type="number" name="quantity" x-model="adjustQty" required min="1" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></div>
          <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">Reason *</label><input type="text" name="reason" x-model="adjustReason" required placeholder="Supplier delivery / Damaged" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;"></div>
          <div><label style="font-size:0.75rem; font-weight:600; color:#525252;">Reference</label><input type="text" name="reference" x-model="adjustReference" placeholder="PO-00231" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></div>
        </div>
        <div style="padding:1rem; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem; background:#f4f4f4;">
          <button type="button" @click="showStockAdjustModal=false" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer;">Cancel</button>
          <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer;">Confirm</button>
        </div>
      </form>
    </div>
  </div>

  <div x-show="showSerialModal" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div @click.away="showSerialModal=false" style="background:#fff; width:100%; max-width:32rem; border:1px solid #e0e0e0; max-height:90vh; display:flex; flex-direction:column;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
        <div>
          <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Goods Receiving</div>
          <h3 style="font-size:0.875rem; font-weight:600; color:#161616; margin-top:0.125rem;" x-text="'Receive: ' + serialProduct.name"></h3>
          <p style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;" x-text="serialProduct.sku"></p>
        </div>
        <button @click="showSerialModal=false" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;"><span class="material-symbols-outlined" style="font-size:16px;">close</span></button>
      </div>
      <form action="{{ route('sales.products.store') }}" method="POST" style="padding:1rem; display:flex; flex-direction:column; gap:0.75rem; overflow-y:auto;">
        @csrf
        <input type="hidden" name="id" :value="serialProduct.id">
        <input type="hidden" name="is_serial_receive" value="1">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
          <select name="supplier_id" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem;"><option value="">Supplier —</option>@foreach($suppliers as $sup)<option value="{{ $sup->id }}">{{ $sup->name }}</option>@endforeach</select>
          <input type="text" name="purchase_order" placeholder="PO-00231" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace;">
        </div>
        <div>
          <label style="font-size:0.75rem; font-weight:600; color:#525252;">Serials (one per line)</label>
          <textarea name="serial_numbers" x-model="serialNumbers" rows="6" required placeholder="5CD123001&#10;5CD123002&#10;5CD123003" style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;"></textarea>
          <p style="font-size:0.75rem; color:#525252; margin-top:0.25rem;" x-text="(serialNumbers.split('\n').filter(s=>s.trim()).length) + ' serials'"></p>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:0.5rem; padding-top:0.75rem; border-top:1px solid #e0e0e0;">
          <button type="button" @click="showSerialModal=false" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer;">Cancel</button>
          <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer;">Receive</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Delete confirm Carbon modal --}}
  <div x-show="showDeleteConfirm" x-cloak @keydown.escape.window="closeDeleteConfirm()" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div x-trap.noscroll="showDeleteConfirm" role="dialog" aria-modal="true" aria-labelledby="delete-inventory-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeDeleteConfirm()">
      <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
        <h3 id="delete-inventory-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Remove product?</h3>
        <button @click="closeDeleteConfirm()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
      </div>
      <div style="padding:1.25rem; background:#fff;">
        <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Remove <span style="font-weight:600; color:#161616;" x-text="pendingDeleteProduct.name"></span> from inventory? This cannot be undone.</p>
      </div>
      <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
        <button x-ref="cancelDeleteProductBtn" @click="closeDeleteConfirm()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
        <button @click="confirmDeleteProduct()" type="button" style="height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Delete</button>
      </div>
    </div>
  </div>
</div>
@endsection
