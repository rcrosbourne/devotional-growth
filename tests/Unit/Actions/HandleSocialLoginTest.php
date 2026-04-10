<?php

declare(strict_types=1);

use App\Actions\HandleSocialLogin;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

it('auto-verifies email for newly created users', function (): void {
    $action = new HandleSocialLogin;
    $user = $action->handle('google', makeSocialiteUser());

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->email_verified_at->startOfSecond())->toEqual(now()->startOfSecond());
});

it('does not modify email verification for existing users', function (): void {
    User::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);

    $action = new HandleSocialLogin;
    $user = $action->handle('google', makeSocialiteUser());

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('creates user with null password for social-only login', function (): void {
    $action = new HandleSocialLogin;
    $user = $action->handle('google', makeSocialiteUser());

    expect($user->password)->toBeNull();
});

it('does not create a duplicate user when email already exists', function (): void {
    User::factory()->create(['email' => 'john@example.com']);

    $action = new HandleSocialLogin;
    $action->handle('google', makeSocialiteUser());

    expect(User::query()->where('email', 'john@example.com')->count())->toBe(1);
});

it('falls back to nickname when name is null', function (): void {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456';
    $socialiteUser->name = null;
    $socialiteUser->nickname = 'johndoe';
    $socialiteUser->email = 'john@example.com';
    $socialiteUser->token = 'test-token';
    $socialiteUser->refreshToken = 'test-refresh-token';

    $action = new HandleSocialLogin;
    $user = $action->handle('google', $socialiteUser);

    expect($user->name)->toBe('johndoe');
});

it('falls back to email local part when name and nickname are null', function (): void {
    $action = new HandleSocialLogin;

    $user = $action->handle('google', makeSocialiteUser(name: null));

    expect($user->name)->toBe('john');
});
