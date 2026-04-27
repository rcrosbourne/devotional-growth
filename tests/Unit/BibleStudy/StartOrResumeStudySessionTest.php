<?php

declare(strict_types=1);

use App\Actions\BibleStudy\StartOrResumeStudySession;
use App\Models\BibleStudySession;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('creates a session for a user with no existing session', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    resolve(StartOrResumeStudySession::class)->handle($user, $theme, 'Job', 1, 13, 22);

    $session = BibleStudySession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->bible_study_theme_id)->toBe($theme->id)
        ->and($session->current_book)->toBe('Job')
        ->and($session->current_chapter)->toBe(1)
        ->and($session->current_verse_start)->toBe(13)
        ->and($session->current_verse_end)->toBe(22);
});

it('updates the session in place when one already exists', function (): void {
    $user = User::factory()->create();
    BibleStudySession::factory()->for($user)->create([
        'current_book' => 'Genesis', 'current_chapter' => 1, 'current_verse_start' => 1, 'current_verse_end' => 5,
    ]);

    resolve(StartOrResumeStudySession::class)->handle($user, null, 'John', 3, 16, null);

    $count = BibleStudySession::query()->where('user_id', $user->id)->count();
    $session = BibleStudySession::query()->where('user_id', $user->id)->first();

    expect($count)->toBe(1)
        ->and($session->bible_study_theme_id)->toBeNull()
        ->and($session->current_book)->toBe('John')
        ->and($session->current_verse_end)->toBeNull();
});
