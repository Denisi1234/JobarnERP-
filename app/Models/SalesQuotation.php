<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalesQuotation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'timeline_events' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($q) {
            if (empty($q->uuid)) {
                $q->uuid = (string) Str::uuid();
            }
            $year = date('Y');
            if (empty($q->quote_number)) {
                $count = static::whereYear('created_at', $year)->count() + 1;
                $q->quote_number = 'QT-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
            }
            if (empty($q->timeline_events)) {
                $q->timeline_events = [
                    [
                        'action' => 'created',
                        'title' => 'Quotation created',
                        'actor' => auth()->user()?->name ?? 'Sales Division',
                        'timestamp' => now()->toDateTimeString(),
                        'note' => 'Initial quotation generated in Jorban ERP',
                    ]
                ];
            }
        });
    }

    public function recordEvent(string $action, string $title, ?string $note = null)
    {
        $events = $this->timeline_events ?: [];
        $events[] = [
            'action' => $action,
            'title' => $title,
            'actor' => auth()->user()?->name ?? 'Sales Division',
            'timestamp' => now()->toDateTimeString(),
            'note' => $note,
        ];
        $this->timeline_events = $events;
        $this->save();
    }

    public function opportunity()
    {
        return $this->belongsTo(SalesOpportunity::class, 'opportunity_id');
    }

    public function items()
    {
        return $this->hasMany(SalesQuotationItem::class, 'quotation_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentQuotation()
    {
        return $this->belongsTo(SalesQuotation::class, 'parent_quotation_id');
    }

    public function invoices()
    {
        return $this->hasMany(SaleInvoice::class, 'sales_quotation_id');
    }
}
