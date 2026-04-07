<?php

declare(strict_types=1);

use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\User;

test('reading plan to array', function (): void {
    $plan = ReadingPlan::factory()->create()->refresh();

    expect(array_keys($plan->toArray()))
        ->toBe([
            'id',
            'name',
            'description',
            'total_days',
            'is_default',
            'created_at',
            'updated_at',
        ]);
});

test('reading plan has many days', function (): void {
    $plan = ReadingPlan::factory()->create();
    ReadingPlanDay::factory()->for($plan, 'readingPlan')->count(3)->create();

    expect($plan->days)->toHaveCount(3);
});

test('reading plan has many progress', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();
    ReadingPlanProgress::factory()->for($plan, 'readingPlan')->for($day, 'readingPlanDay')->count(2)->create();

    expect($plan->progress)->toHaveCount(2);
});

test('reading plan scope default filters to default plans', function (): void {
    ReadingPlan::factory()->create();
    ReadingPlan::factory()->default()->create();

    expect(ReadingPlan::query()->default()->count())->toBe(1);
});

test('reading plan factory default state sets is_default', function (): void {
    $plan = ReadingPlan::factory()->default()->create();

    expect($plan->is_default)->toBeTrue();
});

test('reading plan day to array', function (): void {
    $day = ReadingPlanDay::factory()->create()->refresh();

    expect(array_keys($day->toArray()))
        ->toBe([
            'id',
            'reading_plan_id',
            'day_number',
            'passages',
            'created_at',
            'updated_at',
        ]);
});

test('reading plan day belongs to reading plan', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();

    expect($day->readingPlan)
        ->toBeInstanceOf(ReadingPlan::class)
        ->id->toBe($plan->id);
});

test('reading plan day passages is an array', function (): void {
    $day = ReadingPlanDay::factory()->create();

    expect($day->passages)->toBeArray();
});

test('reading plan day has many progress', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();
    ReadingPlanProgress::factory()->for($plan, 'readingPlan')->for($day, 'readingPlanDay')->count(2)->create();

    expect($day->progress)->toHaveCount(2);
});

test('reading plan progress to array', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();
    $progress = ReadingPlanProgress::factory()
        ->for($plan, 'readingPlan')
        ->for($day, 'readingPlanDay')
        ->create()
        ->refresh();

    expect(array_keys($progress->toArray()))
        ->toBe([
            'id',
            'user_id',
            'reading_plan_id',
            'reading_plan_day_id',
            'started_at',
            'completed_at',
            'created_at',
            'updated_at',
        ]);
});

test('reading plan progress belongs to user', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();
    $progress = ReadingPlanProgress::factory()
        ->for($user)
        ->for($plan, 'readingPlan')
        ->for($day, 'readingPlanDay')
        ->create();

    expect($progress->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('reading plan progress belongs to reading plan day', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();
    $progress = ReadingPlanProgress::factory()
        ->for($plan, 'readingPlan')
        ->for($day, 'readingPlanDay')
        ->create();

    expect($progress->readingPlanDay)
        ->toBeInstanceOf(ReadingPlanDay::class)
        ->id->toBe($day->id);
});

test('reading plan progress belongs to reading plan', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();
    $progress = ReadingPlanProgress::factory()
        ->for($plan, 'readingPlan')
        ->for($day, 'readingPlanDay')
        ->create();

    expect($progress->readingPlan)
        ->toBeInstanceOf(ReadingPlan::class)
        ->id->toBe($plan->id);
});

test('reading plan progress completed state sets completed_at', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->for($plan, 'readingPlan')->create();
    $progress = ReadingPlanProgress::factory()
        ->for($plan, 'readingPlan')
        ->for($day, 'readingPlanDay')
        ->completed()
        ->create();

    expect($progress->completed_at)->not->toBeNull();
});
