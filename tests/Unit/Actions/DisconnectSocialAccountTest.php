<?php

declare(strict_types=1);

use App\Actions\DisconnectSocialAccount;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('disconnects a social account when user has another social account', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->github()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');

    expect($user->socialAccounts()->count())->toBe(1);

    $remaining = $user->socialAccounts()->sole();

    expect($remaining->provider->value)->toBe('github');
});

it('disconnects a social account when user has a password', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);
    SocialAccount::factory()->google()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');

    expect($user->socialAccounts()->count())->toBe(0);
});

it('throws when disconnecting the only auth method without password', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');
})->throws(ValidationException::class, 'You cannot disconnect your only authentication method.');

it('preserves the social account when disconnect is rejected', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();

    $action = new DisconnectSocialAccount;

    try {
        $action->handle($user, 'google');
    } catch (ValidationException) {
        // expected
    }

    expect($user->socialAccounts()->count())->toBe(1);
});

it('throws for an invalid provider', function (): void {
    $user = User::factory()->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'invalid');
})->throws(ValueError::class);

it('does nothing when the provider is not linked to the user', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);
    SocialAccount::factory()->google()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'github');

    expect($user->socialAccounts()->count())->toBe(1)
        ->and($user->socialAccounts()->sole()->provider->value)->toBe('google');
});

it('only deletes the specified provider account', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->apple()->for($user)->create();
    SocialAccount::factory()->github()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'apple');

    expect($user->socialAccounts()->count())->toBe(2);

    $providers = $user->socialAccounts()->pluck('provider')->map->value->sort()->values()->all();

    expect($providers)->toBe(['github', 'google']);
});

it('does not affect other users social accounts', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);
    $otherUser = User::factory()->create();
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->google()->for($otherUser)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');

    expect($user->socialAccounts()->count())->toBe(0)
        ->and($otherUser->socialAccounts()->count())->toBe(1);
});

it('allows disconnect when user has multiple social accounts and no password', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->github()->for($user)->create();
    SocialAccount::factory()->apple()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');

    expect($user->socialAccounts()->count())->toBe(2);
});
