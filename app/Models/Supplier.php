<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'supply_categories' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($supplier) {
            if (empty($supplier->uuid)) {
                $supplier->uuid = (string) Str::uuid();
            }
        });
    }

    public function products()
    {
        return $this->hasMany(PosProduct::class, 'supplier_id');
    }

    public function messages()
    {
        return $this->hasMany(SupplierMessage::class)->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
