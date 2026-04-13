<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Models\LessonDay;
use App\Models\LessonDayCompletion;
use App\Models\User;

final readonly class UncompleteLessonDay
{
    public function handle(User $user, LessonDay $lessonDay): void
    {
        LessonDayCompletion::query()
            ->where('user_id', $user->id)
            ->where('lesson_day_id', $lessonDay->id)
            ->delete();
    }
}
