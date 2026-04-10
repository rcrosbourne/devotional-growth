<?php

declare(strict_types=1);

use App\Actions\VerifyEmailOtp;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('authenticates an existing user on successful OTP verification', function (): void {
    $user = User::factory()->create(['email' => 'user@example.com']);

    EmailOtp::factory()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);
    $result = $action->handle('user@example.com', '123456');

    expect($result->id)->toBe($user->id)
        ->and($result->email)->toBe('user@example.com');
});

it('creates a new user when no user exists for the email', function (): void {
    EmailOtp::factory()->create(['email' => 'newuser@example.com']);

    $action = resolve(VerifyEmailOtp::class);
    $result = $action->handle('newuser@example.com', '123456');

    expect($result->email)->toBe('newuser@example.com')
        ->and($result->name)->toBe('newuser')
        ->and($result->email_verified_at)->not->toBeNull()
        ->and($result->password)->toBeNull();
});

it('marks the email as verified for an existing unverified user', function (): void {
    User::factory()->create([
        'email' => 'user@example.com',
        'email_verified_at' => null,
    ]);

    EmailOtp::factory()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);
    $result = $action->handle('user@example.com', '123456');

    expect($result->email_verified_at)->not->toBeNull();
});

it('deletes the OTP record after successful verification', function (): void {
    EmailOtp::factory()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);
    $action->handle('user@example.com', '123456');

    expect(EmailOtp::query()->where('email', 'user@example.com')->count())->toBe(0);
});

it('throws a validation exception when no OTP exists for the email', function (): void {
    $action = resolve(VerifyEmailOtp::class);
    $action->handle('user@example.com', '123456');
})->throws(ValidationException::class, 'No OTP found for this email address.');

it('throws a validation exception when the OTP has expired', function (): void {
    EmailOtp::factory()->expired()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);
    $action->handle('user@example.com', '123456');
})->throws(ValidationException::class, 'This OTP has expired.');

it('deletes the OTP record when it has expired', function (): void {
    EmailOtp::factory()->expired()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);

    try {
        $action->handle('user@example.com', '123456');
    } catch (ValidationException) {
        // expected
    }

    expect(EmailOtp::query()->where('email', 'user@example.com')->count())->toBe(0);
});

it('throws a validation exception when attempts are exceeded', function (): void {
    EmailOtp::factory()->exhausted()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);
    $action->handle('user@example.com', '123456');
})->throws(ValidationException::class, 'Too many incorrect attempts.');

it('deletes the OTP record when attempts are exceeded', function (): void {
    EmailOtp::factory()->exhausted()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);

    try {
        $action->handle('user@example.com', '123456');
    } catch (ValidationException) {
        // expected
    }

    expect(EmailOtp::query()->where('email', 'user@example.com')->count())->toBe(0);
});

it('throws a validation exception when the code is incorrect', function (): void {
    EmailOtp::factory()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);
    $action->handle('user@example.com', '000000');
})->throws(ValidationException::class, 'The OTP code is incorrect.');

it('increments the attempt counter on incorrect code', function (): void {
    EmailOtp::factory()->create([
        'email' => 'user@example.com',
        'attempts' => 0,
    ]);

    $action = resolve(VerifyEmailOtp::class);

    try {
        $action->handle('user@example.com', '000000');
    } catch (ValidationException) {
        // expected
    }

    $otp = EmailOtp::query()->where('email', 'user@example.com')->sole();

    expect($otp->attempts)->toBe(1);
});

it('does not delete the OTP record on incorrect code', function (): void {
    EmailOtp::factory()->create(['email' => 'user@example.com']);

    $action = resolve(VerifyEmailOtp::class);

    try {
        $action->handle('user@example.com', '000000');
    } catch (ValidationException) {
        // expected
    }

    expect(EmailOtp::query()->where('email', 'user@example.com')->count())->toBe(1);
});

it('does not create a user on failed verification', function (): void {
    EmailOtp::factory()->create(['email' => 'newuser@example.com']);

    $action = resolve(VerifyEmailOtp::class);

    try {
        $action->handle('newuser@example.com', '000000');
    } catch (ValidationException) {
        // expected
    }

    expect(User::query()->where('email', 'newuser@example.com')->count())->toBe(0);
});

it('increments attempts up to the limit then rejects', function (): void {
    EmailOtp::factory()->create([
        'email' => 'user@example.com',
        'attempts' => 2,
    ]);

    $action = resolve(VerifyEmailOtp::class);

    try {
        $action->handle('user@example.com', '000000');
    } catch (ValidationException) {
        // expected — this is the 3rd failed attempt, incrementing to 3
    }

    $otp = EmailOtp::query()->where('email', 'user@example.com')->first();

    expect($otp->attempts)->toBe(3);

    // Next attempt should be rejected as exceeded
    expect(fn () => $action->handle('user@example.com', '123456'))
        ->toThrow(ValidationException::class, 'Too many incorrect attempts.');
});
