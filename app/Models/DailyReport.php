<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'teller_id',
        'reviewed_by',
        'report_date',
        'initial_capital',
        'total_deposit',
        'total_withdrawal',
        'system_cash',
        'physical_cash',
        'status',
        'rejection_note',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'teller_id', 'teller_id')
            ->whereDate('created_at', $this->report_date);
    }
}
