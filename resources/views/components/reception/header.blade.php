@php
 $currentRole = auth()->user()->role ?? 'reception';
 $isAdmin = in_array($currentRole, ['admin', 'manager']);
 $portalHome = match(true) {
 request()->routeIs('it.*') => route('it.index'),
 request()->routeIs('sales.*') => route('sales.index'),
 request()->routeIs('manager.*') => route('manager.index'),
 default => route('reception.dashboard'),
 };
 $portalLabel = match(true) {
 request()->routeIs('it.*') => 'IT Support',
 request()->routeIs('sales.*') => 'Sales & Billing',
 request()->routeIs('manager.*') => 'Manager',
 default => 'Reception Desk',
 };
@endphp
<header class="fixed top-0 left-0 right-0 h-20 bg-white border-b border-slate-300 z-50 px-6 flex items-center justify-between">
 <div class="flex items-center gap-4">
 <a href="{{ $portalHome }}" class="flex items-center gap-2">
 <img alt="Jobarn Logo" class="h-8 w-auto object-contain" src="{{ asset('logo.svg') }}" />
 </a>
 </div>

 <div class="flex-1 max-w-xl mx-6 hidden lg:block relative">
 <div class="relative flex items-center">
 <span class="material-symbols-outlined absolute left-3 text-slate-500 text-[18px]">search</span>
 <input
 class="w-full bg-white border border-slate-300 pl-10 pr-16 py-2.5 text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-all"
 placeholder="Search visitor, employee, IT ticket…"
 type="text"
 id="global-search"
 autocomplete="off"
 />
 <div class="absolute right-2 flex items-center gap-1">
 <kbd class="hidden sm:inline-flex font-mono bg-slate-50 border border-slate-300 text-slate-600 px-1.5 py-0.5 text-[10px] font-bold">⌘K</kbd>
 <span id="search-spinner" class="hidden w-4 h-4 border-2 border-slate-300 border-t-slate-900 animate-spin" style="border-radius:9999px"></span>
 </div>
 </div>
 {{-- Live dropdown — legacy clean card --}}
 <div id="search-dropdown" class="hidden absolute top-full left-0 right-0 mt-2 bg-white border border-slate-300 none overflow-hidden z-50">
 <div id="search-results" class="max-h-[380px] overflow-y-auto divide-y divide-slate-200"></div>
 <div id="search-empty" class="hidden p-4 text-center text-xs text-slate-500">No results — try visitor name, phone, employee, or ticket.</div>
 <div class="px-3 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
 <span class="font-mono text-[11px] text-slate-500">↵ Go • Esc close</span>
 <a href="{{ route('reception.visitors') }}" class="text-[11px] font-bold text-slate-900 hover:underline">View all visitors →</a>
 </div>
 </div>
 </div>

 <div class="flex items-center gap-3">
 <!-- Real Database Notification Bell & Dropdown — live working -->
 @php
 $authUser = auth()->user();
 $unreadNotifications = $authUser ? $authUser->unreadNotifications()->take(6)->get() : collect();
 $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
 $allRecentNotifications = $authUser ? $authUser->notifications()->take(6)->get() : collect();
 @endphp
 <div class="relative">
 <button id="notif-menu-btn" type="button" class="relative flex w-9 h-9 items-center justify-center text-slate-600 hover:bg-slate-50 hover:text-slate-900 border border-transparent hover:border-slate-300 transition-all cursor-pointer {{ request()->routeIs('notifications.*') ? 'bg-slate-900 text-white border-slate-900' : '' }}" aria-label="Notifications" title="Notifications — live">
 <span class="material-symbols-outlined text-[20px]">notifications</span>
 <span id="notif-badge" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-600 text-white text-[10px] font-bold flex items-center justify-center border border-white {{ $unreadCount > 0 ? '' : 'hidden' }}">
 {{ $unreadCount > 99 ? '99+' : $unreadCount }}
 </span>
 <span id="notif-live-dot" class="hidden absolute -top-1 -right-1 w-2 h-2 bg-emerald-500 border border-white"></span>
 </button>

 <!-- Notifications Dropdown — legacy clean card, live -->
 <div id="notif-menu-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-slate-300 z-50 overflow-hidden">
 <div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between border-b border-slate-900">
 <div class="flex items-center gap-2">
 <span class="text-xs font-bold uppercase tracking-[0.12em]">Notifications</span>
 <span id="notif-header-count" class="px-1.5 py-0.5 text-[10px] font-bold bg-red-600 text-white {{ $unreadCount > 0 ? '' : 'hidden' }}">{{ $unreadCount }} new</span>
 <span id="notif-live-pulse" class="hidden w-1.5 h-1.5 bg-emerald-400 animate-pulse"></span>
 </div>
 <div class="flex items-center gap-2">
 <span id="notif-last-sync" class="text-[10px] text-slate-400 font-mono hidden"></span>
 <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
 @csrf
 <button type="submit" class="text-[11px] text-slate-300 hover:text-white underline">Mark all read</button>
 </form>
 </div>
 </div>
 <div id="notif-live-counts" class="px-3 py-2 bg-slate-50 border-b border-slate-200 hidden">
 <div class="flex items-center gap-2 text-[11px] font-medium text-slate-700">
 <span class="inline-flex items-center gap-1 border border-slate-300 bg-white px-2 py-1 text-[10px] font-bold"><span class="w-1.5 h-1.5 bg-amber-500"></span><span id="notif-count-it">0 IT</span></span>
 <span class="inline-flex items-center gap-1 border border-slate-300 bg-white px-2 py-1 text-[10px] font-bold"><span class="w-1.5 h-1.5 bg-slate-900"></span><span id="notif-count-sales">0 Sales</span></span>
 <span class="inline-flex items-center gap-1 border border-slate-300 bg-white px-2 py-1 text-[10px] font-bold"><span class="w-1.5 h-1.5 bg-slate-500"></span><span id="notif-count-tasks">0 Tasks</span></span>
 </div>
 </div>

 <div id="notif-dropdown-list" class="max-h-[360px] overflow-y-auto divide-y divide-slate-200">
 @forelse($allRecentNotifications as $n)
 @php
 $isUnread = is_null($n->read_at);
 $kind = $n->data['kind'] ?? 'system';
 $icon = match($kind) {
 'it_ticket_created' => 'dns',
 'it_ticket_reassigned' => 'person_add',
 'it_ticket_resolved' => 'verified',
 'it_ticket_returned' => 'replay',
 'logbook_submitted' => 'menu_book',
 'overdue' => 'warning',
 'completed' => 'task_alt',
 'verified' => 'verified',
 'submitted' => 'approval',
 'returned' => 'assignment_return',
 'declined' => 'do_not_disturb_on',
 default => 'notifications',
 };
 $iconColor = match($kind) {
 'it_ticket_created', 'logbook_submitted' => 'text-blue-600',
 'it_ticket_resolved', 'completed', 'verified' => 'text-emerald-600',
 'it_ticket_returned', 'returned', 'overdue' => 'text-amber-600',
 'it_ticket_reassigned' => 'text-indigo-600',
 default => 'text-slate-600',
 };
 @endphp
 <a href="{{ route('notifications.read', $n->id) }}" class="flex items-start gap-3 px-4 py-3 text-left transition-colors {{ $isUnread ? 'bg-slate-50 hover:bg-slate-100 font-medium' : 'bg-white hover:bg-slate-50 opacity-80' }}">
 <span class="w-7 h-7 mt-0.5 border border-slate-200 bg-white flex items-center justify-center shrink-0">
 <span class="material-symbols-outlined text-[16px] {{ $iconColor }}">{{ $icon }}</span>
 </span>
 <div class="flex-1 min-w-0">
 <div class="flex items-center justify-between gap-1">
 <span class="text-xs text-slate-900 truncate {{ $isUnread ? 'font-semibold' : 'font-normal' }}">{{ $n->data['title'] ?? 'Notification' }}</span>
 @if($isUnread)
 <span class="w-1.5 h-1.5 rounded-full bg-blue-600 shrink-0"></span>
 @endif
 </div>
 <p class="text-[11px] text-slate-600 truncate mt-0.5">{{ $n->data['message'] ?? '' }}</p>
 <span class="text-[10px] text-slate-400 block mt-1">{{ $n->created_at->diffForHumans() }}</span>
 </div>
 </a>
 @empty
 <div class="py-8 px-4 text-center">
 <span class="material-symbols-outlined text-slate-300 text-[28px] mb-1">notifications_off</span>
 <p class="text-xs text-slate-500 font-medium">No notifications yet</p>
 <p class="text-[11px] text-slate-400 mt-0.5">Real alerts for tickets, handovers & logbooks appear here</p>
 </div>
 @endforelse
 </div>

 <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-center">
 <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-slate-900 hover:text-blue-600 flex items-center justify-center gap-1">
 <span>View all notifications</span>
 <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
 </a>
 </div>
 </div>
 </div>

 <div class="h-6 w-px bg-slate-300 hidden sm:block"></div>

 <!-- User Profile & Portal Switcher Dropdown — legacy clean -->
 <div class="relative">
 <button id="user-menu-btn" type="button" class="flex items-center gap-3 p-1 border border-transparent hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer">
 <div class="text-right hidden sm:block">
 <div class="text-xs font-bold text-slate-900 leading-tight">
 {{ auth()->user()->name ?? 'Aisha Mwinyi' }}
 </div>
 <div class="text-[11px] text-slate-500 leading-tight capitalize">
 {{ auth()->user()->role ?? 'Reception' }} Portal
 </div>
 </div>
 <div class="w-8 h-8 bg-slate-900 flex items-center justify-center text-white shrink-0 font-bold text-[13px] border border-slate-900">
 {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
 </div>
 <span class="material-symbols-outlined text-slate-500 text-[18px]">expand_more</span>
 </button>

 <!-- Dropdown Menu — legacy sharp -->
 <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-64 bg-white border border-slate-300 py-2 z-50">
 <div class="px-4 py-2.5 border-b border-slate-200">
 <div class="font-bold text-slate-900 text-[13px] truncate">{{ auth()->user()->name ?? 'Aisha Mwinyi' }}</div>
 <div class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email ?? 'reception@jobarn.co.tz' }}</div>
 <div class="mt-1.5">
 <span class="inline-flex px-2 py-0.5 border border-slate-300 bg-white text-[10px] font-mono font-bold text-slate-700 uppercase">
 Role: {{ auth()->user()->role ?? 'Reception' }}
 </span>
 </div>
 </div>

 @if($isAdmin)
 <!-- Fast Portal Switcher (Admins Only) -->
 <div class="px-2 py-2 border-b border-outline-variant/20">
 <div class="px-2 pb-1 text-[10px] uppercase font-semibold text-slate-900-variant">Switch Department Portal</div>
 <div class="flex flex-col gap-0.5 text-[12px]">
 <a href="{{ route('reception.dashboard') }}" class="flex items-center gap-2 px-2.5 py-1.5 hover:bg-white border border-slate-300 transition-colors {{ request()->routeIs('reception.*') ? 'text-slate-700 font-semibold' : 'text-slate-900' }}">
 <span class="material-symbols-outlined text-[16px] text-slate-700">storefront</span>
 <span>Reception Desk</span>
 </a>
 <a href="{{ route('it.index') }}" class="flex items-center gap-2 px-2.5 py-1.5 hover:bg-white border border-slate-300 transition-colors {{ request()->routeIs('it.*') ? 'text-amber-600 font-semibold' : 'text-slate-900' }}">
 <span class="material-symbols-outlined text-[16px] text-amber-600">computer</span>
 <span>IT Service Portal</span>
 </a>
 <a href="{{ route('sales.index') }}" class="flex items-center gap-2 px-2.5 py-1.5 hover:bg-white border border-slate-300 transition-colors {{ request()->routeIs('sales.*') ? 'text-emerald-600 font-semibold' : 'text-slate-900' }}">
 <span class="material-symbols-outlined text-[16px] text-emerald-600">payments</span>
 <span>Sales & Invoicing</span>
 </a>
 <a href="{{ route('manager.index') }}" class="flex items-center gap-2 px-2.5 py-1.5 hover:bg-white border border-slate-300 transition-colors {{ request()->routeIs('manager.*') ? 'text-indigo-600 font-semibold' : 'text-slate-900' }}">
 <span class="material-symbols-outlined text-[16px] text-indigo-600">monitoring</span>
 <span>Executive Manager</span>
 </a>
 <a href="/admin" class="flex items-center gap-2 px-2.5 py-1.5 hover:bg-white border border-slate-300 transition-colors text-slate-700">
 <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
 <span>Admin Center</span>
 </a>
 </div>
 </div>
 @endif

 <!-- Sign out -->
 <div class="px-2 pt-1">
 <form method="POST" action="/logout">
 @csrf
 <button type="submit" class="w-full flex items-center gap-2 px-2.5 py-2 text-[12px] font-medium text-error hover:bg-error-container/20 transition-colors">
 <span class="material-symbols-outlined text-[16px]">logout</span>
 <span>Sign out</span>
 </button>
 </form>
 </div>
 </div>
 </div>
 </div>
