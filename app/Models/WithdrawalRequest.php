<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'amount',
        'withdrawal_date',
        'note',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount'          => 'decimal:0',
        'withdrawal_date' => 'date',
        'reviewed_at'     => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['is_expired'];

    // ─── Relationships ───────────────────────────────────────────────────────

    /**
     * Get the customer who made this withdrawal request.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the admin user who reviewed this request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved requests.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected requests.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /**
     * Determine if the withdrawal request is expired.
     * Returns true when the withdrawal_date has passed and status is still pending.
     *
     * @return bool
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'pending'
            && $this->withdrawal_date !== null
            && $this->withdrawal_date->isPast();
    }
}
