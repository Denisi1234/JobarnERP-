<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PosProduct extends Model
{
    use HasFactory;

    protected $table = 'pos_products';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_service' => 'boolean',
        'is_active' => 'boolean',
        'specs' => 'array',
        'min_stock_alert' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
            if (empty($product->sku)) {
                $prefix = strtoupper(substr($product->category ?? 'PRD', 0, 3));
                $product->sku = $prefix . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('is_service', true)
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeLowStock($query, $threshold = 5)
    {
        return $query->where('is_service', false)
                     ->where('stock_quantity', '<=', $threshold);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class, 'pos_product_id')->latest('id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function units()
    {
        return $this->hasMany(InventoryUnit::class, 'pos_product_id');
    }

    public function isSerialized(): bool
    {
        return $this->tracking_method === 'serial';
    }
}
