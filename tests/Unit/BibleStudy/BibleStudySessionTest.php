<?php

declare(strict_types=1);

use App\Models\BibleStudySession;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('allows at most one session per user', function (): void {
    $user = User::factory()->create();
    BibleStudySession::factory()->for($user)->create();

    expect(fn () => BibleStudySession::factory()->for($user)->create())
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('is theme-linkable but ad-hoc is allowed (theme_id null)', function (): void {
    $session = BibleStudySession::factory()->create(['bible_study_theme_id' => null]);

    expect($session->bible_study_theme_id)->toBeNull()
        ->and($session->user)->not->toBeNull();
});

it('optionally belongs to a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $session = BibleStudySession::factory()->for($theme, 'theme')->create();

    expect($session->theme->is($theme))->toBeTrue();
});
