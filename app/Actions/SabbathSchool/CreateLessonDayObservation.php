<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Models\LessonDay;
use App\Models\LessonDayObservation;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PartnerAddedLessonDayObservation;

final readonly class CreateLessonDayObservation
{
    public function handle(User $user, LessonDay $lessonDay, string $body): LessonDayObservation
    {
        $observation = LessonDayObservation::query()->create([
            'user_id' => $user->id,
            'lesson_day_id' => $lessonDay->id,
            'body' => $body,
        ]);

        $partner = $user->partner;

        if ($partner !== null) {
            $preference = NotificationPreference::query()
                ->where('user_id', $partner->id)
                ->first();

            $shouldNotify = $preference === null || $preference->observation_notifications;

            if ($shouldNotify) {
                $partner->notify(new PartnerAddedLessonDayObservation($user, $lessonDay, $observation));
            }
        }

        return $observation;
    }
}
