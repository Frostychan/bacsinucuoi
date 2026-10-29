<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'status',
        'payment_status',
        'paid_at',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MedicineOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MedicinePayment::class);
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }
}
