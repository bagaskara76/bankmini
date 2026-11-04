<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'status',
        'approved_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
