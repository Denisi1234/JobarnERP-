<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItTicket extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'integer',
        'attachments' => 'array',
        'qa_checklist' => 'array',
        'spare_parts' => 'array',
        'device_specs' => 'array',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'reassigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAgingHoursAttribute(): int
    {
        $start = $this->created_at ?? now();
        $end = $this->resolved_at ?? now();
        return (int) $start->diffInHours($end);
    }

    public function getIsAgingAttribute(): bool
    {
        return !in_array($this->status, ['resolved', 'closed', 'returned', 'rejected']) && $this->aging_hours >= 24;
    }

    public function getIsCriticalOverdueAttribute(): bool
    {
        return !in_array($this->status, ['resolved', 'closed', 'returned', 'rejected']) && $this->aging_hours >= 48;
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SaleInvoice::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function timelines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VisitTimeline::class, 'it_ticket_id')->latest('created_at');
    }

    /**
     * Get combined activity timeline for ticket and linked visit
     */
    public function getAllTimelines()
    {
        return VisitTimeline::where('it_ticket_id', $this->id)
            ->when($this->visit_id, fn($q) => $q->orWhere('visit_id', $this->visit_id))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Log activity event for this IT ticket
     */
    public function logActivity(string $eventType, string $title, ?string $description = null, ?string $department = 'it', ?int $userId = null, ?array $metadata = null): VisitTimeline
    {
        $tl = VisitTimeline::create([
            'it_ticket_id' => $this->id,
            'visit_id' => $this->visit_id,
            'user_id' => $userId ?: auth()->id(),
            'event_type' => $eventType,
            'department' => $department,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        return $tl;
    }

    public function scopePending($q) { return $q->whereIn('status', ['pending', 'new']); }
    public function scopeAccepted($q) { return $q->where('status', 'accepted'); }
    public function scopeInProgress($q) { return $q->where('status', 'in_progress'); }
    public function scopeResolved($q) { return $q->where('status', 'resolved'); }
    public function scopeReturned($q) { return $q->whereIn('status', ['returned', 'rejected']); }
}

