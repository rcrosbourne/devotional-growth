<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class VerifyEmailOtp
{
    /**
     * @throws ValidationException
     */
    public function handle(string $email, string $code): User
    {
        $otp = EmailOtp::query()->where('email', $email)->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => ['No OTP found for this email address. Please request a new one.'],
            ]);
        }

        if ($otp->isExpired()) {
            $otp->delete();

            throw ValidationException::withMessages([
                'code' => ['This OTP has expired. Please request a new one.'],
            ]);
        }

        if ($otp->hasExceededAttempts()) {
            $otp->delete();

            throw ValidationException::withMessages([
                'code' => ['Too many incorrect attempts. Please request a new OTP.'],
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['The OTP code is incorrect.'],
            ]);
        }

        return DB::transaction(function () use ($email, $otp): User {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => str($email)->before('@')->value(),
                    'email_verified_at' => now(),
                    'password' => null,
                ],
            );

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            $otp->delete();

            return $user;
        });
    }
}
