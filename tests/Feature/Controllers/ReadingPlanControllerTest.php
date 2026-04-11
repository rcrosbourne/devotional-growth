<?php

declare(strict_types=1);

use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\User;
use Illuminate\Support\Facades\Date;

// Index

it('renders the bible study index page for authenticated verified users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('bible-study/index'));
});

it('lists all available reading plans', function (): void {
    $user = User::factory()->create();
    ReadingPlan::factory()->count(2)->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/index')
            ->has('plans', 2)
        );
});

it('includes progress data for active plans', function (): void {
    Date::setTestNow('2026-03-15');

    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create(['total_days' => 10]);
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    $day2 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 2]);

    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day1->id,
        'started_at' => '2026-03-10',
        'completed_at' => '2026-03-10',
    ]);
    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day2->id,
        'started_at' => '2026-03-10',
        'completed_at' => '2026-03-11',
    ]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/index')
            ->has('activePlanIds', 1)
            ->where(sprintf('progressByPlan.%d.completed_days', $plan->id), 2)
            ->where(sprintf('progressByPlan.%d.total_days', $plan->id), 10)
            ->where(sprintf('progressByPlan.%d.percentage', $plan->id), 20)
            ->where(sprintf('progressByPlan.%d.current_day_number', $plan->id), 6)
        );

    Date::setTestNow();
});

it('redirects unauthenticated users to login from bible study index', function (): void {
    $response = $this->get(route('bible-study.index'));

    $response->assertRedirectToRoute('login');
});

it('redirects unverified users from bible study index', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.index'));

    $response->assertRedirect(route('verification.notice'));
});

// Show

it('renders the reading plan show page', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.reading-plan.show', $plan));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('bible-study/reading-plan'));
});

it('includes progress data on show page', function (): void {
    Date::setTestNow('2026-03-15');

    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create(['total_days' => 5]);
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    $day2 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 2]);

    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day1->id,
        'started_at' => '2026-03-13',
        'completed_at' => '2026-03-13',
    ]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.reading-plan.show', $plan));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/reading-plan')
            ->where('progress.completed_days', 1)
            ->where('progress.total_days', 5)
            ->where('progress.percentage', 20)
            ->where('progress.current_day_number', 3)
        );

    Date::setTestNow();
});

it('shows missed days on the reading plan show page', function (): void {
    Date::setTestNow('2026-03-15');

    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create(['total_days' => 10]);
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 2]);
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 3]);
    $day4 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 4]);

    // Activated 4 days ago, completed day 1 only — missed days 2, 3, 4
    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day1->id,
        'started_at' => '2026-03-11',
        'completed_at' => '2026-03-11',
    ]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.reading-plan.show', $plan));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/reading-plan')
            ->where('missedDays', [2, 3, 4])
        );

    Date::setTestNow();
});

it('shows current day passages', function (): void {
    Date::setTestNow('2026-03-12');

    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create(['total_days' => 10]);
    $day1 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);
    $day3 = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 3, 'passages' => ['Genesis 6-8']]);

    ReadingPlanProgress::factory()->create([
        'user_id' => $user->id,
        'reading_plan_id' => $plan->id,
        'reading_plan_day_id' => $day1->id,
        'started_at' => '2026-03-10',
        'completed_at' => '2026-03-10',
    ]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.reading-plan.show', $plan));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/reading-plan')
            ->where('currentDay.day_number', 3)
            ->where('currentDay.passages', ['Genesis 6-8'])
        );

    Date::setTestNow();
});

it('returns null current day when plan is not activated', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create(['total_days' => 10]);
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.reading-plan.show', $plan));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/reading-plan')
            ->where('currentDay', null)
            ->where('progress.current_day_number', null)
        );
});

it('redirects unauthenticated users to login from reading plan show', function (): void {
    $plan = ReadingPlan::factory()->create();

    $response = $this->get(route('bible-study.reading-plan.show', $plan));

    $response->assertRedirectToRoute('login');
});

// Activate

it('activates a reading plan for the user', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $response = $this->actingAs($user)
        ->post(route('bible-study.reading-plan.activate', $plan));

    $response->assertRedirect(route('bible-study.reading-plan.show', $plan));

    expect(ReadingPlanProgress::query()
        ->where('user_id', $user->id)
        ->where('reading_plan_id', $plan->id)
        ->count()
    )->toBe(1);
});

it('redirects unauthenticated users to login from activate', function (): void {
    $plan = ReadingPlan::factory()->create();

    $response = $this->post(route('bible-study.reading-plan.activate', $plan));

    $response->assertRedirectToRoute('login');
});

// Complete Day

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
        'completed_at' => '2026-03-09',
    ]);

    $response = $this->actingAs($user)
        ->post(route('bible-study.reading-plan.complete-day', $day2));

    $response->assertRedirect(route('bible-study.reading-plan.show', $plan));

    $progress = ReadingPlanProgress::query()
        ->where('user_id', $user->id)
        ->where('reading_plan_day_id', $day2->id)
        ->first();

    expect($progress)->not->toBeNull()
        ->and($progress->completed_at->toDateString())->toBe('2026-03-10');

    Date::setTestNow();
});

it('returns 404 when completing a day for a non-activated plan', function (): void {
    $user = User::factory()->create();
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $response = $this->actingAs($user)
        ->post(route('bible-study.reading-plan.complete-day', $day));

    $response->assertNotFound();
});

it('redirects unauthenticated users to login from complete day', function (): void {
    $plan = ReadingPlan::factory()->create();
    $day = ReadingPlanDay::factory()->create(['reading_plan_id' => $plan->id, 'day_number' => 1]);

    $response = $this->post(route('bible-study.reading-plan.complete-day', $day));

    $response->assertRedirectToRoute('login');
});
