<?php

declare(strict_types=1);

use App\Models\EmailOtp;

test('to array', function (): void {
    $otp = EmailOtp::factory()->create()->refresh();

    expect(array_keys($otp->toArray()))
        ->toBe([
            'id',
            'email',
            'code_hash',
            'attempts',
            'expires_at',
            'created_at',
            'updated_at',
        ]);
});

test('is expired returns true when otp has expired', function (): void {
    $otp = EmailOtp::factory()->expired()->create();

    expect($otp->isExpired())->toBeTrue();
});

test('is expired returns false when otp has not expired', function (): void {
    $otp = EmailOtp::factory()->create();

    expect($otp->isExpired())->toBeFalse();
});

test('has exceeded attempts returns true when attempts reach limit', function (): void {
    $otp = EmailOtp::factory()->exhausted()->create();

    expect($otp->hasExceededAttempts())->toBeTrue();
});

test('has exceeded attempts returns false when under limit', function (): void {
    $otp = EmailOtp::factory()->create();

    expect($otp->hasExceededAttempts())->toBeFalse();
});

test('factory creates valid otp by default', function (): void {
    $otp = EmailOtp::factory()->create();

    expect($otp->attempts)->toBe(0)
        ->and($otp->isExpired())->toBeFalse()
        ->and($otp->hasExceededAttempts())->toBeFalse();
});

test('factory expired state sets expires_at in the past', function (): void {
    $otp = EmailOtp::factory()->expired()->create();

    expect($otp->expires_at->isPast())->toBeTrue();
});

test('factory exhausted state sets attempts to 3', function (): void {
    $otp = EmailOtp::factory()->exhausted()->create();

    expect($otp->attempts)->toBe(3);
});
