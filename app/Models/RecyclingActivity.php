<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecyclingActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'device_model',
        'device_category',
        'condition',
        'recommended_action',
        'eco_score',
        'points_awarded',
        'ewaste_kg',
        'co2_kg',
        'pollution_prevented_kg',
        'materials_recovered',
        'hazards',
        'facility',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'eco_score' => 'integer',
            'points_awarded' => 'integer',
            'ewaste_kg' => 'decimal:2',
            'co2_kg' => 'decimal:2',
            'pollution_prevented_kg' => 'decimal:2',
            'materials_recovered' => 'array',
            'hazards' => 'array',
            'facility' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(RecyclingCertificate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}





