<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecyclingCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_owner_id',
        'name',
        'city',
        'state',
        'pincode',
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'accepted_categories',
        'status',
        'opening_hours',
    ];

    protected function casts(): array
    {
        return [
            'accepted_categories' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function recyclingRequests(): HasMany
    {
        return $this->hasMany(RecyclingRequest::class);
    }
}
