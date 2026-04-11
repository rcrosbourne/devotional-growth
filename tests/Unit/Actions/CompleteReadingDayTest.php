<?php

declare(strict_types=1);

use App\Actions\CompleteReadingDay;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\User;
use Illuminate\Support\Facades\Date;

it('marks a reading plan day as complete', function (): void {
    Date::setTestNow('2026-03-10');

    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    $day2 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 2]);

    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day1->id,
        'started_at' => '2026-03-09',
        'completed_at' => null,
    ]);

    $action = resolve(CompleteReadingDay::class);
    $progress = $action->handle($user, $day2);

    expect($progress)->toBeInstanceOf(ReadingPlanProgress::class)
        ->and($progress->user_id)->toBe($user->id)
        ->and($progress->reading_plan_day_id)->toBe($day2->id)
        ->and($progress->completed_at->toDateString())->toBe('2026-03-10')
        ->and($progress->started_at->toDateString())->toBe('2026-03-09');

    Date::setTestNow();
});

it('preserves the original started_at from activation', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    $day3 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 3]);

    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day1->id,
        'started_at' => '2026-01-01',
        'completed_at' => '2026-01-01',
    ]);

    $action = resolve(CompleteReadingDay::class);
    $progress = $action->handle($user, $day3);

    expect($progress->started_at->toDateString())->toBe('2026-01-01');
});

it('aborts with 404 if the plan has not been activated', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $action = resolve(CompleteReadingDay::class);
    $action->handle($user, $day);
})->throws(Symfony\Component\HttpKernel\Exception\HttpException::class);

it('does not create duplicate progress records for the same day', function (): void {
    Date::setTestNow('2026-03-10');

    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day1->id,
        'started_at' => '2026-03-09',
        'completed_at' => null,
    ]);

    $action = resolve(CompleteReadingDay::class);
    $action->handle($user, $day1);

    expect(ReadingPlanProgress::query()
        ->where('user_id', $user->id)
        ->where('reading_plan_day_id', $day1->id)
        ->count()
    )->toBe(1);

    Date::setTestNow();
});
