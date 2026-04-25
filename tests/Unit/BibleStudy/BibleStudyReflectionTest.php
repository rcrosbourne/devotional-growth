<?php

declare(strict_types=1);

use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('distinguishes passage vs verse scope by verse_number null', function (): void {
    $user = User::factory()->create();
    $passageLevel = BibleStudyReflection::factory()->for($user)->create(['verse_number' => null]);
    $verseLevel = BibleStudyReflection::factory()->for($user)->create(['verse_number' => 3]);

    expect($passageLevel->verse_number)->toBeNull()
        ->and($verseLevel->verse_number)->toBe(3)
        ->and($passageLevel->user->is($user))->toBeTrue();
});

it('defaults share flag to false', function (): void {
    $reflection = BibleStudyReflection::factory()->create();

    expect($reflection->is_shared_with_partner)->toBeFalse();
});

it('optionally links to a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($theme, 'theme')->create();

    expect($reflection->theme->is($theme))->toBeTrue();
});
