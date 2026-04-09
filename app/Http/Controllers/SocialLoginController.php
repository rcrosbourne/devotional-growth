<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\HandleSocialLogin;
use App\Enums\SocialProvider;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

final readonly class SocialLoginController
{
    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, HandleSocialLogin $handleSocialLogin): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (Exception) {
            return to_route('login')->withErrors([
                'socialite' => 'Unable to authenticate with '.$provider.'. Please try again.',
            ]);
        }

        if (! $socialiteUser instanceof SocialiteUser) {
            return to_route('login')->withErrors([
                'socialite' => 'Unable to authenticate with '.$provider.'. Please try again.',
            ]);
        }

        try {
            $user = $handleSocialLogin->handle($provider, $socialiteUser);
        } catch (ValidationException $validationException) {
            return to_route('login')->withErrors($validationException->errors());
        }

        Auth::login($user, remember: true);

        session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function validateProvider(string $provider): void
    {
        abort_if(SocialProvider::tryFrom($provider) === null, 404);
    }
}
