<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $fillable = [
        'uuid',
        'sender_id',
        'recipient_id',
        'department_id',
        'recipient_handle',
        'body',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            if (empty($m->uuid)) $m->uuid = (string) Str::uuid();
        });
    }

    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function readers() { return $this->belongsToMany(User::class, 'message_user')->withPivot('read_at')->withTimestamps(); }

    public function scopeForUser($query, User $user)
    {
        $deptIds = Employee::where('email', $user->email)->pluck('department_id')->filter()->all();
        if(empty($deptIds)){
            $map = ['reception'=>'Front Desk Operations','it'=>'Information Technology','sales'=>'Sales & Business Dev','manager'=>'Administration'];
            $n = $map[$user->role ?? ''] ?? null;
            if($n){
                $d = Department::whereRaw('LOWER(name) = ?', [strtolower($n)])->first()
                  ?? Department::where('name','like','%'.explode(' ', $n)[0].'%')->first();
                if($d) $deptIds = [$d->id];
            }
        }
        $a = $user->id;
        return $query->where(function($q) use ($a, $deptIds){
            $q->where('recipient_id', $a)
              ->orWhere(function($dq) use ($deptIds){
                  if($deptIds) $dq->whereIn('department_id', $deptIds)->whereNull('recipient_id');
              });
        });
    }
}
