<?php

declare(strict_types=1);

use App\Actions\CreateObservation;
use App\Models\DevotionalEntry;
use App\Models\NotificationPreference;
use App\Models\Observation;
use App\Models\User;
use App\Notifications\PartnerAddedObservation;
use Illuminate\Support\Facades\Notification;

it('creates an observation for the user on a devotional entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CreateObservation::class);

    $observation = $action->handle($user, $entry, 'This passage really spoke to me.');

    expect($observation)
        ->toBeInstanceOf(Observation::class)
        ->user_id->toBe($user->id)
        ->devotional_entry_id->toBe($entry->id)
        ->body->toBe('This passage really spoke to me.')
        ->edited_at->toBeNull();
});

it('allows multiple observations from the same user on the same entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CreateObservation::class);

    $action->handle($user, $entry, 'First thought');
    $action->handle($user, $entry, 'Second thought');

    expect(Observation::query()->count())->toBe(2);
});

it('allows different users to add observations on the same entry', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CreateObservation::class);

    $action->handle($user1, $entry, 'User 1 thought');
    $action->handle($user2, $entry, 'User 2 thought');

    expect(Observation::query()->count())->toBe(2);
});

it('sends a notification to the partner when user adds an observation', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CreateObservation::class);

    $action->handle($user, $entry, 'A shared reflection');

    Notification::assertSentTo($partner, PartnerAddedObservation::class);
});

it('does not send a notification when user has no partner', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CreateObservation::class);

    $action->handle($user, $entry, 'Solo reflection');

    Notification::assertNothingSent();
});

it('does not send a notification when partner has disabled observation notifications', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'observation_notifications' => false,
    ]);
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CreateObservation::class);

    $action->handle($user, $entry, 'A reflection');

    Notification::assertNotSentTo($partner, PartnerAddedObservation::class);
});

it('sends a notification when partner has observation notifications enabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'observation_notifications' => true,
    ]);
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CreateObservation::class);

    $action->handle($user, $entry, 'A reflection');

    Notification::assertSentTo($partner, PartnerAddedObservation::class);
});

it('includes correct data in the notification', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create(['title' => 'Walking in Faith']);
    $action = resolve(CreateObservation::class);

    $action->handle($user, $entry, 'My reflection');

    Notification::assertSentTo($partner, function (PartnerAddedObservation $notification) use ($user, $entry): bool {
        $data = $notification->toArray($notification);

        return $data['partner_id'] === $user->id
            && $data['entry_id'] === $entry->id
            && $data['entry_title'] === 'Walking in Faith'
            && $data['theme_id'] === $entry->theme_id
            && isset($data['observation_id']);
    });
});
