<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PartnerCompletedEntry;

final readonly class CompleteDevotionalEntry
{
    public function handle(User $user, DevotionalEntry $entry): DevotionalCompletion
    {
        $completion = DevotionalCompletion::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'devotional_entry_id' => $entry->id,
            ],
            [
                'completed_at' => now(),
            ],
        );

        $partner = $user->partner;

        if ($partner !== null) {
            $preference = NotificationPreference::query()
                ->where('user_id', $partner->id)
                ->first();

            $shouldNotify = $preference === null || $preference->completion_notifications;

            if ($shouldNotify) {
                $partner->notify(new PartnerCompletedEntry($user, $entry));
            }
        }

        return $completion;
    }
}
