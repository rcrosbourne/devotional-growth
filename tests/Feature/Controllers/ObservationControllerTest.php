<?php

declare(strict_types=1);

use App\Models\DevotionalEntry;
use App\Models\NotificationPreference;
use App\Models\Observation;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\PartnerAddedObservation;
use Illuminate\Support\Facades\Notification;

// Store

it('creates an observation on a devotional entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();

    $response = $this->actingAs($user)
        ->post(route('observations.store', $entry), [
            'body' => 'This is my reflection.',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('observations', [
        'user_id' => $user->id,
        'devotional_entry_id' => $entry->id,
        'body' => 'This is my reflection.',
    ]);
});

it('requires a body to create an observation', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->published()->create();

    $response = $this->actingAs($user)
        ->post(route('observations.store', $entry), [
            'body' => '',
        ]);

    $response->assertSessionHasErrors('body');
});

it('requires authentication to create an observation', function (): void {
    $entry = DevotionalEntry::factory()->published()->create();

    $response = $this->post(route('observations.store', $entry), [
        'body' => 'Some text',
    ]);

    $response->assertRedirect(route('login'));
});

it('sends partner notification when creating an observation', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();

    $this->actingAs($user)
        ->post(route('observations.store', $entry), [
            'body' => 'A shared thought.',
        ]);

    Notification::assertSentTo($partner, PartnerAddedObservation::class);
});

it('does not send partner notification when observation notifications are disabled', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    NotificationPreference::factory()->for($partner)->create([
        'observation_notifications' => false,
    ]);
    $user = User::factory()->withPartner($partner)->create();
    $entry = DevotionalEntry::factory()->published()->create();

    $this->actingAs($user)
        ->post(route('observations.store', $entry), [
            'body' => 'A thought.',
        ]);

    Notification::assertNotSentTo($partner, PartnerAddedObservation::class);
});

// Update

it('updates an observation belonging to the user', function (): void {
    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create(['body' => 'Original']);

    $response = $this->actingAs($user)
        ->put(route('observations.update', $observation), [
            'body' => 'Updated reflection.',
        ]);

    $response->assertRedirect();
    expect($observation->fresh())
        ->body->toBe('Updated reflection.')
        ->edited_at->not->toBeNull();
});

it('requires a body to update an observation', function (): void {
    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->put(route('observations.update', $observation), [
            'body' => '',
        ]);

    $response->assertSessionHasErrors('body');
});

it('forbids updating another user observation', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $observation = Observation::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)
        ->put(route('observations.update', $observation), [
            'body' => 'Hacked!',
        ]);

    $response->assertForbidden();
});

it('requires authentication to update an observation', function (): void {
    $observation = Observation::factory()->create();

    $response = $this->put(route('observations.update', $observation), [
        'body' => 'Updated',
    ]);

    $response->assertRedirect(route('login'));
});

// Destroy

it('deletes an observation belonging to the user', function (): void {
    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->delete(route('observations.destroy', $observation));

    $response->assertRedirect();
    $this->assertDatabaseMissing('observations', ['id' => $observation->id]);
});

it('forbids deleting another user observation', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $observation = Observation::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)
        ->delete(route('observations.destroy', $observation));

    $response->assertForbidden();
    $this->assertDatabaseHas('observations', ['id' => $observation->id]);
});

it('requires authentication to delete an observation', function (): void {
    $observation = Observation::factory()->create();

    $response = $this->delete(route('observations.destroy', $observation));

    $response->assertRedirect(route('login'));
});

// Chronological ordering

it('returns observations in chronological order on the entry show page', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $first = Observation::factory()->for($user)->for($entry)->create(['created_at' => now()->subMinutes(2)]);
    $second = Observation::factory()->for($user)->for($entry)->create(['created_at' => now()->subMinute()]);
    $third = Observation::factory()->for($user)->for($entry)->create(['created_at' => now()]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->has('entry.observations', 3)
            ->where('entry.observations.0.id', $first->id)
            ->where('entry.observations.1.id', $second->id)
            ->where('entry.observations.2.id', $third->id)
        );
});

// Partner visibility

it('shows partner observations when users are linked', function (): void {
    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    Observation::factory()->for($user)->for($entry)->create(['body' => 'My note']);
    Observation::factory()->for($partner)->for($entry)->create(['body' => 'Partner note']);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('entry.observations', 2)
        );
});

it('does not show other users observations when not partnered', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    Observation::factory()->for($user)->for($entry)->create(['body' => 'My note']);
    Observation::factory()->for($otherUser)->for($entry)->create(['body' => 'Other note']);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('entry.observations', 1)
        );
});
