<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final readonly class SendEmailOtp
{
    /**
     * @throws ValidationException
     */
    public function handle(string $email): void
    {
        $rateLimitKey = 'send-email-otp:'.$email;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            throw ValidationException::withMessages([
                'email' => [sprintf('Too many OTP requests. Please try again in %d seconds.', $seconds)],
            ]);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        EmailOtp::query()->where('email', $email)->delete();

        $code = mb_str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::query()->create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->send(new OtpMail($code));
    }
}
