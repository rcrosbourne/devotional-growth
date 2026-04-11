<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Notifications\Notification;

final readonly class SendPartnerNotification
{
    public function handle(User $partner, string $type, Notification $notification): void
    {
        $preference = NotificationPreference::query()
            ->where('user_id', $partner->id)
            ->first();

        if (! $this->shouldNotify($preference, $type)) {
            return;
        }

        $partner->notify($notification);
    }

    private function shouldNotify(?NotificationPreference $preference, string $type): bool
    {
        if (! $preference instanceof NotificationPreference) {
            return true;
        }

        return match ($type) {
            'completion' => $preference->completion_notifications,
            'observation' => $preference->observation_notifications,
            'new_theme' => $preference->new_theme_notifications,
            default => true,
        };
    }
}
