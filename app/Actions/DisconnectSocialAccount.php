<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class DisconnectSocialAccount
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, string $provider): void
    {
        $remainingSocialAccounts = $user->socialAccounts()
            ->where('provider', '!=', $provider)
            ->count();

        $hasPassword = $user->password !== null;

        if ($remainingSocialAccounts === 0 && ! $hasPassword) {
            throw ValidationException::withMessages([
                'provider' => ['Cannot disconnect this social account. You must have at least one other authentication method (another social account or a password).'],
            ]);
        }

        $user->socialAccounts()
            ->where('provider', $provider)
            ->delete();
    }
}
