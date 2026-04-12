<?php

declare(strict_types=1);

use App\Models\SocialAccount;
use App\Models\User;

// Index

it('renders the settings page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/devotional'));
});

it('returns partner data when user has a linked partner', function (): void {
    $partner = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user = User::factory()->create(['partner_id' => $partner->id]);

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/devotional')
            ->where('partner.name', 'Jane Doe')
            ->where('partner.email', 'jane@example.com')
        );
});

it('returns null partner when user has no partner', function (): void {
    $user = User::factory()->create(['partner_id' => null]);

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/devotional')
            ->where('partner', null)
        );
});

it('returns notification preferences', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/devotional')
            ->has('preferences')
        );
});

it('returns social accounts for the user', function (): void {
    $user = User::factory()->create();
    SocialAccount::factory()->google()->for($user)->create();

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/devotional')
            ->has('socialAccounts', 1)
            ->where('socialAccounts.0.provider', 'google')
        );
});

it('returns available providers list', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/devotional')
            ->where('availableProviders', ['google', 'apple', 'github'])
        );
});

it('returns two factor enabled status', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/devotional')
            ->has('twoFactorEnabled')
        );
});

it('requires authentication to view settings', function (): void {
    $response = $this->get(route('settings.index'));

    $response->assertRedirect(route('login'));
});

// Disconnect Social Account

it('disconnects a social account', function (): void {
    $user = User::factory()->create();
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->github()->for($user)->create();

    $response = $this->actingAs($user)
        ->delete(route('settings.disconnect-social', ['provider' => 'google']));

    $response->assertRedirect();

    expect($user->socialAccounts()->count())->toBe(1);
    expect($user->socialAccounts()->first()->provider->value)->toBe('github');
});

it('requires authentication to disconnect a social account', function (): void {
    $response = $this->delete(route('settings.disconnect-social', ['provider' => 'google']));

    $response->assertRedirect(route('login'));
});
