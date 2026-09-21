@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
 [x-cloak] { display: none !important; }
 .reception-portal { max-width: 1480px; margin-inline: auto; }
 .reception-portal * { border-radius: 0 !important; }
 .reception-card { background: #ffffff; border: 1px solid #e0e0e0; transition: box-shadow 150ms; }
 .reception-card:hover { box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
 .reception-card table thead { background: #f4f4f4; border-bottom: 1px solid #e0e0e0; }
 .reception-card table th { color: #525252 !important; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; font-weight: 600; font-family:'IBM Plex Sans',sans-serif; }
</style>
<div class="reception-portal flex flex-col w-full">

 {{-- ───────────────── HEADER — IBM polished ───────────────── --}}
 <div class="bg-white border border-[#e0e0e0] border-t-[3px] border-t-[#0f62fe] p-5 lg:p-6 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
 <div>
 <div class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Reception — Visitors</div>
 <h1 class="mt-1 text-[26px] font-semibold tracking-tight text-[#161616]" style="letter-spacing:-0.02em">Customer & Visitor Register</h1>
 <p class="mt-1 text-xs text-[#525252]" style="font-family:'IBM Plex Mono',monospace">{{ now()->format('l, d F Y') }} · Live</p>
 </div>
 <div class="flex items-center gap-2">
 <button id="btn-walkin" type="button" onclick="openCreateTicketModal()"
 class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#0f62fe] hover:bg-[#0353e9] text-white text-xs font-semibold border border-[#0f62fe] cursor-pointer">
 <span class="material-symbols-outlined text-[16px]">person_add</span>
 <span>Add Customer</span>
 </button>
 <button id="btn-fast-pass" type="button" onclick="openFastPassModal()"
 class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-white hover:bg-[#f4f4f4] text-[#161616] text-xs font-semibold border border-[#8d8d8d]">
 <span class="material-symbols-outlined text-[16px]">qr_code_scanner</span>
 <span>Fast Pass</span>
 </button>
 </div>
 </div>

 {{-- ───────────────── 4 METRIC CARDS — IBM polished ───────────────── --}}
 <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between border-l-[3px] border-l-[#0f62fe]">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Active On-Site</span>
 <span class="w-8 h-8 bg-[#edf5ff] border border-[#d0e2ff] flex items-center justify-center text-[#0f62fe]"><span class="material-symbols-outlined text-[16px]">store</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace" id="stat-inside">{{ $onPremisesCount ?? 0 }}</div>
 <div class="text-xs text-[#525252] mt-1">Currently on premises</div>
 </div>
 </div>
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Total Today</span>
 <span class="w-8 h-8 bg-[#f4f4f4] border border-[#e0e0e0] flex items-center justify-center text-[#525252]"><span class="material-symbols-outlined text-[16px]">how_to_reg</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace" id="stat-today">{{ $todayCount ?? 0 }}</div>
 <div class="text-xs text-[#525252] mt-1">Visits logged today</div>
 </div>
 </div>
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Waiting / Queue</span>
 <span class="w-8 h-8 bg-[#f4f4f4] border border-[#e0e0e0] flex items-center justify-center text-[#525252]"><span class="material-symbols-outlined text-[16px]">hourglass_top</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace" id="stat-waiting">{{ $waitingCount ?? 0 }}</div>
 <div class="text-xs text-[#525252] mt-1">In customer lounge</div>
 </div>
 </div>
 <div class="reception-card p-5 flex min-h-[118px] flex-col justify-between">
 <div class="flex items-center justify-between">
 <span class="text-[11px] font-semibold tracking-widest text-[#525252] uppercase">Checked Out Today</span>
 <span class="w-8 h-8 bg-[#f4f4f4] border border-[#e0e0e0] flex items-center justify-center text-[#525252]"><span class="material-symbols-outlined text-[16px]">logout</span></span>
 </div>
 <div class="mt-3">
 <div class="text-[28px] font-semibold tracking-tight text-[#161616] leading-none" style="font-family:'IBM Plex Mono',monospace" id="stat-checkout">{{ $checkoutReadyCount ?? 0 }}</div>
 <div class="text-xs text-[#525252] mt-1">Departures recorded</div>
 </div>
 </div>
 </div>

 {{-- ═══════════════════════════════════════════════════════
 REGISTER PANEL
 ═══════════════════════════════════════════════════════ --}}
 <div class="reception-card overflow-hidden">

 {{-- Panel header: tabs + search + actions — IBM polished --}}
 <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3.5 border-b border-[#e0e0e0] bg-[#f4f4f4]">

 {{-- Tabs — IBM --}}
 <div class="flex items-center gap-1 border border-[#e0e0e0] bg-white p-1">
 <button id="tab-btn-active" onclick="switchTab('active')"
 class="tab-btn px-3 py-1.5 bg-[#0f62fe] border border-[#0f62fe] text-white text-xs font-semibold transition-all">
 Active
 <span id="tab-count-active"
 class="ml-1.5 px-1.5 py-0.5 border border-white/40 bg-white/20 text-white font-bold text-[10px]" style="font-family:'IBM Plex Mono',monospace">
 {{ $onPremisesCount ?? 0 }}
 </span>
 </button>
 <button id="tab-btn-all" onclick="switchTab('all')"
 class="tab-btn px-3 py-1.5 bg-white border border-[#e0e0e0] text-[#525252] text-xs font-semibold transition-all hover:bg-[#f4f4f4]">
 All Visits
 <span id="tab-count-all"
 class="ml-1.5 px-1.5 py-0.5 border border-[#e0e0e0] bg-[#f4f4f4] text-[#525252] font-bold text-[10px]" style="font-family:'IBM Plex Mono',monospace">
 {{ $todayCount ?? 0 }}
 </span>
 </button>
 </div>

 {{-- Search + export tools --}}
 <div class="flex items-center gap-space-sm">
  <div class="relative">
  <span class="material-symbols-outlined text-[#8d8d8d] text-[16px] absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
  <input id="visitor-search" type="text" placeholder="Search visitor, phone…"
  class="pl-8 pr-3 py-2 bg-white border border-[#8d8d8d] text-xs font-medium text-[#161616] placeholder:text-[#8d8d8d] focus:outline-none focus:border-[#0f62fe] focus:ring-1 focus:ring-[#0f62fe] w-48 transition-all focus:w-64" style="font-family:'IBM Plex Sans',sans-serif" />
  </div>

  <select id="dept-filter"
  class="px-3 py-2 bg-white border border-[#8d8d8d] text-xs font-semibold text-[#161616] focus:outline-none focus:border-[#0f62fe]">
  <option value="">All departments</option>
  <option value="it">IT Support</option>
  <option value="sales">Sales</option>
  </select>

  {{-- Export --}}
  <div class="relative">
  <button id="btn-export-menu" type="button"
  class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-[#f4f4f4] border border-[#8d8d8d] text-[#161616] text-xs font-semibold transition-colors" title="Export Register">
  <span class="material-symbols-outlined text-[16px] text-[#0f62fe]">download</span>
  <span>Export</span>
  <span class="material-symbols-outlined text-[14px] text-[#525252]">expand_more</span>
  </button>
 <div id="export-dropdown"
 class="hidden absolute right-0 mt-1.5 w-48 bg-white border border-slate-300 py-1 z-50">
 <a href="{{ route('reception.visitors.export') }}"
 class="w-full text-left px-space-md py-space-xs font-body-sm text-body-sm text-on-surface hover:bg-surface-container flex items-center gap-space-sm">
 <span class="material-symbols-outlined text-tertiary text-[16px]">table_view</span>
 <span>All Records (CSV)</span>
 </a>
 <button type="button" onclick="exportFilteredCsv()"
 class="w-full text-left px-space-md py-space-xs font-body-sm text-body-sm text-on-surface hover:bg-surface-container flex items-center gap-space-sm">
 <span class="material-symbols-outlined text-primary text-[16px]">filter_alt</span>
 <span>Current Filter (CSV)</span>
 </button>
 </div>
 </div>
 </div>
 </div>

 {{-- Table --}}
 <div class="overflow-x-auto">
 <table class="w-full" id="customer-register-table">
 <thead>
 <tr class="bg-slate-50 border-b border-slate-300">
 <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-widest text-slate-600 whitespace-nowrap">Customer</th>
 <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-widest text-slate-600 whitespace-nowrap">Purpose</th>
 <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-widest text-slate-600 whitespace-nowrap">Department</th>
 <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-widest text-slate-600 whitespace-nowrap">Host</th>
 <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-widest text-slate-600 whitespace-nowrap">Check-in</th>
 <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-widest text-slate-600 whitespace-nowrap">Status</th>
 <th class="px-space-md py-space-sm text-right font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest whitespace-nowrap">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-200" id="customer-tbody">

 @forelse($visits as $v)
 @php
 $initials = collect(explode(' ', $v->visitor))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->join('');
 if (empty($initials)) $initials = 'CU';
 $isInside = empty($v->departure);
 $hasIt = $v->itTickets && $v->itTickets->count() > 0;
 $visitTime = $v->arrival ? \Carbon\Carbon::parse($v->arrival)->format('H:i') : '—';
 $visitDate = $v->arrival ? \Carbon\Carbon::parse($v->arrival)->format('d M Y') : 'Today';
 $deptKey = $hasIt ? 'it' : 'sales';
 @endphp
 <tr class="customer-row hover:bg-slate-50 transition-colors cursor-pointer border-b border-slate-100 last:border-0"
 data-id="{{ $v->id }}"
 data-customer="{{ $v->visitor }}"
 data-phone="{{ $v->visitor_phone ?? '' }}"
 data-type="{{ $v->zone === 'VIP' ? 'corporate' : 'walk-in' }}"
 data-dept="{{ $deptKey }}"
 data-presence="{{ $isInside ? 'inside' : 'departed' }}"
 data-status="{{ $isInside ? ($hasIt ? 'service' : 'waiting') : 'completed' }}"
 data-purpose="{{ $v->purpose ?: 'Showroom Visit' }}"
 data-host="{{ $v->employee?->name ?? 'Front Desk Staff' }}"
 data-date="{{ $visitDate }}"
 data-checkin="{{ $visitTime }}"
 data-checkout="{{ $v->departure ? \Carbon\Carbon::parse($v->departure)->format('H:i') : '—' }}"
 data-badge="{{ $v->uuid ?? '' }}"
 data-search="{{ strtolower($v->visitor . ' ' . ($v->visitor_phone ?? '') . ' ' . ($v->purpose ?? '') . ' ' . ($v->employee?->name ?? '')) }}"
 onclick="openCustomerDrawer(this)">

 {{-- Customer --}}
 <td class="px-space-md py-space-sm">
 <div class="flex items-center gap-space-sm">
 <span class="flex items-center justify-center w-9 h-9 bg-white border border-slate-300 text-slate-700 font-bold shrink-0 text-xs">
 {{ $initials }}
 </span>
 <div class="min-w-0">
 <p class="text-xs font-bold text-slate-900 truncate">{{ $v->visitor }}</p>
 <p class="text-[11px] text-slate-500 font-mono">{{ $v->visitor_phone ?: '—' }}</p>
 </div>
 </div>
 </td>

 {{-- Purpose --}}
 <td class="px-space-md py-space-sm max-w-[160px]">
 <span class="text-xs text-slate-700 truncate block">{{ $v->purpose ?: 'General Inquiry' }}</span>
 </td>

 {{-- Department --}}
 <td class="px-space-md py-space-sm whitespace-nowrap">
 <span class="text-xs font-bold {{ $hasIt ? 'text-slate-900' : 'text-slate-600' }}">
 {{ $hasIt ? 'IT Support' : ($v->employee?->department?->name ?? 'Sales') }}
 </span>
 </td>

 {{-- Host --}}
 <td class="px-space-md py-space-sm">
 <span class="text-xs text-slate-700">{{ $v->employee?->name ?? 'Receptionist' }}</span>
 </td>

 {{-- Check-in --}}
 <td class="px-space-md py-space-sm whitespace-nowrap">
 <span class="font-mono text-xs text-slate-600">{{ $visitTime }}</span>
 </td>

 {{-- Status --}}
 <td class="px-space-md py-space-sm whitespace-nowrap">
 @if($isInside)
 <span class="inline-flex items-center gap-1.5 px-2 py-1 border border-slate-300 bg-white text-slate-700 text-[11px] font-bold">
 <span class="w-1.5 h-1.5 rounded-full bg-tertiary animate-pulse"></span> Inside
 </span>
 @else
 <span class="inline-flex items-center gap-1.5 px-2 py-1 border border-slate-200 bg-slate-50 text-slate-600 text-[11px] font-bold">
 <span class="w-1.5 h-1.5 rounded-full bg-outline"></span> Departed
 </span>
 @endif
 </td>

 {{-- Actions --}}
 <td class="px-space-md py-space-sm text-right" onclick="event.stopPropagation()">
 <div class="flex items-center justify-end gap-space-xs">
 <button type="button" onclick="openSmsModal(this.closest('tr'))" title="Send SMS"
 class="flex items-center justify-center w-8 h-8 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-all" title="Send SMS">
 <span class="material-symbols-outlined text-[16px]">sms</span>
 </button>
 <button type="button" onclick="openCustomerDrawer(this.closest('tr'))"
 class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-900 text-xs font-bold border border-slate-300 transition-all">
 <span class="material-symbols-outlined text-[14px]">open_in_new</span>
 View
 </button>
 </div>
 </td>
 </tr>

 @empty
 <tr id="empty-visitors-row">
 <td colspan="7" class="py-16 text-center">
 <span class="material-symbols-outlined text-[48px] text-outline block mb-3">person_search</span>
 <p class="font-label-lg text-label-lg text-on-surface-variant font-semibold">No visitors yet</p>
 <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 mb-space-md">Check-ins will appear here.</p>
 <button type="button" onclick="openWalkinModal()"
 class="inline-flex items-center gap-space-xs px-space-md py-space-sm bg-primary text-on-primary font-label-md text-label-md hover:opacity-90 transition-opacity">
 <span class="material-symbols-outlined text-[16px]">person_add</span>
 Check-in First Customer
 </button>
 </td>
 </tr>
 @endforelse

 </tbody>
 </table>

 {{-- Filter no-results --}}
 <div id="no-results-row" class="hidden py-12 text-center border-t border-slate-200 bg-slate-50">
 <span class="material-symbols-outlined text-[36px] text-outline block mb-2">manage_search</span>
 <p class="font-label-md text-label-md text-on-surface-variant">No visitors match your search.</p>
 </div>
 </div>

 {{-- Table Footer --}}
 <div class="px-4 py-3 bg-slate-50 border-t border-slate-300 flex flex-col sm:flex-row items-center justify-between gap-3">
 <div class="flex items-center gap-space-sm font-body-sm text-body-sm text-on-surface-variant">
 <span>Show</span>
 <select id="per-page-select"
 class="bg-surface-container-lowest border border-outline-variant px-2 py-1 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-primary/30">
 <option value="10" selected>10</option>
 <option value="20">20</option>
 <option value="50">50</option>
 </select>
 <span>per page</span>
 <span class="text-outline-variant mx-1">·</span>
 <span>
 <strong class="text-on-surface" id="page-start">1</strong>–<strong class="text-on-surface" id="page-end">0</strong>
 of <strong class="text-on-surface" id="page-total">{{ count($visits ?? []) }}</strong>
 </span>
 </div>
 <nav class="inline-flex items-center border border-outline-variant overflow-hidden bg-surface-container-lowest">
 <button type="button" id="btn-page-prev"
 class="px-2.5 py-1.5 font-body-sm text-body-sm text-on-surface-variant hover:bg-surface-container disabled:opacity-40 disabled:cursor-not-allowed border-r border-outline-variant">
 <span class="material-symbols-outlined text-[16px]">chevron_left</span>
 </button>
 <div id="pagination-pages" class="flex items-center divide-x divide-outline-variant"></div>
 <button type="button" id="btn-page-next"
 class="px-2.5 py-1.5 font-body-sm text-body-sm text-on-surface-variant hover:bg-surface-container disabled:opacity-40 disabled:cursor-not-allowed border-l border-outline-variant">
 <span class="material-symbols-outlined text-[16px]">chevron_right</span>
 </button>
 </nav>
 </div>

 </div>{{-- end register panel --}}
</div>

{{-- ═══════════════════════════════════════════════════════════════
 CUSTOMER DRAWER
═══════════════════════════════════════════════════════════════ --}}
<div id="customer-drawer-backdrop"
 class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-[90]"
 onclick="closeCustomerDrawer()"></div>

<aside id="customer-drawer"
 class="fixed right-0 top-0 bottom-0 w-full max-w-[460px] bg-surface-container-lowest z-[100] transform translate-x-full transition-transform duration-250 flex flex-col border-l border-outline-variant">

 {{-- Head --}}
 <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant bg-surface-container">
 <div class="flex items-center gap-space-md min-w-0">
 <span id="drawer-avatar"
 class="flex items-center justify-center w-11 h-11 rounded-full bg-primary-container text-on-primary-container font-headline-sm text-headline-sm font-bold shrink-0 select-none">
 CU
 </span>
 <div class="min-w-0">
 <div class="flex items-center gap-space-xs flex-wrap">
 <h2 id="drawer-name" class="font-label-lg text-label-lg text-on-surface font-bold truncate">Customer</h2>
 <span id="drawer-presence-badge"
 class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-label-sm text-label-sm bg-tertiary/10 text-tertiary font-semibold">
 <span class="w-1.5 h-1.5 rounded-full bg-tertiary animate-pulse"></span>
 <span id="drawer-presence-text">Inside</span>
 </span>
 </div>
 <p class="font-label-mono text-label-mono text-on-surface-variant text-[11px] mt-0.5 truncate" id="drawer-phone-top">—</p>
 </div>
 </div>
 <button type="button" onclick="closeCustomerDrawer()"
 class="flex items-center justify-center w-8 h-8 hover:bg-surface-container-high text-on-surface-variant transition-colors shrink-0">
 <span class="material-symbols-outlined text-[20px]">close</span>
 </button>
 </div>

 {{-- Body --}}
 <div class="flex-1 overflow-y-auto">

 {{-- Detail grid --}}
 <div class="px-space-lg pt-space-lg pb-space-md border-b border-outline-variant">
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-space-md">Visit Information</p>
 <div class="grid grid-cols-2 gap-x-space-lg gap-y-space-md">
 <div>
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-0.5">Phone</p>
 <p id="drawer-phone" class="font-label-mono text-label-mono text-on-surface font-semibold">—</p>
 </div>
 <div>
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-0.5">Visit ID</p>
 <p id="drawer-visit-id" class="font-label-mono text-label-mono text-on-surface text-[11px]">—</p>
 </div>
 <div>
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-0.5">Department</p>
 <p id="drawer-dept" class="font-label-md text-label-md text-primary font-semibold">—</p>
 </div>
 <div>
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-0.5">Host / Staff</p>
 <p id="drawer-host" class="font-label-md text-label-md text-on-surface">—</p>
 </div>
 <div>
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-0.5">Date</p>
 <p id="drawer-date" class="font-label-mono text-label-mono text-on-surface">—</p>
 </div>
 <div>
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-0.5">Check-in Time</p>
 <p id="drawer-checkin" class="font-label-mono text-label-mono text-on-surface">—</p>
 </div>
 </div>
 <div class="mt-space-md bg-surface-container px-space-md py-space-sm border border-outline-variant">
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-1">Purpose</p>
 <p id="drawer-purpose" class="font-body-sm text-body-sm text-on-surface leading-relaxed">—</p>
 </div>
 </div>

 {{-- Visit Timeline --}}
 <div class="px-space-lg py-space-md border-b border-outline-variant">
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-space-md">Timeline</p>
 <div class="flex flex-col gap-0" id="drawer-timeline">
 {{-- Populated by JS --}}
 </div>
 </div>

 {{-- Billing --}}
 <div class="px-space-lg py-space-md">
 <div class="flex items-center justify-between">
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Service Billing</p>
 <span id="drawer-payment-badge"
 class="inline-flex items-center px-2.5 py-1 font-label-sm text-label-sm bg-surface-container text-on-surface-variant border border-outline-variant">
 No Charge / Pending
 </span>
 </div>
 </div>
 </div>

 {{-- Footer --}}
 <div class="px-space-lg py-space-md border-t border-outline-variant bg-surface-container grid grid-cols-3 gap-space-sm">
 <button type="button" onclick="openSmsModal(activeDrawerRow)"
 class="flex items-center justify-center gap-space-xs py-space-sm bg-primary text-on-primary font-label-md text-label-md font-semibold hover:opacity-90 transition-opacity">
 <span class="material-symbols-outlined text-[16px]">sms</span>
 SMS
 </button>
 <button type="button" onclick="exportCurrentVisitorCsv(activeDrawerRow)"
 class="flex items-center justify-center gap-space-xs py-space-sm border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-on-surface font-label-md text-label-md transition-colors">
 <span class="material-symbols-outlined text-[16px] text-primary">download</span>
 Export CSV
 </button>
 <button type="button" id="drawer-checkout-btn"
 class="flex items-center justify-center gap-space-xs py-space-sm bg-inverse-surface text-inverse-on-surface font-label-md text-label-md hover:opacity-90 transition-opacity">
 <span class="material-symbols-outlined text-[16px]">logout</span>
 Check Out
 </button>
 </div>
</aside>

{{-- ═══════════════════════════════════════════════════════════════
 SMS MODAL
═══════════════════════════════════════════════════════════════ --}}
<div id="sms-compose-modal" class="hidden fixed inset-0 z-[140] flex items-center justify-center p-4">
 <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeSmsModal()"></div>
 <div class="relative bg-surface-container-lowest border border-outline-variant w-full max-w-lg flex flex-col overflow-hidden">
 <div class="px-space-lg py-space-md border-b border-outline-variant flex items-center justify-between bg-surface-container">
 <div class="flex items-center gap-space-sm">
 <span class="flex items-center justify-center w-9 h-9 bg-primary text-on-primary">
 <span class="material-symbols-outlined text-[20px]">sms</span>
 </span>
 <div>
 <h3 class="font-label-lg text-label-lg text-on-surface font-bold">Send SMS</h3>
 <p class="font-body-sm text-body-sm text-on-surface-variant">
 To: <strong id="sms-modal-recipient-name" class="text-on-surface">—</strong>
 · <span id="sms-modal-recipient-phone" class="font-label-mono text-label-mono text-primary">—</span>
 </p>
 </div>
 </div>
 <button type="button" onclick="closeSmsModal()"
 class="flex items-center justify-center w-8 h-8 hover:bg-surface-container text-on-surface-variant">
 <span class="material-symbols-outlined text-[18px]">close</span>
 </button>
 </div>

 <div class="p-space-lg flex flex-col gap-space-md">
 <div>
 <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest mb-space-sm">Quick Templates</p>
 <div class="grid grid-cols-2 gap-space-xs">
 @foreach([
 ['welcome', '👋 Karibu Generat'],
 ['ready', '✅ Kazi Imekamilika'],
 ['it_update', '🔧 Taarifa ya IT'],
 ['thanks', '🙏 Asante kwa Kututembelea'],
 ] as [$key, $label])
 <button type="button" onclick="insertSmsModalTemplate('{{ $key }}')"
 class="px-space-sm py-space-xs bg-surface-container hover:bg-surface-container-high border border-outline-variant text-left font-body-sm text-body-sm text-on-surface transition-colors truncate">
 {{ $label }}
 </button>
 @endforeach
 </div>
 </div>

 <div>
 <div class="flex items-center justify-between mb-1">
 <p class="font-label-sm text-label-sm text-on-surface font-semibold">Message</p>
 <span class="font-label-sm text-label-sm text-on-surface-variant">via NextSMS</span>
 </div>
 <textarea id="modal-sms-text" rows="4"
 placeholder="Type your message…"
 class="w-full p-space-sm border border-outline-variant bg-surface-container focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary font-body-sm text-body-sm text-on-surface placeholder:text-on-surface-variant resize-none transition-colors"></textarea>
 <p id="modal-sms-charcount" class="font-label-mono text-label-mono text-on-surface-variant text-[11px] mt-1">0 / 160 characters</p>
 </div>

 <div class="flex items-center justify-end gap-space-sm pt-space-xs border-t border-outline-variant">
 <button type="button" onclick="closeSmsModal()"
 class="px-space-md py-space-sm border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-on-surface font-label-md text-label-md transition-colors">
 Cancel
 </button>
 <button type="button" id="btn-modal-send-sms" onclick="sendModalSms()"
 class="inline-flex items-center gap-space-xs px-space-md py-space-sm bg-primary text-on-primary font-label-md text-label-md font-semibold hover:opacity-90 transition-opacity">
 <span class="material-symbols-outlined text-[15px]">send</span>
 Send SMS
 </button>
 </div>
 </div>
 </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
 CHECK-IN MODAL
═══════════════════════════════════════════════════════════════ --}}
<div id="vis-walkin-modal" class="hidden fixed inset-0 z-[120] flex items-center justify-center p-4">
 <div id="vis-walkin-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
 <div class="relative bg-surface-container-lowest border border-outline-variant w-full max-w-lg flex flex-col overflow-hidden">
 <div class="px-space-lg py-space-md border-b border-outline-variant bg-surface-container flex items-center justify-between">
 <div class="flex items-center gap-space-sm">
 <span class="flex items-center justify-center w-9 h-9 rounded-full bg-primary-container text-on-primary-container">
 <span class="material-symbols-outlined text-[20px]">person_add</span>
 </span>
 <div>
 <h3 class="font-label-lg text-label-lg text-on-surface font-bold">Check-in Customer</h3>
 <p class="font-body-sm text-body-sm text-on-surface-variant">{{ now()->format('H:i · d M Y') }}</p>
 </div>
 </div>
 <button id="vis-walkin-close" type="button"
 class="flex items-center justify-center w-8 h-8 hover:bg-surface-container text-on-surface-variant">
 <span class="material-symbols-outlined text-[18px]">close</span>
 </button>
 </div>
 <form id="vis-walkin-form" class="p-space-lg flex flex-col gap-space-md">

 {{-- Guest type --}}
 <div class="flex items-center gap-space-lg font-label-md text-label-md text-on-surface">
 <label class="flex items-center gap-space-xs cursor-pointer">
 <input type="radio" name="guest_type" value="Individual" checked class="text-primary focus:ring-primary"> Individual
 </label>
 <label class="flex items-center gap-space-xs cursor-pointer">
 <input type="radio" name="guest_type" value="Company" class="text-primary focus:ring-primary"> Company / Corporate
 </label>
 </div>

 {{-- Name + Phone --}}
 <div class="grid grid-cols-2 gap-space-md">
 <label class="flex flex-col gap-1 col-span-2 sm:col-span-1">
 <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Full Name *</span>
 <input id="vis-name" required placeholder="e.g. Aza Rashid"
 class="h-9 px-space-sm border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none font-body-sm text-body-sm text-on-surface" />
 </label>
 <label class="flex flex-col gap-1 col-span-2 sm:col-span-1">
 <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Phone Number *</span>
 <input id="vis-phone" required placeholder="+255 7XX XXX XXX"
 class="h-9 px-space-sm border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none font-body-sm text-body-sm text-on-surface" />
 </label>
 </div>

 {{-- Purpose + Host --}}
 <div class="grid grid-cols-2 gap-space-md">
 <label class="flex flex-col gap-1">
 <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Purpose *</span>
 <select id="vis-purpose" required
 class="h-9 px-space-sm border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary/30 font-body-sm text-body-sm text-on-surface">
 <option value="Product Inquiry">Product Inquiry</option>
 <option value="IT Support">IT Support &amp; Repair</option>
 <option value="Executive Meeting">Executive Meeting</option>
 <option value="Purchasing">Purchasing / Cash Sale</option>
 <option value="Other">Other</option>
 </select>
 </label>
 <label class="flex flex-col gap-1">
 <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Host / Staff</span>
 <input id="vis-host" placeholder="e.g. Hassan Ally"
 class="h-9 px-space-sm border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary/30 outline-none font-body-sm text-body-sm text-on-surface" />
 </label>
 </div>

 <div class="flex items-center justify-end gap-space-sm pt-space-sm border-t border-outline-variant">
 <button type="button" id="vis-walkin-cancel"
 class="px-space-md py-space-sm border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-on-surface font-label-md text-label-md">
 Cancel
 </button>
 <button type="submit"
 class="inline-flex items-center gap-space-xs px-space-md py-space-sm bg-primary text-on-primary font-label-md text-label-md font-semibold hover:opacity-90 transition-opacity">
 <span class="material-symbols-outlined text-[16px]">how_to_reg</span>
 Save &amp; Check In
 </button>
 </div>
 </form>
 </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
 FAST PASS MODAL
═══════════════════════════════════════════════════════════════ --}}
<div id="fastpass-modal" class="hidden fixed inset-0 z-[120] flex items-center justify-center p-4">
 <div id="fastpass-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
 <div class="relative bg-surface-container-lowest border border-outline-variant w-full max-w-md flex flex-col overflow-hidden">
 <div class="px-space-lg py-space-md border-b border-outline-variant bg-surface-container flex items-center justify-between">
 <div class="flex items-center gap-space-sm">
 <span class="flex items-center justify-center w-9 h-9 bg-surface-container-high text-on-surface">
 <span class="material-symbols-outlined text-[20px]">qr_code_scanner</span>
 </span>
 <h3 class="font-label-lg text-label-lg text-on-surface font-bold">Fast Pass Lookup</h3>
 </div>
 <button id="fastpass-close" type="button"
 class="flex items-center justify-center w-8 h-8 hover:bg-surface-container text-on-surface-variant">
 <span class="material-symbols-outlined text-[18px]">close</span>
 </button>
 </div>
 <div class="p-space-lg flex flex-col gap-space-md">
 <p class="font-body-sm text-body-sm text-on-surface-variant">Enter a pre-registration reference code to look up a visitor.</p>
 <div class="flex gap-space-sm">
 <input id="fastpass-qr" placeholder="e.g. PRE-092"
 class="flex-1 h-9 px-space-sm border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary/30 font-label-mono text-label-mono text-on-surface outline-none" />
 <button id="fastpass-lookup"
 class="px-space-md bg-surface-container hover:bg-surface-container-high border border-outline-variant font-label-md text-label-md text-on-surface transition-colors">
 Lookup
 </button>
 </div>
 <div id="fastpass-result"
 class="hidden p-space-md bg-surface-container border border-outline-variant flex flex-col gap-space-sm">
 <p class="font-label-md text-label-md text-on-surface font-semibold">Denisi Esau — Bio-Pharma Diagnostics</p>
 <p class="font-body-sm text-body-sm text-on-surface-variant">Host: Robert Hayes · 01:05 PM</p>
 <button id="fastpass-checkin"
 class="w-full py-space-sm bg-primary text-on-primary font-label-md text-label-md font-semibold hover:opacity-90 transition-opacity">
 Confirm Check-in
 </button>
 </div>
 </div>
 </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
 CHECKOUT MODAL
═══════════════════════════════════════════════════════════════ --}}
<div id="checkout-modal" class="hidden fixed inset-0 z-[130] flex items-center justify-center p-4">
 <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
 <div class="relative bg-surface-container-lowest border border-outline-variant w-full max-w-sm p-space-lg">
 <div class="text-center mb-space-lg">
 <span class="flex items-center justify-center w-14 h-14 rounded-full bg-surface-container text-on-surface-variant mx-auto mb-space-md">
 <span class="material-symbols-outlined text-[28px]">logout</span>
 </span>
 <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">Confirm Check-out</h3>
 <p id="checkout-text" class="font-body-sm text-body-sm text-on-surface-variant mt-1">—</p>
 </div>
 <div class="flex gap-space-sm">
 <button id="checkout-cancel"
 class="flex-1 py-space-sm border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-on-surface font-label-md text-label-md transition-colors">
 Cancel
 </button>
 <button id="checkout-confirm"
 class="flex-1 py-space-sm bg-error text-on-error font-label-md text-label-md font-semibold hover:opacity-90 transition-opacity">
 Check Out
 </button>
 </div>
 </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
 TOAST
═══════════════════════════════════════════════════════════════ --}}
<div id="vis-toast"
 class="fixed bottom-6 right-6 z-[150] flex items-center gap-space-sm px-space-md py-space-sm bg-inverse-surface text-inverse-on-surface font-label-md text-label-md opacity-0 translate-y-4 pointer-events-none transition-all duration-300">
 <span id="vis-toast-icon" class="material-symbols-outlined text-[18px]">check_circle</span>
 <span id="vis-toast-text">Done</span>
