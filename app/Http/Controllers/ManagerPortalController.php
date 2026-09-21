<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\InventoryLog;
use App\Models\PosProduct;
use App\Models\SaleInvoice;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\TaskUpdate;
use App\Models\User;
use App\Models\Visit;
use App\Services\ItService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManagerPortalController extends Controller
{
    public function staff()
    {
        $departments = Department::withCount('employees')->orderBy('name')->get();
        $staff = Employee::with('department')->orderBy('first_name')->paginate(30);
        $rolesByEmail = User::whereIn('email', $staff->getCollection()->pluck('email'))->pluck('role', 'email');
        $staff->getCollection()->each(fn ($employee) => $employee->portal_role = $rolesByEmail[$employee->email] ?? null);
        $portalUsers = User::whereIn('role', ['reception', 'it', 'sales', 'manager'])->orderBy('name')->get();
        return view('manager.staff.index', compact('departments', 'staff', 'portalUsers'));
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100', 'last_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:255|unique:users,email|unique:employees,email',
            'department_id' => 'required|exists:departments,id',
            'role' => 'required|in:reception,it,sales,manager',
            'password' => 'required|string|min:8|confirmed',
        ]);
        DB::transaction(function () use ($data) {
            User::create(['name'=>trim($data['first_name'].' '.($data['last_name'] ?? '')), 'email'=>$data['email'], 'password'=>Hash::make($data['password']), 'role'=>$data['role'], 'is_admin'=>false]);
            Employee::create(['first_name'=>$data['first_name'], 'last_name'=>$data['last_name'] ?? null, 'email'=>$data['email'], 'department_id'=>$data['department_id']]);
        });
        return back()->with('success', 'Staff account created and routed to its department workspace.');
    }

    public function linkStaffAccount(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        $user = User::findOrFail($data['user_id']);
        abort_unless(in_array($user->role, ['reception', 'it', 'sales', 'manager'], true), 422);
        $names = preg_split('/\s+/', trim($user->name), 2);

        Employee::updateOrCreate(
            ['email' => $user->email],
            [
                'first_name' => $names[0] ?: 'Staff',
                'last_name' => $names[1] ?? null,
                'department_id' => $data['department_id'],
            ],
        );

        return back()->with('success', $user->name.' is now linked to the selected department.');
    }
    public function approvals(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'returned', 'rejected'])
            ? $request->query('status') : 'pending';
        $requests = ApprovalRequest::with(['requester', 'department', 'actions.actor'])
            ->where('status', $status)->latest('requested_at')->paginate(20)->withQueryString();
        $counts = ApprovalRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $departments = Department::orderBy('name')->get();

        return view('manager.approvals.index', compact('requests', 'counts', 'status', 'departments'));
    }

    public function storeApproval(Request $request)
    {
        $data = $request->validate([
            'request_type' => 'required|in:quotation,discount,credit_limit,purchase,stock_adjustment,write_off,invoice_cancellation,refund,debit_note,budget_exception',
            'title' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'required|string|max:3000',
        ]);
        $data['requested_by'] = auth()->id();
        ApprovalRequest::create($data);

        return back()->with('success', 'Approval request submitted to the manager queue.');
    }

    public function decideApproval(Request $request, ApprovalRequest $approval)
    {
        abort_unless($approval->status === 'pending', 422, 'This request has already been decided.');
        $data = $request->validate([
            'action' => 'required|in:approved,returned,rejected',
            'comment' => 'nullable|string|max:2000',
        ]);
        if (in_array($data['action'], ['returned', 'rejected']) && blank($data['comment'])) {
            return back()->withErrors(['comment' => 'A comment is required when returning or rejecting a request.']);
        }

        DB::transaction(function () use ($approval, $data) {
            $approval->update(['status' => $data['action'], 'decided_at' => now()]);
            ApprovalAction::create([
                'approval_request_id' => $approval->id,
                'actor_id' => auth()->id(),
                'action' => $data['action'],
                'comment' => $data['comment'] ?? null,
            ]);
        });

        return back()->with('success', 'Approval decision recorded.');
    }

    public function index(ItService $itService)
    {
        // 1. Reception / Visitor Stats
        $today = now()->startOfDay();
        $totalVisitsToday = Visit::where('created_at', '>=', $today)->count();
        $activeVisitors = Visit::whereNull('departure')->count();
        $totalVisitsAllTime = Visit::count();
        $recentVisits = Visit::with('employee.department')->latest()->take(8)->get();

        // 2. IT Service Stats
        $itStats = $itService->stats();
        $itTickets = $itService->allTickets();
        $recentTickets = array_slice($itTickets, 0, 6);

        // 3. Real Live Sales & Billing Stats
        try {
            $invoices = SaleInvoice::with(['itTicket', 'owner'])->latest('id')->get();
            $paidInvoices = $invoices->where('status', 'paid');
            $pendingInvoices = $invoices->where('status', 'pending_payment');
            
            $revenuePaid = (int) $paidInvoices->sum('amount');
            $revenuePending = (int) $pendingInvoices->sum('amount');
            $todayRevenue = (int) $paidInvoices->filter(fn($i) => $i->paid_at && $i->paid_at->isToday())->sum('amount');
            
            // Payment method breakdown
            $cashRevenue = (int) $paidInvoices->filter(fn($i) => strtolower($i->payment_method ?? '') === 'cash')->sum('amount');
            $mobileRevenue = (int) $paidInvoices->filter(fn($i) => in_array(strtolower($i->payment_method ?? ''), ['mpesa', 'tigopesa', 'airtel', 'mobile_money', 'm-pesa']))->sum('amount');
            $bankRevenue = $revenuePaid - ($cashRevenue + $mobileRevenue);
            
            $recentInvoices = $invoices->take(6);
        } catch (\Throwable $e) {
            $salesInvoices = $itService->allInvoices();
            $recentInvoices = array_slice($salesInvoices, 0, 6);
            $revenuePaid = $itStats['revenue_paid'];
            $revenuePending = $itStats['revenue_pending'];
            $todayRevenue = 0;
            $cashRevenue = 0;
            $mobileRevenue = 0;
            $bankRevenue = 0;
        }

        // 4. Real Live Inventory Stats & Stock Movements
        try {
            $products = PosProduct::active()->get();
            $physicalProducts = $products->where('is_service', false);
            $totalInventoryUnits = (int) $physicalProducts->sum('stock_quantity');
            $totalInventoryValuation = (int) $physicalProducts->sum(fn($p) => ($p->stock_quantity ?? 0) * ($p->price ?? 0));
            $lowStockProducts = $physicalProducts->filter(fn($p) => ($p->stock_quantity ?? 0) <= ($p->min_stock_alert ?? 5))->values();
            $lowStockCount = $lowStockProducts->count();
            $recentInventoryLogs = InventoryLog::with(['product', 'user'])->latest('id')->take(8)->get();
        } catch (\Throwable $e) {
            $totalInventoryUnits = 0;
            $totalInventoryValuation = 0;
            $lowStockCount = 0;
            $lowStockProducts = collect();
            $recentInventoryLogs = collect();
        }

        // 5. Staff, Tasks & Executive Alerts
        $totalEmployees = Employee::count();
        $totalDepartments = Department::count();
        $departments = Department::withCount([
            'employees',
            'tasks as open_tasks_count' => function($q) {
                $q->whereNotIn('status', ['verified', 'closed', 'cancelled']);
            }
        ])->get()->map(function($dept) {
            $dept->pending_approvals_count = ApprovalRequest::where('department_id', $dept->id)->where('status', 'pending')->count();
            return $dept;
        });
        
        $overdueTasksCount = Task::whereNotIn('status', ['verified', 'closed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        // SLA Breach / Bottleneck Alerts
        $slaBreachTickets = collect($itTickets)->filter(function($t) {
            return in_array($t['status'] ?? '', ['pending', 'new', 'assigned', 'in_progress']) && 
                   isset($t['created_at']) && 
                   \Carbon\Carbon::parse($t['created_at'])->diffInHours(now()) >= 24;
        })->values();

        $longStayVisitors = Visit::whereNull('departure')
            ->where('created_at', '<', now()->subHours(4))
            ->take(5)
            ->get();

        $stats = [
            'visitors_today' => $totalVisitsToday,
            'active_visitors' => $activeVisitors,
            'total_visits' => $totalVisitsAllTime,
            'it_tickets_total' => $itStats['tickets_total'],
            'it_tickets_pending' => $itStats['tickets_pending'],
            'it_tasks_completed' => $itStats['tasks_completed'],
            'invoices_pending_count' => isset($pendingInvoices) ? $pendingInvoices->count() : $itStats['invoices_pending'],
            'revenue_pending' => $revenuePending,
            'invoices_paid_count' => isset($paidInvoices) ? $paidInvoices->count() : $itStats['invoices_paid'],
            'revenue_paid' => $revenuePaid,
            'today_revenue' => $todayRevenue,
            'cash_revenue' => $cashRevenue,
            'mobile_revenue' => $mobileRevenue,
            'bank_revenue' => $bankRevenue,
            'inventory_units' => $totalInventoryUnits,
            'inventory_valuation' => $totalInventoryValuation,
            'low_stock_count' => $lowStockCount,
            'total_employees' => $totalEmployees,
            'total_departments' => $totalDepartments,
            'overdue_tasks_count' => $overdueTasksCount,
            'sla_breach_count' => $slaBreachTickets->count(),
            'long_stay_count' => $longStayVisitors->count(),
        ];

        // 6. Cross-Department Live Timelines (Audit Log)
        $recentTimelines = \App\Models\VisitTimeline::with(['visit', 'user'])
            ->latest('id')
            ->take(12)
            ->get();

        return view('manager.index', compact(
            'stats',
            'recentVisits',
            'recentTickets',
            'recentInvoices',
            'recentInventoryLogs',
            'departments',
            'recentTimelines',
            'lowStockProducts',
            'slaBreachTickets',
            'longStayVisitors'
        ));
    }

    public function audit(Request $request, ItService $itService)
    {
        $departments = Department::withCount(['employees'])->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // Filters
        $search = $request->get('search', '');
        $type = $request->get('type', 'all');
        $dept = $request->get('dept', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Gather audit sources — latest on top, includes inventory add/delete
        $recentTimelines = \App\Models\VisitTimeline::with(['visit.employee.department','user'])->latest('id')->take(50)->get();
        $recentInventoryLogs = InventoryLog::with(['product','user'])->latest('id')->take(50)->get();
        $recentVisits = Visit::with('employee.department')->latest()->take(50)->get();
        $itTickets = $itService->allTickets();
        $recentTickets = array_slice($itTickets, 0, 20);
        $recentInvoices = SaleInvoice::with(['owner'])->latest('id')->take(50)->get();
        $recentTasks = Task::with(['assignee','department'])->latest('id')->take(50)->get();
        $recentWorkReports = \App\Models\WorkReport::with(['user','department'])->latest('id')->take(50)->get();
        $recentProducts = PosProduct::with(['supplier'])->latest('id')->take(50)->get();
        $recentProductsUpdated = PosProduct::with(['supplier'])->latest('updated_at')->take(50)->get();

        // Unified audit collection — latest first
        $auditItems = collect();
        foreach($recentTimelines as $tl){ $auditItems->push(['_type'=>'timeline','_dept'=>strtolower($tl->visit?->employee?->department?->name ?? 'general'), 'time'=>$tl->created_at, 'title'=>$tl->title, 'desc'=>$tl->description, 'user'=>$tl->user?->name ?? 'System', 'dept'=>$tl->visit?->employee?->department?->name ?? 'General', 'icon'=>$tl->event_type, 'raw'=>$tl]); }
        foreach($recentInventoryLogs as $log){ $auditItems->push(['_type'=>'inventory','_dept'=>strtolower('warehouse'), 'time'=>$log->created_at, 'title'=>$log->product?->name ?? 'Inventory', 'desc'=>($log->reason ?? 'Stock movement').' • '.($log->quantity_change>0?'+':'').$log->quantity_change.' • '.($log->user?->name ?? 'Staff'), 'user'=>$log->user?->name ?? 'Staff', 'dept'=>'Warehouse', 'icon'=>'inventory']); }
        foreach($recentVisits as $v){ $auditItems->push(['_type'=>'visit','_dept'=>strtolower($v->employee?->department?->name ?? 'reception'), 'time'=>$v->created_at, 'title'=>'Visit: '.($v->visitor ?? 'Guest'), 'desc'=>($v->purpose ?? 'General Visit').' • Host: '.($v->employee?->full_name ?? 'Reception'), 'user'=>$v->visitor ?? 'Guest', 'dept'=>$v->employee?->department?->name ?? 'Reception', 'icon'=>'visit']); }
        foreach($recentTickets as $t){ $auditItems->push(['_type'=>'ticket','_dept'=>strtolower('it'), 'time'=>isset($t['created_at'])?\Carbon\Carbon::parse($t['created_at']):now(), 'title'=>'IT Ticket: '.($t['title'] ?? 'Issue'), 'desc'=>($t['category'] ?? 'Support').' • '.($t['status'] ?? 'pending').' • '.($t['visitor_name'] ?? ''), 'user'=>$t['visitor_name'] ?? 'Customer', 'dept'=>'IT', 'icon'=>'ticket']); }
        foreach($recentInvoices as $inv){ $auditItems->push(['_type'=>'invoice','_dept'=>strtolower('sales'), 'time'=>$inv->created_at ?? now(), 'title'=>'Sale: '.($inv->receipt_number ?? '#'.$inv->id), 'desc'=>($inv->service ?? 'Sale').' • TZS '.number_format($inv->amount).' • '.$inv->status, 'user'=>$inv->customer_name ?? 'Walk-in', 'dept'=>'Sales', 'icon'=>'invoice']); }
        foreach($recentTasks as $task){ $auditItems->push(['_type'=>'task','_dept'=>strtolower($task->department?->name ?? 'general'), 'time'=>$task->created_at, 'title'=>'Task: '.$task->title, 'desc'=>($task->status ?? 'pending').' • '.($task->assignee?->name ?? 'Unassigned'), 'user'=>$task->assignee?->name ?? $task->creator?->name ?? 'System', 'dept'=>$task->department?->name ?? 'General', 'icon'=>'task']); }
        foreach($recentWorkReports as $wr){ $auditItems->push(['_type'=>'workreport','_dept'=>strtolower($wr->department?->name ?? 'general'), 'time'=>$wr->created_at, 'title'=>'Logbook: '.($wr->user?->name ?? 'Staff'), 'desc'=>\Str::limit($wr->activity_performed ?? '', 60).' • '.$wr->status, 'user'=>$wr->user?->name ?? 'Staff', 'dept'=>$wr->department?->name ?? 'General', 'icon'=>'logbook']); }
        foreach($recentProducts as $prod){ $auditItems->push(['_type'=>'inventory','_dept'=>strtolower($prod->category ?? 'warehouse'), 'time'=>$prod->created_at, 'title'=>'Product Added: '.$prod->name, 'desc'=>($prod->sku ?? '').' • '.($prod->category ?? '').' • TZS '.number_format($prod->price ?? 0).' • Stock: '.($prod->stock_quantity ?? 0), 'user'=>'System', 'dept'=>$prod->category ?? 'Warehouse', 'icon'=>'inventory']); }
        foreach($recentProductsUpdated->filter(fn($p)=> $p->updated_at && $p->updated_at->gt($p->created_at)) as $prod){ $auditItems->push(['_type'=>'inventory','_dept'=>strtolower($prod->category ?? 'warehouse'), 'time'=>$prod->updated_at, 'title'=>'Product Updated: '.$prod->name, 'desc'=>($prod->sku ?? '').' • Updated • Stock: '.($prod->stock_quantity ?? 0), 'user'=>'System', 'dept'=>$prod->category ?? 'Warehouse', 'icon'=>'inventory']); }

        $auditItems = $auditItems->sortByDesc('time');

        // Apply filters
        if($search){
            $auditItems = $auditItems->filter(fn($a) => str_contains(strtolower($a['title'].' '.$a['desc'].' '.$a['user'].' '.$a['dept']), strtolower($search)));
        }
        if($type !== 'all'){
            $auditItems = $auditItems->filter(fn($a) => $a['_type'] === $type);
        }
        if($dept !== 'all'){
            $auditItems = $auditItems->filter(fn($a) => strtolower($a['dept']) === strtolower($dept));
        }
        if($dateFrom){
            $auditItems = $auditItems->filter(fn($a) => $a['time'] && \Carbon\Carbon::parse($a['time'])->gte(\Carbon\Carbon::parse($dateFrom)->startOfDay()));
        }
        if($dateTo){
            $auditItems = $auditItems->filter(fn($a) => $a['time'] && \Carbon\Carbon::parse($a['time'])->lte(\Carbon\Carbon::parse($dateTo)->endOfDay()));
        }

        $auditItems = $auditItems->values()->take(100);
        $stats = [
            'total' => $auditItems->count(),
            'visits' => $auditItems->where('_type','visit')->count(),
            'inventory' => $auditItems->where('_type','inventory')->count(),
            'invoices' => $auditItems->where('_type','invoice')->count(),
            'tickets' => $auditItems->where('_type','ticket')->count(),
        ];

        return view('manager.audit', compact('departments','users','auditItems','search','type','dept','dateFrom','dateTo','stats'));
    }

    public function tasks(Request $request)
    {
        $tasks = Task::with(['assignee', 'creator', 'collaborators', 'watchers', 'department', 'lead', 'quotation', 'salesOrder', 'product'])->latest('id')->get();
        $view = in_array($request->get('view'), ['list', 'board', 'calendar']) ? $request->get('view') : 'list';
        $section = in_array($request->get('section'), ['all','my','team','pending_review','calendar','templates','reports']) ? $request->get('section') : null;
        if ($section === 'pending_review') $view = 'list';
        if ($section === 'calendar') $view = 'calendar';
        if ($section === 'templates') $view = 'list';

        // Calendar month data (by due date)
        $month = $request->get('month');
        try {
            $monthDate = $month ? \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable $e) {
            $monthDate = now()->startOfMonth();
        }
        $calendarStart = $monthDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $calendarEnd = $monthDate->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $calendarWeeks = [];
        $week = [];
        for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) {
            $dayTasks = $tasks->filter(fn($t) => $t->due_at && \Carbon\Carbon::parse($t->due_at)->isSameDay($day))->take(4);
            $dayCount = $tasks->filter(fn($t) => $t->due_at && \Carbon\Carbon::parse($t->due_at)->isSameDay($day))->count();
            $week[] = ['date' => $day->copy(), 'inMonth' => $day->month === $monthDate->month, 'tasks' => $dayTasks, 'count' => $dayCount];
            if (count($week) === 7) {
                $calendarWeeks[] = $week;
                $week = [];
            }
        }
        $users = User::all();
        $departments = Department::all();
        $taskTypes = Task::taskTypes();
        $leads = \App\Models\SalesLead::latest('id')->take(50)->get();
        $quotations = \App\Models\SalesQuotation::latest('id')->take(50)->get();
        $orders = SaleInvoice::latest('id')->take(50)->get();
        $products = PosProduct::active()->orderBy('name')->take(50)->get();

        $stats = [
            'all' => $tasks->count(),
            'assigned' => $tasks->whereIn('status', ['assigned', 'pending', 'draft'])->count(),
            'in_progress' => $tasks->whereIn('status', ['accepted', 'in_progress'])->count(),
            'waiting' => $tasks->where('status', 'waiting')->count(),
            'blocked' => $tasks->where('status', 'blocked')->count(),
            'overdue' => $tasks->filter(fn($t) => $t->due_at && $t->due_at->isPast() && !in_array($t->status, ['completed', 'verified', 'closed', 'cancelled']))->count(),
            'pending_review' => $tasks->whereIn('status', ['submitted', 'completed', 'under_review'])->filter(fn($t) => is_null($t->verified_at))->count(),
            'completed' => $tasks->whereIn('status', ['verified', 'closed', 'completed'])->count(),
        ];
        // Full 7-metric alignment for spec: keep legacy keys too
        $stats['total'] = $stats['all'];

        $teamPerformance = $users->map(function ($u) use ($tasks) {
            $mine = $tasks->where('assigned_to', $u->id);
            return [
                'name' => $u->name,
                'assigned' => $mine->count(),
                'completed' => $mine->whereIn('status', ['completed', 'verified', 'closed'])->count(),
                'overdue' => $mine->filter(fn($t) => $t->due_at && $t->due_at->isPast() && !in_array($t->status, ['completed', 'verified', 'closed']))->count(),
            ];
        })->filter(fn($r) => $r['assigned'] > 0)->values();

        $attention = $tasks->filter(fn($t) => ($t->due_at && $t->due_at->isPast() && !in_array($t->status, ['completed', 'verified', 'closed'])) || in_array($t->status, ['blocked', 'waiting']))->take(5);

        $templates = TaskTemplate::with('creator')->latest('id')->get();

        // Reports data for spec section 18
        $report = [
            'created_today' => $tasks->filter(fn($t) => $t->created_at->isToday())->count(),
            'completed_today' => $tasks->filter(fn($t) => $t->completed_at && $t->completed_at->isToday())->count(),
            'avg_completion_hours' => null,
            'blocked_count' => $tasks->where('status', 'blocked')->count(),
            'returned_count' => $tasks->where('status', 'returned')->count(),
            'waiting_count' => $tasks->where('status', 'waiting')->count(),
        ];
        $completedWithTime = $tasks->filter(fn($t) => $t->completed_at && $t->created_at);
        if ($completedWithTime->count() > 0) {
            $report['avg_completion_hours'] = round($completedWithTime->avg(fn($t) => $t->completed_at->diffInHours($t->created_at)), 1);
        }
        $deptPerformance = $departments->map(function ($d) use ($tasks) {
            $deptTasks = $tasks->filter(fn($t) => (int) ($t->department_id ?? 0) === (int) $d->id);
            return ['name' => $d->name, 'total' => $deptTasks->count(), 'completed' => $deptTasks->whereIn('status', ['verified','closed','completed'])->count(), 'overdue' => $deptTasks->filter(fn($t) => $t->due_at && $t->due_at->isPast() && !in_array($t->status, ['verified','closed','completed','cancelled']))->count()];
        });

        return view('manager.tasks.index', compact(
            'tasks', 'users', 'departments', 'taskTypes', 'leads', 'quotations', 'orders', 'products',
            'stats', 'teamPerformance', 'attention', 'templates', 'view', 'calendarWeeks', 'monthDate', 'report', 'deptPerformance', 'section'
        ));
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
            'tasks' => 'required|array|min:1|max:25',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $created = 0;
        foreach ($validated['tasks'] as $row) {
            if (empty(trim($row['title'] ?? ''))) {
                continue;
            }
            $task = Task::create([
                'uuid' => (string) Str::uuid(),
                'title' => trim($row['title']),
                'priority' => $validated['priority'] ?? 'medium',
                'status' => 'assigned',
                'department_id' => $validated['department_id'] ?? null,
                'assigned_to' => $row['assigned_to'] ?? auth()->id(),
                'due_at' => $validated['due_at'] ?? null,
                'progress' => 0,
                'created_by' => auth()->id(),
            ]);
            TaskUpdate::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'status_from' => null,
                'status_to' => 'assigned',
                'comment' => 'Created via bulk creation',
            ]);
            $created++;
        }

        return redirect()->back()->with('success', "Created {$created} tasks");
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string|max:5000',
            'expected_outcome' => 'nullable|string|max:5000',
            'task_type' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent,critical',
            'default_duration_days' => 'nullable|integer|min:1|max:90',
            'checklist_items' => 'nullable|string|max:2000',
        ]);

        $template = TaskTemplate::create(array_merge($validated, [
            'task_type' => $validated['task_type'] ?? 'general',
            'department' => $validated['department'] ?? 'general',
            'priority' => $validated['priority'] ?? 'medium',
            'default_duration_days' => $validated['default_duration_days'] ?? 1,
            'checklist_items' => $validated['checklist_items'] ?? null,
            'created_by' => auth()->id(),
        ]));

        return redirect()->back()->with('success', "Template '{$template->title}' saved");
    }

    public function useTemplate(Request $request, $id)
    {
        $template = TaskTemplate::findOrFail($id);
        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id',
            'collaborators' => 'nullable|array',
            'collaborators.*' => 'integer|exists:users,id',
            'watchers' => 'nullable|array',
            'watchers.*' => 'integer|exists:users,id',
            'due_at' => 'nullable|date',
        ]);

        $task = Task::create([
            'uuid' => (string) Str::uuid(),
            'title' => $template->title,
            'description' => $template->description,
            'instructions' => $template->instructions,
            'expected_outcome' => $template->expected_outcome,
            'task_type' => $template->task_type,
            'department' => $template->department,
            'department_id' => $template->department_id,
            'priority' => $template->priority,
            'status' => 'assigned',
            'assigned_to' => $validated['assigned_to'] ?? auth()->id(),
            'due_at' => $validated['due_at'] ?? now()->addDays($template->default_duration_days)->format('Y-m-d'),
            'progress' => 0,
            'created_by' => auth()->id(),
        ]);

        if (!empty($validated['collaborators'])) $task->collaborators()->sync($validated['collaborators']);
        if (!empty($validated['watchers'])) $task->watchers()->sync($validated['watchers']);

        if (!empty($template->checklist_items)) {
            $position = 0;
            foreach (preg_split('/\r\n|\r|\n/', $template->checklist_items) as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $position++;
                \App\Models\TaskChecklist::create(['task_id' => $task->id, 'title' => mb_substr($line, 0, 255), 'position' => $position]);
            }
        }

        TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => null,
            'status_to' => 'assigned',
            'comment' => "Created from template '{$template->title}'",
        ]);

        if ($task->assigned_to && (int) $task->assigned_to !== (int) auth()->id()) {
            $task->assignee?->notify(new \App\Notifications\TaskAssigned($task));
        }

        return redirect()->back()->with('success', "Task created from template '{$template->title}'");
    }

    public function destroyTemplate($id)
    {
        TaskTemplate::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Template deleted');
    }

    public function exportReport(ItService $itService)
    {
        $stats = $itService->stats();
        $totalVisitsToday = Visit::where('created_at', '>=', now()->startOfDay())->count();
        $activeVisitors = Visit::whereNull('departure')->count();
        $totalVisits = Visit::count();
        $totalEmployees = Employee::count();
        $totalDepartments = Department::count();

        $filename = 'executive_kpi_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($stats, $totalVisitsToday, $activeVisitors, $totalVisits, $totalEmployees, $totalDepartments) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            
            fputcsv($handle, ['JOBARN ERP — EXECUTIVE OPERATIONAL SUMMARY']);
            fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s') . ' EAT']);
            fputcsv($handle, []);

            fputcsv($handle, ['METRIC CATEGORY', 'METRIC NAME', 'VALUE', 'UNIT / STATUS']);
            fputcsv($handle, ['Reception Operations', 'Active On-Premises Visitors', $activeVisitors, 'Visitors']);
            fputcsv($handle, ['Reception Operations', 'Total Arrivals Today', $totalVisitsToday, 'Visitors']);
            fputcsv($handle, ['Reception Operations', 'Historical Total Visits', $totalVisits, 'Visits']);
            
            fputcsv($handle, ['IT Service Support', 'Total IT Tickets', $stats['tickets_total'], 'Tickets']);
            fputcsv($handle, ['IT Service Support', 'Pending / In Progress Tickets', $stats['tickets_pending'], 'Tickets']);
            fputcsv($handle, ['IT Service Support', 'Fixed / Completed Tickets', $stats['tickets_resolved'], 'Tickets']);
            fputcsv($handle, ['IT Service Support', 'Tasks Completed Rate', $stats['tasks_completed'] . '/' . ($stats['tasks_pending'] + $stats['tasks_completed']), 'Tasks']);

            fputcsv($handle, ['Sales & Revenue', 'Total Revenue Collected (Paid)', number_format($stats['revenue_paid']), 'TZS']);
            fputcsv($handle, ['Sales & Revenue', 'Pending Receivables (Unpaid)', number_format($stats['revenue_pending']), 'TZS']);
            fputcsv($handle, ['Sales & Revenue', 'Paid Invoices Count', $stats['invoices_paid'], 'Invoices']);
            fputcsv($handle, ['Sales & Revenue', 'Pending Invoices Count', $stats['invoices_pending'], 'Invoices']);

            fputcsv($handle, ['Workforce & HR', 'Total Active Employees', $totalEmployees, 'Staff Members']);
            fputcsv($handle, ['Workforce & HR', 'Active Departments', $totalDepartments, 'Departments']);

            fputcsv($handle, []);
            fputcsv($handle, ['DEPARTMENT BREAKDOWN']);
            fputcsv($handle, ['Department Name', 'Description', 'Headcount']);
            foreach (Department::withCount('employees')->get() as $dept) {
                fputcsv($handle, [$dept->name, $dept->description ?? '—', $dept->employees_count . ' staff']);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
