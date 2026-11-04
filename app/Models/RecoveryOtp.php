<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryOtp extends Model
{
    protected $fillable = [
        'user_id',
        'purpose',
        'otp_hash',
        'expires_at',
        'consumed_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
