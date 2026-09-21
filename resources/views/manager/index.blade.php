@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/carbon-components@11.42.0/css/carbon-components.min.css" rel="stylesheet">
<style>
  :root{ --ink:#161616; --muted:#525252; --border:#e0e0e0; }
  *{font-family:'IBM Plex Sans',system-ui,sans-serif}
  .mono{font-family:'IBM Plex Mono',monospace}
  [x-cloak]{display:none!important}
  .cds--tile,.cds--btn{border-radius:0!important}
</style>

<div class="min-h-screen bg-[#f4f4f4] -m-6">
  <div class="mx-auto max-w-[1480px] px-6 lg:px-8 py-6 lg:py-7">

    <div class="bg-white border border-[#e0e0e0] p-5 lg:p-6 mb-6 relative overflow-hidden border-t-[3px] border-t-[#0f62fe]">
      <nav class="flex flex-wrap items-center gap-2 text-xs text-[#525252]">
        <a href="{{ route('reception.dashboard') }}" class="hover:text-[#161616] hover:underline underline-offset-4">Home</a>
        <span class="text-[#8d8d8d]">/</span>
        <a href="{{ route('manager.index') }}" class="hover:text-[#161616] hover:underline underline-offset-4">Management</a>
        <span class="text-[#8d8d8d]">/</span>
        <span class="font-semibold text-[#161616]">Executive dashboard</span>
        <span class="mono ml-2 inline-flex items-center gap-1.5 border border-[#d0e2ff] bg-[#edf5ff] px-2 py-0.5 text-[11px] font-medium text-[#0f62fe]"><span class="h-1.5 w-1.5 rounded-full bg-[#0f62fe]"></span> Live</span>
      </nav>
      <div class="mt-4">
        <h1 class="text-[28px] font-semibold tracking-tight text-[#161616]" style="letter-spacing:-0.02em">Executive overview</h1>
        <p class="mt-1.5 max-w-[680px] text-sm leading-6 text-[#525252]">Daily operations at a glance — visitors, support, sales and team. Everything you need to review and act.</p>
      </div>
    </div>

    {{-- KPI — IBM consistent: identical treatment, neutral icon, semantic status only --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
      <div class="border border-[#e0e0e0] bg-white p-5 hover:shadow-sm transition-shadow">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold tracking-widest text-[#525252]">ACTIVE VISITORS</p>
          <span class="flex h-9 w-9 items-center justify-center border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252]"><span class="material-symbols-outlined text-[18px]">badge</span></span>
        </div>
        <p class="mono mt-4 text-[30px] font-semibold tracking-tight text-[#161616]">{{ $stats['active_visitors'] }}</p>
        <p class="mt-1 text-xs text-[#525252]"><span class="font-semibold text-[#161616]">{{ $stats['visitors_today'] }} today</span> · {{ $stats['long_stay_count'] }} long stay</p>
        <div class="mt-4 flex items-center gap-2"><span class="cds--tag cds--tag--blue" style="margin:0">Reception</span><span class="cds--tag cds--tag--cool-gray" style="margin:0">Live</span></div>
      </div>
      <div class="border border-[#e0e0e0] bg-white p-5 hover:shadow-sm transition-shadow">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold tracking-widest text-[#525252]">SUPPORT QUEUE</p>
          <span class="flex h-9 w-9 items-center justify-center border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252]"><span class="material-symbols-outlined text-[18px]">engineering</span></span>
        </div>
        <p class="mono mt-4 text-[30px] font-semibold tracking-tight text-[#161616]">{{ $stats['it_tickets_pending'] }} <span class="text-sm font-normal text-[#525252]">pending</span></p>
        <p class="mt-1 text-xs text-[#525252]">{{ $stats['sla_breach_count'] }} delayed · {{ $stats['it_tasks_completed'] }} resolved</p>
        <div class="mt-4 flex items-center gap-2">@if($stats['sla_breach_count']>0)<span class="cds--tag cds--tag--red" style="margin:0">{{ $stats['sla_breach_count'] }} delayed</span>@else<span class="cds--tag cds--tag--green" style="margin:0">On time</span>@endif<span class="cds--tag cds--tag--cool-gray" style="margin:0">Support</span></div>
      </div>
      <div class="border border-[#e0e0e0] bg-white p-5 hover:shadow-sm transition-shadow">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold tracking-widest text-[#525252]">REVENUE · PAID</p>
          <span class="flex h-9 w-9 items-center justify-center border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252]"><span class="material-symbols-outlined text-[18px]">account_balance_wallet</span></span>
        </div>
        <p class="mono mt-4 text-[22px] font-semibold tracking-tight text-[#161616]">TZS {{ number_format($stats['revenue_paid']) }}</p>
        <p class="mt-1 text-xs text-[#525252]">TZS {{ number_format($stats['revenue_pending']) }} pending</p>
        <div class="mt-4 flex items-center gap-2"><span class="cds--tag cds--tag--green" style="margin:0">Collected</span><span class="cds--tag cds--tag--warm-gray" style="margin:0">{{ $stats['invoices_pending_count'] ?? 0 }} due</span></div>
      </div>
      <div class="border border-[#e0e0e0] bg-white p-5 hover:shadow-sm transition-shadow">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold tracking-widest text-[#525252]">TEAM</p>
          <span class="flex h-9 w-9 items-center justify-center border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252]"><span class="material-symbols-outlined text-[18px]">corporate_fare</span></span>
        </div>
        <p class="mono mt-4 text-[30px] font-semibold tracking-tight text-[#161616]">{{ $stats['total_employees'] }} <span class="text-sm font-normal text-[#525252]">staff</span></p>
        <p class="mt-1 text-xs text-[#525252]">{{ $stats['total_departments'] }} teams · {{ $stats['overdue_tasks_count'] }} overdue</p>
        <div class="mt-4 flex items-center gap-2">@if($stats['overdue_tasks_count']>0)<span class="cds--tag cds--tag--red" style="margin:0">Needs attention</span>@else<span class="cds--tag cds--tag--green" style="margin:0">On track</span>@endif<span class="cds--tag cds--tag--cool-gray" style="margin:0">Team</span></div>
      </div>
    </div>

    {{-- Metrics row — color where it fits: channel tint + health status --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-6">
      <div class="cds--tile border border-[#e0e0e0] bg-white p-0 overflow-hidden lg:col-span-2" style="border-radius:0">
        <div class="flex items-center justify-between border-b border-[#e0e0e0] bg-[#f4f4f4] px-4 py-3">
          <div class="flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[14px]">payments</span></span>
            <h3 class="text-sm font-semibold tracking-tight text-[#161616]">Payment channels</h3>
          </div>
          <span class="cds--tag cds--tag--green mono text-[11px] bg-[#defbe6] border border-[#a7f0ba] text-[#0e6027] px-2 py-0.5">Updated</span>
        </div>
        <div class="cds--data-table-container p-5 bg-white">
          <div class="grid grid-cols-3 gap-4">
            <div class="cds--tile bg-[#f4f4f4] border border-[#e0e0e0] p-4" style="border-radius:0">
              <p class="text-[11px] font-semibold tracking-widest text-[#525252]">CASH</p>
              <p class="mono mt-2 text-[18px] font-semibold tracking-tight text-[#161616]">TZS {{ number_format($stats['cash_revenue']) }}</p>
              <div class="mt-3 h-1 bg-[#e0e0e0]"><div class="h-1 bg-[#8d8d8d]" style="width:58%"></div></div>
              <p class="mono mt-1.5 text-[11px] text-[#525252]">58% of total</p>
            </div>
            <div class="cds--tile bg-white border border-[#0f62fe] p-4 border-l-[3px] border-l-[#0f62fe]" style="border-radius:0">
              <p class="text-[11px] font-semibold tracking-widest text-[#0f62fe]">MOBILE MONEY</p>
              <p class="mono mt-2 text-[18px] font-semibold tracking-tight text-[#161616]">TZS {{ number_format($stats['mobile_revenue']) }}</p>
              <div class="mt-3 h-1 bg-[#d0e2ff]"><div class="h-1 bg-[#0f62fe]" style="width:72%"></div></div>
              <p class="mono mt-1.5 text-[11px] text-[#0f62fe]">72% · Primary channel</p>
            </div>
            <div class="cds--tile bg-[#f4f4f4] border border-[#e0e0e0] p-4" style="border-radius:0">
              <p class="text-[11px] font-semibold tracking-widest text-[#525252]">BANK / OTHER</p>
              <p class="mono mt-2 text-[18px] font-semibold tracking-tight text-[#161616]">TZS {{ number_format($stats['bank_revenue']) }}</p>
              <div class="mt-3 h-1 bg-[#e0e0e0]"><div class="h-1 bg-[#8d8d8d]" style="width:38%"></div></div>
              <p class="mono mt-1.5 text-[11px] text-[#525252]">38% of total</p>
            </div>
          </div>
        </div>
        <div class="mono flex justify-between border-t border-[#e0e0e0] bg-[#f4f4f4] px-4 py-2.5 text-[11px] text-[#525252]">
          <span>Total paid <strong class="text-[#0e6027]">TZS {{ number_format($stats['revenue_paid']) }}</strong></span>
          <span>Pending <strong class="text-[#da1e28]">TZS {{ number_format($stats['revenue_pending']) }}</strong></span>
        </div>
      </div>
      <div class="cds--tile border border-[#e0e0e0] bg-white p-0 overflow-hidden" style="border-radius:0">
        <div class="flex items-center justify-between border-b border-[#e0e0e0] bg-[#f4f4f4] px-4 py-3">
          <div class="flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center bg-[#da1e28] text-white"><span class="material-symbols-outlined text-[14px]">warning</span></span>
            <h3 class="text-sm font-semibold tracking-tight text-[#161616]">Need attention</h3>
            <span class="mono text-[11px] text-[#525252]">· {{ ($stats['sla_breach_count'] ?? 0) + ($stats['long_stay_count'] ?? 0) + ($stats['overdue_tasks_count'] ?? 0) + ($stats['low_stock_count'] ?? 0) }} issues</span>
          </div>
          <span class="h-1.5 w-1.5 rounded-full bg-[#da1e28]"></span>
        </div>
        <div class="p-0">
          @if($stats['sla_breach_count']>0)
          <div class="cds--inline-notification cds--inline-notification--low-contrast cds--inline-notification--error flex items-center gap-3 border-l-[3px] border-l-[#da1e28] bg-[#fff1f1] px-4 py-3" role="alert">
            <span class="material-symbols-outlined text-[18px] text-[#da1e28]">error</span>
            <div class="flex-1">
              <p class="text-sm font-semibold text-[#161616]">{{ $stats['sla_breach_count'] }} delayed requests</p>
              <p class="mono text-xs text-[#525252]">Exceeds 24h SLA · requires review</p>
            </div>
            <a href="{{ route('it.index') }}" class="cds--btn cds--btn--ghost text-xs font-semibold text-[#0f62fe] px-3 py-1.5 border border-[#0f62fe] hover:bg-[#edf5ff]">View</a>
          </div>
          @else
          <div class="flex items-center gap-2 border-b border-[#e0e0e0] bg-[#defbe6] px-4 py-3 text-sm text-[#0e6027]"><span class="material-symbols-outlined text-[16px]">check_circle</span> All support requests on time</div>
          @endif
          @if($stats['long_stay_count']>0)
          <div class="cds--inline-notification cds--inline-notification--low-contrast cds--inline-notification--warning flex items-center gap-3 border-l-[3px] border-l-[#f1c21b] bg-[#fff8e1] px-4 py-3">
            <span class="material-symbols-outlined text-[18px] text-[#8d3800]">schedule</span>
            <div class="flex-1">
              <p class="text-sm font-semibold text-[#161616]">{{ $stats['long_stay_count'] }} visitors &gt;4h</p>
              <p class="mono text-xs text-[#525252]">Long stay in lobby · check out</p>
            </div>
            <a href="{{ route('reception.visitors') }}" class="cds--btn cds--btn--ghost text-xs font-semibold text-[#0f62fe] px-3 py-1.5 border border-[#0f62fe] hover:bg-[#edf5ff]">Check out</a>
          </div>
          @endif
          @if($stats['overdue_tasks_count']>0)
          <div class="flex items-center gap-3 border-l-[3px] border-l-[#da1e28] bg-[#fff1f1] px-4 py-3 text-sm text-[#161616]"><span class="material-symbols-outlined text-[18px] text-[#da1e28]">assignment_late</span> {{ $stats['overdue_tasks_count'] }} tasks overdue</div>
          @endif
          @if($stats['low_stock_count']>0)
          <div class="flex items-center justify-between border-l-[3px] border-l-[#0f62fe] bg-[#edf5ff] px-4 py-3 text-sm">
            <div class="flex items-center gap-2 text-[#001d6c]"><span class="material-symbols-outlined text-[18px] text-[#0f62fe]">inventory_2</span> {{ $stats['low_stock_count'] }} items need restocking</div>
            <a href="{{ route('sales.inventory') }}" class="cds--btn cds--btn--primary bg-[#0f62fe] text-white px-3 py-1.5 text-xs font-semibold hover:bg-[#0353e9]">View</a>
          </div>
          @endif
          @if($stats['sla_breach_count']==0 && $stats['long_stay_count']==0 && $stats['overdue_tasks_count']==0 && $stats['low_stock_count']==0)
            <div class="mono border border-dashed border-[#e0e0e0] bg-[#f4f4f4] mx-4 my-4 px-3 py-8 text-center text-xs text-[#525252]">All clear — no issues at the moment</div>
          @endif
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <div class="space-y-6 lg:col-span-8">
        <div class="cds--tile border border-[#e0e0e0] bg-white p-0 overflow-hidden" style="border-radius:0">
          <div class="flex items-center justify-between border-b border-[#e0e0e0] bg-[#f4f4f4] px-4 py-3">
            <div class="flex items-center gap-2">
              <span class="flex h-6 w-6 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[14px]">meeting_room</span></span>
              <div>
                <h3 class="text-sm font-semibold tracking-tight text-[#161616]">Reception activity</h3>
                <p class="mono text-[11px] text-[#525252]">{{ $stats['visitors_today'] }} today · {{ $stats['active_visitors'] }} on-site</p>
              </div>
            </div>
            <a href="{{ route('reception.visitors') }}" class="cds--btn cds--btn--ghost border border-[#8d8d8d] bg-white px-3 py-1.5 text-xs font-semibold text-[#161616] hover:bg-[#f4f4f4]" style="border-radius:0">View all →</a>
          </div>
          <div class="cds--data-table-container overflow-x-auto">
            <table class="cds--data-table w-full">
              <thead class="bg-[#f4f4f4]">
                <tr class="text-[11px] tracking-widest text-[#525252]"><th class="px-4 py-2.5 font-semibold">Visitor</th><th class="px-4 py-2.5 font-semibold">Host / Department</th><th class="px-4 py-2.5 font-semibold">Purpose</th><th class="px-4 py-2.5 font-semibold">Time</th><th class="px-4 py-2.5 font-semibold text-right">Status</th></tr>
              </thead>
              <tbody class="divide-y divide-[#e0e0e0] bg-white">
                @forelse($recentVisits as $v)
                <tr class="hover:bg-[#f4f4f4]">
                  <td class="px-4 py-3"><div class="text-sm font-semibold text-[#161616]">{{ $v->visitor }}</div><div class="mono text-xs text-[#525252]">{{ $v->visitor_phone ?? 'No phone' }}</div></td>
                  <td class="px-4 py-3"><div class="text-sm font-medium text-[#161616]">{{ $v->employee?->full_name ?? 'General Reception' }}</div><div class="text-xs text-[#525252]">{{ $v->employee?->department?->name ?? 'Front Office' }}</div></td>
                  <td class="px-4 py-3 text-sm text-[#525252] max-w-[160px] truncate">{{ $v->purpose ?? 'General Visit' }}</td>
                  <td class="mono px-4 py-3 text-xs text-[#525252]">{{ $v->arrival ? \Carbon\Carbon::parse($v->arrival)->format('H:i') : $v->created_at->format('H:i') }}</td>
                  <td class="px-4 py-3 text-right">@if(is_null($v->departure))<span class="cds--tag cds--tag--green bg-[#defbe6] border border-[#a7f0ba] text-[#0e6027] px-2 py-0.5 text-[11px]"><span class="h-1.5 w-1.5 rounded-full bg-[#0e6027] inline-block mr-1"></span> In office</span>@else<span class="cds--tag cds--tag--cool-gray border border-[#e0e0e0] bg-white px-2 py-0.5 text-[11px] text-[#525252]">Departed</span>@endif</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-[#525252]">No visitors logged today yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mono flex justify-between border-t border-[#e0e0e0] bg-[#f4f4f4] px-4 py-2.5 text-[11px] text-[#525252]"><span>Showing {{ $recentVisits->count() }} most recent</span><span>{{ $stats['active_visitors'] }} on premises</span></div>
        </div>

        <div class="border border-[#e0e0e0] bg-white overflow-hidden">
          <div class="flex items-center justify-between border-b border-[#e0e0e0] bg-[#f4f4f4] px-5 py-4">
            <div>
              <h3 class="text-sm font-semibold text-[#161616]">Support requests</h3>
              <p class="mono mt-0.5 text-[11px] text-[#525252]">{{ $stats['it_tickets_pending'] }} pending · {{ $stats['it_tasks_completed'] }} resolved</p>
            </div>
            <a href="{{ route('it.index') }}" class="border border-[#8d8d8d] bg-white px-3 py-1.5 text-xs font-medium text-[#161616] hover:bg-[#e8e8e8]">View all →</a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="bg-[#f4f4f4] text-[11px] font-semibold tracking-widest text-[#525252]"><tr><th class="px-5 py-3 font-semibold">ID</th><th class="px-5 py-3 font-semibold">Customer</th><th class="px-5 py-3 font-semibold">Issue</th><th class="px-5 py-3 font-semibold">Officer</th><th class="px-5 py-3 font-semibold">Price</th><th class="px-5 py-3 text-right font-semibold">Status</th></tr></thead>
              <tbody class="divide-y divide-[#e0e0e0]">
                @forelse($recentTickets as $t)
                <tr class="hover:bg-[#f4f4f4]">
                  <td class="mono px-5 py-3.5 text-xs font-medium text-[#525252]">#{{ $t['id'] }}</td>
                  <td class="px-5 py-3.5"><div class="text-sm font-medium text-[#161616]">{{ $t['visitor_name'] }}</div><div class="text-xs text-[#525252]">{{ $t['visitor_company'] ?: 'Individual' }}</div></td>
                  <td class="px-5 py-3.5"><div class="max-w-[160px] truncate text-sm font-medium text-[#161616]">{{ $t['title'] }}</div><span class="mt-1 inline-flex border border-[#e0e0e0] bg-[#f4f4f4] px-2 py-0.5 text-[11px] font-medium text-[#525252]">{{ $t['category'] }}</span></td>
                  <td class="px-5 py-3.5 text-xs text-[#525252]">{{ $t['assigned_to'] ?? 'Unassigned' }}</td>
                  <td class="mono px-5 py-3.5 text-xs font-medium text-[#161616]">{{ $t['price'] ? 'TZS ' . number_format($t['price']) : '—' }}</td>
                  <td class="px-5 py-3.5 text-right">@if($t['status']==='resolved')<span class="rounded-full bg-[#defbe6] border border-[#a7f0ba] px-2.5 py-1 text-[11px] font-medium text-[#0e6027]">Resolved</span>@else<span class="border border-[#e0e0e0] bg-[#f4f4f4] px-2.5 py-1 text-[11px] font-medium text-[#525252]">{{ $t['status'] }}</span>@endif</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-[#525252]">No active tickets.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="border border-[#e0e0e0] bg-white p-5">
          <div class="flex items-center justify-between border-b border-[#e0e0e0] bg-[#f4f4f4] -m-5 mb-5 px-5 py-3">
            <h3 class="text-sm font-semibold text-[#161616]">Recent activity</h3>
            <span class="mono border border-[#e0e0e0] bg-white px-2.5 py-1 text-[11px] font-medium text-[#525252]">Recent</span>
          </div>
          <div class="relative pl-6">
            <div class="absolute bottom-2 left-[11px] top-2 w-px bg-[#e0e0e0]"></div>
            <div class="space-y-6">
              @forelse($recentTimelines as $tl)
              <div class="relative flex gap-3">
                <span class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252]"><span class="material-symbols-outlined text-[14px]">@if($tl->event_type==='check_in') person_add @elseif($tl->event_type==='completed') task_alt @else schedule @endif</span></span>
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2"><p class="text-sm font-semibold text-[#161616]">{{ $tl->title }}</p><span class="mono shrink-0 text-[11px] text-[#525252]">{{ $tl->created_at ? \Carbon\Carbon::parse($tl->created_at)->format('H:i') : now()->format('H:i') }}</span></div>
                  <p class="mt-0.5 text-[13px] leading-5 text-[#525252]">{{ $tl->description }}</p>
                </div>
              </div>
              @empty
              <p class="py-6 text-center text-sm text-[#525252]">No recent activity logged.</p>
              @endforelse
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6 lg:col-span-4">
        <div class="cds--tile border border-[#e0e0e0] bg-white p-0 overflow-hidden" style="border-radius:0">
          <div class="flex items-center justify-between border-b border-[#e0e0e0] bg-[#f4f4f4] px-4 py-3">
            <div class="flex items-center gap-2">
              <span class="flex h-6 w-6 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[14px]">receipt_long</span></span>
              <h3 class="text-sm font-semibold tracking-tight text-[#161616]">Sales & collections</h3>
            </div>
            <span class="cds--tag cds--tag--green mono text-[11px] bg-[#defbe6] border border-[#a7f0ba] text-[#0e6027] px-2 py-0.5">Updated</span>
          </div>
          <div class="cds--data-table-container p-0">
            <table class="cds--data-table w-full">
              <thead class="bg-[#f4f4f4]">
                <tr class="text-[11px] tracking-widest text-[#525252]"><th class="px-4 py-2 font-semibold">Invoice</th><th class="px-4 py-2 font-semibold text-right">Amount</th><th class="px-4 py-2 font-semibold text-right">Status</th></tr>
              </thead>
              <tbody class="divide-y divide-[#e0e0e0] bg-white">
                @forelse($recentInvoices as $inv)
                @php $isPaid = (is_object($inv) ? $inv->status : ($inv['status'] ?? '')) === 'paid'; @endphp
                <tr class="hover:bg-[#f4f4f4]">
                  <td class="px-4 py-3">
                    <p class="truncate text-sm font-semibold text-[#161616]">{{ is_object($inv) ? ($inv->receipt_number ?? ('#ORD-'.$inv->id)) : ($inv['receipt_number'] ?? ('#INV-'.$inv['id'])) }}</p>
                    <p class="truncate text-xs text-[#525252]">{{ is_object($inv) ? ($inv->customer_name ?: 'Walk-in') : $inv['customer_name'] }} · <span class="mono text-[11px]">{{ is_object($inv) ? ($inv->service ?: 'POS Items') : $inv['service'] }}</span></p>
                  </td>
                  <td class="px-4 py-3 text-right"><p class="mono text-sm font-semibold text-[#161616]">TZS {{ number_format(is_object($inv) ? $inv->amount : $inv['amount']) }}</p></td>
                  <td class="px-4 py-3 text-right">@if($isPaid)<span class="cds--tag cds--tag--green bg-[#defbe6] border border-[#a7f0ba] text-[#0e6027] px-2 py-0.5 text-[11px]">Paid</span>@else<span class="cds--tag cds--tag--cool-gray border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252] px-2 py-0.5 text-[11px]">Pending</span>@endif</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-[#525252]">No invoices generated yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mono flex justify-between border-y border-[#e0e0e0] bg-[#f4f4f4] px-4 py-2.5 text-[11px] text-[#525252]">
            <span>Pending <strong class="text-[#da1e28]">TZS {{ number_format($stats['revenue_pending']) }}</strong></span>
            <span>Collected <strong class="text-[#0e6027]">TZS {{ number_format($stats['revenue_paid']) }}</strong></span>
          </div>
          <div class="p-3 bg-white"><a href="{{ route('sales.index') }}" class="cds--btn cds--btn--primary flex h-9 w-full items-center justify-center bg-[#0f62fe] text-sm font-semibold text-white hover:bg-[#0353e9]" style="border-radius:0">View Sales portal →</a></div>
        </div>

        <div class="border border-[#e0e0e0] bg-white overflow-hidden">
          <div class="border-b border-[#e0e0e0] bg-[#f4f4f4] px-5 py-3.5 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-[#161616]">Low stock</h3>
            <span class="inline-flex items-center gap-1 rounded-full bg-[#fff1f1] border border-[#ffd7d9] px-2.5 py-1 text-[11px] font-medium text-[#da1e28]"><span class="h-1.5 w-1.5 rounded-full bg-[#da1e28]"></span> {{ $stats['low_stock_count'] }} items</span>
          </div>
          <div class="p-5 space-y-2.5">
            @forelse($lowStockProducts as $p)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-[#ffd7d9] bg-[#fff1f1] px-3 py-3">
              <div class="min-w-0"><p class="truncate text-sm font-semibold text-[#161616]">{{ $p->name }} <span class="mono text-xs font-normal text-[#525252]">{{ $p->sku }}</span></p><p class="mono text-xs text-[#525252]">Stock <span class="font-semibold text-[#da1e28]">{{ $p->stock_quantity }}</span> / Min {{ $p->min_stock_alert ?? 5 }}</p></div>
              <a href="{{ route('sales.inventory') }}" class="shrink-0 bg-[#da1e28] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#b81921]">Restock</a>
            </div>
            @empty
            <div class="rounded-lg border border-dashed border-[#e0e0e0] bg-[#f4f4f4] px-3 py-6 text-center text-sm text-[#525252]">All items sufficiently stocked</div>
            @endforelse
          </div>
        </div>

      </div>
    </div>

  </div>
</div>
@endsection
