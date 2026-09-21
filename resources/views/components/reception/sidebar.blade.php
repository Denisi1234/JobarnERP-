@php
$user = auth()->user();
$isAdmin = false;
$role = $user->role ?? 'reception';
$isReception = request()->routeIs('reception.*');
$isIt = request()->routeIs('it.*');
$isSales = request()->routeIs('sales.*');
$isManager = request()->routeIs('manager.*');

$receptionNav = [
 ['route' => 'reception.dashboard', 'icon' => 'dashboard', 'label' => __('reception.dashboard'), 'pattern' => 'reception.dashboard'],
 ['route' => 'reception.visitors', 'icon' => 'badge', 'label' => __('reception.visitors_badges'), 'pattern' => 'reception.visitors*'],
 ['route' => 'reception.directory', 'icon' => 'contacts', 'label' => __('reception.staff_directory'), 'pattern' => 'reception.directory*'],
 ['route' => 'reception.tasks', 'icon' => 'checklist', 'label' => __('reception.reception_tasks'), 'pattern' => 'reception.tasks*'],
 ['route' => 'reception.logbook', 'icon' => 'edit_note', 'label' => 'My Logbook', 'pattern' => 'reception.logbook'],
 ['route' => 'reception.deliveries', 'icon' => 'inventory_2', 'label' => __('reception.deliveries_packages'), 'pattern' => 'reception.deliveries*'],
 ['route' => 'reception.settings', 'icon' => 'settings', 'label' => __('reception.system_settings'), 'pattern' => 'reception.settings*'],
];
$itFilter = request()->get('filter', request()->get('status', (request()->get('assigned') === 'me' ? 'my_assigned' : '')));
$itNav = [
 ['url' => route('it.index'), 'icon' => 'dashboard', 'label' => 'Service Desk', 'active' => empty($itFilter) && !request()->has('view') && request()->routeIs('it.index')],
 ['url' => route('it.index', ['filter' => 'forwarded']), 'icon' => 'add_circle', 'label' => 'Forwarded Intake', 'active' => in_array($itFilter, ['forwarded', 'new', 'pending'])],
 ['url' => route('it.index', ['filter' => 'active']), 'icon' => 'build', 'label' => 'Active Repairs', 'active' => in_array($itFilter, ['active', 'in_progress', 'accepted'])],
 ['url' => route('it.index', ['filter' => 'my_assigned']), 'icon' => 'assignment_ind', 'label' => 'My Assigned Tasks', 'active' => in_array($itFilter, ['my_assigned', 'me'])],
 ['url' => route('it.index', ['filter' => 'completed']), 'icon' => 'task_alt', 'label' => 'Resolved & Billed', 'active' => in_array($itFilter, ['completed', 'resolved', 'closed'])],
 ['url' => route('it.logbook'), 'icon' => 'edit_note', 'label' => 'My Logbook', 'active' => request()->routeIs('it.logbook')],
];
$salesTab = request()->get('tab', 'dashboard');
$salesNav = [
 ['url' => route('sales.index', ['tab' => 'dashboard']), 'icon' => 'dashboard', 'label' => 'Sales Dashboard', 'active' => request()->routeIs('sales.index') && ($salesTab === 'dashboard' || empty($salesTab) || $salesTab === 'pos') && !request()->routeIs('sales.history')],
 ['url' => route('sales.index', ['tab' => 'register']), 'icon' => 'point_of_sale', 'label' => 'POS Terminal', 'active' => request()->routeIs('sales.index') && ($salesTab === 'register' || $salesTab === 'orders') && !request()->routeIs('sales.history')],
 ['url' => route('sales.history'), 'icon' => 'history', 'label' => 'Sales History', 'active' => request()->routeIs('sales.history')],
 ['url' => route('sales.inventory'), 'icon' => 'inventory_2', 'label' => 'Inventory', 'active' => request()->routeIs('sales.inventory') || $salesTab === 'products' || $salesTab === 'inventory' || $salesTab === 'stock'],
 ['url' => route('sales.suppliers.index'), 'icon' => 'local_shipping', 'label' => 'Suppliers', 'active' => request()->routeIs('sales.suppliers.*')],
 ['url' => route('sales.debits.index'), 'icon' => 'storefront', 'label' => 'Showroom Debits', 'active' => request()->routeIs('sales.debits.*')],
 ['url' => route('sales.tasks.index'), 'icon' => 'task', 'label' => 'Tasks', 'active' => request()->routeIs('sales.tasks.*')],
 ['url' => route('sales.logbook'), 'icon' => 'edit_note', 'label' => 'My Logbook', 'active' => request()->routeIs('sales.logbook')],
];
$managerNav = [
 ['url' => route('manager.index'), 'icon' => 'analytics', 'label' => 'Executive Overview', 'active' => request()->routeIs('manager.index') && !request()->routeIs('manager.tasks*') && !request()->routeIs('manager.audit')],
 ['url' => route('manager.audit'), 'icon' => 'receipt_long', 'label' => 'Audit Log', 'active' => request()->routeIs('manager.audit')],

 ['url' => route('manager.approvals'), 'icon' => 'fact_check', 'label' => 'Approval Center', 'active' => request()->routeIs('manager.approvals*')],
 ['url' => route('manager.staff'), 'icon' => 'groups', 'label' => 'Staff & Access', 'active' => request()->routeIs('manager.staff*')],
 ['url' => route('manager.tasks'), 'icon' => 'task', 'label' => 'Work Management', 'active' => request()->routeIs('manager.tasks*') && request()->get('section')===null && request()->get('view')!=='calendar'],
 ['url' => route('manager.tasks', ['section' => 'pending_review']), 'icon' => 'rate_review', 'label' => 'Pending Review', 'active' => request()->get('section')==='pending_review'],
 ['url' => route('manager.tasks', ['view' => 'calendar']), 'icon' => 'calendar_month', 'label' => 'Calendar', 'active' => request()->get('view')==='calendar'],
 ['url' => route('manager.tasks', ['section' => 'reports']), 'icon' => 'monitoring', 'label' => 'Reports', 'active' => request()->get('section')==='reports'],
 ['url' => route('manager.logbooks'), 'icon' => 'edit_note', 'label' => 'Department Logbooks', 'active' => request()->routeIs('manager.logbooks')],
];
// Shared: all portals for quick switcher (professional)
$allPortals = [
 ['label' => 'Reception', 'icon' => 'support_agent', 'route' => route('reception.dashboard'), 'active' => $isReception, 'desc' => 'FrontDesk OS'],
 ['label' => 'IT Service', 'icon' => 'computer', 'route' => route('it.index'), 'active' => $isIt, 'desc' => 'Service Desk'],
 ['label' => 'Sales', 'icon' => 'point_of_sale', 'route' => route('sales.index'), 'active' => $isSales, 'desc' => 'Sales & Invoicing'],
 ['label' => 'Manager', 'icon' => 'admin_panel_settings', 'route' => route('manager.index'), 'active' => $isManager, 'desc' => 'Executive'],
];
@endphp

