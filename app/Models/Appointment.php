<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    protected $fillable = [
        'patient_id', 'patient_age', 'doctor_id', 'specialty_id', 'starts_at', 'ends_at',
        'consultation_fee', 'payment_status', 'paid_at',
        'status', 'reason', 'notes', 'doctor_notes', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'consultation_fee' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function medicineOrder(): HasOne
    {
        return $this->hasOne(MedicineOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MedicinePayment::class);
    }
}
