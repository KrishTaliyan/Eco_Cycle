<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme',
        'density',
        'timezone',
        'locale',
        'notification_channels',
        'dashboard_preferences',
        'onboarding_step',
    ];

    protected function casts(): array
    {
        return [
            'notification_channels' => 'array',
            'dashboard_preferences' => 'array',
            'onboarding_step' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
