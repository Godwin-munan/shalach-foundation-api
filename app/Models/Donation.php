<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'donor_name',
        'donor_email',
        'donor_phone',
        'amount',
        'currency',
        'payment_channel',
        'status',
        'paystack_reference',
        'purpose',
        'is_anonymous',
        'metadata',
        'paid_at',
        'ip_address'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function notifications()
    {
        return $this->hasMany(DonationNotification::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getAmountInKoboAttribute()
    {
        return $this->amount * 100; // Paystack uses kobo (smallest unit)
    }
}
