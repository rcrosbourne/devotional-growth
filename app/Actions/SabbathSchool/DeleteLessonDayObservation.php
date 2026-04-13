<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Models\LessonDayObservation;

final readonly class DeleteLessonDayObservation
{
    public function handle(LessonDayObservation $observation): void
    {
        $observation->delete();
    }
}
