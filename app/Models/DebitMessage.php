<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DebitMessage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($msg) {
            if (empty($msg->uuid)) {
                $msg->uuid = (string) Str::uuid();
            }
        });
    }

    public function debit()
    {
        return $this->belongsTo(Debit::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
