<?php

declare(strict_types=1);

use App\Actions\HandleSocialLogin;
use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

function makeSocialiteUser(
    string $id = '123456',
    ?string $name = 'John Doe',
    ?string $email = 'john@example.com',
    ?string $token = 'test-token',
    ?string $refreshToken = 'test-refresh-token',
): SocialiteUser {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = $id;
    $socialiteUser->name = $name;
    $socialiteUser->email = $email;
    $socialiteUser->token = $token;
    $socialiteUser->refreshToken = $refreshToken;

    return $socialiteUser;
}

it('creates a new user and social account when neither exist', function () {
    $action = new HandleSocialLogin;

    $user = $action->handle('google', makeSocialiteUser());

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->toBeNull();

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => SocialProvider::Google->value,
        'provider_id' => '123456',
        'provider_token' => 'test-token',
        'provider_refresh_token' => 'test-refresh-token',
    ]);
});

it('returns existing user when social account already exists', function () {
    $existingUser = User::factory()->create();
    SocialAccount::factory()->for($existingUser)->create([
        'provider' => SocialProvider::Google,
        'provider_id' => '123456',
        'provider_token' => 'old-token',
        'provider_refresh_token' => 'old-refresh-token',
    ]);

    $action = new HandleSocialLogin;

    $user = $action->handle('google', makeSocialiteUser());

    expect($user->id)->toBe($existingUser->id);
    expect(User::count())->toBe(1);
    expect(SocialAccount::count())->toBe(1);

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $existingUser->id,
        'provider_token' => 'test-token',
        'provider_refresh_token' => 'test-refresh-token',
    ]);
});

it('links social account to existing user matched by email', function () {
    $existingUser = User::factory()->create(['email' => 'john@example.com']);

    $action = new HandleSocialLogin;

    $user = $action->handle('google', makeSocialiteUser());

    expect($user->id)->toBe($existingUser->id);
    expect(User::count())->toBe(1);
    expect(SocialAccount::count())->toBe(1);

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $existingUser->id,
        'provider' => SocialProvider::Google->value,
        'provider_id' => '123456',
    ]);
});

it('updates tokens on existing social account', function () {
    $existingUser = User::factory()->create();
    SocialAccount::factory()->for($existingUser)->create([
        'provider' => SocialProvider::Google,
        'provider_id' => '123456',
        'provider_token' => 'old-token',
        'provider_refresh_token' => 'old-refresh',
    ]);

    $action = new HandleSocialLogin;
    $action->handle('google', makeSocialiteUser(
        token: 'new-token',
        refreshToken: 'new-refresh',
    ));

    $this->assertDatabaseHas('social_accounts', [
        'provider_token' => 'new-token',
        'provider_refresh_token' => 'new-refresh',
    ]);
});

it('handles different providers independently', function () {
    $action = new HandleSocialLogin;

    $user1 = $action->handle('google', makeSocialiteUser(id: '111'));
    $user2 = $action->handle('github', makeSocialiteUser(id: '111'));

    expect($user1->id)->toBe($user2->id);
    expect(SocialAccount::count())->toBe(2);
});

it('throws exception when social provider returns no email', function () {
    $action = new HandleSocialLogin;

    $action->handle('google', makeSocialiteUser(email: null));
})->throws(ValidationException::class, 'An email address is required for social login.');

it('throws exception when social provider returns empty email', function () {
    $action = new HandleSocialLogin;

    $action->handle('google', makeSocialiteUser(email: ''));
})->throws(ValidationException::class, 'An email address is required for social login.');

it('throws exception for invalid provider', function () {
    $action = new HandleSocialLogin;

    $action->handle('invalid', makeSocialiteUser());
})->throws(ValueError::class);
