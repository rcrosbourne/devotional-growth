<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingPlan;
use App\Models\ReadingPlanProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class ActivateReadingPlan
{
    public function handle(User $user, ReadingPlan $plan, ?CarbonInterface $startDate = null): ReadingPlanProgress
    {
        $startDate ??= Date::today();

        return DB::transaction(function () use ($user, $plan, $startDate): ReadingPlanProgress {
            $firstDay = $plan->days()->orderBy('day_number')->firstOrFail();

            return ReadingPlanProgress::query()->create([
                'user_id' => $user->id,
                'reading_plan_id' => $plan->id,
                'reading_plan_day_id' => $firstDay->id,
                'started_at' => $startDate,
                'completed_at' => null,
            ]);
        });
    }
}
