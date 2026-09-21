<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkReport extends Model { protected $guarded=[]; protected $casts=['report_date'=>'date','entry_time'=>'datetime:H:i','out_time'=>'datetime:H:i','reviewed_at'=>'datetime']; public function user(){return $this->belongsTo(User::class);} public function department(){return $this->belongsTo(Department::class);} public function reviewer(){return $this->belongsTo(User::class,'reviewed_by');} }
