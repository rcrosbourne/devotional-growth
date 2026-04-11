<?php

declare(strict_types=1);

use App\Models\DevotionalEntry;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PartnerCompletedEntry;

// Index

it('renders the notification center page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('notifications.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('notifications/index'));
});

it('lists notifications in reverse chronological order', function (): void {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $entry1 = DevotionalEntry::factory()->published()->create(['title' => 'First']);
    $entry2 = DevotionalEntry::factory()->published()->create(['title' => 'Second']);

    $user->notify(new PartnerCompletedEntry($partner, $entry1));
    $user->notify(new PartnerCompletedEntry($partner, $entry2));

    $response = $this->actingAs($user)
        ->get(route('notifications.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('notifications', 2)
        );
});

it('marks all unread notifications as read on visit', function (): void {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();

    $user->notify(new PartnerCompletedEntry($partner, $entry));

    expect($user->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('notifications.index'));

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('returns notification preferences', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('notifications.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('preferences')
        );
});

it('requires authentication to view notifications', function (): void {
    $response = $this->get(route('notifications.index'));

    $response->assertRedirect(route('login'));
});

// Update Preferences

it('updates notification preferences', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('notifications.preferences.update'), [
            'completion_notifications' => false,
            'observation_notifications' => true,
            'new_theme_notifications' => false,
            'reminder_notifications' => true,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'completion_notifications' => false,
        'observation_notifications' => true,
        'new_theme_notifications' => false,
        'reminder_notifications' => true,
    ]);
});

it('creates notification preferences if none exist', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('notifications.preferences.update'), [
            'completion_notifications' => true,
            'observation_notifications' => false,
            'new_theme_notifications' => true,
            'reminder_notifications' => false,
        ]);

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'observation_notifications' => false,
    ]);
});

it('updates existing notification preferences', function (): void {
    $user = User::factory()->create();
    NotificationPreference::factory()->for($user)->create([
        'completion_notifications' => true,
    ]);

    $this->actingAs($user)
        ->put(route('notifications.preferences.update'), [
            'completion_notifications' => false,
            'observation_notifications' => true,
            'new_theme_notifications' => true,
            'reminder_notifications' => true,
        ]);

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'completion_notifications' => false,
    ]);
});

it('validates notification preference fields are required', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('notifications.preferences.update'), []);

    $response->assertSessionHasErrors([
        'completion_notifications',
        'observation_notifications',
        'new_theme_notifications',
        'reminder_notifications',
    ]);
});

it('validates notification preference fields are boolean', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('notifications.preferences.update'), [
            'completion_notifications' => 'not-a-boolean',
            'observation_notifications' => 'not-a-boolean',
            'new_theme_notifications' => 'not-a-boolean',
            'reminder_notifications' => 'not-a-boolean',
        ]);

    $response->assertSessionHasErrors([
        'completion_notifications',
        'observation_notifications',
        'new_theme_notifications',
        'reminder_notifications',
    ]);
});

it('requires authentication to update preferences', function (): void {
    $response = $this->put(route('notifications.preferences.update'), [
        'completion_notifications' => true,
        'observation_notifications' => true,
        'new_theme_notifications' => true,
        'reminder_notifications' => true,
    ]);

    $response->assertRedirect(route('login'));
});
