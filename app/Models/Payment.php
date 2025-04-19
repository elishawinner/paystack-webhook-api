<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reference',
        'email',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata' // For additional payment data
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'reference';
    }

    /**
     * Scope a query to only include successful payments.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Format amount with currency symbol
     */
    public function getFormattedAmountAttribute()
    {
        return $this->currency === 'NGN' 
            ? '₦' . number_format($this->amount, 2)
            : number_format($this->amount, 2) . ' ' . $this->currency;
    }
}