<?php

declare(strict_types=1);

use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('redirects to the social provider', function (string $provider): void {
    Socialite::fake($provider);

    $response = $this->get(route('social.redirect', ['provider' => $provider]));

    $response->assertRedirect();
})->with(['google', 'apple', 'github']);

it('returns 404 for an invalid provider', function (): void {
    $response = $this->get(route('social.redirect', ['provider' => 'invalid']));

    $response->assertNotFound();
});

it('authenticates a new user via social callback', function (string $provider): void {
    Socialite::fake($provider, (new SocialiteUser)->map([
        'id' => 'provider-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ])->setToken('fake-token')->setRefreshToken('fake-refresh-token'));

    $response = $this->get(route('social.callback', ['provider' => $provider]));

    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    $this->assertDatabaseHas('social_accounts', [
        'provider' => $provider,
        'provider_id' => 'provider-123',
    ]);
})->with(['google', 'apple', 'github']);

it('authenticates an existing user via social callback', function (): void {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => 'github-456',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
    ])->setToken('fake-token')->setRefreshToken('fake-refresh-token'));

    $response = $this->get(route('social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_id' => 'github-456',
    ]);
});

it('authenticates a returning social user', function (): void {
    $user = User::factory()->create(['email' => 'returning@example.com']);

    SocialAccount::factory()->github()->create([
        'user_id' => $user->id,
        'provider_id' => 'github-789',
    ]);

    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => 'github-789',
        'name' => 'Returning User',
        'email' => 'returning@example.com',
    ])->setToken('new-token')->setRefreshToken('new-refresh-token'));

    $response = $this->get(route('social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_token' => 'new-token',
        'provider_refresh_token' => 'new-refresh-token',
    ]);
});

it('redirects to login with error when provider returns no email', function (): void {
    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => 'github-no-email',
        'name' => 'No Email User',
        'email' => null,
    ])->setToken('fake-token'));

    $response = $this->get(route('social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('redirects to login with error when provider returns unexpected user type', function (): void {
    $driver = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $driver->shouldReceive('user')->andReturn(
        Mockery::mock(Laravel\Socialite\Contracts\User::class),
    );

    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

    $response = $this->get(route('social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('socialite');

    $this->assertGuest();
});

it('redirects to login with error when socialite throws an exception', function (): void {
    $driver = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $driver->shouldReceive('user')->andThrow(new Exception('OAuth error'));

    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

    $response = $this->get(route('social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('socialite');

    $this->assertGuest();
});

it('returns 404 for invalid provider on callback', function (): void {
    $response = $this->get(route('social.callback', ['provider' => 'invalid']));

    $response->assertNotFound();
});

it('redirects authenticated users away from social redirect', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('social.redirect', ['provider' => 'github']));

    $response->assertRedirectToRoute('dashboard');
});

it('redirects authenticated users away from social callback', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('social.callback', ['provider' => 'github']));

    $response->assertRedirectToRoute('dashboard');
});
