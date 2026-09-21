<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrder extends Model { protected $guarded=[]; protected $casts=['expected_on'=>'date','approved_at'=>'datetime','total_amount'=>'decimal:2']; public function supplier(){return $this->belongsTo(Supplier::class);} public function department(){return $this->belongsTo(Department::class);} public function items(){return $this->hasMany(PurchaseOrderItem::class);} }
