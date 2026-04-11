<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\User;
use Illuminate\Support\Facades\Date;

final readonly class CompleteReadingDay
{
    public function handle(User $user, ReadingPlanDay $day): ReadingPlanProgress
    {
        $startedAt = ReadingPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('reading_plan_id', $day->reading_plan_id)
            ->value('started_at');

        abort_unless($startedAt !== null, 404, 'Reading plan has not been activated.');

        return ReadingPlanProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'reading_plan_id' => $day->reading_plan_id,
                'reading_plan_day_id' => $day->id,
            ],
            [
                'started_at' => $startedAt,
                'completed_at' => Date::today(),
            ],
        );
    }
}