</header>

@push('scripts')
<script>
// Keyboard shortcut for search
document.addEventListener('keydown', (e) => {
 if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
 e.preventDefault();
 document.getElementById('global-search')?.focus();
 }
});

// Dropdown user menu toggle
(function(){
 const btn = document.getElementById('user-menu-btn');
 const dropdown = document.getElementById('user-menu-dropdown');
 if(!btn || !dropdown) return;

 btn.addEventListener('click', (e) => {
 e.stopPropagation();
 // Close notification dropdown if open
 document.getElementById('notif-menu-dropdown')?.classList.add('hidden');
 dropdown.classList.toggle('hidden');
 });

 document.addEventListener('click', (e) => {
 if(!dropdown.contains(e.target) && !btn.contains(e.target)){
 dropdown.classList.add('hidden');
 }
 });
})();

// Dropdown notification menu toggle
(function(){
 const btn = document.getElementById('notif-menu-btn');
 const dropdown = document.getElementById('notif-menu-dropdown');
 if(!btn || !dropdown) return;

 btn.addEventListener('click', (e) => {
 e.stopPropagation();
 // Close user dropdown if open
 document.getElementById('user-menu-dropdown')?.classList.add('hidden');
 dropdown.classList.toggle('hidden');
 });

 document.addEventListener('click', (e) => {
 if(!dropdown.contains(e.target) && !btn.contains(e.target)){
 dropdown.classList.add('hidden');
 }
 });
})();

