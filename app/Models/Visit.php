<?php

namespace App\Models;

use App\Traits\UuidScopeTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory;
    use UuidScopeTrait;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'arrival' => 'datetime',
        'departure' => 'datetime',
        'total_amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($visit) {
            if (empty($visit->ticket_code)) {
                $code = 'TKT-' . now()->format('Ymd') . '-' . str_pad((string)$visit->id, 4, '0', STR_PAD_LEFT);
                $visit->updateQuietly(['ticket_code' => $code]);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VisitService::class)->latest('id');
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(VisitTimeline::class)->latest('id');
    }

    public function itTickets(): HasMany
    {
        return $this->hasMany(ItTicket::class);
    }

    /**
     * Helper to log an audit timeline event
     */
    public function logTimeline(string $eventType, string $title, ?string $description = null, ?string $department = null, ?int $userId = null, ?array $metadata = null): VisitTimeline
    {
        return $this->timelines()->create([
            'event_type' => $eventType,
            'department' => $department ?: ($this->current_department ?? 'reception'),
            'title' => $title,
            'description' => $description,
            'user_id' => $userId ?: auth()->id(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Recalculate total amount from services
     */
    public function recalculateTotal(): int
    {
        $total = $this->services()->where('status', '!=', 'cancelled')->sum(\DB::raw('price * quantity'));
        $this->update(['total_amount' => $total]);
        return (int) $total;
    }
}
