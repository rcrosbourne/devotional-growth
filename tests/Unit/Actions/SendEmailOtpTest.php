<?php

declare(strict_types=1);

use App\Actions\SendEmailOtp;
use App\Mail\OtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    Mail::fake();
});

it('generates a 6-digit OTP, hashes it, and stores it with 10-minute expiry', function (): void {
    $this->freezeTime();

    $action = resolve(SendEmailOtp::class);

    $action->handle('user@example.com');

    $otp = EmailOtp::query()->where('email', 'user@example.com')->sole();

    expect($otp->email)->toBe('user@example.com')
        ->and($otp->attempts)->toBe(0)
        ->and($otp->expires_at->toDateTimeString())->toBe(now()->addMinutes(10)->toDateTimeString());

    // code_hash should be a bcrypt hash, not plaintext
    expect($otp->code_hash)->toStartWith('$2y$')
        ->and(mb_strlen($otp->code_hash))->toBeGreaterThan(50);
});

it('sends the OTP code via email', function (): void {
    $action = resolve(SendEmailOtp::class);

    $action->handle('user@example.com');

    Mail::assertSent(OtpMail::class, fn (OtpMail $mail): bool => $mail->hasTo('user@example.com')
        && mb_strlen($mail->code) === 6
        && ctype_digit($mail->code));
});

it('deletes existing OTPs for the same email before creating a new one', function (): void {
    EmailOtp::factory()->create(['email' => 'user@example.com']);
    EmailOtp::factory()->create(['email' => 'user@example.com']);

    expect(EmailOtp::query()->where('email', 'user@example.com')->count())->toBe(2);

    $action = resolve(SendEmailOtp::class);
    $action->handle('user@example.com');

    expect(EmailOtp::query()->where('email', 'user@example.com')->count())->toBe(1);
});

it('does not delete OTPs for other emails', function (): void {
    EmailOtp::factory()->create(['email' => 'other@example.com']);

    $action = resolve(SendEmailOtp::class);
    $action->handle('user@example.com');

    expect(EmailOtp::query()->where('email', 'other@example.com')->count())->toBe(1)
        ->and(EmailOtp::query()->where('email', 'user@example.com')->count())->toBe(1);
});

it('stores a hashed code that matches the sent plaintext code', function (): void {
    $action = resolve(SendEmailOtp::class);

    $action->handle('user@example.com');

    $otp = EmailOtp::query()->where('email', 'user@example.com')->sole();

    Mail::assertSent(OtpMail::class, fn (OtpMail $mail): bool => Hash::check($mail->code, $otp->code_hash));
});

it('rate limits to 5 requests per email per hour', function (): void {
    $action = resolve(SendEmailOtp::class);

    for ($i = 0; $i < 5; $i++) {
        $action->handle('user@example.com');
    }

    expect(fn () => $action->handle('user@example.com'))
        ->toThrow(ValidationException::class, 'Too many OTP requests.');
});

it('rate limits independently per email', function (): void {
    $action = resolve(SendEmailOtp::class);

    for ($i = 0; $i < 5; $i++) {
        $action->handle('user1@example.com');
    }

    // Different email should not be rate limited
    $action->handle('user2@example.com');

    expect(EmailOtp::query()->where('email', 'user2@example.com')->count())->toBe(1);
});

it('renders the OTP mail with correct subject and content', function (): void {
    $mail = new OtpMail('123456');

    $mail->assertHasSubject('Your Devotional Growth Login Code')
        ->assertSeeInOrderInHtml(['123456']);
});

it('resets attempts to zero for the new OTP', function (): void {
    EmailOtp::factory()->create([
        'email' => 'user@example.com',
        'attempts' => 2,
    ]);

    $action = resolve(SendEmailOtp::class);
    $action->handle('user@example.com');

    $otp = EmailOtp::query()->where('email', 'user@example.com')->sole();

    expect($otp->attempts)->toBe(0);
});
