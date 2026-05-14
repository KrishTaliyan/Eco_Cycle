<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecyclingCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'recycling_activity_id',
        'session_id',
        'certificate_number',
        'holder_name',
        'verification_token',
        'qr_payload',
        'impact_summary',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'impact_summary' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(RecyclingActivity::class, 'recycling_activity_id');
    }
}
