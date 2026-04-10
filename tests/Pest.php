<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function makeSocialiteUser(
    string $id = '123456',
    ?string $name = 'John Doe',
    ?string $email = 'john@example.com',
    ?string $token = 'test-token',
    ?string $refreshToken = 'test-refresh-token',
): Laravel\Socialite\Two\User {
    $socialiteUser = new Laravel\Socialite\Two\User;
    $socialiteUser->id = $id;
    $socialiteUser->name = $name;
    $socialiteUser->email = $email;
    $socialiteUser->token = $token;
    $socialiteUser->refreshToken = $refreshToken;

    return $socialiteUser;
}
