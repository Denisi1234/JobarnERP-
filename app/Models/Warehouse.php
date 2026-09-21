<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Warehouse extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($w) => $w->uuid = $w->uuid ?? (string) Str::uuid());
    }

    public function units()
    {
        return $this->hasMany(InventoryUnit::class);
    }

    public function products()
    {
        return $this->hasMany(PosProduct::class);
    }
}
