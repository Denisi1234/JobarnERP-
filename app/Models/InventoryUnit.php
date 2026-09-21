<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryUnit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'purchase_date' => 'date',
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'reserved_until' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($u) => $u->uuid = $u->uuid ?? (string) Str::uuid());
    }

    const STATUSES = ['available','reserved','sold','delivered','under_repair','returned','damaged','defective','in_transit','awaiting_inspection'];

    public function product()
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SaleInvoice::class, 'invoice_id');
    }

    public function warrantyClaims()
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    public function isWarrantyActive(): bool
    {
        return $this->warranty_status === 'active' && $this->warranty_end && $this->warranty_end->isFuture();
    }

    public function history()
    {
        return InventoryLog::where('serial_number', $this->serial_number)->latest('id')->get();
    }
}
