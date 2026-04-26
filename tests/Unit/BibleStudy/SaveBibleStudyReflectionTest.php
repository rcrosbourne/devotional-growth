<?php

declare(strict_types=1);

use App\Actions\BibleStudy\SaveBibleStudyReflection;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('creates a passage-level reflection when none exists', function (): void {
    $user = User::factory()->create();

    $reflection = resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: null,
        book: 'Job',
        chapter: 1,
        verseStart: 13,
        verseEnd: 22,
        verseNumber: null,
        body: 'Worship before understanding.',
        shareWithPartner: false,
    );

    expect($reflection->user_id)->toBe($user->id)
        ->and($reflection->verse_number)->toBeNull()
        ->and($reflection->body)->toBe('Worship before understanding.')
        ->and($reflection->is_shared_with_partner)->toBeFalse();
});

it('updates the existing passage-level reflection in place', function (): void {
    $user = User::factory()->create();
    $existing = BibleStudyReflection::factory()->for($user)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
        'verse_number' => null, 'body' => 'old', 'is_shared_with_partner' => false,
    ]);

    $result = resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: null,
        book: 'Job', chapter: 1, verseStart: 13, verseEnd: 22, verseNumber: null,
        body: 'new', shareWithPartner: true,
    );

    expect($result->id)->toBe($existing->id)
        ->and($result->fresh()->body)->toBe('new')
        ->and($result->fresh()->is_shared_with_partner)->toBeTrue()
        ->and(BibleStudyReflection::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('creates a separate verse-level annotation alongside the passage-level reflection', function (): void {
    $user = User::factory()->create();
    BibleStudyReflection::factory()->for($user)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22, 'verse_number' => null,
    ]);

    resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: null,
        book: 'Job', chapter: 1, verseStart: 13, verseEnd: 22, verseNumber: 20,
        body: 'shaved his head — ritual mourning.',
        shareWithPartner: false,
    );

    $rows = BibleStudyReflection::query()->where('user_id', $user->id)->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->whereNull('verse_number')->count())->toBe(1)
        ->and($rows->whereNotNull('verse_number')->count())->toBe(1);
});

it('records the theme id when supplied', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    $reflection = resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: $theme,
        book: 'Job', chapter: 1, verseStart: 13, verseEnd: 22, verseNumber: null,
        body: 'b', shareWithPartner: false,
    );

    expect($reflection->bible_study_theme_id)->toBe($theme->id);
});
