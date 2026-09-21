<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    const STATUS_DRAFT = 'draft';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_WAITING = 'waiting';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_RETURNED = 'returned';
    const STATUS_COMPLETED = 'completed';
    const STATUS_VERIFIED = 'verified';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DECLINED = 'declined';

    // Professional lifecycle: who can transition to what
    const LIFECYCLE = [
        'draft' => ['assigned', 'cancelled'],
        'assigned' => ['accepted', 'declined', 'cancelled'],
        'accepted' => ['in_progress', 'cancelled'],
        'in_progress' => ['waiting', 'blocked', 'submitted', 'cancelled'],
        'waiting' => ['in_progress', 'blocked', 'submitted', 'cancelled'],
        'blocked' => ['in_progress', 'waiting', 'cancelled'],
        'submitted' => ['verified', 'closed', 'returned', 'cancelled'], // manager reviews
        'returned' => ['in_progress', 'cancelled'],
        'completed' => ['verified', 'closed', 'returned'], // legacy completed = submitted
        'verified' => [],
        'closed' => [],
        'cancelled' => [],
        'declined' => ['assigned', 'cancelled'],
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'attachments' => 'array',
        'completion_attachments' => 'array',
        'is_recurring' => 'boolean',
        'repeat_until' => 'date',
        'start_date' => 'date',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'due_time' => 'datetime',
        'all_day' => 'boolean',
        'submitted_at' => 'datetime',
        'expected_resolution_at' => 'date',
    ];

    public function itTicket(): BelongsTo
    {
        return $this->belongsTo(ItTicket::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->latest('id');
    }

    public function updates()
    {
        return $this->hasMany(TaskUpdate::class)->latest('id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SalesLead::class, 'lead_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SalesQuotation::class, 'quotation_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SaleInvoice::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PosProduct::class, 'product_id');
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'task_collaborators', 'task_id', 'user_id')->withPivot('role')->withTimestamps();
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'task_watchers', 'task_id', 'user_id')->withTimestamps();
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && !in_array($this->status, [self::STATUS_VERIFIED, self::STATUS_CLOSED, self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::LIFECYCLE[$this->status] ?? null;
        if ($allowed === null) return true;
        return in_array($newStatus, $allowed);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('id');
    }

    public function checklists()
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('position')->orderBy('id');
    }

    public function dependencies()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')->withTimestamps();
    }

    public function dependents()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')->withTimestamps();
    }

    /**
     * Tasks this task waits on that are not finished yet.
     */
    public function incompleteDependencies()
    {
        return $this->dependencies()->whereNotIn('tasks.status', ['completed', 'verified', 'closed'])->get();
    }

    /**
     * Recalculate progress: subtask average wins, else checklist ratio.
     * Returns the computed value (does not save when children exist and count is zero).
     */
    public function recalculateProgress(): int
    {
        $subtasks = $this->subtasks()->get();
        if ($subtasks->count() > 0) {
            return (int) round($subtasks->avg('progress'));
        }

        $total = $this->checklists()->count();
        if ($total > 0) {
            return (int) round($this->checklists()->where('is_done', true)->count() / $total * 100);
        }

        return (int) ($this->progress ?? 0);
    }

    public static function taskTypes(): array
    {
        return [
            'General' => ['General', 'Other'],
            'Sales' => ['Customer Follow-up', 'Lead Generation', 'Customer Visit', 'Quotation Follow-up', 'Market Research', 'Payment Follow-up', 'Complaint Resolution'],
            'Administration' => ['Administration', 'Prepare Report', 'Document Review', 'Meeting Preparation'],
            'Finance' => ['Finance', 'Payment Follow-up', 'Reconciliation', 'Document Verification'],
            'Inventory' => ['Inventory', 'Stock Count', 'Stock Verification'],
            'IT/Maintenance' => ['IT/Maintenance', 'System Check', 'Equipment Inspection', 'Installation', 'Maintenance', 'Procurement', 'Inspection'],
            'Procurement' => ['Procurement', 'Supplier Verification'],
            'Reporting' => ['Reporting', 'Inspection'],
        ];
    }

    public static function priorities(): array
    {
        return ['low' => 'Low', 'normal' => 'Normal', 'medium' => 'Normal', 'high' => 'High', 'critical' => 'Critical', 'urgent' => 'Critical'];
    }

    public static function professionalStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_WAITING => 'Waiting',
            self::STATUS_BLOCKED => 'Blocked',
            self::STATUS_SUBMITTED => 'Submitted for Review',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_DECLINED => 'Declined',
        ];
    }
}
