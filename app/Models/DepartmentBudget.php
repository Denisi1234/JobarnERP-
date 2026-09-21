<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DepartmentBudget extends Model { protected $guarded=[]; protected $casts=['amount'=>'decimal:2','committed_amount'=>'decimal:2']; public function department(){return $this->belongsTo(Department::class);} }
