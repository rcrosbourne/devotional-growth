<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Models\LessonDay;
use App\Models\LessonDayCompletion;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PartnerCompletedLessonDay;

final readonly class CompleteLessonDay
{
    public function handle(User $user, LessonDay $lessonDay): LessonDayCompletion
    {
        $completion = LessonDayCompletion::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_day_id' => $lessonDay->id,
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
                $partner->notify(new PartnerCompletedLessonDay($user, $lessonDay));
            }
        }

        return $completion;
    }
}
