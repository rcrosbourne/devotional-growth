<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as SocialiteUser;

final readonly class HandleSocialLogin
{
    public function handle(string $provider, SocialiteUser $socialiteUser): User
    {
        return DB::transaction(function () use ($provider, $socialiteUser): User {
            $socialProvider = SocialProvider::from($provider);

            $socialAccount = SocialAccount::query()
                ->where('provider', $socialProvider)
                ->where('provider_id', $socialiteUser->getId())
                ->first();

            if ($socialAccount) {
                $socialAccount->update([
                    'provider_token' => $socialiteUser->token,
                    'provider_refresh_token' => $socialiteUser->refreshToken,
                ]);

                return $socialAccount->user()->firstOrFail();
            }

            $email = $socialiteUser->getEmail();

            if ($email === null || $email === '') {
                throw ValidationException::withMessages([
                    'email' => ['An email address is required for social login.'],
                ]);
            }

            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $name = $socialiteUser->getName()
                    ?? $socialiteUser->getNickname()
                    ?? str($email)->before('@')->value();

                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => null,
                ]);
            }

            $user->socialAccounts()->updateOrCreate(
                ['provider' => $socialProvider],
                [
                    'provider_id' => $socialiteUser->getId(),
                    'provider_token' => $socialiteUser->token,
                    'provider_refresh_token' => $socialiteUser->refreshToken,
                ],
            );

            return $user;
        });
    }
}
