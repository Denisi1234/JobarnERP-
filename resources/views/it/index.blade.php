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
  .it-main-grid { display:grid; grid-template-columns: 2fr 1fr; gap:1rem; align-items:start; }
  @media(max-width:66rem){ .it-main-grid { grid-template-columns: 1fr; } }
</style>

<div class="cds--content" style="background:#f4f4f4; min-height:100vh; margin:-1.5rem -1.5rem 0 -1.5rem; padding:0 0 2rem 0;">

<div style="background:#ffffff; border-bottom:1px solid #e0e0e0; border-top:3px solid #0f62fe;">
  <div class="ibm-grid" style="padding-top:1rem; padding-bottom:0;">
    <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
      <div class="cds--breadcrumb-item"><a href="{{ route('it.index') }}" style="color:#0f62fe; text-decoration:none;">IT</a></div>
      <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;">Service Desk</span></div>
    </nav>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; padding-bottom:1rem;">
      <div>
        <h1 style="font-size:2rem; font-weight:600; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans',sans-serif; letter-spacing:-0.02em;">Service Desk</h1>
        <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:42rem; line-height:1.5; font-family:'IBM Plex Sans',sans-serif;">{{ now()->format('l, d F Y') }} — Active repairs and billing queue · <span style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; background:#edf5ff; border:1px solid #d0e2ff; color:#0f62fe; padding:0.125rem 0.375rem;">Live</span></p>
      </div>
      <div style="display:flex; gap:0.625rem; flex-wrap:wrap;">
        <a href="{{ route('it.tickets.export') }}" class="cds--btn cds--btn--tertiary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; background:#ffffff; color:#161616; border:1px solid #8d8d8d; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
          <svg width="16" height="16" viewBox="0 0 32 32" fill="#525252"><path d="M26 24v4H6v-4H4v4a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2v-4zm0-10l-1.41-1.41L17 20.17V2h-2v18.17l-7.59-7.58L6 14l10 10l10-10z"/></svg> Export CSV
        </a>
      </div>
    </div>
  </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">

  @if(session('success'))
  <x-success-popup :message="session('success')" />
  @endif

  {{-- IBM KPI Tiles — 5 — polished --}}
  <div class="ibm-hgrid-5">
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; border-left:3px solid #0f62fe; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $stats['tickets_total'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem; font-family:'IBM Plex Sans',sans-serif;">Total Tickets</div>
      <div style="margin-top:0.5rem; font-size:0.6875rem; color:#8d8d8d; font-family:'IBM Plex Mono',monospace;">All time</div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#161616;">{{ $stats['tickets_pending'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">Open / Active</div>
      <div style="margin-top:0.5rem;"><span style="background:#e0e0e0; color:#525252; font-size:0.6875rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Mono',monospace;">Live</span></div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:{{ ($stats['aging_count'] ?? 0) > 0 ? '#da1e28' : '#161616' }};">{{ $stats['aging_count'] ?? 0 }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">Aging (&gt;24h)</div>
      @if(($stats['aging_count'] ?? 0) > 0)<div style="margin-top:0.5rem;"><span style="background:#fff1f1; border:1px solid #ffd7d9; color:#da1e28; font-size:0.6875rem; padding:0.125rem 0.375rem; font-weight:600;">Attention</span></div>@endif
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.5rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#0e6027;">{{ $stats['tickets_resolved'] }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">Resolved</div>
      <div style="margin-top:0.5rem;"><span style="background:#defbe6; border:1px solid #a7f0ba; color:#0e6027; font-size:0.6875rem; padding:0.125rem 0.375rem; font-weight:600;">Done</span></div>
    </div>
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:1.25rem; text-align:center;">
      <div style="font-size:1.25rem; font-weight:600; font-family:'IBM Plex Mono',monospace; color:#161616;">TZS {{ number_format($stats['revenue_pending']) }}</div>
      <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; margin-top:0.375rem;">Pending ({{ $stats['invoices_pending'] }})</div>
      <div style="margin-top:0.5rem; font-size:0.6875rem; color:#8d8d8d; font-family:'IBM Plex Mono',monospace;">To collect</div>
    </div>
  </div>

  {{-- Main Grid — 8 + 4 — IBM Carbon Responsive --}}
  <div class="it-main-grid">
    <div>
      <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; display:flex; flex-direction:column;">
        <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; background:#f4f4f4;">
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <span style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Service Request Queue</span>
            <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ count($tickets) }}</span>
          </div>
          <div style="display:flex; align-items:center; gap:0.375rem; flex-wrap:wrap;">
            <button onclick="filterTickets('all')" class="it-tab" data-filter="all" style="height:2rem; padding:0 0.75rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.75rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">All</button>
            <button onclick="filterTickets('forwarded')" class="it-tab" data-filter="forwarded" style="height:2rem; padding:0 0.75rem; background:#fff; color:#525252; border:1px solid #e0e0e0; font-size:0.75rem; font-weight:600; cursor:pointer;">Forwarded</button>
            <button onclick="filterTickets('active')" class="it-tab" data-filter="active" style="height:2rem; padding:0 0.75rem; background:#fff; color:#525252; border:1px solid #e0e0e0; font-size:0.75rem; font-weight:600; cursor:pointer;">Active</button>
            <button onclick="filterTickets('aging')" class="it-tab" data-filter="aging" style="height:2rem; padding:0 0.75rem; background:#fff; color:#525252; border:1px solid #e0e0e0; font-size:0.75rem; font-weight:600; cursor:pointer;">Aging</button>
            <button onclick="filterTickets('my_assigned')" class="it-tab" data-filter="my_assigned" style="height:2rem; padding:0 0.75rem; background:#fff; color:#525252; border:1px solid #e0e0e0; font-size:0.75rem; font-weight:600; cursor:pointer;">My Assigned</button>
            <button onclick="filterTickets('completed')" class="it-tab" data-filter="completed" style="height:2rem; padding:0 0.75rem; background:#fff; color:#525252; border:1px solid #e0e0e0; font-size:0.75rem; font-weight:600; cursor:pointer;">Completed</button>
          </div>
        </div>

        <div style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
              <tr style="background:#e0e0e0;">
                <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; font-family:'IBM Plex Sans',sans-serif;">Ticket / Customer</th>
                <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Service Request</th>
                <th style="padding:0.75rem 1rem; text-align:center; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Status</th>
                <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Technician</th>
                <th style="padding:0.75rem 1rem; text-align:right; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tickets as $t)
              @php
                $st = strtolower($t['status'] ?? 'pending');
                $stNorm = match($st) { 'pending','new' => 'new', 'assigned' => 'accepted', default => $st, };
                $pri = strtolower($t['priority'] ?? 'normal');
                $isAging = ($t['is_aging'] ?? false) || (($t['aging_hours'] ?? 0) >= 24);
                $isCritical = ($t['is_critical_overdue'] ?? false) || (($t['aging_hours'] ?? 0) >= 48);
              @endphp
              <tr class="it-row" data-status="{{ $stNorm }}" data-aging="{{ $isAging ? 'true' : 'false' }}" data-assigned-id="{{ $t['assigned_user_id'] ?? '' }}" data-assigned-name="{{ strtolower($t['assigned_to'] ?? '') }}" style="border-bottom:1px solid #e0e0e0; background:#fff;">
                <td style="padding:0.75rem 1rem; vertical-align:top;">
                  <a href="{{ route('it.tickets.show', $t['id']) }}" style="text-decoration:none; display:block;">
                    <div style="display:flex; align-items:center; gap:0.375rem;">
                      <span style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; font-weight:600; color:#0f62fe;">{{ $t['ticket_code'] ?? '#IT-'.$t['id'] }}</span>
                      @if($isCritical && !in_array($stNorm, ['resolved','closed','returned']))
                        <span style="background:#da1e28; color:#fff; font-size:0.625rem; padding:0.125rem 0.25rem; font-weight:600; letter-spacing:0.02em;">&gt;48h</span>
                      @elseif($isAging && !in_array($stNorm, ['resolved','closed','returned']))
                        <span style="background:#e0e0e0; color:#525252; font-size:0.625rem; padding:0.125rem 0.25rem; font-weight:600;">&gt;24h</span>
                      @endif
                    </div>
                    <div style="font-size:0.875rem; font-weight:600; color:#161616; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">{{ ($t['customer_type'] ?? 'individual') === 'company' ? '🏢 ' : '' }}{{ $t['visitor_name'] }}</div>
                    @if(!empty($t['contact_person']))
                      <div style="font-size:0.75rem; color:#525252;">{{ $t['contact_person'] }}</div>
                    @endif
                    <div style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; color:#525252; margin-top:0.125rem;">{{ $t['visitor_phone'] ?: '—' }}</div>
                  </a>
                </td>
                <td style="padding:0.75rem 1rem; vertical-align:top;">
                  <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $t['title'] }}</div>
                  <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ Str::limit($t['customer_statement'] ?? $t['description'] ?? '', 80) }}</div>
                  <div style="display:flex; align-items:center; gap:0.375rem; margin-top:0.5rem; flex-wrap:wrap;">
                    <span style="background:#e0e0e0; border:1px solid #c6c6c6; color:#393939; font-size:0.6875rem; padding:0.125rem 0.375rem; font-family:'IBM Plex Sans',sans-serif;">{{ $t['category'] }}</span>
                    @if(in_array($pri, ['urgent','high']))
                    <span style="background:{{ $pri==='urgent' ? '#da1e28' : '#e9730c' }}; color:#fff; font-size:0.6875rem; padding:0.125rem 0.375rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em;">{{ strtoupper($pri) }}</span>
                    @endif
                  </div>
                </td>
                <td style="padding:0.75rem 1rem; text-align:center; vertical-align:top;">
                  @php $stLabel = match($stNorm) { 'new' => 'New', 'accepted' => 'Accepted', 'in_progress' => 'In Progress', 'waiting' => 'Waiting', 'resolved' => 'Resolved', 'closed' => 'Closed', 'returned' => 'Returned', default => ucfirst($stNorm), }; @endphp
                  <span style="background:#e0e0e0; border:1px solid #c6c6c6; color:#393939; font-size:0.6875rem; padding:0.125rem 0.375rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; font-family:'IBM Plex Sans',sans-serif;">{{ $stLabel }}</span>
                  @if($t['resolved_at'])
                    <div style="font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; color:#525252; margin-top:0.25rem;">✓ {{ $t['resolved_at'] }}</div>
                  @endif
                </td>
                <td style="padding:0.75rem 1rem; vertical-align:top;">
                  @if($t['assigned_to'])
                    <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $t['assigned_to'] }}</div>
                  @else
                    <div style="font-size:0.875rem; color:#8d8d8d; font-style:italic;">Unassigned</div>
                  @endif
                  <div style="font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; color:#525252; margin-top:0.125rem;">{{ $t['created_at'] }}</div>
                </td>
                <td style="padding:0.75rem 1rem; text-align:right; vertical-align:top;">
                  <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.375rem; flex-wrap:wrap;">
                    <a href="{{ route('it.tickets.show', $t['id']) }}" style="height:2rem; padding:0 0.75rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">Open</a>
                    @if(in_array($stNorm, ['new','pending']))
                      <form method="POST" action="{{ route('it.tickets.accept', $t['id']) }}" style="display:inline;">
                        @csrf
                        <button type="submit" style="height:2rem; padding:0 0.75rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.75rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Accept</button>
                      </form>
                    @elseif(in_array($stNorm, ['accepted','in_progress','waiting']))
                      <button onclick="openQuickResolve({{ $t['id'] }}, '{{ addslashes($t['visitor_name']) }}', '{{ addslashes($t['title']) }}')" style="height:2rem; padding:0 0.75rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.75rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Resolve</button>
                    @elseif($stNorm === 'resolved' && $t['price'])
                      <span style="font-family:'IBM Plex Mono',monospace; font-size:0.75rem; font-weight:600; color:#161616; background:#e0e0e0; border:1px solid #c6c6c6; padding:0.25rem 0.5rem;">TZS {{ number_format($t['price']) }}</span>
                    @endif
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" style="padding:3rem; text-align:center; color:#525252;">
                  <div style="width:3rem; height:3rem; background:#f4f4f4; border:1px solid #e0e0e0; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem; color:#8d8d8d;">
                    <span class="material-symbols-outlined">inbox</span>
                  </div>
                  <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">No IT tickets found</div>
                  <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">Tickets forwarded from Reception will appear here.</div>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div style="padding:1rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">
          <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <span>Show</span>
            <select id="it-per-page-select" style="height:2rem; padding:0 0.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.75rem; font-weight:600;">
              <option value="5" selected>5</option>
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <span>per page</span>
            <span style="color:#e0e0e0;">|</span>
            <span>Showing <strong style="color:#161616; font-family:'IBM Plex Mono',monospace;" id="it-page-start">1</strong>–<strong style="color:#161616; font-family:'IBM Plex Mono',monospace;" id="it-page-end">5</strong> of <strong style="color:#161616; font-family:'IBM Plex Mono',monospace;" id="it-page-total">{{ count($tickets) }}</strong> tickets</span>
          </div>
          <div style="display:flex; align-items:center; gap:1rem;">
            <div style="display:flex; align-items:center; border:1px solid #8d8d8d; background:#fff;">
              <button type="button" id="it-btn-page-prev" style="height:2rem; padding:0 0.5rem; background:#fff; border:0; border-right:1px solid #e0e0e0; color:#525252; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span>
              </button>
              <div id="it-pagination-pages" style="display:flex; align-items:center; divide-x:1px solid #e0e0e0;"></div>
              <button type="button" id="it-btn-page-next" style="height:2rem; padding:0 0.5rem; background:#fff; border:0; border-left:1px solid #e0e0e0; color:#525252; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span>
              </button>
            </div>
            <a href="{{ route('it.tickets.export') }}" style="font-size:0.75rem; font-weight:600; color:#0f62fe; text-decoration:none; display:inline-flex; align-items:center; gap:0.25rem;">
              <span class="material-symbols-outlined" style="font-size:14px;">download</span> CSV
            </a>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:1rem;">
      <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0;">
        <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
          <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Task Activity</div>
          <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ $stats['tasks_completed'] }} completed</span>
        </div>
        <div style="max-height:20rem; overflow-y:auto;">
          @forelse(array_slice($tasks, 0, 8) as $tsk)
          <div style="padding:0.75rem 1rem; display:flex; align-items:flex-start; gap:0.75rem; border-bottom:1px solid #f4f4f4; background:#fff;">
            <span class="material-symbols-outlined" style="font-size:16px; margin-top:0.125rem; color:{{ $tsk['status'] === 'completed' ? '#0f62fe' : '#8d8d8d' }}; flex-shrink:0;">{{ $tsk['status'] === 'completed' ? 'check_circle' : 'radio_button_unchecked' }}</span>
            <div style="flex:1; min-width:0;">
              <div style="font-size:0.875rem; font-weight:600; color:#161616; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'IBM Plex Sans',sans-serif;">{{ $tsk['title'] }}</div>
              <div style="display:flex; align-items:center; justify-content:space-between; margin-top:0.125rem;">
                <span style="font-size:0.75rem; color:#525252;">{{ $tsk['assigned_to'] ?? 'ICT Staff' }}</span>
                <span style="font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; color:#8d8d8d;">{{ $tsk['created_at'] }}</span>
              </div>
            </div>
          </div>
          @empty
          <div style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">No tasks logged yet.</div>
          @endforelse
        </div>
      </div>

      <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0;">
        <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
          <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Service Invoices</div>
          <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;">{{ count($invoices) }} entries</span>
        </div>
        <div>
          @forelse(array_slice($invoices, 0, 6) as $inv)
          <div style="padding:0.75rem 1rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; border-bottom:1px solid #f4f4f4; background:#fff;">
            <div style="flex:1; min-width:0;">
              <div style="font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; font-weight:600; color:#525252;">#INV-{{ $inv['id'] }}</div>
              <div style="font-size:0.875rem; font-weight:600; color:#161616; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'IBM Plex Sans',sans-serif;">{{ $inv['customer_name'] }}</div>
              <div style="font-size:0.75rem; color:#525252; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $inv['service'] }}</div>
            </div>
            <div style="text-align:right; flex-shrink:0;">
              <div style="font-family:'IBM Plex Mono',monospace; font-size:0.875rem; font-weight:600; color:#161616;">TZS {{ number_format($inv['amount']) }}</div>
              <span style="background:{{ $inv['status'] === 'paid' ? '#defbe6' : '#fff8e1' }}; border:1px solid {{ $inv['status'] === 'paid' ? '#a7f0ba' : '#f1c21b' }}; color:{{ $inv['status'] === 'paid' ? '#044317' : '#684e00' }}; font-size:0.6875rem; padding:0.125rem 0.375rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; letter-spacing:0.02em;">{{ $inv['status'] === 'paid' ? 'Paid' : 'Pending' }}</span>
            </div>
          </div>
          @empty
          <div style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">Invoices auto-generate when IT resolves tickets.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
