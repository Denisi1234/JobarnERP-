@extends('reception.layout')

@section('content')
<style>
 [x-cloak] { display: none !important; }
 .reception-portal { max-width: 1480px; margin-inline: auto; }
 .reception-portal * { border-radius: 0 !important; }
 .reception-card { background: #ffffff; border: 1px solid #e0e0e0; transition: box-shadow 150ms; }
 .reception-card:hover { box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
 .reception-card table thead { background: #f4f4f4; border-bottom: 1px solid #e0e0e0; }
 .reception-card table th { color: #525252 !important; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; font-weight: 600; font-family: 'IBM Plex Sans', sans-serif; }
</style>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<div class="reception-portal flex flex-col w-full">

 {{-- ───────────────── HEADER — polished IBM ───────────────── --}}
 <div class="bg-white border border-[#e0e0e0] border-t-[3px] border-t-[#0f62fe] p-5 lg:p-6 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
 <div>
 <div class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Reception Desk — Front Office</div>
 <h1 class="mt-1 text-[26px] font-semibold tracking-tight text-[#161616]" style="letter-spacing:-0.02em">Reception Overview</h1>
 <p class="mt-1 text-xs text-[#525252] font-mono">{{ now()->format('l, d F Y') }} · Live</p>
 </div>
 <div class="flex items-center gap-2">
 <button type="button" onclick="openCreateTicketModal()"
 class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#0f62fe] hover:bg-[#0353e9] text-white text-xs font-semibold border border-[#0f62fe] cursor-pointer">
 <span class="material-symbols-outlined text-[16px]">person_add</span>
 <span>Add Customer</span>
 </button>
 <a href="{{ route('reception.tasks') }}"
 class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-white hover:bg-[#f4f4f4] text-[#161616] text-xs font-semibold border border-[#8d8d8d]">
 <span class="material-symbols-outlined text-[16px]">checklist</span>
 <span>New Task</span>
 </a>
 </div>
 </div>

 {{-- ───────────────── 4 METRIC CARDS — IBM consistent polished ───────────────── --}}
 <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between border-l-[3px] border-l-[#0f62fe]">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Active On-Site</span>
 <span class="w-8 h-8 bg-[#edf5ff] border border-[#d0e2ff] flex items-center justify-center text-[#0f62fe]"><span class="material-symbols-outlined text-[16px]">badge</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace">{{ $onPremisesCount }}</div>
 <div class="text-xs text-[#525252] mt-1">Currently on premises</div>
 </div>
 </div>
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Expected Today</span>
 <span class="w-8 h-8 bg-[#f4f4f4] border border-[#e0e0e0] flex items-center justify-center text-[#525252]"><span class="material-symbols-outlined text-[16px]">group</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace">{{ $todayCount }}</div>
 <div class="text-xs text-[#525252] mt-1">Total arrivals today</div>
 </div>
 </div>
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Open IT Requests</span>
 <span class="w-8 h-8 bg-[#f4f4f4] border border-[#e0e0e0] flex items-center justify-center text-[#525252]"><span class="material-symbols-outlined text-[16px]">computer</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace">{{ $itPendingCount }}</div>
 <div class="text-xs text-[#525252] mt-1">Pending & assigned tickets</div>
 </div>
 </div>
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Checked Out Today</span>
 <span class="w-8 h-8 bg-[#f4f4f4] border border-[#e0e0e0] flex items-center justify-center text-[#525252]"><span class="material-symbols-outlined text-[16px]">logout</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace">{{ $checkoutReadyCount }}</div>
 <div class="text-xs text-[#525252] mt-1">Departures recorded</div>
 </div>
 </div>
 </div>

 {{-- ───────────────── MAIN GRID (LEFT 8 / RIGHT 4) ───────────────── --}}
 <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

 {{-- ═══ LEFT PANEL — Active Visits Table ═══ --}}
 <div class="lg:col-span-8 flex flex-col gap-6">
 <div class="reception-card overflow-hidden">

 {{-- Panel header — IBM --}}
 <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#e0e0e0] bg-[#f4f4f4]">
 <div class="flex items-center gap-2">
 <span class="flex h-6 w-6 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[14px]">people</span></span>
 <h2 class="text-sm font-semibold tracking-tight text-[#161616]">Active Visits</h2>
 <span class="ml-1 mono border border-[#d0e2ff] bg-[#edf5ff] px-2 py-0.5 text-[11px] font-semibold text-[#0f62fe]">
 {{ $onPremisesCount }}
 </span>
 </div>
 <a href="{{ route('reception.visitors') }}"
 class="text-xs font-semibold text-[#0f62fe] hover:underline flex items-center gap-1 border border-[#8d8d8d] bg-white px-3 py-1.5 hover:bg-[#f4f4f4]">
 View all
 <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
 </a>
 </div>

 {{-- Table --}}
 <div class="overflow-x-auto">
 <table class="w-full text-sm">
 <thead>
 <tr class="bg-[#f4f4f4] border-b border-[#e0e0e0]">
 <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-widest text-[#525252]">Customer</th>
 <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-widest text-[#525252]">Purpose</th>
 <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-widest text-[#525252]">Host</th>
 <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-widest text-[#525252]">Arrived</th>
 <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-widest text-[#525252]">Status</th>
 <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-widest text-[#525252]">Action</th>
 </tr>
 </thead>
 <tbody id="active-visits-tbody" class="divide-y divide-[#e0e0e0]">
 @forelse($activeVisits as $visit)
 @php
 $isInsideRow = empty($visit->departure);
 $hasItRow = $visit->itTickets && $visit->itTickets->count() > 0;
 $deptKeyRow = $hasItRow ? 'it' : 'sales';
 $badgeRow = $visit->uuid ?? '';
 $dateRow = $visit->arrival ? $visit->arrival->format('d M Y') : 'Today';
 $checkinRow = $visit->arrival ? $visit->arrival->format('H:i') : '—';
 $checkoutRow = $visit->departure ? $visit->departure->format('H:i') : '—';
 @endphp
 <tr class="hover:bg-[#f4f4f4] transition-colors cursor-pointer visit-row"
 data-id="{{ $visit->uuid }}"
 data-customer="{{ $visit->visitor }}"
 data-phone="{{ $visit->visitor_phone ?? '' }}"
 data-badge="{{ $badgeRow }}"
 data-dept="{{ $deptKeyRow }}"
 data-host="{{ $visit->employee->name ?? 'Front Desk Staff' }}"
 data-date="{{ $dateRow }}"
 data-checkin="{{ $checkinRow }}"
 data-checkout="{{ $checkoutRow }}"
 data-purpose="{{ $visit->purpose ?? '—' }}"
 data-presence="{{ $isInsideRow ? 'inside' : 'departed' }}"
 data-status="{{ $visit->status ?? 'Waiting' }}"
 data-search="{{ strtolower($visit->visitor . ' ' . ($visit->visitor_phone ?? '') . ' ' . ($visit->purpose ?? '')) }}">
 <td class="px-4 py-3">
 <div class="flex items-center gap-3">
 <span class="flex items-center justify-center w-8 h-8 bg-white border border-[#e0e0e0] text-[#525252] font-semibold shrink-0 text-xs">
 {{ strtoupper(substr($visit->visitor, 0, 1)) }}
 </span>
 <div>
 <p class="text-xs font-semibold text-[#161616]">{{ $visit->visitor }}</p>
 <p class="text-[11px] text-[#525252]" style="font-family:'IBM Plex Mono',monospace">#{{ strtoupper(substr($visit->uuid, 0, 8)) }}</p>
 </div>
 </div>
 </td>
 <td class="px-4 py-3">
 <span class="text-xs text-[#525252]">{{ $visit->purpose ?? '—' }}</span>
 </td>
 <td class="px-4 py-3">
 <span class="text-xs text-[#525252]">{{ $visit->employee->name ?? '—' }}</span>
 </td>
 <td class="px-4 py-3">
 <span class="text-xs text-[#525252]" style="font-family:'IBM Plex Mono',monospace">
 {{ $visit->arrival ? $visit->arrival->format('H:i') : '—' }}
 </span>
 </td>
 <td class="px-4 py-3">
 @php
 $status = $visit->status ?? 'Waiting';
 $dotClass = match($status) {
 'In service' => 'bg-[#0f62fe]',
 'Ready for checkout' => 'bg-[#8a3800]',
 'Waiting' => 'bg-[#8d8d8d]',
 default => 'bg-[#8d8d8d]',
 };
 $textClass = match($status) {
 'In service' => 'text-[#0f62fe]',
 'Ready for checkout' => 'text-[#8a3800]',
 'Waiting' => 'text-[#525252]',
 default => 'text-[#525252]',
 };
 @endphp
 <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $textClass }}">
 <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
 {{ $status }}
 </span>
 </td>
 <td class="px-4 py-3">
 <button type="button"
 onclick="openVisitDrawer(this.closest('tr'))"
 class="inline-flex items-center gap-1 px-3 py-1.5 bg-white hover:bg-[#f4f4f4] text-[#161616] text-xs font-semibold border border-[#8d8d8d]">
 <span class="material-symbols-outlined text-[14px]">open_in_new</span>
 View
 </button>
 </td>
 </tr>
 @empty
 <tr id="empty-visits-row">
 <td colspan="6" class="px-4 py-10 text-center text-[#525252] text-sm">
 <span class="material-symbols-outlined text-[28px] block mb-2 text-[#8d8d8d]">people</span>
 No active visitors on premises
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
 </div>

 {{-- ═══ RIGHT PANEL ═══ --}}
 <div class="lg:col-span-4 flex flex-col gap-6">

 {{-- Quick Links — polished IBM tile --}}
 <div class="reception-card p-0 overflow-hidden">
 <div class="flex items-center gap-2 px-5 py-3.5 border-b border-[#e0e0e0] bg-[#f4f4f4]">
 <span class="flex h-6 w-6 items-center justify-center bg-[#0f62fe] text-white"><span class="material-symbols-outlined text-[14px]">bolt</span></span>
 <h2 class="text-sm font-semibold tracking-tight text-[#161616]">Quick Links</h2>
 </div>
 <div class="flex flex-col p-2">
 <a href="{{ route('reception.visitors') }}"
 class="flex items-center gap-3 px-3 py-2.5 hover:bg-[#f4f4f4] border border-transparent hover:border-[#e0e0e0] transition-colors group">
 <span class="flex h-7 w-7 items-center justify-center bg-white border border-[#e0e0e0] text-[#525252]"><span class="material-symbols-outlined text-[16px]">person_pin</span></span>
 <span class="text-xs font-semibold text-[#161616]">Visitors Register</span>
 <span class="material-symbols-outlined text-[16px] text-[#8d8d8d] ml-auto group-hover:text-[#161616]">chevron_right</span>
 </a>
 <a href="{{ route('reception.tasks') }}"
 class="flex items-center gap-3 px-3 py-2.5 hover:bg-[#f4f4f4] border border-transparent hover:border-[#e0e0e0] transition-colors group">
 <span class="flex h-7 w-7 items-center justify-center bg-white border border-[#e0e0e0] text-[#525252]"><span class="material-symbols-outlined text-[16px]">checklist</span></span>
 <span class="flex-1 text-xs font-semibold text-[#161616]">Tasks</span>
 <span class="material-symbols-outlined text-[16px] text-[#8d8d8d] group-hover:text-[#161616]">chevron_right</span>
 </a>
 <a href="{{ route('reception.deliveries') }}"
 class="flex items-center gap-3 px-3 py-2.5 hover:bg-[#f4f4f4] border border-transparent hover:border-[#e0e0e0] transition-colors group">
 <span class="flex h-7 w-7 items-center justify-center bg-white border border-[#e0e0e0] text-[#525252]"><span class="material-symbols-outlined text-[16px]">local_shipping</span></span>
 <span class="text-xs font-semibold text-[#161616]">Deliveries</span>
 <span class="material-symbols-outlined text-[16px] text-[#8d8d8d] ml-auto group-hover:text-[#161616]">chevron_right</span>
 </a>
 <a href="{{ route('reception.directory') }}"
 class="flex items-center gap-3 px-3 py-2.5 hover:bg-[#f4f4f4] border border-transparent hover:border-[#e0e0e0] transition-colors group">
 <span class="flex h-7 w-7 items-center justify-center bg-white border border-[#e0e0e0] text-[#525252]"><span class="material-symbols-outlined text-[16px]">contacts</span></span>
 <span class="text-xs font-semibold text-[#161616]">Directory</span>
 <span class="material-symbols-outlined text-[16px] text-[#8d8d8d] ml-auto group-hover:text-[#161616]">chevron_right</span>
 </a>
 </div>
 </div>

 {{-- Recent Activity — polished IBM --}}
 <div class="reception-card p-0 overflow-hidden">
 <div class="flex items-center gap-2 px-5 py-3.5 border-b border-[#e0e0e0] bg-[#f4f4f4]">
 <span class="flex h-6 w-6 items-center justify-center bg-[#525252] text-white"><span class="material-symbols-outlined text-[14px]">history</span></span>
 <h2 class="text-sm font-semibold tracking-tight text-[#161616]">Recent Activity</h2>
 <span class="ml-auto mono text-[11px] text-[#525252] border border-[#e0e0e0] bg-white px-2 py-0.5">{{ $recentVisits->count() }} today</span>
 </div>
 <div class="flex flex-col p-2">
 @forelse($recentVisits as $v)
 <div class="flex items-start gap-3 px-3 py-2.5 hover:bg-[#f4f4f4] border border-transparent hover:border-[#e0e0e0] transition-colors">
 <span class="flex items-center justify-center w-7 h-7 bg-white border border-[#e0e0e0] text-[#525252] font-semibold shrink-0 text-xs">
 {{ strtoupper(substr($v->visitor, 0, 1)) }}
 </span>
 <div class="flex-1 min-w-0">
 <p class="text-xs font-semibold text-[#161616] truncate">{{ $v->visitor }}</p>
 <p class="text-[11px] text-[#525252] truncate">
 {{ $v->departure ? 'Checked out' : 'Checked in' }}
 @if($v->employee) · {{ $v->employee->name }} @endif
 </p>
 </div>
 <span class="font-mono text-[#525252] shrink-0 text-[11px] border border-[#e0e0e0] bg-white px-1.5 py-0.5">
 {{ $v->arrival ? $v->arrival->format('H:i') : '' }}
 </span>
 </div>
 @empty
 <p class="text-sm text-[#525252] text-center py-6">No recent activity</p>
 @endforelse
 </div>
 </div>

 </div>{{-- end right panel --}}
 </div>{{-- end grid --}}
</div>

{{-- ═══════════════════════════════════════════════════════════════
 VISIT DRAWER (slide-over) — polished
════════════════════════════════════════════════════════════════ --}}
<div id="visit-drawer-overlay"
 class="hidden fixed inset-0 bg-black/30 backdrop-blur-sm z-[90]"
 onclick="closeVisitDrawer()"></div>

<aside id="visit-drawer"
 class="fixed right-0 top-0 bottom-0 w-full max-w-[460px] bg-white border-l border-[#e0e0e0] z-[100] transform translate-x-full transition-transform duration-250 flex flex-col shadow-2xl">
 {{-- Head — IBM --}}
 <div class="flex items-center justify-between px-5 py-4 border-b border-[#e0e0e0] bg-[#f4f4f4]">
 <div class="flex items-center gap-3 min-w-0">
 <span id="drawer-avatar"
 class="flex items-center justify-center w-11 h-11 bg-[#0f62fe] text-white font-semibold text-xs shrink-0">
 CU
 </span>
 <div class="min-w-0">
 <div class="flex items-center gap-2 flex-wrap">
 <h2 id="drawer-name" class="text-sm font-semibold text-[#161616] truncate">Customer</h2>
 <span id="drawer-presence-badge"
 class="inline-flex items-center gap-1 px-2 py-0.5 border text-[11px] font-semibold bg-[#defbe6] border-[#a7f0ba] text-[#0e6027]">
 <span class="w-1.5 h-1.5 bg-[#0e6027] rounded-full"></span>
 <span id="drawer-presence-text">Inside</span>
 </span>
 </div>
 <p class="text-[11px] text-[#525252] truncate" style="font-family:'IBM Plex Mono',monospace" id="drawer-phone-top">—</p>
 </div>
 </div>
 <button type="button" onclick="closeVisitDrawer()"
 class="flex items-center justify-center w-8 h-8 hover:bg-white text-[#525252] hover:text-[#161616] border border-transparent hover:border-[#e0e0e0] transition-colors shrink-0">
 <span class="material-symbols-outlined text-[20px]">close</span>
 </button>
 </div>
 {{-- Body — Visit Information + Timeline + Billing --}}
 <div class="flex-1 overflow-y-auto">
 <div class="px-5 pt-5 pb-4 border-b border-[#e0e0e0]">
 <p class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase mb-3">Visit Information</p>
 <div class="grid grid-cols-2 gap-x-4 gap-y-3">
 <div>
 <p class="text-[11px] font-semibold tracking-widest text-[#8d8d8d] uppercase mb-0.5">Phone</p>
 <p id="drawer-phone" class="text-xs font-semibold text-[#161616]" style="font-family:'IBM Plex Mono',monospace">—</p>
 </div>
 <div>
 <p class="text-[11px] font-semibold tracking-widest text-[#8d8d8d] uppercase mb-0.5">Visit ID</p>
 <p id="drawer-visit-id" class="text-[11px] text-[#525252]" style="font-family:'IBM Plex Mono',monospace">—</p>
 </div>
 <div>
 <p class="text-[11px] font-semibold tracking-widest text-[#8d8d8d] uppercase mb-0.5">Department</p>
 <p id="drawer-dept" class="text-xs font-semibold text-[#161616]">—</p>
 </div>
 <div>
 <p class="text-[11px] font-semibold tracking-widest text-[#8d8d8d] uppercase mb-0.5">Host / Staff</p>
 <p id="drawer-host" class="text-xs font-semibold text-[#161616]">—</p>
 </div>
 <div>
 <p class="text-[11px] font-semibold tracking-widest text-[#8d8d8d] uppercase mb-0.5">Date</p>
 <p id="drawer-date" class="text-xs text-[#161616]" style="font-family:'IBM Plex Mono',monospace">—</p>
 </div>
 <div>
 <p class="text-[11px] font-semibold tracking-widest text-[#8d8d8d] uppercase mb-0.5">Check-in Time</p>
 <p id="drawer-checkin" class="text-xs text-[#161616]" style="font-family:'IBM Plex Mono',monospace">—</p>
 </div>
 </div>
 <div class="mt-4 bg-[#f4f4f4] border border-[#e0e0e0] px-3 py-2">
 <p class="text-[11px] font-semibold tracking-widest text-[#8d8d8d] uppercase mb-1">Purpose</p>
 <p id="drawer-purpose" class="text-xs text-[#525252] leading-relaxed">—</p>
 </div>
 </div>
 <div class="px-5 py-4 border-b border-[#e0e0e0]">
 <p class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase mb-3">Timeline</p>
 <div class="flex flex-col gap-0" id="drawer-timeline"></div>
 </div>
 <div class="px-5 py-4">
 <div class="flex items-center justify-between">
 <p class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Service Billing</p>
 <span id="drawer-payment-badge"
 class="inline-flex items-center px-2.5 py-1 border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252] text-[11px] font-semibold">
 No Charge / Pending
 </span>
 </div>
 </div>
 </div>
 {{-- Footer --}}
 <div class="px-5 py-4 border-t border-[#e0e0e0] bg-[#f4f4f4] flex gap-2">
 <a id="drawer-full-link" href="{{ route('reception.visitors') }}"
 class="flex-1 flex items-center justify-center gap-1 py-2 bg-white hover:bg-white text-[#161616] text-xs font-semibold border border-[#8d8d8d] transition-colors">
 <span class="material-symbols-outlined text-[16px]">open_in_new</span>
 All Visitors
 </a>
 <button type="button" onclick="closeVisitDrawer()"
 class="flex-1 flex items-center justify-center gap-1 py-2 bg-[#0f62fe] hover:bg-[#0353e9] text-white text-xs font-semibold border border-[#0f62fe] transition-colors">
 <span class="material-symbols-outlined text-[16px]">close</span>
 Close
 </button>
 </div>
</aside>

{{-- ═══ TOAST ═══ --}}
<div id="toast"
 class="fixed bottom-6 right-6 z-[60] flex items-center gap-3 px-4 py-3 bg-[#161616] text-white text-sm font-medium opacity-0 translate-y-4 pointer-events-none transition-all duration-300 border border-[#393939]">
 <span id="toast-icon" class="material-symbols-outlined text-[18px]">check_circle</span>
 <span id="toast-msg">Done</span>
</div>

<script>
// ─── Drawer — unified with register (full details) ─────────────────
function openVisitDrawer(row) {
 const d = row.dataset;
 document.getElementById('drawer-avatar').textContent = (d.customer || d.name || 'CU').substring(0,2).toUpperCase();
 document.getElementById('drawer-name').textContent = d.customer || d.name || 'Customer';
 document.getElementById('drawer-phone-top').textContent = d.phone || '—';
 document.getElementById('drawer-phone').textContent = d.phone || '—';
 document.getElementById('drawer-visit-id').textContent = d.badge ? d.badge.toUpperCase().slice(0,8) : (d.id ? d.id.toUpperCase().slice(0,8) : '—');
 document.getElementById('drawer-dept').textContent = d.dept ? d.dept.toUpperCase() : 'SALES';
 document.getElementById('drawer-host').textContent = d.host || 'Front Desk Staff';
 document.getElementById('drawer-date').textContent = d.date || '—';
 document.getElementById('drawer-checkin').textContent = d.checkin || d.arrived || '—';
 document.getElementById('drawer-purpose').textContent = d.purpose || '—';
 const badge = document.getElementById('drawer-presence-badge');
 const badgeText = document.getElementById('drawer-presence-text');
 const isInside = (d.presence || d.status || '').toLowerCase().includes('inside') || (d.status||'').toLowerCase()==='waiting';
 if(badge && badgeText){
 badgeText.textContent = isInside ? 'Inside' : (d.presence==='departed'?'Departed': d.status||'Waiting');
 badge.className = 'inline-flex items-center gap-1 px-2 py-0.5 border text-[11px] font-semibold ' + (isInside ? 'bg-[#defbe6] border-[#a7f0ba] text-[#0e6027]' : 'bg-white border-[#e0e0e0] text-[#525252]');
 }
 const tl = document.getElementById('drawer-timeline');
 if(tl){
 tl.innerHTML = '<div class=\'flex gap-3 py-2\'><span class=\'w-2 h-2 bg-[#0f62fe] mt-1.5 shrink-0\'></span><div><div class=\'text-xs font-semibold text-[#161616]\'>Checked in at Front Desk</div><div class=\'text-[11px] text-[#525252]\'>'+(d.checkin||d.arrived||'—')+' · '+ (d.host||'') +'</div></div></div>' + (d.dept==='it' ? '<div class=\'flex gap-3 py-2 border-t border-[#e0e0e0]\'><span class=\'w-2 h-2 bg-white border border-[#8d8d8d] mt-1.5 shrink-0\'></span><div><div class=\'text-xs font-semibold text-[#161616]\'>Routed to IT Support</div><div class=\'text-[11px] text-[#525252]\'>—</div></div></div>' : '');
 }
 const payBadge = document.getElementById('drawer-payment-badge');
 if(payBadge) payBadge.textContent = isInside ? 'No Charge / Pending' : 'Completed';
 document.getElementById('visit-drawer-overlay').classList.remove('hidden');
 requestAnimationFrame(() => {
 document.getElementById('visit-drawer').classList.remove('translate-x-full');
 });
}
function closeVisitDrawer() {
 document.getElementById('visit-drawer').classList.add('translate-x-full');
 setTimeout(() => {
 document.getElementById('visit-drawer-overlay').classList.add('hidden');
 }, 300);
}

// ─── Toast ────────────────────────────────────────────────────
function showToast(msg, icon = 'check_circle') {
 const t = document.getElementById('toast');
 document.getElementById('toast-msg').textContent = msg;
 document.getElementById('toast-icon').textContent = icon;
 t.classList.remove('opacity-0','translate-y-4','pointer-events-none');
 setTimeout(() => {
 t.classList.add('opacity-0','translate-y-4','pointer-events-none');
 }, 2500);
}

// ─── Keyboard: Escape closes drawer ──────────────────────────
document.addEventListener('keydown', e => {
 if (e.key === 'Escape') closeVisitDrawer();
});
</script>

<x-reception.customer-ticket-modal :departments="$departments ?? collect()" :employees="$employees ?? collect()" />
@endsection
