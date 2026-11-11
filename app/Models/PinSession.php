<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PinSession extends Model
{
    protected $fillable = [
        'customer_id',
        'teller_id',
        'token',
        'status',
        'amount',
        'note',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'amount'     => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}

