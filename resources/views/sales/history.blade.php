@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  [x-cloak]{display:none!important}
  .cds--tile, .cds--data-table-container, .cds--btn, .cds--tag { border-radius:0 !important; }
  .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
  @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
</style>

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0; margin: -1.5rem -1.5rem 0 -1.5rem; padding: 1rem 1.5rem 0 1.5rem;">
  <div class="ibm-grid" style="padding:0;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('sales.index') }}" style="color:#0f62fe; text-decoration:none;">Sales</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Sales History</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; padding-bottom:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans', sans-serif; letter-spacing:-0.02em;">Sales History</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans', sans-serif;">Hub for all sales — invoices, receipts and quotations — searchable, filterable, exportable</p>
      </div>
      <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <a href="{{ route('sales.invoices.export') }}" class="cds--btn cds--btn--secondary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; background:#393939; color:#fff; border:1px solid #393939; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
          <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M26 24v4H6v-4H4v4a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2v-4zm0-10l-1.41-1.41L17 20.17V2h-2v18.17l-7.59-7.58L6 14l10 10l10-10z"/></svg> Export CSV
        </a>
        <a href="{{ route('sales.index', ['tab' => 'register']) }}" class="cds--btn cds--btn--primary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; background:#0f62fe; color:#fff; border:1px solid #0f62fe; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">New Sale</a>
      </div>
    </div>

  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  {{-- KPIs --}}
  <div style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem;">
    @if($tab==='quotations')
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Total Quotations</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $totalQuotations }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">All documents</div>
      </div>
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Quoted Value</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">TZS {{ number_format($quotedValue) }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Total quoted</div>
      </div>
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Paid Invoices</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $paidCount }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Settled</div>
      </div>
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Pending</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $pendingCount }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Awaiting payment</div>
      </div>
    @else
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Total Sales</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">TZS {{ number_format($totalSales) }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Paid invoices</div>
      </div>
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Pending</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">TZS {{ number_format($totalPending) }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Awaiting collection</div>
      </div>
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Today</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $todayCount }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Receipts today</div>
      </div>
      <div style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
        <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Total Receipts</div>
        <div style="font-size:1.5rem; font-weight:600; margin-top:0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $allInvoices->count() }}</div>
        <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">All time</div>
      </div>
    @endif
  </div>

  {{-- Filters --}}
  <form method="GET" action="{{ route('sales.history') }}" style="background:#fff; border:1px solid #e0e0e0; padding:1rem; display:flex; flex-wrap:wrap; gap:0.75rem; align-items:end; margin-bottom:1rem;">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div style="flex:1; min-width:220px;">
      <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Search</label>
      <div style="position:relative; margin-top:0.25rem;">
        <span class="material-symbols-outlined" style="position:absolute; left:0.625rem; top:50%; transform:translateY(-50%); font-size:16px; color:#525252;">search</span>
        <input type="text" name="search" value="{{ $search }}" placeholder="Customer, receipt, quote..." style="width:100%; height:2rem; padding:0 0.75rem 0 2rem; border:1px solid #8d8d8d; border-bottom:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
      </div>
    </div>
    <div>
      <label style="font-size:0.75rem; font-weight:600; color:#525252;">Status</label>
      <select name="status" style="height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">
        <option value="all" {{ $status==='all' ? 'selected' : '' }}>All</option>
        @if($tab==='quotations')
          <option value="draft" {{ $status==='draft' ? 'selected' : '' }}>Draft</option>
          <option value="sent" {{ $status==='sent' ? 'selected' : '' }}>Sent</option>
          <option value="accepted" {{ $status==='accepted' ? 'selected' : '' }}>Accepted</option>
        @else
          <option value="paid" {{ $status==='paid' ? 'selected' : '' }}>Paid</option>
          <option value="pending_payment" {{ $status==='pending_payment' ? 'selected' : '' }}>Pending</option>
        @endif
      </select>
    </div>
    <div>
      <label style="font-size:0.75rem; font-weight:600; color:#525252;">From</label>
      <input type="date" name="date_from" value="{{ $dateFrom }}" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; margin-top:0.25rem;">
    </div>
    <div>
      <label style="font-size:0.75rem; font-weight:600; color:#525252;">To</label>
      <input type="date" name="date_to" value="{{ $dateTo }}" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; margin-top:0.25rem;">
    </div>
    <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Filter</button>
    <a href="{{ route('sales.history', ['tab' => $tab]) }}" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; display:inline-flex; align-items:center; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">Clear</a>
  </form>

  {{-- Data --}}
  @if($tab==='quotations')
    <div style="background:#fff; border:1px solid #e0e0e0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:0.875rem; font-weight:600;">Quotations</div>
        <span style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;">{{ $quotations->total() }} total</span>
      </div>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
          <thead>
            <tr style="background:#e0e0e0;">
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Quote #</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Customer</th>
              <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Total (TZS)</th>
              <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Status</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Date</th>
              <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($quotations as $q)
            <tr style="border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
              <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-weight:600;">{{ $q->quote_number }}</td>
              <td style="padding:0.75rem 1rem;">
                <div style="font-weight:600;">{{ $q->customer_name }}</div>
                @if($q->company)<div style="font-size:0.75rem; color:#525252;">{{ $q->company }}</div>@endif
              </td>
              <td style="padding:0.75rem 1rem; text-align:right; font-family:'IBM Plex Mono',monospace; font-weight:700;">TZS {{ number_format($q->total_amount) }}</td>
              <td style="padding:0.75rem 1rem; text-align:center;"><span style="padding:0.125rem 0.5rem; font-size:0.75rem; font-weight:600; background:{{ $q->status==='accepted' ? '#a7f0ba' : ($q->status==='sent' ? '#d0e2ff' : '#e0e0e0') }}; color:{{ $q->status==='accepted' ? '#044317' : ($q->status==='sent' ? '#002d9c' : '#393939') }};">{{ strtoupper($q->status) }}</span></td>
              <td style="padding:0.75rem 1rem; font-size:0.75rem; color:#525252;">{{ $q->created_at ? $q->created_at->format('d M Y') : '—' }}</td>
              <td style="padding:0.75rem 1rem; text-align:right;">
                <a href="{{ route('sales.quotations.print',$q->id) }}" target="_blank style="padding:0.375rem 0.75rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.75rem; font-weight:600; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">Print</a>
              </td>
            </tr>
            @empty
            <tr><td colspan="6" style="padding:2rem; text-align:center; color:#525252;">No quotations found</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:1rem; border-top:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#525252;">
        <span>Showing {{ $quotations->firstItem() ?? 0 }}–{{ $quotations->lastItem() ?? 0 }} of {{ $quotations->total() }}</span>
        <span>{{ $quotations->links() }}</span>
      </div>
    </div>
  @else
    <div style="background:#fff; border:1px solid #e0e0e0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:0.875rem; font-weight:600;">Invoices & Receipts</div>
        <span style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;">{{ $invoices->total() }} total</span>
      </div>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
          <thead>
            <tr style="background:#e0e0e0;">
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Receipt #</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Customer</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Items</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Payment</th>
              <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Amount (TZS)</th>
              <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Status</th>
              <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Date</th>
              <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($invoices as $inv)
            @php $pay = strtoupper($inv->payment_method ?? 'CASH'); @endphp
            <tr style="border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
              <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-weight:600;">{{ $inv->receipt_number ?? ('#ORD-'.$inv->id) }}</td>
              <td style="padding:0.75rem 1rem;">
                <div style="font-weight:600;">{{ $inv->customer_name ?: 'Walk-in' }}</div>
                @if($inv->company)<div style="font-size:0.75rem; color:#525252;">{{ $inv->company }}</div>@endif
              </td>
              <td style="padding:0.75rem 1rem; max-width:16rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:0.8125rem; color:#525252;">{{ Str::limit($inv->service ?? '—', 40) }}</td>
              <td style="padding:0.75rem 1rem;"><span style="padding:0.125rem 0.375rem; font-size:0.6875rem; font-weight:600; background:#e0e0e0; color:#393939; font-family:'IBM Plex Mono',monospace;">{{ $pay }}</span></td>
              <td style="padding:0.75rem 1rem; text-align:right; font-family:'IBM Plex Mono',monospace; font-weight:700;">TZS {{ number_format($inv->amount) }}</td>
              <td style="padding:0.75rem 1rem; text-align:center;"><span style="padding:0.125rem 0.5rem; font-size:0.75rem; font-weight:600; background:{{ $inv->status==='paid' ? '#a7f0ba' : '#e0e0e0' }}; color:{{ $inv->status==='paid' ? '#044317' : '#393939' }};">{{ $inv->status==='paid' ? 'Paid' : 'Pending' }}</span></td>
              <td style="padding:0.75rem 1rem; font-size:0.75rem; color:#525252;">{{ $inv->created_at ? $inv->created_at->format('d M Y, H:i') : '—' }}</td>
              <td style="padding:0.75rem 1rem; text-align:right;">
                <a href="{{ route('sales.invoices.print',$inv->id) }}" target="_blank style="padding:0.375rem 0.625rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.75rem; font-weight:600; text-decoration:none;">Print</a>
              </td>
            </tr>
            @empty
            <tr><td colspan="8" style="padding:2rem; text-align:center; color:#525252;">No invoices found — adjust filters or create a sale</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:1rem; border-top:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#525252;">
        <span>Showing {{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }}</span>
        <span>{{ $invoices->links() }}</span>
      </div>
    </div>
  @endif
</div>
@endsection
