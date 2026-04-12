<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DisconnectSocialAccount;
use App\Enums\SocialProvider;
use App\Models\NotificationPreference;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DevotionalSettingsController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        $user->load(['partner', 'socialAccounts']);

        $preferences = NotificationPreference::query()
            ->firstOrCreate(
                ['user_id' => $user->id],
            );

        return Inertia::render('settings/devotional', [
            'partner' => $user->partner?->only(['id', 'name', 'email']),
            'preferences' => $preferences,
            'socialAccounts' => $user->socialAccounts->map(fn (SocialAccount $account): array => [
                'id' => $account->id,
                'provider' => $account->provider->value,
            ]),
            'availableProviders' => array_map(
                fn (SocialProvider $provider): string => $provider->value,
                SocialProvider::cases(),
            ),
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function disconnectSocialAccount(
        #[CurrentUser] User $user,
        string $provider,
        DisconnectSocialAccount $action,
    ): RedirectResponse {
        $action->handle($user, $provider);

        return back();
    }
}
