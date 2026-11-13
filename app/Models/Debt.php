<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'person_name',
        'amount',
        'paid_amount',
        'remaining_amount',
        'description',
        'due_date',
        'reminder_date',
        'is_paid',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
        'reminder_date' => 'date',
        'is_paid' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * Calculate remaining amount based on payments
     */
    public function calculateRemainingAmount(): float
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;
        $this->remaining_amount = $this->amount - $totalPaid;
        $this->is_paid = $this->remaining_amount <= 0;
        return $this->remaining_amount;
    }

    /**
     * Boot method to auto-calculate remaining amount
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($debt) {
            if ($debt->isDirty('amount') || $debt->isDirty('paid_amount')) {
                $debt->remaining_amount = $debt->amount - $debt->paid_amount;
                $debt->is_paid = $debt->remaining_amount <= 0;
            }
        });
    }
}

