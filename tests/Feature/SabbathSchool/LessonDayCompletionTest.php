<?php

declare(strict_types=1);

use App\Actions\SabbathSchool\CompleteLessonDay;
use App\Actions\SabbathSchool\UncompleteLessonDay;
use App\Models\LessonDay;
use App\Models\LessonDayCompletion;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PartnerCompletedLessonDay;
use Illuminate\Support\Facades\Notification;

// CompleteLessonDay action

it('creates a completion record for a lesson day', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $action = new CompleteLessonDay();
    $completion = $action->handle($user, $day);

    expect($completion)->toBeInstanceOf(LessonDayCompletion::class);
    expect($completion->user_id)->toBe($user->id);
    expect($completion->lesson_day_id)->toBe($day->id);
    expect($completion->completed_at)->not->toBeNull();
});

it('does not duplicate completion on repeated calls', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $action = new CompleteLessonDay();
    $action->handle($user, $day);
    $action->handle($user, $day);

    expect(LessonDayCompletion::query()->count())->toBe(1);
});

it('notifies partner when completing a lesson day', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $day = LessonDay::factory()->create();

    $action = new CompleteLessonDay();
    $action->handle($user, $day);

    Notification::assertSentTo($partner, PartnerCompletedLessonDay::class);
});

it('does not notify partner when completion notifications are disabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    NotificationPreference::query()->create([
        'user_id' => $partner->id,
        'completion_notifications' => false,
        'observation_notifications' => true,
        'new_theme_notifications' => true,
        'reminder_notifications' => true,
    ]);
    $day = LessonDay::factory()->create();

    $action = new CompleteLessonDay();
    $action->handle($user, $day);

    Notification::assertNotSentTo($partner, PartnerCompletedLessonDay::class);
});

it('does not notify when user has no partner', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $action = new CompleteLessonDay();
    $action->handle($user, $day);

    Notification::assertNothingSent();
});

// UncompleteLessonDay action

it('removes a completion record', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();
    LessonDayCompletion::factory()->create(['user_id' => $user->id, 'lesson_day_id' => $day->id]);

    $action = new UncompleteLessonDay();
    $action->handle($user, $day);

    expect(LessonDayCompletion::query()->count())->toBe(0);
});

it('does nothing when uncompleting an uncompleted day', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $action = new UncompleteLessonDay();
    $action->handle($user, $day);

    expect(LessonDayCompletion::query()->count())->toBe(0);
});

// Completion endpoints

it('marks a day as complete via POST endpoint', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $this->actingAs($user)
        ->post(route('sabbath-school.days.complete', $day))
        ->assertRedirect();

    expect(LessonDayCompletion::query()->where('user_id', $user->id)->where('lesson_day_id', $day->id)->exists())->toBeTrue();
});

it('unmarks a day as complete via DELETE endpoint', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();
    LessonDayCompletion::factory()->create(['user_id' => $user->id, 'lesson_day_id' => $day->id]);

    $this->actingAs($user)
        ->delete(route('sabbath-school.days.uncomplete', $day))
        ->assertRedirect();

    expect(LessonDayCompletion::query()->where('user_id', $user->id)->where('lesson_day_id', $day->id)->exists())->toBeFalse();
});

it('requires authentication for completion', function (): void {
    $day = LessonDay::factory()->create();

    $this->post(route('sabbath-school.days.complete', $day))
        ->assertRedirect();
});

it('requires authentication for uncompletion', function (): void {
    $day = LessonDay::factory()->create();

    $this->delete(route('sabbath-school.days.uncomplete', $day))
        ->assertRedirect();
});

// PartnerCompletedLessonDay notification

it('notification contains correct data', function (): void {
    $user = User::factory()->create(['name' => 'Alice']);
    $day = LessonDay::factory()->forDay(1)->create(['day_name' => 'Sunday']);
    $day->load('lesson');

    $notification = new PartnerCompletedLessonDay($user, $day);
    $data = $notification->toArray($user);

    expect($data['partner_id'])->toBe($user->id);
    expect($data['partner_name'])->toBe('Alice');
    expect($data['lesson_day_id'])->toBe($day->id);
    expect($data['day_name'])->toBe('Sunday');
    expect($data['message'])->toContain('Alice');
    expect($data['message'])->toContain('Sunday');
});

it('notification uses database channel', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();
    $day->load('lesson');

    $notification = new PartnerCompletedLessonDay($user, $day);

    expect($notification->via($user))->toBe(['database']);
});

// Model tests

it('lesson day has completions relationship', function (): void {
    $day = LessonDay::factory()->create();
    LessonDayCompletion::factory()->count(2)->create(['lesson_day_id' => $day->id]);

    expect($day->completions)->toHaveCount(2);
});

it('lesson day completion belongs to user and lesson day', function (): void {
    $completion = LessonDayCompletion::factory()->create();

    expect($completion->user)->toBeInstanceOf(User::class);
    expect($completion->lessonDay)->toBeInstanceOf(LessonDay::class);
});

it('lesson day completion casts work correctly', function (): void {
    $completion = LessonDayCompletion::factory()->create();
    $fresh = LessonDayCompletion::query()->find($completion->id);

    expect($fresh->id)->toBeInt();
    expect($fresh->user_id)->toBeInt();
    expect($fresh->lesson_day_id)->toBeInt();
    expect($fresh->completed_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
    expect($fresh->created_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
});
