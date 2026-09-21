@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  [x-cloak]{display:none!important}
  .cds--tile, .cds--data-table-container, .cds--btn, .cds--tag { border-radius:0 !important; }
  .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
  @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
  .ibm-hgrid-3 { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem; }
  @media(max-width:66rem){ .ibm-hgrid-3 { grid-template-columns: repeat(1, minmax(0, 1fr)); } }
</style>

<div class="cds--content" style="background:#f4f4f4; min-height:100vh; margin:-1.5rem -1.5rem 0 -1.5rem; padding:0 0 2rem 0;" x-data="{ showSupplierModal: false, showMessageModal: false, messageSupplier: { id: null, name: '', phone: '', email: '' }, messageBody: '', messageChannel: 'sms', openMessage(supplier) { this.messageSupplier = supplier; this.messageBody = 'Habari ' + supplier.name + ', tunahitaji kuagiza stock. Tafadhali tujulishe upatikanaji na bei. — JOBARN'; this.messageChannel = supplier.phone ? 'sms' : 'email'; this.showMessageModal = true; } }">

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0;">
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:0;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('sales.index') }}" style="color:#0f62fe; text-decoration:none;">Sales</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Suppliers</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; padding-bottom:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">Suppliers</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">Manage stock suppliers — linked to inventory for purchase orders and stock receipts</p>
      </div>
      <div style="display:flex; gap:0.625rem; flex-wrap:wrap;">
        <button @click="showSupplierModal = true" type="button" class="cds--btn cds--btn--primary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; display:inline-flex; align-items:center; gap:0.5rem;">
          <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M17 15V8h-2v7H8v2h7v7h2v-7h7v-2z"/></svg> Add supplier
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

  {{-- IBM KPI Tiles --}}
  <div class="ibm-hgrid-3">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Total suppliers</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $suppliers->count() }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem; font-family:'IBM Plex Sans',sans-serif;">Registered</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Active</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#0f62fe;">{{ $suppliers->where('is_active', true)->count() }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">Available</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem;">
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Linked products</div>
      <div style="font-size:1.5rem; font-weight:600; margin-top:0.75rem; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $suppliers->sum('products_count') }}</div>
      <div style="font-size:0.75rem; color:#525252; margin-top:0.375rem;">Inventory links</div>
    </div>
  </div>

  {{-- IBM Data Table — Suppliers --}}
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-bottom:1rem;">
    <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
      <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Suppliers</div>
      <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $suppliers->count() }} total</span>
    </div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
        <thead>
          <tr style="background:#e0e0e0;">
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; font-family:'IBM Plex Sans',sans-serif;">Supplier</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Supplies</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Phone</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Email</th>
            <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Products</th>
            <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($suppliers as $s)
          <tr style="border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
            <td style="padding:0.75rem 1rem;">
              <div style="display:flex; align-items:center; gap:0.75rem;">
                <div style="width:2rem; height:2rem; background:#e0e0e0; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; flex-shrink:0;">
                  <span class="material-symbols-outlined" style="font-size:16px;">local_shipping</span>
                </div>
                <div style="min-width:0;">
                  <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif; line-height:1.2;">{{ $s->name }}</div>
                  <div style="font-size:0.75rem; color:#525252; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:12rem;">{{ $s->company ?? $s->product_kind ?? '—' }} @if($s->company && $s->product_kind) • {{ $s->product_kind }} @endif</div>
                </div>
              </div>
            </td>
            <td style="padding:0.75rem 1rem;">
              @if(!empty($s->supply_categories))
                <div style="display:flex; flex-wrap:wrap; gap:0.25rem;">
                  @foreach(array_slice($s->supply_categories,0,3) as $cat)
                    <span style="background:#e0e0e0; color:#393939; font-size:0.75rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif; border:1px solid #c6c6c6;">{{ $cat }}</span>
                  @endforeach
                  @if(count($s->supply_categories)>3)
                    <span style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;">+{{ count($s->supply_categories)-3 }}</span>
                  @endif
                </div>
              @elseif($s->product_kind)
                <span style="background:#e0e0e0; color:#393939; font-size:0.75rem; padding:0.125rem 0.375rem; border:1px solid #c6c6c6;">{{ $s->product_kind }}</span>
              @else
                <span style="font-size:0.75rem; color:#8d8d8d;">—</span>
              @endif
            </td>
            <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.875rem; color:#161616;">{{ $s->phone ?? '—' }}</td>
            <td style="padding:0.75rem 1rem; font-size:0.875rem; color:#525252; max-width:12rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $s->email ?? '—' }}</td>
            <td style="padding:0.75rem 1rem; text-align:center;"><span style="background:#e0e0e0; border:1px solid #c6c6c6; color:#393939; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $s->products_count }}</span></td>
            <td style="padding:0.75rem 1rem; text-align:right;">
              <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.25rem;">
                <button @click="openMessage(@js($s))" type="button" style="width:2rem; height:2rem; background:#fff; border:1px solid #8d8d8d; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" title="Send message">
                  <span class="material-symbols-outlined" style="font-size:16px;">chat</span>
                </button>
                <form action="{{ route('sales.suppliers.destroy', $s->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete {{ addslashes($s->name) }}?');">
                  @csrf @method('DELETE')
                  <button type="submit" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#da1e28; cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">No suppliers yet. Add one to link to products.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Recent Messages — IBM --}}
  @if(isset($recentMessages) && $recentMessages->count())
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-bottom:1rem;">
    <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
      <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Recent supplier messages</div>
      <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $recentMessages->count() }} sent</span>
    </div>
    <div style="divide-y:1px solid #e0e0e0;">
      @foreach($recentMessages as $msg)
      <div style="padding:1rem; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; border-bottom:1px solid #e0e0e0; {{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }}">
        <div style="min-width:0; flex:1;">
          <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <span style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $msg->supplier?->name ?? '—' }}</span>
            <span style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252;">{{ $msg->phone ?? $msg->email }}</span>
            <span style="background:{{ $msg->status==='sent' ? '#defbe6' : ($msg->status==='log_only' ? '#fff8e1' : '#fff1f1') }}; border:1px solid {{ $msg->status==='sent' ? '#a7f0ba' : ($msg->status==='log_only' ? '#f1c21b' : '#ffb3b8') }}; color:{{ $msg->status==='sent' ? '#044317' : ($msg->status==='log_only' ? '#684e00' : '#750e13') }}; font-size:0.6875rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; letter-spacing:0.02em;">{{ strtoupper($msg->status) }}</span>
            <span style="font-size:0.75rem; color:#8d8d8d; font-family:'IBM Plex Sans',sans-serif;">{{ $msg->channel }}</span>
          </div>
          <div style="font-size:0.875rem; color:#525252; margin-top:0.375rem; font-family:'IBM Plex Sans',sans-serif; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $msg->message }}</div>
          <div style="font-size:0.75rem; color:#8d8d8d; margin-top:0.25rem; font-family:'IBM Plex Mono',monospace;">{{ $msg->created_at?->format('d M Y, h:i A') }} • by {{ $msg->sender?->name ?? '—' }} • {{ $msg->created_at?->diffForHumans() }}</div>
        </div>
        <form action="{{ route('sales.suppliers.messages.destroy', $msg->id) }}" method="POST" style="flex-shrink:0;" onsubmit="return confirm('Delete this message?');">
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

  {{-- Add Supplier Modal — IBM Carbon --}}
  <div x-show="showSupplierModal" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div @click.away="showSupplierModal = false" style="background:#fff; width:100%; max-width:36rem; max-height:92vh; display:flex; flex-direction:column; border:1px solid #e0e0e0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
        <div>
          <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Procurement</div>
          <h3 style="font-size:0.875rem; font-weight:600; color:#161616; margin-top:0.125rem; font-family:'IBM Plex Sans',sans-serif;">New supplier</h3>
          <p style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Linked to stock-in and products</p>
        </div>
        <button @click="showSupplierModal = false" type="button" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;">
          <span class="material-symbols-outlined" style="font-size:16px;">close</span>
        </button>
      </div>
      <form action="{{ route('sales.suppliers.store') }}" method="POST" style="flex:1; overflow-y:auto; display:flex; flex-direction:column;">
        @csrf
        <div style="padding:1rem; display:flex; flex-direction:column; gap:1rem; background:#f4f4f4; flex:1; overflow-y:auto;">
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Name <span style="color:#da1e28;">*</span></label>
            <input type="text" name="name" required placeholder="e.g. Jorani Tech Supplies" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;">
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Company</label>
            <input type="text" name="company" placeholder="Company Ltd" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:#525252;">Phone</label>
              <input type="text" name="phone" placeholder="+255..." style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
            </div>
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:#525252;">Email</label>
              <input type="email" name="email" placeholder="supplier@example.com" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
            </div>
          </div>
          <div style="background:#fff; border:1px solid #e0e0e0; padding:1rem;">
            <label style="font-size:0.75rem; font-weight:600; letter-spacing:0.02em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">What does he supply?</label>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-top:0.5rem;">
              @php $supplyCats = ['Hardware','Accessories','Networking','Software','Consumables','Services']; @endphp
              @foreach($supplyCats as $cat)
              <label style="display:flex; align-items:center; gap:0.5rem; padding:0.5rem 0.75rem; background:#fff; border:1px solid #e0e0e0; cursor:pointer; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
                <input type="checkbox" name="supply_categories[]" value="{{ $cat }}" style="width:1rem; height:1rem; accent-color:#0f62fe;">
                <span>{{ $cat }}</span>
              </label>
              @endforeach
            </div>
            <div style="margin-top:0.75rem;">
              <label style="font-size:0.75rem; font-weight:600; color:#525252;">Primary kind</label>
              <select name="product_kind" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
                <option value="">— Select —</option>
                @foreach($supplyCats as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
              </select>
            </div>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Address / Notes</label>
            <textarea name="address" rows="2" placeholder="Physical address" style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;"></textarea>
            <textarea name="notes" rows="2" placeholder="Notes: e.g. delivers Mondays, payment Net 30" style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; margin-top:0.5rem;"></textarea>
          </div>
        </div>
        <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#fff; display:flex; justify-content:space-between; align-items:center;">
          <button type="button" @click="showSupplierModal = false" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Create supplier</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Send Message Modal — IBM Carbon --}}
  <div x-show="showMessageModal" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
    <div @click.away="showMessageModal = false" style="background:#fff; width:100%; max-width:32rem; max-height:92vh; display:flex; flex-direction:column; border:1px solid #e0e0e0;">
      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
        <div style="display:flex; align-items:center; gap:0.75rem;">
          <div style="width:2rem; height:2rem; background:#0f62fe; color:#fff; display:flex; align-items:center; justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:16px;">chat</span>
          </div>
          <div>
            <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Message</div>
            <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;" x-text="messageSupplier.name + ' • ' + (messageSupplier.phone || messageSupplier.email || 'No contact')"></div>
          </div>
        </div>
        <button @click="showMessageModal = false" type="button" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;">
          <span class="material-symbols-outlined" style="font-size:16px;">close</span>
        </button>
      </div>
      <form :action="'/sales/suppliers/' + messageSupplier.id + '/message'" method="POST" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
        @csrf
        <div style="flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:1rem; background:#f4f4f4;">
          <div style="background:#fff; border:1px solid #e0e0e0; padding:0.75rem;">
            <div style="display:flex; justify-content:space-between; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"><span style="font-weight:600; color:#525252;">To:</span><span style="font-family:'IBM Plex Mono',monospace; color:#161616;" x-text="messageSupplier.phone || '—'"></span></div>
            <div style="display:flex; justify-content:space-between; font-size:0.875rem; margin-top:0.375rem;"><span style="font-weight:600; color:#525252;">Email:</span><span style="color:#525252; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:12rem;" x-text="messageSupplier.email || '—'"></span></div>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Channel</label>
            <select name="channel" x-model="messageChannel" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
              <option value="sms">SMS only</option>
              <option value="email">Email only</option>
              <option value="both">Both SMS + Email</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Message <span style="color:#da1e28;">*</span></label>
            <textarea name="message" x-model="messageBody" required rows="4" maxlength="1000" placeholder="e.g. Habari, tunahitaji kuagiza..." style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;"></textarea>
            <div style="display:flex; justify-content:space-between; margin-top:0.25rem;">
              <span style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;" x-text="messageBody.length + '/1000'"></span>
              <span style="font-size:0.75rem; color:#525252;">Cost per SMS may apply</span>
            </div>
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
</div>
@endsection
