<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalesLead extends Model
{
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($lead) {
            if (empty($lead->uuid)) {
                $lead->uuid = (string) Str::uuid();
            }
        });
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function opportunities()
    {
        return $this->hasMany(SalesOpportunity::class, 'lead_id');
    }
}
