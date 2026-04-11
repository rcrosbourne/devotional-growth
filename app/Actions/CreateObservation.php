<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DevotionalEntry;
use App\Models\NotificationPreference;
use App\Models\Observation;
use App\Models\User;
use App\Notifications\PartnerAddedObservation;

final readonly class CreateObservation
{
    public function handle(User $user, DevotionalEntry $entry, string $body): Observation
    {
        $observation = Observation::query()->create([
            'user_id' => $user->id,
            'devotional_entry_id' => $entry->id,
            'body' => $body,
        ]);

        $partner = $user->partner;

        if ($partner !== null) {
            $preference = NotificationPreference::query()
                ->where('user_id', $partner->id)
                ->first();

            $shouldNotify = $preference === null || $preference->observation_notifications;

            if ($shouldNotify) {
                $partner->notify(new PartnerAddedObservation($user, $entry, $observation));
            }
        }

        return $observation;
    }
}
