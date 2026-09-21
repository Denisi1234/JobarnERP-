<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkReport;
use Illuminate\Http\Request;

class WorkReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isManager = $request->routeIs('manager.logbooks');
        if ($isManager) {
            abort_unless(in_array($user->role ?? '', ['manager', 'admin']), 403);
        }

        // Determine current portal department
        $portalDepartmentId = $this->departmentIdForRoute($request, $user);

        $userRole = $user->role ?? 'reception';
        // Portal logbooks are now accessible directly — no forced redirect.
        // Previous strict role redirect caused IT My Logbook to open Sales logbook when sales user viewed IT portal.
        // Keep only manager/admin bypass; portal isolation is handled via department filtering below.

        // Resolve portal department IDs for strict isolation (sales has 2 depts, IT has 3, etc.)
        $portalDepartmentIds = $this->departmentIdsForRoute($request, $user);
        $query = WorkReport::with(['user','department','reviewer'])->latest('report_date')->latest('id');

        if (!$isManager) {
            // Portal workspace logbook: strict isolation — reception sees ONLY reception, sales sees ONLY sales
            // Managers/admins viewing a portal logbook see ALL entries for that portal department (not just their own)
            if (in_array($userRole, ['manager','admin'])) {
                if (!empty($portalDepartmentIds)) {
                    $query->whereIn('department_id', $portalDepartmentIds);
                }
            } else {
                $query->where('user_id', $user->id);
                if (!empty($portalDepartmentIds)) {
                    $query->whereIn('department_id', $portalDepartmentIds);
                } else {
                    // Fallback: if department could not be resolved, enforce role-based department to prevent cross-portal leak
                    $fallbackDept = $this->departmentIdFor($user);
                    if ($fallbackDept) {
                        $query->where('department_id', $fallbackDept);
                    } else {
                        // No department found — return empty to avoid leaking other portals
                        $query->whereRaw('1=0');
                    }
                }
            }
        } else {
            // Executive Manager / Admin portal: View all department logbooks with optional filtering
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->integer('department_id'));
            }
        }

        $reports = $query->paginate(25)->withQueryString();
        $departments = $isManager ? \App\Models\Department::orderBy('name')->get() : collect();
        $pending = $isManager ? WorkReport::where('status','submitted')->count() : 0;
        $departmentId = $portalDepartmentId;

        return view('work-reports.index', compact('reports','isManager','departmentId','departments','pending'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $departmentId = $request->input('department_id') ?: $this->departmentIdForRoute($request, $user);
        
        if (!$departmentId) {
            return back()->withErrors(['department' => 'Your user account is not linked to a department. Ask a manager to update Staff & Access.']);
        }

        $data = $request->validate([
            'report_date' => 'required|date|before_or_equal:today',
            'entry_time' => 'required|date_format:H:i',
            'out_time' => 'required|date_format:H:i|after:entry_time',
            'activity_performed' => 'required|string|max:6000'
        ]);

        $data['report_type'] = 'daily';
        $data['work_completed'] = $data['activity_performed'];

        $report = WorkReport::updateOrCreate(
            ['user_id' => $user->id, 'report_date' => $data['report_date'], 'report_type' => 'daily'],
            array_merge($data, [
                'department_id' => $departmentId,
                'status' => 'submitted',
                'outcomes' => null,
                'blockers' => null,
                'next_actions' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'manager_comment' => null
            ])
        );

        // Send real notification to Managers and Admins
        try {
            $managers = User::whereIn('role', ['manager', 'admin'])->get();
            foreach ($managers as $mgr) {
                if ($mgr->id !== $user->id) {
                    $mgr->notify(new \App\Notifications\LogbookSubmitted($report));
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WorkReportController] Notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Logbook entry submitted to the manager.');
    }

    public function review(Request $request, WorkReport $workReport)
    {
        abort_unless(in_array($request->user()->role ?? '', ['manager', 'admin']), 403);
        $data = $request->validate([
            'status' => 'required|in:reviewed,returned',
            'manager_comment' => 'nullable|string|max:3000'
        ]);

        if ($data['status'] === 'returned' && blank($data['manager_comment'])) {
            return back()->withErrors(['manager_comment' => 'A comment is required when returning a report.']);
        }

        $workReport->update([
            'status' => $data['status'],
            'manager_comment' => $data['manager_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now()
        ]);

        return back()->with('success', 'Work report review recorded.');
    }

    /**
     * Resolve department based on current portal route and user role
     */
    private function departmentIdForRoute(Request $request, User $user): ?int
    {
        // 1. Prioritize portal for strict isolation — /reception sees ONLY reception, /sales ONLY sales
        $referer = $request->headers->get('referer', '');
        $isIt = $request->routeIs('it.logbook') || str_contains($referer, '/it/');
        $isSales = $request->routeIs('sales.logbook') || str_contains($referer, '/sales/');
        $isReception = $request->routeIs('reception.logbook') || str_contains($referer, '/reception/');

        $departmentNames = [];
        if ($isIt) {
            $departmentNames = ['IT and Infrastructure', 'Information Technology', 'Technology'];
        } elseif ($isSales) {
            $departmentNames = ['Sales and Marketing', 'Sales & Business Dev', 'Sales'];
        } elseif ($isReception) {
            $departmentNames = ['Administration', 'Front Desk Operations', 'Reception'];
        }

        if (!empty($departmentNames)) {
            $deptId = Department::whereIn('name', $departmentNames)->value('id');
            if ($deptId) {
                return (int) $deptId;
            }
        }

        return $this->departmentIdFor($user);
    }

    private function departmentIdsForRoute(Request $request, User $user): array
    {
        $referer = $request->headers->get('referer', '');
        $isIt = $request->routeIs('it.logbook') || str_contains($referer, '/it/');
        $isSales = $request->routeIs('sales.logbook') || str_contains($referer, '/sales/');
        $isReception = $request->routeIs('reception.logbook') || str_contains($referer, '/reception/');
        $names = [];
        if ($isIt) $names = ['IT and Infrastructure', 'Information Technology', 'Technology'];
        elseif ($isSales) $names = ['Sales and Marketing', 'Sales & Business Dev', 'Sales'];
        elseif ($isReception) $names = ['Administration', 'Front Desk Operations', 'Reception'];
        if (empty($names)) return [];
        return Department::whereIn('name', $names)->pluck('id')->map(fn($v)=>(int)$v)->all();
    }

    /** Resolve a staff member's department, including standard portal accounts
     * whose login email differs from the employee-directory email. */
    private function departmentIdFor(User $user): ?int
    {
        $employeeDepartmentId = Employee::where('email', $user->email)->value('department_id');
        if ($employeeDepartmentId) {
            return (int) $employeeDepartmentId;
        }

        $departmentNames = match ($user->role ?? '') {
            'it' => ['Information Technology', 'IT and Infrastructure', 'Technology'],
            'sales' => ['Sales & Business Dev', 'Sales and Marketing', 'Sales'],
            'reception' => ['Front Desk Operations', 'Reception'],
            default => [],
        };

        return Department::whereIn('name', $departmentNames)->value('id');
    }
}
