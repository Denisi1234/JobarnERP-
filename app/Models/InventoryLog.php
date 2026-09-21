<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_product_id',
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'reason',
        'reference',
        'user_id',
    ];

    public function product()
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
