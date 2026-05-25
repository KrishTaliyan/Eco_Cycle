<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OPTIONS = [
        'customer' => [
            'label' => 'Customer',
            'description' => 'Submit devices, track pickups, and earn rewards.',
            'icon' => 'user-round',
        ],
        'shop_owner' => [
            'label' => 'Shop Owner',
            'description' => 'Manage centers, review requests, and assign points.',
            'icon' => 'store',
        ],
        'admin' => [
            'label' => 'Admin',
            'description' => 'Manage users, centers, requests, and platform activity.',
            'icon' => 'shield',
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'organization',
        'job_title',
        'bio',
        'avatar_url',
        'last_login_at',
        'onboarding_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function recyclingActivities(): HasMany
    {
        return $this->hasMany(RecyclingActivity::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(RecyclingCertificate::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(FacilityBookmark::class);
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    public function ownedRecyclingCenters(): HasMany
    {
        return $this->hasMany(RecyclingCenter::class, 'shop_owner_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function recyclingRequests(): HasMany
    {
        return $this->hasMany(RecyclingRequest::class, 'customer_id');
    }

    public function shopRecyclingRequests(): HasMany
    {
        return $this->hasMany(RecyclingRequest::class, 'shop_owner_id');
    }

    public function rewardPoints(): HasMany
    {
        return $this->hasMany(RewardPoint::class);
    }

    public function platformNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function assignRole(string $name): void
    {
        $role = Role::query()->where('name', $name)->first();

        if ($role) {
            $this->roles()->sync([$role->id]);
        } else {
            $this->roles()->detach();
        }

        if ($this->role !== $name) {
            $this->forceFill(['role' => $name])->save();
        }
    }

    public static function roleOptions(): array
    {
        return self::ROLE_OPTIONS;
    }

    public function roleLabel(): string
    {
        return self::ROLE_OPTIONS[$this->role]['label'] ?? ucfirst(str_replace('_', ' ', $this->role));
    }

    public function roleDescription(): string
    {
        return self::ROLE_OPTIONS[$this->role]['description'] ?? 'Workspace access';
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        $aliases = [
            'member' => 'customer',
            'operator' => 'shop_owner',
        ];
        $currentRole = $aliases[$this->role] ?? $this->role;

        return in_array($currentRole, $roles, true)
            || in_array($this->role, $roles, true)
            || $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }
}
