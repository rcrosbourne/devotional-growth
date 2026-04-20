<?php

declare(strict_types=1);

use App\Models\NotificationPreference;
use App\Models\User;

it('defaults bible_study_partner_share to true', function (): void {
    $user = User::factory()->create();
    $pref = NotificationPreference::query()->create(['user_id' => $user->id])->fresh();

    expect($pref->bible_study_partner_share_notifications)->toBeTrue();
});

it('casts bible_study_partner_share column to boolean', function (): void {
    $user = User::factory()->create();
    $pref = NotificationPreference::query()->create([
        'user_id' => $user->id,
        'bible_study_partner_share_notifications' => false,
    ]);

    expect($pref->bible_study_partner_share_notifications)->toBeFalse();
});
