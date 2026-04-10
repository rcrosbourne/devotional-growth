<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendEmailOtp;
use App\Actions\VerifyEmailOtp;
use App\Http\Requests\SendEmailOtpRequest;
use App\Http\Requests\VerifyEmailOtpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EmailOtpController
{
    public function create(): Response
    {
        return Inertia::render('auth/email-otp');
    }

    public function store(SendEmailOtpRequest $request, SendEmailOtp $sendEmailOtp): RedirectResponse
    {
        $email = $request->string('email')->value();

        $sendEmailOtp->handle($email);

        return to_route('email-otp.verify.show')
            ->with('email', $email)
            ->with('status', 'We have sent a verification code to your email address.');
    }

    public function showVerify(): Response
    {
        return Inertia::render('auth/email-otp-verify', [
            'email' => session('email', ''),
        ]);
    }

    public function verify(VerifyEmailOtpRequest $request, VerifyEmailOtp $verifyEmailOtp): RedirectResponse
    {
        $user = $verifyEmailOtp->handle(
            $request->string('email')->value(),
            $request->string('code')->value(),
        );

        Auth::login($user, remember: true);

        session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
