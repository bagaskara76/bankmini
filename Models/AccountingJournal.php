<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_code',
        'type',
        'amount',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
