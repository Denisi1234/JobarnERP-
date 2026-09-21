<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalesOpportunity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expected_close_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($opp) {
            if (empty($opp->uuid)) {
                $opp->uuid = (string) Str::uuid();
            }
        });
    }

    public function lead()
    {
        return $this->belongsTo(SalesLead::class, 'lead_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function quotations()
    {
        return $this->hasMany(SalesQuotation::class, 'opportunity_id');
    }
}
