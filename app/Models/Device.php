<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recycling_center_id',
        'category',
        'brand',
        'model',
        'condition',
        'estimated_weight_kg',
        'points_preview',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_weight_kg' => 'decimal:2',
            'points_preview' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recyclingCenter(): BelongsTo
    {
        return $this->belongsTo(RecyclingCenter::class);
    }

    public function recyclingRequest(): HasOne
    {
        return $this->hasOne(RecyclingRequest::class);
    }
}
