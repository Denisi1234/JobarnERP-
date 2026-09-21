<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            $request->uuid ??= (string) Str::uuid();
            $request->requested_at ??= now();
        });
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class)->latest('id');
    }
}