// Global Search AJAX
(function(){
 const input = document.getElementById('global-search');
 const dd = document.getElementById('search-dropdown');
 const resultsEl = document.getElementById('search-results');
 const emptyEl = document.getElementById('search-empty');
 const spinner = document.getElementById('search-spinner');
 if(!input || !dd) return;
 let timer=null, lastQ='';
 const url ="{{ route('reception.api.search') }}";
 function hide(){ dd.classList.add('hidden'); }
 function show(){ dd.classList.remove('hidden'); }
 function render(items, q){
 resultsEl.innerHTML='';
 if(!items.length){ emptyEl.classList.remove('hidden'); show(); return; }
 emptyEl.classList.add('hidden');
 items.forEach(r=>{
 const div=document.createElement('a');
 div.href=r.url;
 div.className='flex items-center gap-3 px-4 py-3 hover:bg-slate-50 border-b border-slate-200 last:border-0';
 div.innerHTML=`<span class="w-8 h-8 bg-white border border-slate-300 flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-[16px] text-slate-700">${r.icon}</span></span><div class="flex-1 min-w-0"><div class="font-label-lg text-slate-900 truncate">${r.title}</div><div class="font-body-sm text-slate-900-variant truncate">${r.subtitle||''}</div></div><div class="text-right shrink-0"><div class="font-label-mono text-[11px] text-slate-900-variant">${r.meta||''}</div>${r.status?`<span class="inline-flex px-1.5 py-0.5 bg-tertiary-fixed text-on-tertiary-fixed text-[10px]">${r.status}</span>`:''}</div>`;
 resultsEl.appendChild(div);
 });
 show();
 }
 async function search(q){
 if(q.length<2){ hide(); return; }
 if(q===lastQ) return;
 lastQ=q;
 spinner?.classList.remove('hidden');
 try{
 const res=await fetch(url+'?q='+encodeURIComponent(q),{headers:{'Accept':'application/json'}});
 const data=await res.json();
 render(data.results||[], q);
 }catch(err){
 console.error(err);
 }finally{
 spinner?.classList.add('hidden');
 }
 }
 input.addEventListener('input', (e)=>{
 clearTimeout(timer);
 const q=e.target.value.trim();
 timer=setTimeout(()=>search(q), 250);
 });
 input.addEventListener('focus', ()=>{
 if(resultsEl.children.length>0) show();
 });
 document.addEventListener('click', (e)=>{
 if(!input.contains(e.target) && !dd.contains(e.target)) hide();
 });
})();

