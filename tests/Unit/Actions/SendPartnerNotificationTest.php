<?php

declare(strict_types=1);

use App\Actions\SendPartnerNotification;
use App\Models\DevotionalEntry;
use App\Models\NotificationPreference;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\PartnerAddedObservation;
use App\Notifications\PartnerCompletedEntry;
use App\Notifications\PartnerStartedTheme;
use Illuminate\Support\Facades\Notification;

it('sends a completion notification when preferences allow', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $user = User::factory()->create();
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'completion', new PartnerCompletedEntry($user, $entry));

    Notification::assertSentTo($partner, PartnerCompletedEntry::class);
});

it('sends an observation notification when preferences allow', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $user = User::factory()->create();
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'observation', new PartnerAddedObservation($user, $entry));

    Notification::assertSentTo($partner, PartnerAddedObservation::class);
});

it('sends a new theme notification when preferences allow', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $user = User::factory()->create();
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'new_theme', new PartnerStartedTheme($user, $theme));

    Notification::assertSentTo($partner, PartnerStartedTheme::class);
});

it('does not send a completion notification when disabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'completion_notifications' => false,
    ]);
    $entry = DevotionalEntry::factory()->published()->create();
    $user = User::factory()->create();
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'completion', new PartnerCompletedEntry($user, $entry));

    Notification::assertNotSentTo($partner, PartnerCompletedEntry::class);
});

it('does not send an observation notification when disabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'observation_notifications' => false,
    ]);
    $entry = DevotionalEntry::factory()->published()->create();
    $user = User::factory()->create();
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'observation', new PartnerAddedObservation($user, $entry));

    Notification::assertNotSentTo($partner, PartnerAddedObservation::class);
});

it('does not send a new theme notification when disabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'new_theme_notifications' => false,
    ]);
    $theme = Theme::factory()->published()->create();
    $user = User::factory()->create();
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'new_theme', new PartnerStartedTheme($user, $theme));

    Notification::assertNotSentTo($partner, PartnerStartedTheme::class);
});

it('sends notification when no preference record exists (defaults to enabled)', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();
    $user = User::factory()->create();
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'completion', new PartnerCompletedEntry($user, $entry));

    Notification::assertSentTo($partner, PartnerCompletedEntry::class);
});

it('includes correct data in the observation notification', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create(['title' => 'Grace Abounds']);
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'observation', new PartnerAddedObservation($user, $entry));

    Notification::assertSentTo($partner, function (PartnerAddedObservation $notification) use ($user, $entry): bool {
        $data = $notification->toArray($notification);

        return $data['partner_id'] === $user->id
            && $data['entry_id'] === $entry->id
            && $data['entry_title'] === 'Grace Abounds';
    });
});

it('includes correct data in the new theme notification', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create(['name' => 'Forgiveness']);
    $action = resolve(SendPartnerNotification::class);

    $action->handle($partner, 'new_theme', new PartnerStartedTheme($user, $theme));

    Notification::assertSentTo($partner, function (PartnerStartedTheme $notification) use ($user, $theme): bool {
        $data = $notification->toArray($notification);

        return $data['partner_id'] === $user->id
            && $data['theme_id'] === $theme->id
            && $data['theme_name'] === 'Forgiveness';
    });
});
