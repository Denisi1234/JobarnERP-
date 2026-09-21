<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Debit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($debit) {
            if (empty($debit->uuid)) {
                $debit->uuid = (string) Str::uuid();
            }
            // auto overdue check
            if ($debit->status === 'pending' && $debit->due_date && $debit->due_date->isPast()) {
                $debit->status = 'overdue';
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SaleInvoice::class, 'sale_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(DebitMessage::class)->latest('id');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeSupplier($q)
    {
        return $q->where('type', 'supplier');
    }

    public function scopeCustomer($q)
    {
        return $q->where('type', 'customer');
    }
}