{{-- Professional Shared Sidebar — Carbon Production Standard — Gray 10 — IBM Plex — 0 radius --}}
<style>
  .cds-sidebar { font-family: 'IBM Plex Sans', 'Helvetica Neue', Arial, sans-serif; }
  .cds-sidebar * { border-radius: 0 !important; }
  .cds-sidebar__item { transition: background 70ms cubic-bezier(0.2,0,0.38,0.9), border-color 70ms, color 70ms; }
  .cds-sidebar__item--active { background: #e0e0e0 !important; color: #161616 !important; border-left-color: #0f62fe !important; font-weight: 600; }
</style>
<aside class="cds-sidebar fixed left-0 top-0 bottom-0 w-64 bg-white border-r border-[#e0e0e0] z-40 flex flex-col overflow-hidden" style="top:0;">
  @if($isSales || $isIt || $isReception)
  {{-- Sales/IT/Reception — no workspace switcher per request — clean sidebar (Manager only) --}}
  @else
  <div style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; background:#f4f4f4;">
    <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Workspaces</div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-top:0.5rem;">
      @foreach($allPortals as $p)
      <a href="{{ $p['route'] }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.25rem; padding:0.5rem 0.25rem; background: {{ $p['active'] ? '#0f62fe' : '#ffffff' }}; color: {{ $p['active'] ? '#ffffff' : '#525252' }}; border:1px solid {{ $p['active'] ? '#0f62fe' : '#e0e0e0' }}; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">
        <span class="material-symbols-outlined" style="font-size:18px; color: {{ $p['active'] ? '#ffffff' : '#0f62fe' }};">{{ $p['icon'] }}</span>
        <span style="font-size:0.6875rem; font-weight:600; line-height:1;">{{ $p['label'] }}</span>
        <span style="font-size:0.625rem; color: {{ $p['active'] ? '#d0e2ff' : '#8d8d8d' }}; line-height:1;">{{ $p['desc'] }}</span>
      </a>
      @endforeach
    </div>
  </div>
  @endif

  @php $msgUnread = 0; try { $msgUnread = \App\Models\Message::forUser($user)->where('is_read', false)->count(); } catch(\Throwable $e){} $msgRoute = match(true){ $isReception=>route('reception.messages'), $isIt=>route('it.messages'), $isSales=>route('sales.messages'), $isManager=>route('manager.messages'), default=>route('messages.index')}; $msgActive = request()->routeIs('*.messages') || request()->routeIs('messages.*'); $isAdminUser = ($user->is_admin ?? false); @endphp
  <div style="flex:1; overflow-y:auto; padding:0.5rem 0;">
    @if($isAdminUser)
    <nav style="padding:0 0.5rem; margin-bottom:0.5rem">
      <a href="/admin" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.75rem; font-size:0.875rem; text-decoration:none; border-left:3px solid #f59e0b; background:#fffbeb; color:#92400e; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">
        <span class="material-symbols-outlined" style="font-size:18px; color:#d97706;">admin_panel_settings</span>
        <span style="flex:1;">System Admin</span>
        <span style="background:#f59e0b; color:#fff; font-size:0.625rem; padding:0.125rem 0.375rem; font-weight:700;">/admin</span>
      </a>
    </nav>
    <div style="height:1px; background:#f59e0b; margin:0 0.5rem 0.5rem; opacity:0.3"></div>
    @endif
    {{-- Messages — each portal own inbox --}}
    <nav style="padding:0 0.5rem; margin-bottom:0.5rem">
      <a href="{{ $msgRoute }}" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.75rem; font-size:0.875rem; text-decoration:none; border-left:3px solid {{ $msgActive ? '#0f62fe' : 'transparent' }}; background:{{ $msgActive ? '#e0e0e0' : '#edf5ff' }}; color:#161616; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">
        <span class="material-symbols-outlined" style="font-size:18px; color:#0f62fe;">mail</span>
        <span style="flex:1;">Messages</span>
        @if($msgUnread>0)<span style="background:#da1e28; color:#fff; font-size:0.68rem; padding:0.15rem 0.4rem; font-weight:700; min-width:1.1rem; text-align:center">{{ $msgUnread }}</span>@else<span style="background:#e0e0e0; color:#525252; font-size:0.68rem; padding:0.15rem 0.4rem;">0</span>@endif
      </a>
    </nav>
    <div style="height:1px; background:#e0e0e0; margin:0 0.5rem 0.5rem"></div>
    {{-- 1. Current Portal — Expanded — Professional Carbon --}}
    @if($isReception)
      <div style="padding:0.5rem 1rem 0.25rem 1rem; font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Reception — Current</div>
      <nav style="display:flex; flex-direction:column; gap:1px; padding:0 0.5rem;">
        @foreach($receptionNav as $item)
          @php $activeSub = request()->routeIs($item['pattern']); @endphp
          <a href="{{ route($item['route']) }}" @if($activeSub) aria-current="page" @endif style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.75rem; font-size:0.875rem; text-decoration:none; border-left:3px solid {{ $activeSub ? '#0f62fe' : 'transparent' }}; background: {{ $activeSub ? '#e0e0e0' : 'transparent' }}; color: {{ $activeSub ? '#161616' : '#525252' }}; font-weight: {{ $activeSub ? '600' : '400' }}; font-family:'IBM Plex Sans',sans-serif;">
            <span class="material-symbols-outlined" style="font-size:18px; color: {{ $activeSub ? '#161616' : '#525252' }};">{{ $item['icon'] }}</span>
            <span style="flex:1;">{{ $item['label'] }}</span>
            @if($activeSub)<span style="width:0.5rem; height:0.5rem; background:#0f62fe;"></span>@endif
          </a>
        @endforeach
      </nav>
    @elseif($isIt)
      <div style="padding:0.5rem 1rem 0.25rem 1rem; font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">IT — Current</div>
      <nav style="display:flex; flex-direction:column; gap:1px; padding:0 0.5rem;">
        @foreach($itNav as $item)
          <a href="{{ $item['url'] }}" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.75rem; font-size:0.875rem; text-decoration:none; border-left:3px solid {{ $item['active'] ? '#0f62fe' : 'transparent' }}; background: {{ $item['active'] ? '#e0e0e0' : 'transparent' }}; color: {{ $item['active'] ? '#161616' : '#525252' }}; font-weight: {{ $item['active'] ? '600' : '400' }}; font-family:'IBM Plex Sans',sans-serif;">
            <span class="material-symbols-outlined" style="font-size:18px; color: {{ $item['active'] ? '#161616' : '#525252' }};">{{ $item['icon'] }}</span>
            <span>{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>
    @elseif($isSales)
      <nav style="display:flex; flex-direction:column; gap:1px; padding:0.5rem 0.5rem;">
        @foreach($salesNav as $item)
          <a href="{{ $item['url'] }}" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.75rem; font-size:0.875rem; text-decoration:none; border-left:3px solid {{ $item['active'] ? '#0f62fe' : 'transparent' }}; background: {{ $item['active'] ? '#e0e0e0' : 'transparent' }}; color: {{ $item['active'] ? '#161616' : '#525252' }}; font-weight: {{ $item['active'] ? '600' : '400' }}; font-family:'IBM Plex Sans',sans-serif;">
            <span class="material-symbols-outlined" style="font-size:18px; color: {{ $item['active'] ? '#161616' : '#525252' }};">{{ $item['icon'] }}</span>
            <span style="flex:1;">{{ $item['label'] }}</span>
            @if($item['active'])<span style="width:0.5rem; height:0.5rem; background:#0f62fe;"></span>@endif
          </a>
        @endforeach
      </nav>
    @elseif($isManager)
      <div style="padding:0.5rem 1rem 0.25rem 1rem; font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#525252;">Manager — Current</div>
      <nav style="display:flex; flex-direction:column; gap:1px; padding:0 0.5rem;">
        @foreach($managerNav as $item)
          <a href="{{ $item['url'] }}" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.75rem; font-size:0.875rem; text-decoration:none; border-left:3px solid {{ $item['active'] ? '#0f62fe' : 'transparent' }}; background: {{ $item['active'] ? '#e0e0e0' : 'transparent' }}; color: {{ $item['active'] ? '#161616' : '#525252' }}; font-weight: {{ $item['active'] ? '600' : '400' }}; font-family:'IBM Plex Sans',sans-serif;">
            <span class="material-symbols-outlined" style="font-size:18px; color: {{ $item['active'] ? '#161616' : '#525252' }};">{{ $item['icon'] }}</span>
            <span>{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>
    @endif


  </div>

  {{-- Footer — Production --}}
  <div style="border-top:1px solid #e0e0e0; padding:0.75rem 1rem; background:#f4f4f4;">
    <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">
      <span style="width:2rem; height:2rem; background:#525252; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:0.75rem;">{{ strtoupper(substr($user->name ?? 'U',0,1)) }}</span>
      <div style="flex:1; min-width:0;">
        <div style="font-weight:600; color:#161616; font-size:0.8125rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->name ?? 'User' }}</div>
        <div style="font-size:0.6875rem; color:#6a6d70; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->email ?? '' }}</div>
      </div>
    </div>
    <div style="margin-top:0.5rem; display:flex; gap:0.375rem;">
      <a href="#" style="flex:1; text-align:center; padding:0.375rem; background:#fff; border:1px solid #e0e0e0; font-size:0.6875rem; color:#525252; text-decoration:none; font-family:'IBM Plex Sans',sans-serif;">Settings</a>
      <form method="POST" action="{{ route('logout') }}" style="flex:1;">@csrf<button type="submit" style="width:100%; padding:0.375rem; background:#fff; border:1px solid #e0e0e0; font-size:0.6875rem; color:#525252; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Logout</button></form>
    </div>
  </div>
</aside>
