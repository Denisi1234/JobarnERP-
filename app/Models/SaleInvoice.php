<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SaleInvoice extends Model
{
    use HasFactory;

    protected $table = 'sale_invoices';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tendered_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'items_json' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
            if (empty($invoice->receipt_number)) {
                $today = now()->format('Ymd');
                $count = static::whereDate('created_at', today())->count() + 1;
                $invoice->receipt_number = 'RCP-' . $today . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function itTicket(): BelongsTo
    {
        return $this->belongsTo(ItTicket::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
    }

    public function scopePendingPayment($q)
    {
        return $q->where('status', 'pending_payment');
    }

    public function scopePaid($q)
    {
        return $q->where('status', 'paid');
    }
}
