<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\ItTicket;
use App\Models\SaleInvoice;
use App\Models\Task;
use App\Models\Visit;
use App\Services\ItService;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    public function dashboard()
    {
        $today = today();

        try {
            $activeVisits = Visit::with(['employee'])->whereNull('departure')->latest('arrival')->get();
            $recentVisits  = Visit::with(['employee'])->latest('arrival')->take(8)->get();
            $onPremisesCount = $activeVisits->count();
            $todayCount = Visit::whereDate('arrival', $today)->count();
            $itPendingCount = ItTicket::whereIn('status', ['pending', 'assigned'])->count();
            $invoicesPendingCount = SaleInvoice::where('status', 'pending_payment')->count();
            $checkoutReadyCount = Visit::whereNotNull('departure')->whereDate('departure', $today)->count();
        } catch (\Throwable $e) {
            $activeVisits = collect();
            $recentVisits  = collect();
            $onPremisesCount = 0;
            $todayCount = 0;
            $itPendingCount = 0;
            $invoicesPendingCount = 0;
            $checkoutReadyCount = 0;
        }

        $departments = \App\Models\Department::all();
        $employees = \App\Models\Employee::with('department')->get();

        return view('reception.dashboard', [
            'title'                => 'Dashboard',
            'activeVisits'         => $activeVisits,
            'recentVisits'         => $recentVisits,
            'onPremisesCount'      => $onPremisesCount,
            'todayCount'           => $todayCount,
            'itPendingCount'       => $itPendingCount,
            'invoicesPendingCount' => $invoicesPendingCount,
            'checkoutReadyCount'   => $checkoutReadyCount,
            'departments'          => $departments,
            'employees'            => $employees,
        ]);
    }

    public function visitors(Request $request)
    {
        try {
            $query = Visit::with(['employee', 'itTickets'])->latest('arrival');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('visitor', 'LIKE', "%{$search}%")
                      ->orWhere('visitor_phone', 'LIKE', "%{$search}%")
                      ->orWhere('purpose', 'LIKE', "%{$search}%");
                });
            }

            $visits = $query->paginate(20)->withQueryString();
            $onPremisesCount = Visit::whereNull('departure')->count();
            $todayCount = Visit::whereDate('arrival', today())->count();
            $waitingCount = Visit::whereNull('departure')->count();
            $checkoutReadyCount = Visit::whereNotNull('departure')->whereDate('departure', today())->count();
        } catch (\Throwable $e) {
            $visits = collect();
            $onPremisesCount = 0;
            $todayCount = 0;
            $waitingCount = 0;
            $checkoutReadyCount = 0;
        }

        $departments = \App\Models\Department::all();
        $employees = \App\Models\Employee::with('department')->get();

        return view('reception.visitors.index', [
            'title' => 'Customer & Visitor Management',
            'visits' => $visits,
            'onPremisesCount' => $onPremisesCount,
            'todayCount' => $todayCount,
            'waitingCount' => $waitingCount,
            'checkoutReadyCount' => $checkoutReadyCount,
            'departments' => $departments,
            'employees' => $employees,
        ]);
    }

    public function tasks(ItService $itService)
    {
        try {
            $user = auth()->user();
            $departmentIds = \App\Models\Employee::where('email', $user?->email)->pluck('department_id')->filter()->all();
            $taskQuery = Task::with(['itTicket', 'assignee', 'creator', 'collaborators', 'department', 'lead', 'quotation', 'salesOrder', 'product'])->latest('id');
            if (!$user?->is_admin && ($user?->role ?? '') !== 'manager') {
                $taskQuery->where(function ($query) use ($user, $departmentIds) {
                    $query->where('assigned_to', $user->id)
                        ->orWhereHas('collaborators', fn ($collaborators) => $collaborators->where('users.id', $user->id));
                    if ($departmentIds) $query->orWhereIn('department_id', $departmentIds);
                });
            }
            $tasks = $taskQuery->get();
            $totalCount = $tasks->count();
            $completedCount = $tasks->where('status', 'completed')->count();
            $inProgressCount = $tasks->where('status', 'in_progress')->count();
            $pendingCount = $tasks->where('status', 'pending')->count();
            $itCount = $tasks->whereNotNull('it_ticket_id')->count();
            $managerCount = $tasks->whereNull('it_ticket_id')->count();
            $urgentCount = $tasks->whereIn('priority', ['urgent', 'high'])->count();
            $users = \App\Models\User::all();
            $departments = \App\Models\Department::all();
            $taskTypes = Task::taskTypes();
        } catch (\Throwable $e) {
            $tasks = collect();
            $totalCount = 0;
            $completedCount = 0;
            $inProgressCount = 0;
            $pendingCount = 0;
            $itCount = 0;
            $managerCount = 0;
            $urgentCount = 0;
            $users = collect();
            $departments = collect();
            $taskTypes = Task::taskTypes();
            $myWork = ['today' => 0, 'overdue' => 0, 'in_progress' => 0, 'waiting' => 0, 'completed' => 0, 'today_pct' => 0, 'week_pct' => 0];
            $priorityTasks = collect();
            $recentActivity = collect();
        }

        $me = auth()->id();
        $myTasks = $tasks instanceof \Illuminate\Support\Collection
            ? $tasks->filter(fn($t) => (int) ($t->assigned_to ?? 0) === (int) $me || ($t->collaborators ?? collect())->contains('id', $me))->values()
            : collect();
        $openMine = $myTasks->reject(fn($t) => in_array($t->status, ['completed', 'verified', 'closed', 'cancelled']));
        $todayMine = $openMine->filter(fn($t) => $t->due_at && $t->due_at->isToday());
        $overdueMine = $openMine->filter(fn($t) => $t->due_at && $t->due_at->isPast());
        $weekMine = $myTasks->filter(fn($t) => $t->due_at && $t->due_at->isCurrentWeek());
        $myWork = [
            'today' => $todayMine->count(),
            'overdue' => $overdueMine->count(),
            'in_progress' => $openMine->where('status', 'in_progress')->count(),
            'waiting' => $openMine->whereIn('status', ['waiting', 'blocked'])->count(),
            'completed' => $myTasks->whereIn('status', ['completed', 'verified', 'closed'])->count(),
            'today_pct' => $todayMine->count() ? (int) round($todayMine->whereIn('status', ['completed', 'verified', 'closed'])->count() / $todayMine->count() * 100) : 0,
            'week_pct' => $weekMine->count() ? (int) round($weekMine->whereIn('status', ['completed', 'verified', 'closed'])->count() / $weekMine->count() * 100) : 0,
        ];
        $priorityTasks = $openMine->filter(fn($t) => in_array($t->priority, ['urgent', 'high', 'critical']))->sortBy(fn($t) => $t->due_at ?? now()->addYears(5))->take(3);
        $recentActivity = \App\Models\TaskUpdate::with(['user', 'task'])
            ->whereIn('task_id', $myTasks->pluck('id')->all() ?: [0])
            ->latest('id')->take(5)->get();

        return view('reception.tasks.index', [
            'title' => 'Reception Tasks & Shift Operations',
            'tasks' => $tasks,
            'totalCount' => $totalCount,
            'completedCount' => $completedCount,
            'inProgressCount' => $inProgressCount,
            'pendingCount' => $pendingCount,
            'itCount' => $itCount,
            'managerCount' => $managerCount,
            'urgentCount' => $urgentCount,
            'users' => $users,
            'departments' => $departments,
            'taskTypes' => $taskTypes,
            'myWork' => $myWork,
            'priorityTasks' => $priorityTasks,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function showTask($id)
    {
        $task = Task::with(['itTicket', 'assignee', 'creator', 'verifier', 'collaborators', 'department', 'lead', 'quotation', 'salesOrder', 'product', 'updates.user', 'comments.user', 'checklists', 'subtasks.assignee', 'dependencies', 'dependents', 'parent'])->findOrFail($id);
        $users = \App\Models\User::orderBy('name')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        $taskTypes = Task::taskTypes();
        $allTasks = Task::where('id', '!=', $task->id)->latest('id')->take(100)->get(['id', 'title', 'status']);

        return view('tasks.show', [
            'task' => $task,
            'users' => $users,
            'departments' => $departments,
            'taskTypes' => $taskTypes,
            'allTasks' => $allTasks,
        ]);
    }

    public function verifyTask(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $user = auth()->user();

        if (!($user->is_admin || ($user->role ?? '') === 'manager' || $task->created_by === $user->id)) {
            abort(403, 'Only a manager or the task creator can verify this task.');
        }

        $oldStatus = $task->status;
        $task->update([
            'status' => 'verified',
            'progress' => 100,
            'completed_at' => $task->completed_at ?? now(),
            'verified_at' => now(),
            'verified_by' => $user->id,
        ]);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status_from' => $oldStatus,
            'status_to' => 'verified',
            'comment' => $request->input('comment', 'Verified by manager'),
        ]);

        $task->assignee?->notify(new \App\Notifications\TaskVerified($task->fresh()));

        return redirect()->back()->with('success', 'Task verified and closed');
    }

    public function storeTask(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string|max:5000',
            'expected_outcome' => 'nullable|string|max:5000',
            'task_type' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => 'nullable|string|in:low,normal,medium,high,critical,urgent',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'collaborators' => 'nullable|array',
            'collaborators.*' => 'integer|exists:users,id',
            'watchers' => 'nullable|array',
            'watchers.*' => 'integer|exists:users,id',
            'start_date' => 'nullable|date',
            'due_at' => 'nullable|date',
            'due_time' => 'nullable|string|max:20',
            'all_day' => 'nullable|boolean',
            'estimated_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'status' => 'nullable|string|in:draft,assigned',
            'customer_name' => 'nullable|string|max:255',
            'lead_id' => 'nullable|integer|exists:sales_leads,id',
            'quotation_id' => 'nullable|integer|exists:sales_quotations,id',
            'sales_order_id' => 'nullable|integer|exists:sale_invoices,id',
            'product_id' => 'nullable|integer|exists:pos_products,id',
            'is_recurring' => 'nullable|boolean',
            'repeat_interval' => 'nullable|string|in:daily,weekly,monthly,quarterly,custom',
            'repeat_until' => 'nullable|date',
            'save_action' => 'nullable|string|in:draft,assign',
            'checklist_items' => 'nullable|string|max:5000',
            'attachments.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,txt',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('task_attachments', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => '/storage/' . $path,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }
            }
        }

        $task = Task::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'expected_outcome' => $validated['expected_outcome'] ?? null,
            'task_type' => $validated['task_type'] ?? 'general',
            'department' => $validated['department'] ?? 'general',
            'department_id' => $validated['department_id'] ?? null,
            'priority' => $validated['priority'] ?? 'normal',
            'status' => ($validated['save_action'] ?? 'assign') === 'draft' ? 'draft' : ($validated['status'] ?? 'assigned'),
            'assigned_to' => $validated['assigned_to'] ?? auth()->id(),
            'start_date' => $validated['start_date'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'due_time' => !empty($validated['all_day']) ? null : ($validated['due_time'] ?? null),
            'all_day' => !empty($validated['all_day']),
            'estimated_duration_minutes' => $validated['estimated_duration_minutes'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'lead_id' => $validated['lead_id'] ?? null,
            'quotation_id' => $validated['quotation_id'] ?? null,
            'sales_order_id' => $validated['sales_order_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'is_recurring' => !empty($validated['repeat_interval']),
            'repeat_interval' => $validated['repeat_interval'] ?? null,
            'repeat_until' => $validated['repeat_until'] ?? null,
            'progress' => 0,
            'attachments' => $attachments ?: null,
            'created_by' => auth()->id(),
        ]);

        if (!empty($validated['collaborators'])) {
            $task->collaborators()->sync($validated['collaborators']);
        }
        if (!empty($validated['watchers'])) {
            $task->watchers()->sync($validated['watchers']);
        }

        if (!empty($validated['checklist_items'])) {
            $position = 0;
            foreach (preg_split('/\r\n|\r|\n/', $validated['checklist_items']) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $position++;
                \App\Models\TaskChecklist::create([
                    'task_id' => $task->id,
                    'title' => mb_substr($line, 0, 255),
                    'position' => $position,
                ]);
            }
        }

        // Log creation
        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => null,
            'status_to' => $task->status,
            'comment' => $task->status === 'draft' ? 'Task saved as draft' : 'Task created and assigned',
        ]);

        if ($task->status !== 'draft') {
            $this->notifyTaskUsers($task, new \App\Notifications\TaskAssigned($task));
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'task' => $task, 'message' => 'Task created successfully', 'attachments' => $attachments]);
        }

        $msg = 'Task "' . $task->title . '" created successfully';
        if (!empty($attachments)) {
            $names = collect($attachments)->pluck('name')->join(', ');
            $msg .= ' — ' . count($attachments) . ' document(s) uploaded (' . $names . ')';
        }
        if ($task->status === 'draft') $msg .= ' (saved as draft)';

        return redirect()->back()->with('success', $msg);
    }

    public function updateTaskStatus(Request $request, $id)
    {
        $task = Task::with(['dependencies', 'subtasks', 'checklists'])->findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|string|in:draft,assigned,accepted,in_progress,blocked,waiting,submitted,completed,under_review,returned,verified,closed,cancelled,declined,pending',
            'progress' => 'nullable|integer|min:0|max:100',
            'result' => 'nullable|string|max:2000',
            'next_action' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
            'waiting_reason' => 'nullable|string|max:100',
            'expected_resolution_at' => 'nullable|date',
            'what_was_done' => 'nullable|string|max:5000',
            'issues_encountered' => 'nullable|string|max:2000',
            'completion_attachments.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,txt',
        ]);

        $user = auth()->user();
        $oldStatus = $task->status;
        $newStatus = $validated['status'];
        // Normalize legacy completed from employee => submitted for review
        $isEmployeeCompleting = !$user->is_admin && ($user->role ?? '') !== 'manager' && (int) $task->created_by !== (int) $user->id;
        if ($isEmployeeCompleting && $newStatus === 'completed') {
            $newStatus = 'submitted';
        }
        $isManager = $user && ($user->is_admin || ($user->role ?? '') === 'manager' || (int) $task->created_by === (int) $user->id);

        // Manager-only transitions
        if (in_array($newStatus, ['returned', 'verified', 'closed', 'cancelled']) && !$isManager) {
            abort(403, 'Only a manager or the task creator can perform this transition.');
        }
        // Employees cannot verify/close their own tasks
        if (in_array($newStatus, ['verified', 'closed']) && (int) $task->assigned_to === (int) $user->id && !$isManager) {
            abort(403, 'You cannot approve your own task. A manager must review it.');
        }

        // Professional lifecycle guard (allow manager to override cancelled etc)
        if (!$task->canTransitionTo($newStatus) && !$isManager) {
            return redirect()->back()->withErrors(['status' => 'Cannot move from ' . str_replace('_',' ',$oldStatus) . ' to ' . str_replace('_',' ',$newStatus) . '.'])->withInput();
        }

        // Waiting / blocked / declined need a reason
        if (in_array($newStatus, ['waiting', 'blocked', 'declined']) && empty($validated['comment']) && empty($validated['waiting_reason'])) {
            return redirect()->back()->withErrors(['comment' => 'Please tell us why this task is ' . $newStatus . '.'])->withInput();
        }

        // Submitted requires completion report (what was done + result)
        if ($newStatus === 'submitted' && empty($validated['what_was_done']) && empty($validated['result'])) {
            return redirect()->back()->withErrors(['what_was_done' => 'Please describe what you did — the completion report requires What did you do? and Result.'])->withInput();
        }

        // Cannot finish while dependencies are unfinished
        if (in_array($newStatus, ['completed', 'closed', 'verified']) && $task->incompleteDependencies()->count() > 0) {
            $blockers = $task->incompleteDependencies()->map(fn($d) => '#' . $d->id . ' ' . $d->title)->join(', ');
            return redirect()->back()->withErrors(['status' => 'Blocked by unfinished dependencies: ' . $blockers])->withInput();
        }

        // Completion report attachments
        $completionAttachments = null;
        if ($request->hasFile('completion_attachments')) {
            $completionAttachments = [];
            foreach ($request->file('completion_attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('task_completions', 'public');
                    $completionAttachments[] = ['name' => $file->getClientOriginalName(), 'path' => '/storage/' . $path, 'size' => $file->getSize(), 'mime' => $file->getMimeType()];
                }
            }
        }

        $updates = ['status' => $newStatus];
        if (isset($validated['progress'])) $updates['progress'] = $validated['progress'];
        if (isset($validated['result'])) $updates['result'] = $validated['result'];
        if (isset($validated['next_action'])) $updates['next_action'] = $validated['next_action'];
        if (isset($validated['what_was_done'])) $updates['completion_what_was_done'] = $validated['what_was_done'];
        if (isset($validated['issues_encountered'])) $updates['completion_issues'] = $validated['issues_encountered'];
        if ($completionAttachments) $updates['completion_attachments'] = $completionAttachments;
        if ($newStatus === 'declined') $updates['declined_reason'] = $validated['comment'] ?? $validated['waiting_reason'] ?? null;
        if ($newStatus === 'returned') $updates['returned_reason'] = $validated['comment'] ?? null;
        if ($newStatus === 'blocked') $updates['blocked_reason'] = $validated['comment'] ?? $validated['waiting_reason'] ?? null;
        if (in_array($newStatus, ['waiting', 'blocked'])) {
            $updates['waiting_reason'] = $validated['waiting_reason'] ?? null;
            $updates['expected_resolution_at'] = $validated['expected_resolution_at'] ?? null;
        } else {
            $updates['waiting_reason'] = null;
            $updates['expected_resolution_at'] = null;
        }

        // Map to completed_at / submitted_at / verified
        if (in_array($newStatus, ['completed', 'verified', 'closed'])) {
            $updates['completed_at'] = now();
            if ($newStatus === 'verified') {
                $updates['verified_at'] = now();
                $updates['verified_by'] = $user->id;
            }
        } else {
            $updates['completed_at'] = null;
            if ($newStatus !== 'verified') {
                $updates['verified_at'] = null;
                $updates['verified_by'] = null;
            }
        }
        if ($newStatus === 'submitted') {
            $updates['submitted_at'] = now();
        }
        if ($newStatus === 'accepted' && empty($task->accepted_at)) {
            $updates['accepted_at'] = now();
        }
        if ($newStatus === 'in_progress' && empty($task->started_at)) {
            $updates['started_at'] = now();
        }

        // Progress: explicit value wins, else roll up from subtasks/checklist, else status map
        if (!isset($validated['progress'])) {
            $rolledUp = $task->recalculateProgress();
            $hasChildren = $task->subtasks()->count() > 0 || $task->checklists()->count() > 0;
            if ($hasChildren) {
                $updates['progress'] = $rolledUp;
            } else {
                $progressMap = ['draft'=>0,'assigned'=>0,'accepted'=>10,'in_progress'=>50,'blocked'=>50,'waiting'=>50,'submitted'=>80,'completed'=>100,'under_review'=>90,'returned'=>40,'verified'=>100,'closed'=>100,'cancelled'=>0,'declined'=>0,'pending'=>0];
                if (isset($progressMap[$newStatus])) $updates['progress'] = $progressMap[$newStatus];
            }
        }

        $updates['overdue_notified_at'] = null;
        $task->update($updates);

        $comment = $validated['comment'] ?? null;
        if (in_array($newStatus, ['waiting', 'blocked']) && !empty($validated['waiting_reason'])) {
            $reasonLabel = str_replace('_', ' ', $validated['waiting_reason']);
            $comment = trim(($comment ? $comment . ' — ' : '') . 'Reason: ' . $reasonLabel . (!empty($validated['expected_resolution_at']) ? ', expected resolution: ' . $validated['expected_resolution_at'] : ''));
        }

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status_from' => $oldStatus,
            'status_to' => $newStatus,
            'comment' => $comment,
            'result' => $validated['result'] ?? null,
            'next_action' => $validated['next_action'] ?? null,
            'what_was_done' => $validated['what_was_done'] ?? null,
            'issues_encountered' => $validated['issues_encountered'] ?? null,
            'waiting_reason' => $validated['waiting_reason'] ?? null,
            'attachments' => $completionAttachments,
        ]);

        $fresh = $task->fresh();

        // Notifications: creator on submit/complete, assignee on return/verify
        if (in_array($newStatus, ['submitted', 'completed']) && $fresh->created_by && (int) $fresh->created_by !== (int) $user->id) {
            $creator = \App\Models\User::find($fresh->created_by);
            if ($newStatus === 'submitted') {
                $creator?->notify(new \App\Notifications\TaskSubmitted($fresh, $user->name ?? 'Someone'));
            } else {
                $creator?->notify(new \App\Notifications\TaskCompleted($fresh, $user->name ?? 'Someone'));
            }
        }
        if ($newStatus === 'verified') {
            $fresh->assignee?->notify(new \App\Notifications\TaskVerified($fresh));
        }
        if ($newStatus === 'returned') {
            $fresh->assignee?->notify(new \App\Notifications\TaskReturned($fresh, $validated['comment'] ?? null));
        }
        if ($newStatus === 'declined' && $fresh->created_by && (int) $fresh->created_by !== (int) $user->id) {
            $creator = \App\Models\User::find($fresh->created_by);
            $creator?->notify(new \App\Notifications\TaskDeclined($fresh, $user->name ?? 'Someone', $validated['comment'] ?? null));
        }

        // Spawn next occurrence for recurring tasks on completion (verified/closed/submitted recurring)
        if (in_array($newStatus, ['completed', 'closed', 'verified'])) {
            $this->spawnRecurringSuccessor($fresh);
        }

        if ($request->wantsJson() || $request->ajax()) {
            $ajaxMsg = 'Task status updated to ' . str_replace('_', ' ', $newStatus);
            if (!empty($completionAttachments)) $ajaxMsg .= ' — ' . count($completionAttachments) . ' document(s) uploaded';
            return response()->json(['success' => true, 'status' => $fresh->status, 'progress' => $fresh->progress, 'message' => $ajaxMsg, 'files' => $completionAttachments]);
        }

        $msg = 'Task status updated to ' . str_replace('_', ' ', $newStatus);
        if (!empty($completionAttachments)) {
            $msg .= ' — ' . count($completionAttachments) . ' document(s) uploaded (' . collect($completionAttachments)->pluck('name')->join(', ') . ')';
        }
        // Extra clarity for submit for review
        if ($newStatus === 'submitted') $msg = 'Completion report submitted successfully — ' . ($msg) . ' — manager will review and Approve & Close or Return it';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Add a checklist item to a task.
     */
    public function storeChecklist(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $item = \App\Models\TaskChecklist::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'position' => (int) ($task->checklists()->max('position') ?? 0) + 1,
        ]);

        $task->update(['progress' => $task->recalculateProgress()]);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => $task->status,
            'status_to' => $task->status,
            'comment' => 'Added checklist item: ' . $item->title,
        ]);

        return redirect()->back()->with('success', 'Checklist item added');
    }

    public function toggleChecklist(Request $request, $id, $itemId)
    {
        $task = Task::with('checklists')->findOrFail($id);
        $item = $task->checklists()->findOrFail($itemId);
        $item->update(['is_done' => !$item->is_done]);

        $task->update(['progress' => $task->recalculateProgress()]);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => $task->status,
            'status_to' => $task->status,
            'comment' => ($item->fresh()->is_done ? 'Completed checklist: ' : 'Reopened checklist: ') . $item->title,
        ]);

        return redirect()->back();
    }

    public function destroyChecklist($id, $itemId)
    {
        $task = Task::findOrFail($id);
        $item = $task->checklists()->findOrFail($itemId);
        $title = $item->title;
        $item->delete();

        $task->update(['progress' => $task->recalculateProgress()]);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => $task->status,
            'status_to' => $task->status,
            'comment' => 'Removed checklist item: ' . $title,
        ]);

        return redirect()->back()->with('success', 'Checklist item removed');
    }

    /**
     * Add a subtask; inherits department and assignee unless overridden.
     */
    public function storeSubtask(Request $request, $id)
    {
        $parent = Task::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'due_at' => 'nullable|date',
        ]);

        $sub = Task::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'parent_id' => $parent->id,
            'title' => $validated['title'],
            'priority' => $parent->priority,
            'status' => 'assigned',
            'task_type' => $parent->task_type,
            'department' => $parent->department,
            'department_id' => $parent->department_id,
            'assigned_to' => $validated['assigned_to'] ?? $parent->assigned_to ?? auth()->id(),
            'due_at' => $validated['due_at'] ?? $parent->due_at,
            'progress' => 0,
            'created_by' => auth()->id(),
        ]);

        \App\Models\TaskUpdate::create([
            'task_id' => $parent->id,
            'user_id' => auth()->id(),
            'status_from' => $parent->status,
            'status_to' => $parent->status,
            'comment' => 'Added subtask: ' . $sub->title,
        ]);

        $parent->update(['progress' => $parent->recalculateProgress()]);

        if ($sub->assigned_to && (int) $sub->assigned_to !== (int) auth()->id()) {
            $sub->assignee?->notify(new \App\Notifications\TaskAssigned($sub));
        }

        return redirect()->back()->with('success', 'Subtask added');
    }

    /**
     * Link a dependency (this task waits on another). Guards self-links, duplicates and cycles.
     */
    public function storeDependency(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $validated = $request->validate([
            'depends_on_task_id' => 'required|integer|exists:tasks,id',
        ]);

        $depId = (int) $validated['depends_on_task_id'];
        if ($depId === (int) $task->id) {
            return redirect()->back()->withErrors(['depends_on_task_id' => 'A task cannot depend on itself.']);
        }
        if ($task->dependencies()->where('tasks.id', $depId)->exists()) {
            return redirect()->back()->withErrors(['depends_on_task_id' => 'This dependency already exists.']);
        }
        // Cycle guard: the target must not transitively depend on this task
        $seen = [$task->id];
        $frontier = [$depId];
        while (!empty($frontier)) {
            $current = array_pop($frontier);
            if (in_array($current, $seen, true)) {
                continue;
            }
            $seen[] = $current;
            $next = \App\Models\TaskDependency::where('task_id', $current)->pluck('depends_on_task_id')->all();
            if (in_array($task->id, array_map('intval', $next), true)) {
                return redirect()->back()->withErrors(['depends_on_task_id' => 'This would create a dependency cycle.']);
            }
            $frontier = array_merge($frontier, array_map('intval', $next));
        }

        $task->dependencies()->attach($depId);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => $task->status,
            'status_to' => $task->status,
            'comment' => 'Added dependency on task #' . $depId,
        ]);

        return redirect()->back()->with('success', 'Dependency added');
    }

    public function destroyDependency($id, $depId)
    {
        $task = Task::findOrFail($id);
        $task->dependencies()->detach((int) $depId);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => $task->status,
            'status_to' => $task->status,
            'comment' => 'Removed dependency on task #' . $depId,
        ]);

        return redirect()->back()->with('success', 'Dependency removed');
    }

    /**
     * Edit core details with an audit entry for important changes.
     */
    public function updateDetails(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'priority' => 'nullable|string|in:low,medium,normal,high,urgent,critical',
            'due_at' => 'nullable|date',
            'due_time' => 'nullable|string|max:20',
            'task_type' => 'nullable|string|max:100',
            'department_id' => 'nullable|integer|exists:departments,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $changes = [];
        $map = [
            'priority' => 'Priority',
            'task_type' => 'Task type',
        ];
        foreach ($map as $field => $label) {
            if (array_key_exists($field, $validated) && (string) ($task->$field ?? '') !== (string) ($validated[$field] ?? '')) {
                $changes[] = $label . ' changed ' . ucfirst($task->$field ?? '—') . ' → ' . ucfirst($validated[$field] ?? '—');
            }
        }
        if (array_key_exists('due_at', $validated)) {
            $old = $task->due_at ? $task->due_at->format('d M Y') : '—';
            $new = $validated['due_at'] ? \Carbon\Carbon::parse($validated['due_at'])->format('d M Y') : '—';
            if ($old !== $new) {
                $changes[] = 'Due date changed ' . $old . ' → ' . $new;
            }
        }
        if (array_key_exists('assigned_to', $validated) && (int) ($task->assigned_to ?? 0) !== (int) ($validated['assigned_to'] ?? 0)) {
            $oldName = $task->assignee?->name ?? 'Unassigned';
            $newName = $validated['assigned_to'] ? (\App\Models\User::find($validated['assigned_to'])?->name ?? '—') : 'Unassigned';
            $changes[] = 'Responsible changed ' . $oldName . ' → ' . $newName;
        }

        $task->update(array_filter($validated, fn($v) => !is_null($v)) + ['overdue_notified_at' => null]);

        if (!empty($changes)) {
            \App\Models\TaskUpdate::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'status_from' => $task->status,
                'status_to' => $task->status,
                'comment' => implode('; ', $changes),
            ]);
        }

        return redirect()->back()->with('success', 'Task details updated');
    }

    /**
     * Notify the responsible user plus collaborators, skipping the actor.
     */
    protected function notifyTaskUsers(Task $task, \Illuminate\Notifications\Notification $notification): void
    {
        $actorId = (int) auth()->id();
        $recipients = collect([$task->assignee])
            ->merge($task->collaborators ?? collect())
            ->merge($task->watchers ?? collect())
            ->filter(fn($u) => $u && (int) $u->id !== $actorId)
            ->unique('id');

        foreach ($recipients as $user) {
            $user->notify(clone $notification);
        }
    }

    /**
     * Create the next occurrence of a recurring task once the current one closes.
     */
    protected function spawnRecurringSuccessor(Task $task): void
    {
        if (!$task->is_recurring || empty($task->repeat_interval)) {
            return;
        }

        $base = $task->due_at ? \Carbon\Carbon::parse($task->due_at) : now();
        $next = match ($task->repeat_interval) {
            'daily' => $base->copy()->addDay(),
            'weekly' => $base->copy()->addWeek(),
            'monthly' => $base->copy()->addMonth(),
            'quarterly' => $base->copy()->addMonths(3),
            'custom' => $base->copy()->addWeek(),
            default => null,
        };

        if (!$next) {
            return;
        }
        if ($task->repeat_until && $next->startOfDay()->gt(\Carbon\Carbon::parse($task->repeat_until)->startOfDay())) {
            return;
        }

        $copy = $task->replicate(['uuid', 'completed_at', 'verified_at', 'verified_by', 'overdue_notified_at', 'submitted_at', 'accepted_at', 'started_at']);
        $copy->uuid = (string) \Illuminate\Support\Str::uuid();
        $copy->status = 'assigned';
        $copy->progress = 0;
        $copy->result = null;
        $copy->next_action = null;
        $copy->completion_what_was_done = null;
        $copy->completion_issues = null;
        $copy->completion_attachments = null;
        $copy->completed_at = null;
        $copy->verified_at = null;
        $copy->verified_by = null;
        $copy->submitted_at = null;
        $copy->accepted_at = null;
        $copy->started_at = null;
        $copy->overdue_notified_at = null;
        $copy->due_at = $next;
        $copy->created_by = auth()->id();
        $copy->save();
        $copy->collaborators()->sync($task->collaborators()->pluck('users.id')->all());
        if (method_exists($task, 'watchers')) {
            $copy->watchers()->sync($task->watchers()->pluck('users.id')->all());
        }

        \App\Models\TaskUpdate::create([
            'task_id' => $copy->id,
            'user_id' => auth()->id(),
            'status_from' => null,
            'status_to' => 'assigned',
            'comment' => "Recurring copy of task #{$task->id} ({$task->repeat_interval})",
        ]);

        $this->notifyTaskUsers($copy, new \App\Notifications\TaskAssigned($copy));
    }

    public function deleteTask(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Task deleted']);
        }

        return redirect()->back()->with('success', 'Task deleted');
    }

    /**
     * Upload documents to an existing task — real file storage + success message.
     */
    public function uploadTaskAttachments(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $request->validate([
            'attachments' => 'required|array|min:1|max:10',
            'attachments.*' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,txt,csv',
        ]);

        $existing = is_array($task->attachments) ? $task->attachments : [];
        $newFiles = [];
        $failed = [];

        foreach ($request->file('attachments') as $file) {
            if (!$file->isValid()) {
                $failed[] = $file->getClientOriginalName() . ' (invalid)';
                continue;
            }
            try {
                $path = $file->store('task_attachments', 'public');
                $newFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => '/storage/' . $path,
                    'storage_path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now()->toIso8601String(),
                ];
            } catch (\Throwable $e) {
                $failed[] = $file->getClientOriginalName() . ' (' . $e->getMessage() . ')';
            }
        }

        if (empty($newFiles)) {
            return redirect()->back()->withErrors(['attachments' => 'No files were uploaded. ' . implode(', ', $failed)]);
        }

        $merged = array_merge($existing, $newFiles);
        $task->update(['attachments' => $merged]);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => $task->status,
            'status_to' => $task->status,
            'comment' => 'Uploaded ' . count($newFiles) . ' document(s): ' . collect($newFiles)->pluck('name')->join(', '),
            'attachments' => $newFiles,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => count($newFiles) . ' document(s) uploaded successfully',
                'files' => $newFiles,
                'failed' => $failed,
            ]);
        }

        $msg = count($newFiles) . ' document(s) uploaded successfully: ' . collect($newFiles)->pluck('name')->join(', ');
        if (!empty($failed)) $msg .= ' — failed: ' . implode(', ', $failed);

        return redirect()->back()->with('success', $msg);
    }

    public function deleteTaskAttachment(Request $request, $id, $index)
    {
        $task = Task::findOrFail($id);
        $attachments = is_array($task->attachments) ? array_values($task->attachments) : [];

        if (!isset($attachments[$index])) {
            return redirect()->back()->withErrors(['attachments' => 'File not found.']);
        }

        $removed = $attachments[$index];
        // Remove from storage
        try {
            $storagePath = $removed['storage_path'] ?? ltrim($removed['path'] ?? '', '/storage/');
            if ($storagePath) \Illuminate\Support\Facades\Storage::disk('public')->delete($storagePath);
        } catch (\Throwable $e) {}

        array_splice($attachments, $index, 1);
        $task->update(['attachments' => empty($attachments) ? null : $attachments]);

        \App\Models\TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'status_from' => $task->status,
            'status_to' => $task->status,
            'comment' => 'Removed document: ' . ($removed['name'] ?? 'file'),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Document removed']);
        }

        return redirect()->back()->with('success', 'Document "' . ($removed['name'] ?? 'file') . '" removed successfully');
    }

    public function exportVisitors(Request $request)
    {
        $filename = 'visitors_register_' . now()->format('Y-m-d_His') . '.csv';
        $visits = Visit::with(['employee', 'itTickets'])->latest('arrival')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [
            'Reference / ID',
            'Visitor Name',
            'Phone',
            'Type / Zone',
            'Purpose',
            'Host / Employee',
            'Department',
            'Check-In Arrival',
            'Check-Out Departure',
            'Status',
            'Total Amount (TZS)',
        ];

        $callback = function () use ($visits, $columns) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($visits as $v) {
                $isInside = empty($v->departure);
                $hasIt    = $v->itTickets && $v->itTickets->count() > 0;
                $status   = $isInside ? ($hasIt ? 'In IT Service' : 'Inside / Active') : 'Departed / Completed';
                $dept     = $hasIt ? 'IT Support' : ($v->employee?->department?->name ?? 'Sales');

                fputcsv($file, [
                    $v->uuid ?? ('VIS-' . str_pad($v->id, 4, '0', STR_PAD_LEFT)),
                    $v->visitor,
                    $v->visitor_phone ?: '—',
                    $v->zone === 'VIP' ? 'Corporate' : 'Walk-in',
                    $v->purpose ?: 'Showroom Visit',
                    $v->employee?->name ?? 'Front Desk Staff',
                    $dept,
                    $v->arrival ? \Carbon\Carbon::parse($v->arrival)->format('Y-m-d H:i:s') : '—',
                    $v->departure ? \Carbon\Carbon::parse($v->departure)->format('Y-m-d H:i:s') : '—',
                    $status,
                    $v->total_amount ? number_format($v->total_amount, 2) : '0.00',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportTasks(Request $request)
    {
        $filename = 'tasks_register_' . now()->format('Y-m-d_His') . '.csv';
        $tasks = Task::with(['itTicket', 'assignee'])->latest('id')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [
            'Task Reference',
            'Title',
            'Description',
            'Priority',
            'Status',
            'Assigner / Source',
            'Assigned To',
            'Due At',
            'Completed At',
            'Created At',
        ];

        $callback = function () use ($tasks, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($tasks as $t) {
                $isIt = !empty($t->it_ticket_id);
                $source = $isIt ? ('IT Ticket: ' . ($t->itTicket?->category ?? 'Support')) : 'Reception / Manager';

                fputcsv($file, [
                    $t->uuid ?? ('TSK-' . str_pad($t->id, 4, '0', STR_PAD_LEFT)),
                    $t->title,
                    $t->description ?: '—',
                    strtoupper($t->priority ?: 'NORMAL'),
                    strtoupper($t->status ?: 'PENDING'),
                    $source,
                    $t->assignee?->name ?? 'Front Desk',
                    $t->due_at ? $t->due_at->format('Y-m-d H:i:s') : '—',
                    $t->completed_at ? $t->completed_at->format('Y-m-d H:i:s') : '—',
                    $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
