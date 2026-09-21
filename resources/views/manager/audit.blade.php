@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  [x-cloak]{display:none!important}
  .cds--tile, .cds--btn, .cds--tag { border-radius:0 !important; }
  .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
  @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
</style>

<div class="cds--content" style="background:#f4f4f4; min-height:100vh; margin:-1.5rem -1.5rem 0 -1.5rem; padding:0 0 2rem 0;">

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0;">
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:1rem;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a class="cds--link" href="{{ route('manager.index') }}" style="color:#0f62fe; text-decoration:none;">Manager</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Audit Log</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">Audit Log — All Actions</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">Complete trail of every action across Reception, IT, Sales, Inventory, HR — filterable, searchable, professional</p>
      </div>
      <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.375rem 0.75rem; font-family:'IBM Plex Mono',monospace;">{{ $auditItems->count() }} events</span>
        <span style="background:#defbe6; border:1px solid #a7f0ba; color:#044317; font-size:0.75rem; padding:0.375rem 0.75rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Live</span>
      </div>
    </div>
  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  {{-- KPI Tiles --}}
  <div style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem;">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $stats['total'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">Total Events</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#0f62fe;">{{ $stats['visits'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">Visits</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#24a148;">{{ $stats['invoices'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">Sales</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#e9730c;">{{ $stats['tickets'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">IT Tickets</div>
    </div>
  </div>

  {{-- Professional Filtering --}}
  <form method="GET" action="{{ route('manager.audit') }}" class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1rem; margin-bottom:1rem;">
    <div style="display:grid; grid-template-columns: 1.5fr auto auto auto auto; gap:0.75rem; align-items:end;">
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Search</label>
        <div style="position:relative; margin-top:0.25rem;">
          <span class="material-symbols-outlined" style="position:absolute; left:0.5rem; top:50%; transform:translateY(-50%); font-size:16px; color:#8d8d8d;">search</span>
          <input type="text" name="search" value="{{ $search }}" placeholder="Search actions, user, details, department..." style="width:100%; height:2rem; padding:0 0.75rem 0 2rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
        </div>
      </div>
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252;">Type</label>
        <select name="type" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">
          <option value="all" {{ $type==='all' ? 'selected' : '' }}>All types</option>
          <option value="visit" {{ $type==='visit' ? 'selected' : '' }}>Visits</option>
          <option value="inventory" {{ $type==='inventory' ? 'selected' : '' }}>Inventory</option>
          <option value="invoice" {{ $type==='invoice' ? 'selected' : '' }}>Sales</option>
          <option value="ticket" {{ $type==='ticket' ? 'selected' : '' }}>IT Tickets</option>
          <option value="timeline" {{ $type==='timeline' ? 'selected' : '' }}>System</option>
          <option value="task" {{ $type==='task' ? 'selected' : '' }}>Tasks</option>
          <option value="workreport" {{ $type==='workreport' ? 'selected' : '' }}>Logbooks</option>
        </select>
      </div>
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252;">Department</label>
        <select name="dept" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
          <option value="all">All depts</option>
          @foreach($departments as $dept)<option value="{{ strtolower($dept->name) }}" {{ $dept===strtolower($dept->name) ? 'selected' : '' }}>{{ $dept->name }}</option>@endforeach
        </select>
      </div>
      <div style="display:flex; gap:0.5rem; align-items:end;">
        <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Filter</button>
        <a href="{{ route('manager.audit') }}" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; display:inline-flex; align-items:center; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">Clear</a>
      </div>
    </div>
    <div style="display:grid; grid-template-columns: auto auto; gap:0.75rem; margin-top:0.75rem;">
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252;">From</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
      </div>
      <div>
        <label style="font-size:0.75rem; font-weight:600; color:#525252;">To</label>
        <input type="date" name="date_to" value="{{ $dateTo }}" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
      </div>
    </div>
  </form>

  {{-- Audit Log Table --}}
  <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0;">
    <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
      <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">All Actions — Live Audit Trail</div>
      <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $auditItems->count() }} shown • Professional</span>
    </div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
        <thead>
          <tr style="background:#e0e0e0;">
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Time</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Action</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Department</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">User</th>
            <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Details</th>
            <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Type</th>
          </tr>
        </thead>
        <tbody>
          @forelse($auditItems as $a)
          <tr style="border-bottom:1px solid #e0e0e0; background:#fff;">
            <td style="padding:0.75rem 1rem; font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252; white-space:nowrap;">{{ $a['time'] ? \Carbon\Carbon::parse($a['time'])->format('d M Y, H:i') : 'Just now' }}</td>
            <td style="padding:0.75rem 1rem;">
              <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:16rem;">{{ $a['title'] }}</div>
              <div style="font-size:0.75rem; color:#525252; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:16rem;">{{ Str::limit($a['desc'], 60) }}</div>
            </td>
            <td style="padding:0.75rem 1rem;"><span style="background:#e0e0e0; border:1px solid #c6c6c6; color:#393939; font-size:0.75rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif;">{{ $a['dept'] }}</span></td>
            <td style="padding:0.75rem 1rem; font-size:0.875rem; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $a['user'] }}</td>
            <td style="padding:0.75rem 1rem; font-size:0.75rem; color:#525252; max-width:18rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $a['desc'] }}</td>
            <td style="padding:0.75rem 1rem; text-align:center;"><span style="background:#f4f4f4; border:1px solid #e0e0e0; color:#525252; font-size:0.6875rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Mono',monospace; text-transform:uppercase; letter-spacing:0.02em;">{{ $a['_type'] }}</span></td>
          </tr>
          @empty
          <tr><td colspan="6" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">No audit events — adjust filters.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#f4f4f4; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">
      <span>Showing {{ $auditItems->count() }} events • Filtered • Professional audit</span>
      <span style="font-family:'IBM Plex Mono',monospace;">Live • {{ now()->format('H:i') }}</span>
    </div>
  </div>

</div>
</div>
@endsection