</div>

{{-- ═══════════════════════════════════════════════════════════════
 JAVASCRIPT
═══════════════════════════════════════════════════════════════ --}}
<script>
(function () {
 // ── Toast ─────────────────────────────────────────────────
 const toastEl = document.getElementById('vis-toast');
 const toastIcon = document.getElementById('vis-toast-icon');
 const toastText = document.getElementById('vis-toast-text');
 let toastTimer;

 function showToast(msg, isError = false) {
 toastText.textContent = msg;
 toastIcon.textContent = isError ? 'error' : 'check_circle';
 clearTimeout(toastTimer);
 toastEl.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
 toastTimer = setTimeout(() => toastEl.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none'), 3000);
 }

 // ── Tab logic ─────────────────────────────────────────────
 let activeTab = 'active';

 window.switchTab = function (tab) {
 activeTab = tab;
 const rows = document.querySelectorAll('.customer-row');

 const btnActive = document.getElementById('tab-btn-active');
 const btnAll = document.getElementById('tab-btn-all');
 const active = 'bg-surface-container-lowest text-on-surface font-semibold';
 const inactive = 'text-on-surface-variant hover:text-on-surface';

 if (tab === 'active') {
 btnActive.className = `tab-btn px-space-md py-1.5 font-label-md text-label-md transition-all ${active}`;
 btnAll.className = `tab-btn px-space-md py-1.5 font-label-md text-label-md transition-all ${inactive}`;
 } else {
 btnAll.className = `tab-btn px-space-md py-1.5 font-label-md text-label-md transition-all ${active}`;
 btnActive.className = `tab-btn px-space-md py-1.5 font-label-md text-label-md transition-all ${inactive}`;
 }

 applyFilters();
 };

 // ── Search + filter ───────────────────────────────────────
 const searchEl = document.getElementById('visitor-search');
 const deptEl = document.getElementById('dept-filter');
 const noResults = document.getElementById('no-results-row');

 function applyFilters() {
 const q = (searchEl?.value || '').toLowerCase();
 const dept = deptEl?.value || '';
 const rows = document.querySelectorAll('.customer-row');
 let visible = 0;

 rows.forEach(row => {
 const matchTab = activeTab === 'all' || row.dataset.presence === 'inside';
 const matchSearch = !q || (row.dataset.search || '').includes(q);
 const matchDept = !dept || row.dataset.dept === dept;
 const show = matchTab && matchSearch && matchDept;
 row.classList.toggle('hidden', !show);
 if (show) visible++;
 });

 if (noResults) noResults.classList.toggle('hidden', visible > 0 || rows.length === 0);
 updatePagination();
 }

 searchEl?.addEventListener('input', applyFilters);
 deptEl?.addEventListener('change', applyFilters);

 // ── Export dropdown ───────────────────────────────────────
 const exportBtn = document.getElementById('btn-export-menu');
 const exportDropdown = document.getElementById('export-dropdown');

 exportBtn?.addEventListener('click', e => { e.stopPropagation(); exportDropdown.classList.toggle('hidden'); });
 document.addEventListener('click', () => exportDropdown?.classList.add('hidden'));

 window.exportFilteredCsv = function () {
 exportDropdown?.classList.add('hidden');
 const rows = Array.from(document.querySelectorAll('.customer-row:not(.hidden)'));
 if (rows.length === 0) {
 showToast('No records matching current filter', true);
 return;
 }

 const headers = ['Reference / ID', 'Visitor Name', 'Phone', 'Type', 'Purpose', 'Department', 'Host', 'Check-In Date', 'Check-In Time', 'Status', 'Billing'];
 const csvRows = [headers.join(',')];

 rows.forEach(r => {
 const d = r.dataset;
 const rowData = [
 `"${(d.badge || 'VIS-' + (d.id || '001')).replace(/"/g, '""')}"`,
 `"${(d.customer || '').replace(/"/g, '""')}"`,
 `"${(d.phone || '').replace(/"/g, '""')}"`,
 `"${(d.type || '').replace(/"/g, '""')}"`,
 `"${(d.purpose || '').replace(/"/g, '""')}"`,
 `"${(d.dept || '').toUpperCase().replace(/"/g, '""')}"`,
 `"${(d.host || '').replace(/"/g, '""')}"`,
 `"${(d.date || '').replace(/"/g, '""')}"`,
 `"${(d.checkin || '').replace(/"/g, '""')}"`,
 `"${(d.presence === 'inside' ? 'Inside (' + (d.status || 'Active') + ')' : 'Departed').replace(/"/g, '""')}"`,
 `"${(d.payment || '0.00').replace(/"/g, '""')}"`
 ];
 csvRows.push(rowData.join(','));
 });

 const csvContent = '\uFEFF' + csvRows.join('\r\n');
 const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
 const url = URL.createObjectURL(blob);
 const link = document.createElement('a');
 link.setAttribute('href', url);
 link.setAttribute('download', `visitors_filter_${new Date().toISOString().slice(0, 10)}.csv`);
 document.body.appendChild(link);
 link.click();
 document.body.removeChild(link);
 URL.revokeObjectURL(url);
 showToast(`Exported ${rows.length} records to CSV`);
 };

 window.exportCurrentVisitorCsv = function (row) {
 if (!row && activeDrawerRow) row = activeDrawerRow;
 if (!row) {
 showToast('No visitor selected', true);
 return;
 }
 const d = row.dataset;
 const headers = ['Field', 'Value'];
 const rows = [
 headers.join(','),
 `"Reference / ID","${(d.badge || 'VIS-' + (d.id || '001')).replace(/"/g, '""')}"`,
 `"Visitor Name","${(d.customer || '').replace(/"/g, '""')}"`,
 `"Phone","${(d.phone || '').replace(/"/g, '""')}"`,
 `"Type","${(d.type || '').replace(/"/g, '""')}"`,
 `"Department","${(d.dept || '').toUpperCase().replace(/"/g, '""')}"`,
 `"Host / Staff","${(d.host || '').replace(/"/g, '""')}"`,
 `"Visit Date","${(d.date || '').replace(/"/g, '""')}"`,
 `"Check-In Time","${(d.checkin || '').replace(/"/g, '""')}"`,
 `"Check-Out Time","${(d.checkout || '').replace(/"/g, '""')}"`,
 `"Purpose","${(d.purpose || '').replace(/"/g, '""')}"`,
 `"Status","${(d.presence === 'inside' ? 'Inside' : 'Departed').replace(/"/g, '""')}"`,
 ];
 const csvContent = '\uFEFF' + rows.join('\r\n');
 const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
 const url = URL.createObjectURL(blob);
 const link = document.createElement('a');
 link.setAttribute('href', url);
 link.setAttribute('download', `visitor_${(d.customer || 'record').toLowerCase().replace(/\s+/g, '_')}.csv`);
 document.body.appendChild(link);
 link.click();
 document.body.removeChild(link);
 URL.revokeObjectURL(url);
 showToast(`Exported record for ${d.customer || 'visitor'}`);
 };

 window.toggleRowMenu = function (btn, e) { e.stopPropagation(); openCustomerDrawer(btn.closest('tr')); };

 // ── Pagination ────────────────────────────────────────────
 let currentPage = 1;
 let pageSize = 10;
 const perPageSel = document.getElementById('per-page-select');
 const btnPrev = document.getElementById('btn-page-prev');
 const btnNext = document.getElementById('btn-page-next');
 const pagesBox = document.getElementById('pagination-pages');
 const pageStartEl = document.getElementById('page-start');
 const pageEndEl = document.getElementById('page-end');
 const pageTotalEl = document.getElementById('page-total');

 function updatePagination() {
 const rows = Array.from(document.querySelectorAll('.customer-row:not(.hidden)'));
 const total = rows.length;
 const totalPages = Math.ceil(total / pageSize) || 1;
 if (currentPage > totalPages) currentPage = totalPages;
 if (currentPage < 1) currentPage = 1;

 const s = (currentPage - 1) * pageSize;
 const e = Math.min(s + pageSize, total);

 rows.forEach((r, i) => r.style.display = (i >= s && i < e) ? '' : 'none');

 if (pageStartEl) pageStartEl.textContent = total > 0 ? s + 1 : 0;
 if (pageEndEl) pageEndEl.textContent = e;
 if (pageTotalEl) pageTotalEl.textContent = total;
 if (btnPrev) btnPrev.disabled = currentPage <= 1;
 if (btnNext) btnNext.disabled = currentPage >= totalPages;

 if (pagesBox) {
 pagesBox.innerHTML = '';
 for (let p = 1; p <= totalPages; p++) {
 const b = document.createElement('button');
 b.type = 'button'; b.textContent = p;
 b.className = p === currentPage
 ? 'px-3 py-1.5 font-label-sm text-label-sm font-semibold text-primary bg-primary-container/20'
 : 'px-3 py-1.5 font-label-sm text-label-sm text-on-surface-variant hover:bg-surface-container';
 b.addEventListener('click', () => { currentPage = p; updatePagination(); });
 pagesBox.appendChild(b);
 }
 }
 }

 perPageSel?.addEventListener('change', e => { pageSize = +e.target.value || 10; currentPage = 1; updatePagination(); });
 btnPrev?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; updatePagination(); } });
 btnNext?.addEventListener('click', () => {
 const rows = document.querySelectorAll('.customer-row:not(.hidden)');
 if (currentPage < Math.ceil(rows.length / pageSize)) { currentPage++; updatePagination(); }
 });

 // ── Drawer ────────────────────────────────────────────────
 let activeDrawerRow = null;
 const drawer = document.getElementById('customer-drawer');
 const drawerBack = document.getElementById('customer-drawer-backdrop');

 window.openCustomerDrawer = function (row) {
 activeDrawerRow = row;
 const d = row.dataset;

 document.getElementById('drawer-name').textContent = d.customer || 'Customer';
 document.getElementById('drawer-avatar').textContent = (d.customer || 'CU').substring(0, 2).toUpperCase();
 document.getElementById('drawer-phone-top').textContent = d.phone || '—';
 document.getElementById('drawer-phone').textContent = d.phone || '—';
 document.getElementById('drawer-visit-id').textContent = d.badge ? d.badge.toUpperCase().slice(0, 8) : '—';
 document.getElementById('drawer-dept').textContent = d.dept ? d.dept.toUpperCase() : 'SALES';
 document.getElementById('drawer-host').textContent = d.host || 'Front Desk Staff';
 document.getElementById('drawer-date').textContent = d.date || '—';
 document.getElementById('drawer-checkin').textContent = d.checkin || '—';

 let purpose = d.purpose || 'Showroom Customer Visit';
 document.getElementById('drawer-purpose').textContent = purpose;

 // Presence badge
 const badge = document.getElementById('drawer-presence-badge');
 const badgeText = document.getElementById('drawer-presence-text');
 if (d.presence === 'inside') {
 badge.className = 'shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-label-sm text-label-sm bg-tertiary/10 text-tertiary font-semibold';
 badge.querySelector('span').className = 'w-1.5 h-1.5 rounded-full bg-tertiary animate-pulse';
 badgeText.textContent = 'Inside';
 } else {
 badge.className = 'shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-label-sm text-label-sm bg-surface-container text-on-surface-variant font-semibold';
 badge.querySelector('span').className = 'w-1.5 h-1.5 rounded-full bg-outline';
 badgeText.textContent = 'Departed';
 }

 // Timeline
 const tl = document.getElementById('drawer-timeline');
 const events = [
 { time: d.checkin || '—', label: 'Checked in at Front Desk' },
 ...(d.dept === 'it' ? [{ time: '—', label: 'Routed to IT Support' }] : []),
 ...(d.presence !== 'inside' ? [{ time: d.checkout || '—', label: 'Checked out' }] : []),
 ];
 tl.innerHTML = events.map((ev, i) => `
 <div class="flex gap-3 ${i < events.length - 1 ? 'mb-space-sm' : ''}">
 <div class="flex flex-col items-center shrink-0">
 <span class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></span>
 ${i < events.length - 1 ? '<div class="w-px flex-1 mt-1 bg-outline-variant min-h-[18px]"></div>' : ''}
 </div>
 <div class="pb-1 min-w-0">
 <p class="font-label-md text-label-md text-on-surface">${ev.label}</p>
 <p class="font-label-mono text-label-mono text-on-surface-variant text-[11px] mt-0.5">${ev.time}</p>
 </div>
 </div>`).join('');

 // Payment badge
 const payBadge = document.getElementById('drawer-payment-badge');
 payBadge.textContent = d.payment && d.payment !== '—' ? d.payment : 'No Charge / Pending';
 payBadge.className = d.payment && d.payment !== '—'
 ? 'inline-flex items-center px-2.5 py-1 font-label-sm text-label-sm bg-tertiary/10 text-tertiary border border-tertiary/20 font-semibold'
 : 'inline-flex items-center px-2.5 py-1 font-label-sm text-label-sm bg-surface-container text-on-surface-variant border border-outline-variant';

 drawerBack.classList.remove('hidden');
 requestAnimationFrame(() => drawer.classList.remove('translate-x-full'));
 };

 window.closeCustomerDrawer = function () {
 drawer.classList.add('translate-x-full');
 setTimeout(() => drawerBack.classList.add('hidden'), 250);
 };

 document.getElementById('drawer-checkout-btn')?.addEventListener('click', () => {
 if (activeDrawerRow) {
 closeCustomerDrawer();
 wireCheckoutDirect(activeDrawerRow.dataset.customer, activeDrawerRow.dataset.phone, activeDrawerRow.dataset.badge);
 }
 });

 // ── SMS Modal ─────────────────────────────────────────────
 const smsModal = document.getElementById('sms-compose-modal');
 const modalSmsTextarea = document.getElementById('modal-sms-text');
 const modalSmsChar = document.getElementById('modal-sms-charcount');
 const btnSend = document.getElementById('btn-modal-send-sms');
 let activeSmsCustomer = null;

 window.openSmsModal = function (row) {
 if (!row && activeDrawerRow) row = activeDrawerRow;
 if (!row) { showToast('No customer selected', true); return; }
 activeSmsCustomer = { name: row.dataset.customer || 'Customer', phone: row.dataset.phone || '—', dept: row.dataset.dept };
 document.getElementById('sms-modal-recipient-name').textContent = activeSmsCustomer.name;
 document.getElementById('sms-modal-recipient-phone').textContent = activeSmsCustomer.phone;
 if (modalSmsTextarea) { modalSmsTextarea.value = ''; if (modalSmsChar) modalSmsChar.textContent = '0 / 160 characters'; }
 smsModal?.classList.remove('hidden');
 setTimeout(() => modalSmsTextarea?.focus(), 50);
 };

 window.closeSmsModal = function () { smsModal?.classList.add('hidden'); };

 modalSmsTextarea?.addEventListener('input', () => {
 const len = modalSmsTextarea.value.length;
 if (modalSmsChar) modalSmsChar.textContent = `${len} / 160 characters (${Math.ceil(len / 160) || 1} SMS)`;
 });

 window.insertSmsModalTemplate = function (type) {
 if (!activeSmsCustomer) return;
 const n = activeSmsCustomer.name || 'Mteja';
 const templates = {
 welcome: `Habari ${n}, karibu Generat Trading. Ombi lako linashughulikiwa na kitengo chetu.`,
 ready: `Habari ${n}, huduma yako katika Generat Trading imekamilika. Karibu mapokezi kuchukua kifaa chako.`,
 it_update: `Habari ${n}, mafundi wetu wa IT & Service wanaendelea na uchunguzi wa kifaa chako. Tutakutaarifu punde.`,
 thanks: `Asante ${n} kwa kutembelea Generat Trading leo. Tunathamini sana ushirikiano wako. Karibu tena!`,
 };
 if (modalSmsTextarea && templates[type]) {
 modalSmsTextarea.value = templates[type];
 modalSmsTextarea.dispatchEvent(new Event('input'));
 modalSmsTextarea.focus();
 }
 };

 window.sendModalSms = async function () {
 if (!activeSmsCustomer) { showToast('No customer selected', true); return; }
 const { name, phone } = activeSmsCustomer;
 const message = modalSmsTextarea?.value?.trim();
 if (!message) { showToast('Please type a message first', true); modalSmsTextarea?.focus(); return; }
 if (!phone || phone === '—') { showToast('No valid phone number', true); return; }

 if (btnSend) { btnSend.disabled = true; btnSend.innerHTML = '<span class="material-symbols-outlined text-[14px] animate-spin">progress_activity</span><span>Sending…</span>'; }

 try {
 const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
 const res = await fetch('/reception/api/sms/send', {
 method: 'POST',
 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf || '', Accept: 'application/json' },
 body: JSON.stringify({ phone, message, visitor: name })
 });
 const data = await res.json();
 if (res.ok && data.success) { showToast(`SMS sent to ${name}`); closeSmsModal(); }
 else showToast(data.message || 'Failed to send SMS', true);
 } catch (err) {
 console.error(err); showToast('Network error', true);
 } finally {
 if (btnSend) { btnSend.disabled = false; btnSend.innerHTML = '<span class="material-symbols-outlined text-[15px]">send</span><span>Send SMS</span>'; }
 }
 };

 // ── Checkout modal ────────────────────────────────────────
 const checkoutModal = document.getElementById('checkout-modal');
 let pendingCheckout = null;

 window.wireCheckoutDirect = function (name, phone, badge) {
 pendingCheckout = { name, phone, badge };
 document.getElementById('checkout-text').textContent = `${name} — ${badge?.toUpperCase().slice(0, 8) || 'VIS-001'}`;
 checkoutModal?.classList.remove('hidden');
 };

 document.getElementById('checkout-cancel')?.addEventListener('click', () => checkoutModal?.classList.add('hidden'));
 document.getElementById('checkout-confirm')?.addEventListener('click', async () => {
 if (!pendingCheckout) return;
 const name = pendingCheckout.name, phone = pendingCheckout.phone, badge = pendingCheckout.badge;
 checkoutModal?.classList.add('hidden');
 showToast(`Checking out ${name}...`);
 try {
   const res = await fetch('{{ route('reception.api.checkout') }}', {
     method: 'POST',
     headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
     body: JSON.stringify({ visitor: name, visitor_phone: phone, badge: badge })
   });
   const data = await res.json();
   if (data.sms?.sent) showToast(data.message || `${name} checked out — SMS sent ✅`);
   else showToast(data.message || `${name} checked out`, !data.sms?.sent);
   setTimeout(()=> location.reload(), 1200);
 } catch(e){ showToast('Checkout failed: '+(e.message||'error'), true); }
 });

 // ── Check-in modal ────────────────────────────────────────
 const walkinModal = document.getElementById('vis-walkin-modal');
 const fastModal = document.getElementById('fastpass-modal');

 window.openWalkinModal = () => { walkinModal?.classList.remove('hidden'); setTimeout(() => document.getElementById('vis-name')?.focus(), 50); };
 window.closeWalkinModal = () => walkinModal?.classList.add('hidden');
 window.openFastPassModal = () => { fastModal?.classList.remove('hidden'); setTimeout(() => document.getElementById('fastpass-qr')?.focus(), 50); };
 window.closeFastPassModal = () => fastModal?.classList.add('hidden');

 document.getElementById('btn-walkin')?.addEventListener('click', window.openWalkinModal);
 document.getElementById('vis-walkin-close')?.addEventListener('click', window.closeWalkinModal);
 document.getElementById('vis-walkin-cancel')?.addEventListener('click', window.closeWalkinModal);
 document.getElementById('vis-walkin-backdrop')?.addEventListener('click', window.closeWalkinModal);
 document.getElementById('btn-fast-pass')?.addEventListener('click', window.openFastPassModal);
 document.getElementById('fastpass-close')?.addEventListener('click', window.closeFastPassModal);
 document.getElementById('fastpass-backdrop')?.addEventListener('click', window.closeFastPassModal);
 document.getElementById('fastpass-lookup')?.addEventListener('click', () => document.getElementById('fastpass-result')?.classList.remove('hidden'));
 document.getElementById('fastpass-checkin')?.addEventListener('click', () => { window.closeFastPassModal(); showToast('Customer checked in'); });

 document.getElementById('vis-walkin-form')?.addEventListener('submit', async e => {
 e.preventDefault();
 const name = document.getElementById('vis-name')?.value?.trim();
 const phone = document.getElementById('vis-phone')?.value?.trim();
 const purpose = document.getElementById('vis-purpose')?.value;
 const host = document.getElementById('vis-host')?.value?.trim();
 if (!name || !phone) { showToast('Name & phone required', true); return; }
 try {
   const res = await fetch('{{ route('reception.api.checkin') }}', {
     method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content||''},
     body: JSON.stringify({ visitor: name, visitor_phone: phone, purpose: purpose, host: host })
   });
   const data = await res.json();
   if(!res.ok) throw new Error(data.message||'Check-in failed');
   window.closeWalkinModal();
   showToast(data.message || `${name} checked in — SMS ${data.sms?.sent ? 'sent' : 'queued'}`);
   setTimeout(()=> location.reload(), 1000);
 } catch(err){ showToast(err.message||'Check-in failed', true); }
 document.getElementById('vis-walkin-form')?.reset();
 });

 // ── Keyboard shortcuts ────────────────────────────────────
 document.addEventListener('keydown', e => {
 if (e.key === 'Escape') {
 window.closeCustomerDrawer();
 window.closeWalkinModal();
 window.closeFastPassModal();
 window.closeSmsModal();
 checkoutModal?.classList.add('hidden');
 }
 if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
 e.preventDefault();
 searchEl?.focus(); searchEl?.select();
 }
 });

 // ── Init ──────────────────────────────────────────────────
 applyFilters();
})();
</script>

<x-reception.customer-ticket-modal :departments="$departments ?? collect()" :employees="$employees ?? collect()" />
@endsection
