<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineOrderItem extends Model
{
    protected $fillable = [
        'medicine_id',
        'medicine_name',
        'quantity',
        'unit',
        'unit_price',
        'unit_price_is_estimate',
        'unit_price_source',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'unit_price_is_estimate' => 'boolean',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function medicineOrder(): BelongsTo
    {
        return $this->belongsTo(MedicineOrder::class);
    }
}