</div>

{{-- Quick Resolve Modal — IBM Carbon --}}
<div id="modal-resolve" style="position:fixed; inset:0; z-index:50; background:rgba(22,22,22,0.5); display:none; align-items:center; justify-content:center; padding:1rem;">
  <div style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:42rem; max-height:92vh; display:flex; flex-direction:column;">
    <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
      <div>
        <h3 style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Resolve & Close — Showroom Handoff</h3>
        <p id="resolve-ref" style="font-size:0.75rem; color:#525252; margin-top:0.125rem; font-family:'IBM Plex Sans',sans-serif;">—</p>
      </div>
      <button type="button" onclick="closeQuickResolve()" style="width:2rem; height:2rem; background:#fff; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;">
        <span class="material-symbols-outlined" style="font-size:16px;">close</span>
      </button>
    </div>
    <form id="resolve-form" method="POST" action="" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
      @csrf
      <div style="flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:1rem; background:#fff;">
        <div style="background:#f4f4f4; border:1px solid #e0e0e0; padding:0.75rem;">
          <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Task solved — ready for Sales</div>
          <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">Fill diagnosis, actions, and billing. Sales will see it in <span style="font-weight:600; color:#161616;">IT Repair Bills Queue</span>.</div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
          <div style="grid-column: span 2 / span 2;">
            <label style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Diagnosis <span style="color:#8d8d8d; font-weight:400;">(root cause)</span></label>
            <input type="text" name="diagnosis" placeholder="e.g. Failed HDD, overheating, corrupt OS" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">
          </div>
          <div style="grid-column: span 2 / span 2;">
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Action taken <span style="color:#8d8d8d; font-weight:400;">(steps performed)</span></label>
            <input type="text" name="action_taken" placeholder="e.g. Replaced thermal paste, clean OS install" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
          </div>
        </div>
        <div>
          <label style="font-size:0.75rem; font-weight:600; color:#525252;">Resolution summary <span style="color:#da1e28;">*</span></label>
          <textarea name="resolution_summary" required rows="2" placeholder="Briefly describe how the issue was resolved — customer-facing summary..." style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;"></textarea>
        </div>
        <div>
          <label style="font-size:0.75rem; font-weight:600; color:#525252;">Work completed <span style="color:#da1e28;">*</span></label>
          <textarea name="work_completed" required rows="2" placeholder="Specific technical work completed — for audit log..." style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; margin-top:0.25rem;"></textarea>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Service charge (TZS)</label>
            <div style="position:relative; margin-top:0.25rem;">
              <span style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); font-size:0.75rem; color:#525252; font-weight:600;">TZS</span>
              <input type="number" name="price" min="0" step="500" value="50000" style="width:100%; height:2rem; padding:0 0.75rem 0 2.5rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; font-weight:600; font-family:'IBM Plex Mono',monospace;">
            </div>
          </div>
          <div>
            <label style="font-size:0.75rem; font-weight:600; color:#525252;">Customer follow-up</label>
            <select name="customer_followup" style="width:100%; height:2rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#fff; font-size:0.875rem; margin-top:0.25rem;">
              <option value="none">No follow-up</option>
              <option value="monitor_pc">Customer monitors PC</option>
              <option value="contact_customer">Contact customer</option>
            </select>
          </div>
        </div>
        <input type="hidden" name="_ticket_id" id="resolve-ticket-id" value="" />
      </div>
      <div style="padding:1rem; border-top:1px solid #e0e0e0; background:#f4f4f4; display:flex; align-items:center; justify-content:space-between;">
        <a id="resolve-open-wo" href="#" style="font-size:0.75rem; font-weight:600; color:#0f62fe; text-decoration:none; display:inline-flex; align-items:center; gap:0.25rem;">
          <span class="material-symbols-outlined" style="font-size:14px;">open_in_new</span> Open Work Order
        </a>
        <div style="display:flex; gap:0.5rem;">
          <button type="button" onclick="closeQuickResolve()" style="height:2rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
          <button type="submit" style="height:2rem; padding:0 1rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Resolve & Bill to Sales</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openQuickResolve(id, name, title) {
    document.getElementById('resolve-form').action = `/it/tickets/${id}/resolve`;
    document.getElementById('resolve-ref').textContent = title + ' · ' + name;
    document.getElementById('resolve-open-wo').href = `/it/tickets/${id}`;
    const modal = document.getElementById('modal-resolve');
    modal.style.display = 'flex';
}
function closeQuickResolve() {
    const modal = document.getElementById('modal-resolve');
    modal.style.display = 'none';
}
const currentUserId   = {{ (int)auth()->id() }};
const currentUserName = "{{ strtolower(addslashes(auth()->user()?->name ?? '')) }}";
const urlParams = new URLSearchParams(window.location.search);
let currentFilter = urlParams.get('filter') || urlParams.get('status') || (urlParams.get('assigned') === 'me' ? 'my_assigned' : 'all');
let currentPage   = 1;
let pageSize      = 5;
const perPageSel  = document.getElementById('it-per-page-select');
const btnPrev     = document.getElementById('it-btn-page-prev');
const btnNext     = document.getElementById('it-btn-page-next');
const pagesBox    = document.getElementById('it-pagination-pages');
const pageStartEl = document.getElementById('it-page-start');
const pageEndEl   = document.getElementById('it-page-end');
const pageTotalEl = document.getElementById('it-page-total');
function isRowMatching(row, filter) {
    const rowStatus = row.getAttribute('data-status') || '';
    const isAging = row.getAttribute('data-aging') === 'true';
    const rowAssignedId = parseInt(row.getAttribute('data-assigned-id'), 10) || 0;
    const rowAssignedName = (row.getAttribute('data-assigned-name') || '').toLowerCase();
    if (filter === 'all') return true;
    if (filter === 'forwarded' || filter === 'new' || filter === 'pending') return ['new', 'pending'].includes(rowStatus);
    if (filter === 'active' || filter === 'in_progress' || filter === 'accepted') return ['accepted', 'assigned', 'in_progress', 'waiting'].includes(rowStatus);
    if (filter === 'aging') return isAging && !['resolved', 'closed', 'returned'].includes(rowStatus);
    if (filter === 'my_assigned' || filter === 'me') return rowAssignedId === currentUserId || (currentUserName && rowAssignedName.includes(currentUserName));
    if (filter === 'completed' || filter === 'resolved' || filter === 'closed') return ['resolved', 'closed'].includes(rowStatus);
    return rowStatus === filter;
}
function updatePagination() {
    const allRows = Array.from(document.querySelectorAll('.it-row'));
    const matchingRows = allRows.filter(row => isRowMatching(row, currentFilter));
    const total = matchingRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    const startIdx = (currentPage - 1) * pageSize;
    const endIdx   = Math.min(startIdx + pageSize, total);
    allRows.forEach(r => r.style.display = 'none');
    matchingRows.slice(startIdx, endIdx).forEach(r => r.style.display = '');
    if (pageStartEl) pageStartEl.textContent = total > 0 ? (startIdx + 1) : 0;
    if (pageEndEl)   pageEndEl.textContent   = endIdx;
    if (pageTotalEl) pageTotalEl.textContent = total;
    if (btnPrev) btnPrev.disabled = currentPage <= 1;
    if (btnNext) btnNext.disabled = currentPage >= totalPages || total === 0;
    if (pagesBox) {
        pagesBox.innerHTML = '';
        for (let p = 1; p <= totalPages; p++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = p;
            btn.style.cssText = (p === currentPage) ? 'padding:0 0.75rem; height:2rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-weight:600; cursor:pointer;' : 'padding:0 0.75rem; height:2rem; background:#fff; color:#525252; border:1px solid #e0e0e0; cursor:pointer;';
            btn.addEventListener('click', () => { currentPage = p; updatePagination(); });
            pagesBox.appendChild(btn);
        }
    }
}
function filterTickets(filterName) {
    currentFilter = filterName;
    currentPage = 1;
    document.querySelectorAll('.it-tab').forEach(tab => {
        const tabFilter = tab.getAttribute('data-filter') || tab.getAttribute('data-status');
        if (tabFilter === filterName) {
            tab.style.background = '#0f62fe'; tab.style.color = '#fff'; tab.style.borderColor = '#0f62fe';
        } else {
            tab.style.background = '#fff'; tab.style.color = '#525252'; tab.style.borderColor = '#e0e0e0';
        }
    });
    if (window.history && window.history.replaceState) {
        const url = new URL(window.location);
        if (filterName === 'all') { url.searchParams.delete('filter'); url.searchParams.delete('status'); url.searchParams.delete('assigned'); }
        else { url.searchParams.set('filter', filterName); url.searchParams.delete('status'); url.searchParams.delete('assigned'); }
        window.history.replaceState({}, '', url);
    }
    updatePagination();
}
perPageSel?.addEventListener('change', e => { pageSize = parseInt(e.target.value, 10) || 5; currentPage = 1; updatePagination(); });
btnPrev?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; updatePagination(); } });
btnNext?.addEventListener('click', () => { const allRows = Array.from(document.querySelectorAll('.it-row')); const matchingCount = allRows.filter(row => isRowMatching(row, currentFilter)).length; if (currentPage < Math.ceil(matchingCount / pageSize)) { currentPage++; updatePagination(); } });
document.addEventListener('DOMContentLoaded', () => { filterTickets(currentFilter); });
filterTickets(currentFilter);
const resolveModal = document.getElementById('modal-resolve');
if (resolveModal) { resolveModal.addEventListener('click', function(e) { if (e.target === this) { this.style.display = 'none'; } }); }
</script>
@endpush
@endsection
