<?php

declare(strict_types=1);

use App\Actions\ActivateReadingPlan;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\User;
use Illuminate\Support\Facades\Date;

it('creates a progress record for the first day of the plan', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create(['total_days' => 365]);
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 2]);

    $action = resolve(ActivateReadingPlan::class);
    $progress = $action->handle($user, $plan);

    expect($progress)->toBeInstanceOf(ReadingPlanProgress::class)
        ->and($progress->user_id)->toBe($user->id)
        ->and($progress->reading_plan_id)->toBe($plan->id)
        ->and($progress->reading_plan_day_id)->toBe($day1->id)
        ->and($progress->started_at->toDateString())->toBe(Date::today()->toDateString())
        ->and($progress->completed_at)->toBeNull();
});

it('uses the provided start date when given', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create(['total_days' => 365]);
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $startDate = Date::parse('2026-01-15');
    $action = resolve(ActivateReadingPlan::class);
    $progress = $action->handle($user, $plan, $startDate);

    expect($progress->started_at->toDateString())->toBe('2026-01-15');
});

it('defaults to today when no start date is provided', function (): void {
    Date::setTestNow('2026-03-10');

    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $action = resolve(ActivateReadingPlan::class);
    $progress = $action->handle($user, $plan);

    expect($progress->started_at->toDateString())->toBe('2026-03-10');

    Date::setTestNow();
});

it('selects the day with the lowest day_number', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 5]);
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 3]);

    $action = resolve(ActivateReadingPlan::class);
    $progress = $action->handle($user, $plan);

    expect($progress->reading_plan_day_id)->toBe($day1->id);
});

it('persists the progress record to the database', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $action = resolve(ActivateReadingPlan::class);
    $action->handle($user, $plan);

    expect(ReadingPlanProgress::query()->where('user_id', $user->id)->where('reading_plan_id', $plan->id)->count())->toBe(1);
});
