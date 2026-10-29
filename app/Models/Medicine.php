<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'strength',
        'unit',
        'price',
        'price_is_estimate',
        'price_source',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_is_estimate' => 'boolean',
        ];
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(MedicalRecordMedication::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(MedicineOrderItem::class);
    }
}
