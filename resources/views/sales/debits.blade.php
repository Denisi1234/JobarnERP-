@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  [x-cloak]{display:none!important}
  .cds--tile, .cds--data-table-container, .cds--btn, .cds--tag { border-radius:0 !important; }
  .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
  @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
  .ibm-hgrid-5 { display:grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem; }
  @media(max-width:80rem){ .ibm-hgrid-5 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
  @media(max-width:42rem){ .ibm-hgrid-5 { grid-template-columns: 1fr; } }
</style>

<div class="cds--content" style="background:#f4f4f4; min-height:100vh; margin:-1.5rem -1.5rem 0 -1.5rem; padding:0 0 2rem 0;" x-data="{ showDebitModal: false, filter: 'all', search: '', showMessageModal: false, messageDebit: { id: null, type: '', name: '', phone: '', email: '' }, messageBody: '', messageChannel: 'sms', openDebitMessage(debit) { this.messageDebit = debit; const name = debit.type==='supplier' ? (debit.supplier_name || debit.supplier?.name || 'Supplier') : (debit.customer_name || 'Customer'); const phone = debit.type==='supplier' ? (debit.supplier_phone || debit.supplier?.phone || '') : (debit.customer_phone || ''); this.messageBody = debit.type==='supplier' ? 'Habari ' + name + ', tunakumbusha deni la TZS ' + new Intl.NumberFormat().format(debit.amount) + ' kwa showroom. Tafadhali lipa kwa wakati. — JOBARN' : 'Habari ' + name + ', deni lako la TZS ' + new Intl.NumberFormat().format(debit.amount) + ' bado halijalipwa. Tafadhali wasiliana nasi. — JOBARN'; this.messageChannel = phone ? 'sms' : 'email'; this.showMessageModal = true; } }">

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0;">
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:0;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('sales.index') }}" style="color:#0f62fe; text-decoration:none;">Sales</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Debits</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; padding-bottom:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">Showroom Debits</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">Payables and receivables — supplier and customer balances linked to inventory</p>
      </div>
      <div style="display:flex; gap:0.625rem; flex-wrap:wrap;">
        <button @click="showDebitModal = true" type="button" class="cds--btn cds--btn--primary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; display:inline-flex; align-items:center; gap:0.5rem;">
          <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M17 15V8h-2v7H8v2h7v7h2v-7h7v-2z"/></svg> New debit
        </button>
      </div>
    </div>
  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  @if(session('success'))
  <x-success-popup :message="session('success')" />
  @endif
  @if(isset($errors) && $errors->any())
  <div class="cds--inline-notification cds--inline-notification--low-contrast cds--inline-notification--error" role="alert" style="margin-bottom:1rem; padding:1rem; background:#fff1f1; border:1px solid #ffb3b8; border-left:4px solid #da1e28;">
    <ul style="margin:0; padding-left:1.25rem; font-size:0.875rem; color:#750e13; font-family:'IBM Plex Sans',sans-serif;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
  @endif

  {{-- IBM KPI Tiles — 5 --}}
  <div class="ibm-hgrid-5">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Pending total</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#161616;">TZS {{ number_format($totalPending) }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem; font-family:'IBM Plex Sans',sans-serif;">{{ $debits->where('status','pending')->count() }} pending</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Supplier payables</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#e9730c;">TZS {{ number_format($supplierPending) }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">{{ $supplierCount }} supplier debits</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Customer receivables</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#161616;">TZS {{ number_format($customerPending) }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">{{ $customerCount }} customer debits</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Overdue</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#da1e28;">TZS {{ number_format($totalOverdue) }}</div>
      <div style="font-size:0.75rem; color:#750e13; margin-top:0.375rem;">Past due</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Paid</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#24a148;">TZS {{ number_format($totalPaid) }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">{{ $debits->where('status','paid')->count() }} paid</div>
    </div>
  </div>

  {{-- IBM Filter Bar --}}
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-bottom:1rem;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.75rem 1rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0;">
      <div style="display:flex; align-items:center; gap:0.375rem; flex-wrap:wrap;">
        <button @click="filter='all'" :style="filter==='all' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">All ({{ $debits->count() }})</button>
        <button @click="filter='supplier'" :style="filter==='supplier' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">Supplier ({{ $supplierCount }})</button>
        <button @click="filter='customer'" :style="filter==='customer' ? 'background:#0f62fe; color:#fff; border:1px solid #0f62fe;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">Customer ({{ $customerCount }})</button>
        <button @click="filter='pending'" :style="filter==='pending' ? 'background:#e9730c; color:#fff; border:1px solid #e9730c;' : 'background:#fff; color:#525252; border:1px solid #e0e0e0;'" style="height:2rem; padding:0 0.75rem; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; cursor:pointer;">Pending</button>
      </div>
      <div style="position:relative;">
        <svg style="position:absolute; left:0.625rem; top:50%; transform:translateY(-50%); width:1rem; height:1rem; fill:#525252;" viewBox="0 0 32 32"><path d="M14 4A10 10 0 1 0 24 14A10 10 0 0 0 14 4zm0 18A8 8 0 1 1 22 14A8 8 0 0 1 14 22z"/><path d="M26.7 24.7L21.3 19.3L20 20.7l5.4 5.4z"/></svg>
        <input type="text" x-model="search" placeholder="Search party, product, amount..." style="height:2rem; padding:0 0.75rem 0 2rem; width:18rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
      </div>
    </div>

    {{-- IBM Data Table --}}
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
        <thead>
          <tr style="background:#e0e0e0;">
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; font-family:'IBM Plex Sans',sans-serif;">Date</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Party</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Product</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Type</th>
            <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Amount</th>
            <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Status</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Due</th>
            <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($debits as $d)
          <tr x-show="(filter==='all' || filter===$el.dataset.type || (filter==='pending' && $el.dataset.status==='pending')) && ('{{ strtolower($d->supplier?->name ?? $d->customer_name ?? '') }} {{ strtolower($d->product?->name ?? '') }} {{ $d->amount }}'.includes(search.toLowerCase()))"
              data-type="{{ $d->type }}" data-status="{{ $d->status }}"
              x-data="{ type: '{{ $d->type }}', status: '{{ $d->status }}' }"
              style="border-bottom:1px solid #e0e0e0; background:#fff;">
            <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252;">{{ $d->created_at->format('d M, h:i A') }}</td>
            <td style="padding:0.75rem 1rem;">
              @if($d->type==='supplier' && $d->supplier)
                <div style="display:flex; align-items:center; gap:0.5rem;">
                  <div style="width:1.75rem; height:1.75rem; background:#e0e0e0; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:14px;">local_shipping</span>
                  </div>
                  <span style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ Str::limit($d->supplier->name, 16) }}</span>
                </div>
                <div style="font-size:0.75rem; color:#525252; margin-top:0.125rem;">{{ $d->supplier->product_kind ?? '' }}</div>
              @else
                <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ Str::limit($d->customer_name ?? 'Walk-in', 18) }}</div>
                <div style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;">{{ $d->customer_phone ?? '' }}</div>
              @endif
            </td>
            <td style="padding:0.75rem 1rem; font-size:0.875rem; color:#525252;">{{ $d->product ? Str::limit($d->product->name, 18) : ($d->invoice ? Str::limit($d->invoice->service ?? '—', 18) : '—') }} <span style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#8d8d8d;">{{ $d->product?->sku ?? '' }}</span></td>
            <td style="padding:0.75rem 1rem;">
              <span style="background:{{ $d->type==='supplier' ? '#fff8e1' : '#defbe6' }}; border:1px solid {{ $d->type==='supplier' ? '#f1c21b' : '#a7f0ba' }}; color:{{ $d->type==='supplier' ? '#684e00' : '#044317' }}; font-size:0.75rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif; font-weight:600;">{{ $d->type==='supplier' ? 'Payable' : 'Receivable' }}</span>
            </td>
            <td style="padding:0.75rem 1rem; text-align:right; font-family:'IBM Plex Mono',monospace; font-size:0.875rem; font-weight:600; color:#161616;">TZS {{ number_format($d->amount) }}</td>
            <td style="padding:0.75rem 1rem; text-align:center;">
              <span style="background:{{ $d->status==='paid' ? '#defbe6' : ($d->status==='overdue' ? '#fff1f1' : '#fff8e1') }}; border:1px solid {{ $d->status==='paid' ? '#a7f0ba' : ($d->status==='overdue' ? '#ffb3b8' : '#f1c21b') }}; color:{{ $d->status==='paid' ? '#044317' : ($d->status==='overdue' ? '#750e13' : '#684e00') }}; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Sans',sans-serif; font-weight:600; text-transform:uppercase; letter-spacing:0.02em;">{{ strtoupper($d->status) }}</span>
            </td>
            <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252;">{{ $d->due_date ? $d->due_date->format('d M Y') : '—' }}</td>
            <td style="padding:0.75rem 1rem; text-align:right;">
              <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.25rem;">
                <button @click="openDebitMessage({ id: {{ $d->id }}, type: '{{ $d->type }}', amount: {{ $d->amount }}, supplier_name: @js($d->supplier?->name), supplier: @js($d->supplier), supplier_phone: @js($d->supplier?->phone), customer_name: @js($d->customer_name), customer_phone: @js($d->customer_phone) })" type="button" style="width:2rem; height:2rem; background:#fff; border:1px solid #8d8d8d; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" title="Send message">
                  <span class="material-symbols-outlined" style="font-size:14px;">chat</span>
                </button>
                @if($d->status!=='paid')
                <form action="{{ route('sales.debits.pay', $d->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Mark debit #{{ $d->id }} as paid?');">
                  @csrf
                  <button type="submit" style="width:2rem; height:2rem; background:#defbe6; border:1px solid #a7f0ba; display:flex; align-items:center; justify-content:center; color:#044317; cursor:pointer;" title="Mark paid">
                    <span class="material-symbols-outlined" style="font-size:16px;">check</span>
                  </button>
                </form>
                @endif
                <form action="{{ route('sales.debits.destroy', $d->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete debit #{{ $d->id }}?');">
                  @csrf @method('DELETE')
                  <button type="submit" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#da1e28; cursor:pointer;" title="Delete">
                    <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">No debits yet. Supplier restock or customer pending will appear here.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- New Debit Modal — IBM Carbon --}}
  <div x-show="showDebitModal" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div @click.away="showDebitModal = false" style="background:#fff; width:100%; max-width:32rem; max-height:92vh; display:flex; flex-direction:column; border:1px solid #e0e0e0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
        <div>
          <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Showroom Ledger</div>
          <h3 style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif; margin-top:0.125rem;">New debit</h3>
          <p style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Payable or receivable</p>
        </div>
        <button @click="showDebitModal = false" type="button" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;">
          <span class="material-symbols-outlined" style="font-size:16px;">close</span>
        </button>
      </div>
      <form action="{{ route('sales.debits.store') }}" method="POST" style="flex:1; overflow-y:auto; display:flex; flex-direction:column;">
        @csrf
        <div style="padding:1rem; display:flex; flex-direction:column; gap:1rem; background:#f4f4f4; flex:1; overflow-y:auto;" x-data="{ type: 'supplier' }">
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Type <span style="color:#da1e28;">*</span></label>
            <select name="type" x-model="type" required style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">
              <option value="supplier">Supplier — we owe (payable)</option>
              <option value="customer">Customer — owes us (receivable)</option>
            </select>
          </div>
          <div x-show="type==='supplier'">
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Supplier <span style="color:#da1e28;">*</span></label>
            <select name="supplier_id" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
              <option value="">— Select —</option>
              @foreach($suppliers as $sup)<option value="{{ $sup->id }}">{{ $sup->name }} — {{ $sup->product_kind ?? $sup->supply_categories[0] ?? 'General' }}</option>@endforeach
            </select>
          </div>
          <div x-show="type==='customer'" style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:#525252;">Customer name <span style="color:#da1e28;">*</span></label>
              <input type="text" name="customer_name" placeholder="e.g. Juma Ali" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
            </div>
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:#525252;">Phone</label>
              <input type="text" name="customer_phone" placeholder="+255..." style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
            </div>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Product (optional)</label>
            <select name="pos_product_id" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
              <option value="">— None —</option>
              @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>@endforeach
            </select>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:#525252;">Amount (TZS) <span style="color:#da1e28;">*</span></label>
              <input type="number" name="amount" required min="1" placeholder="50000" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;">
            </div>
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:#525252;">Due date</label>
              <input type="date" name="due_date" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
            </div>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Notes</label>
            <textarea name="notes" rows="2" placeholder="e.g. Showroom restock 10x HDMI, PO-123" style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;"></textarea>
          </div>
        </div>
        <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#fff; display:flex; justify-content:space-between; align-items:center;">
          <button type="button" @click="showDebitModal = false" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Create debit</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Send Message Modal — IBM --}}
  <div x-show="showMessageModal" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div @click.away="showMessageModal = false" style="background:#fff; width:100%; max-width:32rem; max-height:92vh; display:flex; flex-direction:column; border:1px solid #e0e0e0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
        <div style="display:flex; align-items:center; gap:0.75rem;">
          <div style="width:2rem; height:2rem; background:#0f62fe; color:#fff; display:flex; align-items:center; justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:16px;">chat</span>
          </div>
          <div>
            <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Message</div>
            <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;" x-text="(messageDebit.type==='supplier' ? (messageDebit.supplier_name || messageDebit.supplier?.name || 'Supplier') : (messageDebit.customer_name || 'Customer')) + ' • TZS ' + new Intl.NumberFormat().format(messageDebit.amount || 0)"></div>
          </div>
        </div>
        <button @click="showMessageModal = false" type="button" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;">
          <span class="material-symbols-outlined" style="font-size:16px;">close</span>
        </button>
      </div>
      <form :action="'/sales/debits/' + messageDebit.id + '/message'" method="POST" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
        @csrf
        <div style="flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:1rem; background:#f4f4f4;">
          <div style="background:#fff; border:1px solid #e0e0e0; padding:0.75rem;">
            <div style="display:flex; justify-content:space-between; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"><span style="font-weight:600; color:#525252;">To:</span><span style="font-family:'IBM Plex Mono',monospace; color:#161616;" x-text="messageDebit.type==='supplier' ? (messageDebit.supplier_phone || messageDebit.supplier?.phone || '—') : (messageDebit.customer_phone || '—')"></span></div>
            <div style="display:flex; justify-content:space-between; font-size:0.875rem; margin-top:0.375rem;"><span style="font-weight:600; color:#525252;">Debit:</span><span style="font-family:'IBM Plex Mono',monospace; color:#161616;" x-text="'#' + messageDebit.id + ' • TZS ' + new Intl.NumberFormat().format(messageDebit.amount || 0)"></span></div>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Channel</label>
            <select name="channel" x-model="messageChannel" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
              <option value="sms">SMS only</option>
              <option value="email">Email only</option>
              <option value="both">Both</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Message <span style="color:#da1e28;">*</span></label>
            <textarea name="message" x-model="messageBody" required rows="4" maxlength="1000" style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;"></textarea>
            <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem; font-family:'IBM Plex Mono',monospace;" x-text="messageBody.length + '/1000'"></div>
          </div>
        </div>
        <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#fff; display:flex; justify-content:space-between; align-items:center;">
          <button type="button" @click="showMessageModal = false" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer;">Cancel</button>
          <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:0.375rem;">
            <span class="material-symbols-outlined" style="font-size:14px;">send</span> Send message
          </button>
        </div>
      </form>
    </div>
  </div>

  @if(isset($recentMessages) && $recentMessages->count())
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-top:1rem;">
    <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
      <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Recent debit messages</div>
      <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $recentMessages->count() }} sent</span>
    </div>
    <div>
      @foreach($recentMessages as $msg)
      <div style="padding:1rem; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
        <div style="min-width:0; flex:1;">
          <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <span style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $msg->debit?->type==='supplier' ? ($msg->supplier?->name ?? $msg->debit?->supplier?->name ?? 'Supplier') : ($msg->customer_name ?? $msg->debit?->customer_name ?? 'Customer') }}</span>
            <span style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252;">{{ $msg->phone ?? $msg->email }}</span>
            <span style="background:{{ $msg->status==='sent' ? '#defbe6' : ($msg->status==='log_only' ? '#fff8e1' : '#fff1f1') }}; border:1px solid {{ $msg->status==='sent' ? '#a7f0ba' : ($msg->status==='log_only' ? '#f1c21b' : '#ffb3b8') }}; color:{{ $msg->status==='sent' ? '#044317' : ($msg->status==='log_only' ? '#684e00' : '#750e13') }}; font-size:0.6875rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; letter-spacing:0.02em;">{{ strtoupper($msg->status) }}</span>
            <span style="font-size:0.75rem; color:#8d8d8d;">{{ $msg->channel }}</span>
          </div>
          <div style="font-size:0.875rem; color:#525252; margin-top:0.375rem; font-family:'IBM Plex Sans',sans-serif; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $msg->message }}</div>
          <div style="font-size:0.75rem; color:#8d8d8d; margin-top:0.25rem; font-family:'IBM Plex Mono',monospace;">{{ $msg->created_at?->format('d M Y, h:i A') }} • by {{ $msg->sender?->name ?? '—' }} • debit #{{ $msg->debit_id }}</div>
        </div>
        <form action="{{ route('sales.debits.messages.destroy', $msg->id) }}" method="POST" style="flex-shrink:0;" onsubmit="return confirm('Delete this message?');">
          @csrf @method('DELETE')
          <button type="submit" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#da1e28; cursor:pointer;">
            <span class="material-symbols-outlined" style="font-size:14px;">delete</span>
          </button>
        </form>
      </div>
      @endforeach
    </div>
  </div>
  @endif
</div>
</div>
@endsection
