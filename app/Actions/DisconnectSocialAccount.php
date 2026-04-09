<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DisconnectSocialAccount
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, string $provider): void
    {
        $socialProvider = SocialProvider::from($provider);

        DB::transaction(function () use ($user, $socialProvider): void {
            $user->socialAccounts()->lockForUpdate()->get();

            $otherAuthMethodCount = $user->socialAccounts()
                ->where('provider', '!=', $socialProvider)
                ->count();

            $hasPassword = $user->password !== null;

            if ($otherAuthMethodCount === 0 && ! $hasPassword) {
                throw ValidationException::withMessages([
                    'provider' => ['You cannot disconnect your only authentication method.'],
                ]);
            }

            $user->socialAccounts()
                ->where('provider', $socialProvider)
                ->delete();
        });
    }
}
