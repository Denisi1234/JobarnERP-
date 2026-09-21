<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($w) => $w->uuid = $w->uuid ?? (string) Str::uuid());
    }

    public function unit()
    {
        return $this->belongsTo(InventoryUnit::class, 'inventory_unit_id');
    }

    public function product()
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
