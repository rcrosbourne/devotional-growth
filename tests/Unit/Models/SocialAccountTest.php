<?php

declare(strict_types=1);

use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;

test('to array', function (): void {
    $socialAccount = SocialAccount::factory()->create()->refresh();

    expect(array_keys($socialAccount->toArray()))
        ->toBe([
            'id',
            'user_id',
            'provider',
            'provider_id',
            'provider_token',
            'provider_refresh_token',
            'created_at',
            'updated_at',
        ]);
});

test('user returns belongs to relationship', function (): void {
    $user = User::factory()->create();
    $socialAccount = SocialAccount::factory()->for($user)->create();

    expect($socialAccount->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('factory creates google provider by default', function (): void {
    $socialAccount = SocialAccount::factory()->create();

    expect($socialAccount->provider)->toBe(SocialProvider::Google);
});

test('factory google state sets provider to google', function (): void {
    $socialAccount = SocialAccount::factory()->google()->create();

    expect($socialAccount->provider)->toBe(SocialProvider::Google);
});

test('factory apple state sets provider to apple', function (): void {
    $socialAccount = SocialAccount::factory()->apple()->create();

    expect($socialAccount->provider)->toBe(SocialProvider::Apple);
});

test('factory github state sets provider to github', function (): void {
    $socialAccount = SocialAccount::factory()->github()->create();

    expect($socialAccount->provider)->toBe(SocialProvider::GitHub);
});

test('user has many social accounts', function (): void {
    $user = User::factory()->create();
    SocialAccount::factory()->google()->for($user)->create();
    SocialAccount::factory()->apple()->for($user)->create();

    expect($user->socialAccounts)->toHaveCount(2);
});
