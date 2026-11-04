<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'grade',
        'kelas',
    ];
}
