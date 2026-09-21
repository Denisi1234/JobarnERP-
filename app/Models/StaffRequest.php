<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StaffRequest extends Model { protected $guarded=[]; protected $casts=['amount'=>'decimal:2','start_date'=>'date','end_date'=>'date','decided_at'=>'datetime']; public function employee(){return $this->belongsTo(Employee::class);} public function department(){return $this->belongsTo(Department::class);} public function requester(){return $this->belongsTo(User::class,'requested_by');} }
