<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'specialty_id', 'license_number', 'bio', 'image_path',
        'consultation_fee', 'slot_duration', 'is_accepting',
    ];

    protected function casts(): array
    {
        return ['consultation_fee' => 'decimal:2', 'is_accepting' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }
}
