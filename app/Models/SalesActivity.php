<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalesActivity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($act) {
            if (empty($act->uuid)) {
                $act->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
