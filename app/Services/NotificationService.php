<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;

class NotificationService
{
    public function send(
        User $user,
        string $title,
        string $body,
        string $type = 'system',
        ?string $actionLabel = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): UserNotification {
        return UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_label' => $actionLabel,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
        ]);
    }

    public function onboarding(User $user): void
    {
        if ($user->notifications()->where('type', 'onboarding')->exists()) {
            return;
        }

        $this->send(
            user: $user,
            title: 'Your EcoCycle workspace is ready',
            body: 'Scan a device, choose a facility, and track your rewards.',
            type: 'onboarding',
            actionLabel: 'Start scan',
            actionUrl: route('sustainability.index').'#deviceForm',
        );
    }
}
