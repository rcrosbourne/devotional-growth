<?php

declare(strict_types=1);

use App\Actions\CompleteDevotionalEntry;
use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PartnerCompletedEntry;
use Illuminate\Support\Facades\Notification;

it('creates a completion record for the user', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CompleteDevotionalEntry::class);

    $completion = $action->handle($user, $entry);

    expect($completion)
        ->toBeInstanceOf(DevotionalCompletion::class)
        ->user_id->toBe($user->id)
        ->devotional_entry_id->toBe($entry->id)
        ->completed_at->not->toBeNull();
});

it('does not create a duplicate completion for the same user and entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CompleteDevotionalEntry::class);

    $first = $action->handle($user, $entry);
    $second = $action->handle($user, $entry);

    expect($first->id)->toBe($second->id);
    expect(DevotionalCompletion::query()->count())->toBe(1);
});

it('allows different users to complete the same entry', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CompleteDevotionalEntry::class);

    $action->handle($user1, $entry);
    $action->handle($user2, $entry);

    expect(DevotionalCompletion::query()->count())->toBe(2);
});

it('sends a notification to the partner when user completes an entry', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CompleteDevotionalEntry::class);

    $action->handle($user, $entry);

    Notification::assertSentTo($partner, PartnerCompletedEntry::class);
});

it('does not send a notification when user has no partner', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CompleteDevotionalEntry::class);

    $action->handle($user, $entry);

    Notification::assertNothingSent();
});

it('does not send a notification when partner has disabled completion notifications', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'completion_notifications' => false,
    ]);
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CompleteDevotionalEntry::class);

    $action->handle($user, $entry);

    Notification::assertNotSentTo($partner, PartnerCompletedEntry::class);
});

it('sends a notification when partner has completion notifications enabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'completion_notifications' => true,
    ]);
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(CompleteDevotionalEntry::class);

    $action->handle($user, $entry);

    Notification::assertSentTo($partner, PartnerCompletedEntry::class);
});

it('includes correct data in the notification', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create(['title' => 'Walking in Faith']);
    $action = resolve(CompleteDevotionalEntry::class);

    $action->handle($user, $entry);

    Notification::assertSentTo($partner, function (PartnerCompletedEntry $notification) use ($user, $entry): bool {
        $data = $notification->toArray($notification);

        return $data['partner_id'] === $user->id
            && $data['entry_id'] === $entry->id
            && $data['entry_title'] === 'Walking in Faith'
            && $data['theme_id'] === $entry->theme_id;
    });
});
