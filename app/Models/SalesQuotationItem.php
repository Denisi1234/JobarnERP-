<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesQuotationItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(SalesQuotation::class, 'quotation_id');
    }

    public function product()
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
