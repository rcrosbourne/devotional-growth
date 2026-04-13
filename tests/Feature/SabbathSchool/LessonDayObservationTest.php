<?php

declare(strict_types=1);

use App\Actions\SabbathSchool\CreateLessonDayObservation;
use App\Actions\SabbathSchool\DeleteLessonDayObservation;
use App\Actions\SabbathSchool\UpdateLessonDayObservation;
use App\Models\LessonDay;
use App\Models\LessonDayObservation;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PartnerAddedLessonDayObservation;
use Illuminate\Support\Facades\Notification;

// CreateLessonDayObservation action

it('creates an observation for a lesson day', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $action = new CreateLessonDayObservation();
    $observation = $action->handle($user, $day, 'My reflection on this passage.');

    expect($observation)->toBeInstanceOf(LessonDayObservation::class);
    expect($observation->user_id)->toBe($user->id);
    expect($observation->lesson_day_id)->toBe($day->id);
    expect($observation->body)->toBe('My reflection on this passage.');
});

it('notifies partner when creating an observation', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $day = LessonDay::factory()->create();

    $action = new CreateLessonDayObservation();
    $action->handle($user, $day, 'Shared thought.');

    Notification::assertSentTo($partner, PartnerAddedLessonDayObservation::class);
});

it('does not notify partner when observation notifications are disabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    NotificationPreference::query()->create([
        'user_id' => $partner->id,
        'completion_notifications' => true,
        'observation_notifications' => false,
        'new_theme_notifications' => true,
        'reminder_notifications' => true,
    ]);
    $day = LessonDay::factory()->create();

    $action = new CreateLessonDayObservation();
    $action->handle($user, $day, 'Private thought.');

    Notification::assertNotSentTo($partner, PartnerAddedLessonDayObservation::class);
});

it('does not notify when user has no partner', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $action = new CreateLessonDayObservation();
    $action->handle($user, $day, 'Solo reflection.');

    Notification::assertNothingSent();
});

// UpdateLessonDayObservation action

it('updates an observation and sets edited_at', function (): void {
    $observation = LessonDayObservation::factory()->create(['body' => 'Original']);

    $action = new UpdateLessonDayObservation();
    $updated = $action->handle($observation, 'Updated text');

    expect($updated->body)->toBe('Updated text');
    expect($updated->edited_at)->not->toBeNull();
});

// DeleteLessonDayObservation action

it('deletes an observation', function (): void {
    $observation = LessonDayObservation::factory()->create();

    $action = new DeleteLessonDayObservation();
    $action->handle($observation);

    expect(LessonDayObservation::query()->count())->toBe(0);
});

// Observation endpoints

it('creates an observation via POST endpoint', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $this->actingAs($user)
        ->post(route('sabbath-school.observations.store', $day), ['body' => 'Test observation'])
        ->assertRedirect();

    expect(LessonDayObservation::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('validates body is required when creating observation', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $this->actingAs($user)
        ->post(route('sabbath-school.observations.store', $day), ['body' => ''])
        ->assertSessionHasErrors('body');
});

it('updates an observation via PUT endpoint', function (): void {
    $user = User::factory()->create();
    $observation = LessonDayObservation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('sabbath-school.observations.update', $observation), ['body' => 'Updated'])
        ->assertRedirect();

    expect($observation->fresh()->body)->toBe('Updated');
    expect($observation->fresh()->edited_at)->not->toBeNull();
});

it('prevents updating another user observation', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $observation = LessonDayObservation::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->put(route('sabbath-school.observations.update', $observation), ['body' => 'Hacked'])
        ->assertForbidden();
});

it('deletes an observation via DELETE endpoint', function (): void {
    $user = User::factory()->create();
    $observation = LessonDayObservation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('sabbath-school.observations.destroy', $observation))
        ->assertRedirect();

    expect(LessonDayObservation::query()->count())->toBe(0);
});

it('prevents deleting another user observation', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $observation = LessonDayObservation::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->delete(route('sabbath-school.observations.destroy', $observation))
        ->assertForbidden();
});

it('requires authentication for observation endpoints', function (): void {
    $day = LessonDay::factory()->create();
    $observation = LessonDayObservation::factory()->create();

    $this->post(route('sabbath-school.observations.store', $day), ['body' => 'Test'])->assertRedirect();
    $this->put(route('sabbath-school.observations.update', $observation), ['body' => 'Test'])->assertRedirect();
    $this->delete(route('sabbath-school.observations.destroy', $observation))->assertRedirect();
});

// Notification data

it('observation notification contains correct data', function (): void {
    $user = User::factory()->create(['name' => 'Bob']);
    $day = LessonDay::factory()->forDay(2)->create(['day_name' => 'Monday']);
    $day->load('lesson');

    $observation = LessonDayObservation::factory()->create(['lesson_day_id' => $day->id]);

    $notification = new PartnerAddedLessonDayObservation($user, $day, $observation);
    $data = $notification->toArray($user);

    expect($data['partner_name'])->toBe('Bob');
    expect($data['day_name'])->toBe('Monday');
    expect($data['observation_id'])->toBe($observation->id);
    expect($data['message'])->toContain('Bob');
    expect($data['message'])->toContain('Monday');
});

it('observation notification uses database channel', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();
    $observation = LessonDayObservation::factory()->create();

    $notification = new PartnerAddedLessonDayObservation($user, $day, $observation);

    expect($notification->via($user))->toBe(['database']);
});

// Model tests

it('lesson day has observations relationship', function (): void {
    $day = LessonDay::factory()->create();
    LessonDayObservation::factory()->count(3)->create(['lesson_day_id' => $day->id]);

    expect($day->observations)->toHaveCount(3);
});

it('observation belongs to user and lesson day', function (): void {
    $observation = LessonDayObservation::factory()->create();

    expect($observation->user)->toBeInstanceOf(User::class);
    expect($observation->lessonDay)->toBeInstanceOf(LessonDay::class);
});

it('observation casts work correctly', function (): void {
    $observation = LessonDayObservation::factory()->edited()->create();
    $fresh = LessonDayObservation::query()->find($observation->id);

    expect($fresh->id)->toBeInt();
    expect($fresh->user_id)->toBeInt();
    expect($fresh->lesson_day_id)->toBeInt();
    expect($fresh->body)->toBeString();
    expect($fresh->edited_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
    expect($fresh->created_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

// Day view passes observations

it('day view passes observations for current user', function (): void {
    $user = User::factory()->create();
    $quarterly = App\Models\Quarterly::factory()->create();
    $lesson = App\Models\Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $day = LessonDay::factory()->forDay(1)->create(['lesson_id' => $lesson->id]);
    LessonDayObservation::factory()->create(['user_id' => $user->id, 'lesson_day_id' => $day->id, 'body' => 'My thought']);

    $this->actingAs($user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $day]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('lessonDay.observations', 1)
            ->where('currentUserId', $user->id)
        );
});

it('day view shows partner observations when partner linked', function (): void {
    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $quarterly = App\Models\Quarterly::factory()->create();
    $lesson = App\Models\Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $day = LessonDay::factory()->forDay(1)->create(['lesson_id' => $lesson->id]);
    LessonDayObservation::factory()->create(['user_id' => $user->id, 'lesson_day_id' => $day->id]);
    LessonDayObservation::factory()->create(['user_id' => $partner->id, 'lesson_day_id' => $day->id]);

    $this->actingAs($user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $day]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('lessonDay.observations', 2)
        );
});