// Real working live header notifications — polls /api/notifications/live every 5s
(function(){
 const badge = document.getElementById('notif-badge');
 const headerCount = document.getElementById('notif-header-count');
 const liveDot = document.getElementById('notif-live-dot');
 const livePulse = document.getElementById('notif-live-pulse');
 const lastSync = document.getElementById('notif-last-sync');
 const countsWrap = document.getElementById('notif-live-counts');
 const countIt = document.getElementById('notif-count-it');
 const countSales = document.getElementById('notif-count-sales');
 const countTasks = document.getElementById('notif-count-tasks');
 const url ="{{ route('notifications.live') }}";
 let lastUnread = -1;
 async function fetchLive(){
 try{
 const res = await fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
 if(!res.ok) return;
 const data = await res.json();
 const unread = data.unread ?? 0;
 const totalBadge = data.totalBadge ?? unread;
 if(badge){
 if(totalBadge > 0){
 badge.textContent = totalBadge > 99 ? '99+' : totalBadge;
 badge.classList.remove('hidden');
 if(unread > 0) badge.classList.add('bg-red-600');
 else badge.classList.remove('bg-red-600'), badge.classList.add('bg-slate-900');
 } else {
 badge.classList.add('hidden');
 }
 }
 if(headerCount){
 if(unread > 0){ headerCount.textContent = unread + ' new'; headerCount.classList.remove('hidden'); }
 else headerCount.classList.add('hidden');
 }
 if(liveDot) liveDot.classList.remove('hidden');
 if(livePulse) livePulse.classList.remove('hidden');
 if(lastSync){ lastSync.textContent = new Date().toLocaleTimeString(); lastSync.classList.remove('hidden'); }
 const c = data.counts || {};
 if(countsWrap){
 if((c.pendingIt||0) >0 || (c.salesQueue||0)>0 || (c.pendingTasks||0)>0) countsWrap.classList.remove('hidden');
 }
 if(countIt) countIt.textContent = (c.pendingIt||0) + ' IT';
 if(countSales) countSales.textContent = (c.salesQueue||0) + ' Sales';
 if(countTasks) countTasks.textContent = (c.pendingTasks||0) + ' Tasks';
 // Render recent notifications live
 const listEl = document.getElementById('notif-dropdown-list');
 if(listEl && data.recent){
 if(data.recent.length === 0){
 listEl.innerHTML = `<div class="py-8 px-4 text-center"><span class="material-symbols-outlined text-slate-300 text-[28px] mb-1">notifications_off</span><p class="text-xs text-slate-500 font-bold">No notifications yet</p><p class="text-[11px] text-slate-400 mt-0.5">Real alerts appear here</p></div>`;
 } else {
 const iconMap = {it_ticket_created:'dns',it_ticket_reassigned:'person_add',it_ticket_resolved:'verified',it_ticket_returned:'replay',logbook_submitted:'menu_book',overdue:'warning',completed:'task_alt',verified:'verified',submitted:'approval',returned:'assignment_return',declined:'do_not_disturb_on'};
 const colorMap = {it_ticket_created:'text-blue-600',logbook_submitted:'text-blue-600',it_ticket_resolved:'text-emerald-600',completed:'text-emerald-600',verified:'text-emerald-600',it_ticket_returned:'text-amber-600',returned:'text-amber-600',overdue:'text-amber-600',it_ticket_reassigned:'text-indigo-600'};
 listEl.innerHTML = data.recent.map(n=>{
 const isUnread = n.isUnread;
 const kind = n.kind || 'system';
 const icon = iconMap[kind] || 'notifications';
 const color = colorMap[kind] || 'text-slate-600';
 return `<a href="`+n.url+`" class="flex items-start gap-3 px-4 py-3 text-left `+(isUnread?'bg-slate-50 hover:bg-slate-100':'bg-white hover:bg-slate-50 opacity-80')+` border-b border-slate-200 last:border-0"><span class="w-7 h-7 border border-slate-200 bg-white flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-[16px] `+color+`">`+icon+`</span></span><div class="flex-1 min-w-0"><div class="flex items-center justify-between gap-1"><span class="text-xs text-slate-900 truncate `+(isUnread?'font-semibold':'')+`">`+n.title+`</span>`+(isUnread?`<span class="w-1.5 h-1.5 bg-blue-600 shrink-0"></span>`:'')+`</div><p class="text-[11px] text-slate-600 truncate mt-0.5">`+(n.message||'')+`</p><span class="text-[10px] text-slate-400 block mt-1">`+n.time+`</span></div></a>`;
 }).join('');
 }
 }
 if(unread !== lastUnread && unread > lastUnread && lastUnread !== -1){
 badge?.animate?.([{transform:'scale(1)'},{transform:'scale(1.2)'},{transform:'scale(1)'}],{duration:300});
 }
 lastUnread = unread;
 }catch(e){ console.warn('[HeaderLive] ', e); }
 }
 fetchLive();
 setInterval(fetchLive, 5000);
 if(window.LiveHandoff && window.LiveHandoff.subscribe){
 window.LiveHandoff.subscribe(()=> fetchLive());
 }
 })();

</script>
@endpush
