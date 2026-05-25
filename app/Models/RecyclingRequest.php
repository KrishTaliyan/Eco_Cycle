<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecyclingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'shop_owner_id',
        'recycling_center_id',
        'device_id',
        'request_number',
        'pickup_address',
        'preferred_slot',
        'status',
        'reward_points',
        'admin_note',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_points' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function recyclingCenter(): BelongsTo
    {
        return $this->belongsTo(RecyclingCenter::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function rewardPoints(): HasMany
    {
        return $this->hasMany(RewardPoint::class);
    }
}
