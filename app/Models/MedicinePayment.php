<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicinePayment extends Model
{
    protected $fillable = [
        'medicine_order_id',
        'appointment_id',
        'provider_transaction_id',
        'reference',
        'transfer_amount',
        'receiving_account',
        'status',
        'is_simulated',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_amount' => 'integer',
            'is_simulated' => 'boolean',
            'received_at' => 'datetime',
        ];
    }

    public function medicineOrder(): BelongsTo
    {
        return $this->belongsTo(MedicineOrder::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
