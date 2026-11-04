<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'teller_id',
        'type',
        'amount',
        'note',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function journals()
    {
        return $this->hasMany(AccountingJournal::class);
    }
}
