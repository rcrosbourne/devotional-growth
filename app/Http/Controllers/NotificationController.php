<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NotificationController
{
    public function index(#[CurrentUser] User $user): Response
    {
        $notifications = $user->notifications()->latest()
            ->get();

        $user->unreadNotifications->markAsRead();

        $preferences = NotificationPreference::query()
            ->firstOrCreate(
                ['user_id' => $user->id],
            );

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'preferences' => $preferences,
        ]);
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated(),
        );

        return back();
    }
}
