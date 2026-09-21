<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ManagerReportSchedule extends Model { protected $guarded=[]; protected $casts=['sections'=>'array','active'=>'boolean','last_sent_at'=>'datetime','next_run_at'=>'datetime']; }
