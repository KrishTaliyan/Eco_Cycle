<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'booking_id',
        'device_model',
        'city',
        'pincode',
        'preferred_window',
        'status',
        'facility',
        'prep_checklist',
        'points_preview',
    ];

    protected function casts(): array
    {
        return [
            'facility' => 'array',
            'prep_checklist' => 'array',
            'points_preview' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
