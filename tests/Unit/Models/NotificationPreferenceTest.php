<?php

declare(strict_types=1);

use App\Models\NotificationPreference;
use App\Models\User;

test('to array', function (): void {
    $pref = NotificationPreference::factory()->create()->refresh();

    expect(array_keys($pref->toArray()))
        ->toBe([
            'id',
            'user_id',
            'completion_notifications',
            'observation_notifications',
            'new_theme_notifications',
            'reminder_notifications',
            'created_at',
            'updated_at',
            'bible_study_partner_share_notifications',
        ]);
});

test('user returns belongs to relationship', function (): void {
    $user = User::factory()->create();
    $pref = NotificationPreference::factory()->for($user)->create();

    expect($pref->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('factory defaults all notifications to true', function (): void {
    $pref = NotificationPreference::factory()->create();

    expect($pref->completion_notifications)->toBeTrue()
        ->and($pref->observation_notifications)->toBeTrue()
        ->and($pref->new_theme_notifications)->toBeTrue()
        ->and($pref->reminder_notifications)->toBeTrue()
        ->and($pref->bible_study_partner_share_notifications)->toBeTrue();
});

test('factory all disabled state sets all notifications to false', function (): void {
    $pref = NotificationPreference::factory()->allDisabled()->create();

    expect($pref->completion_notifications)->toBeFalse()
        ->and($pref->observation_notifications)->toBeFalse()
        ->and($pref->new_theme_notifications)->toBeFalse()
        ->and($pref->reminder_notifications)->toBeFalse()
        ->and($pref->bible_study_partner_share_notifications)->toBeFalse();
});
