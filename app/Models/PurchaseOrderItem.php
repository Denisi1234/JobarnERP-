<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderItem extends Model { protected $guarded=[]; protected $casts=['unit_cost'=>'decimal:2']; public function product(){return $this->belongsTo(PosProduct::class,'pos_product_id');} }
