<?php

declare(strict_types=1);

use App\Actions\DisconnectSocialAccount;
use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('disconnects a social account when user has another social account', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->github()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');

    expect($user->socialAccounts()->count())->toBe(1);
    $this->assertDatabaseMissing('social_accounts', [
        'user_id' => $user->id,
        'provider' => SocialProvider::Google->value,
    ]);
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => SocialProvider::GitHub->value,
    ]);
});

it('disconnects a social account when user has a password', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);
    SocialAccount::factory()->google()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');

    expect($user->socialAccounts()->count())->toBe(0);
    $this->assertDatabaseMissing('social_accounts', [
        'user_id' => $user->id,
        'provider' => SocialProvider::Google->value,
    ]);
});

it('throws when disconnecting the only auth method', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');
})->throws(ValidationException::class, 'You cannot disconnect your only authentication method.');

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

    expect($user->socialAccounts()->count())->toBe(1);
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => SocialProvider::Google->value,
    ]);
});

it('only deletes the specified provider account', function (): void {
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->apple()->for($user)->create();
    SocialAccount::factory()->github()->for($user)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'apple');

    expect($user->socialAccounts()->count())->toBe(2);
    $this->assertDatabaseMissing('social_accounts', [
        'user_id' => $user->id,
        'provider' => SocialProvider::Apple->value,
    ]);
});

it('does not affect other users social accounts', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);
    $otherUser = User::factory()->create();
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->google()->for($otherUser)->create();

    $action = new DisconnectSocialAccount;
    $action->handle($user, 'google');

    expect($user->socialAccounts()->count())->toBe(0);
    expect($otherUser->socialAccounts()->count())->toBe(1);
});
