<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Models\LessonDayObservation;

final readonly class UpdateLessonDayObservation
{
    public function handle(LessonDayObservation $observation, string $body): LessonDayObservation
    {
        $observation->update([
            'body' => $body,
            'edited_at' => now(),
        ]);

        return $observation;
    }
}
