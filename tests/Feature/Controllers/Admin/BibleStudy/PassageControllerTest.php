<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('creates a passage on a draft theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->post(
        route('admin.bible-study.themes.passages.store', $theme),
        [
            'position' => 1,
            'is_guided_path' => true,
            'book' => 'Job',
            'chapter' => 1,
            'verse_start' => 13,
            'verse_end' => 22,
            'passage_intro' => "Job's losses.",
        ]
    );

    $response->assertRedirect();

    expect($theme->passages()->count())->toBe(1);
});

it('updates a passage', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    $response = $this->actingAs($admin)->put(
        route('admin.bible-study.themes.passages.update', [$theme, $passage]),
        [
            'position' => 3,
            'is_guided_path' => false,
            'book' => $passage->book,
            'chapter' => $passage->chapter,
            'verse_start' => $passage->verse_start,
            'verse_end' => $passage->verse_end,
            'passage_intro' => 'Updated intro.',
        ]
    );

    $response->assertRedirect();

    expect($passage->fresh()->passage_intro)->toBe('Updated intro.');
});

it('404s when updating a passage from a different theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme1 = BibleStudyTheme::factory()->draft()->create();
    $theme2 = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme1, 'theme')->create();

    $this->actingAs($admin)->put(
        route('admin.bible-study.themes.passages.update', [$theme2, $passage]),
        [
            'position' => 3,
            'is_guided_path' => false,
            'book' => $passage->book,
            'chapter' => $passage->chapter,
            'verse_start' => $passage->verse_start,
            'verse_end' => $passage->verse_end,
            'passage_intro' => 'x',
        ]
    )->assertNotFound();
});

it('deletes a passage and cascades children', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    $this->actingAs($admin)->delete(
        route('admin.bible-study.themes.passages.destroy', [$theme, $passage])
    )->assertRedirect();

    expect(BibleStudyThemePassage::query()->find($passage->id))->toBeNull();
});

it('404s when deleting a passage from a different theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme1 = BibleStudyTheme::factory()->draft()->create();
    $theme2 = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme1, 'theme')->create();

    $this->actingAs($admin)->delete(
        route('admin.bible-study.themes.passages.destroy', [$theme2, $passage])
    )->assertNotFound();
});

it('reorders passages', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $p1 = BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 1]);
    $p2 = BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 2]);

    $this->actingAs($admin)->put(
        route('admin.bible-study.themes.passages.reorder', $theme),
        ['ids' => [$p2->id, $p1->id]]
    )->assertRedirect();

    expect($p2->fresh()->position)->toBe(1)
        ->and($p1->fresh()->position)->toBe(2);
});
