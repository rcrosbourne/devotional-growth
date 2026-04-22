<?php

declare(strict_types=1);

use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use App\Models\WordStudy;

it('creates a word highlight against an existing word study', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();
    $wordStudy = WordStudy::factory()->create();

    $this->actingAs($admin)->post(
        route('admin.bible-study.passages.word-highlights.store', $passage),
        [
            'word_study_id' => $wordStudy->id,
            'verse_number' => 20,
            'word_index_in_verse' => 3,
            'display_word' => 'worship',
        ]
    )->assertRedirect();

    expect(BibleStudyWordHighlight::query()->count())->toBe(1);
});

it('deletes a highlight', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();
    $highlight = BibleStudyWordHighlight::factory()->for($passage, 'passage')->create();

    $this->actingAs($admin)->delete(
        route('admin.bible-study.passages.word-highlights.destroy', [$passage, $highlight])
    )->assertRedirect();

    expect(BibleStudyWordHighlight::query()->find($highlight->id))->toBeNull();
});

it('404s when deleting a highlight from a different passage', function (): void {
    $admin = User::factory()->admin()->create();
    $p1 = BibleStudyThemePassage::factory()->create();
    $p2 = BibleStudyThemePassage::factory()->create();
    $highlight = BibleStudyWordHighlight::factory()->for($p1, 'passage')->create();

    $this->actingAs($admin)->delete(
        route('admin.bible-study.passages.word-highlights.destroy', [$p2, $highlight])
    )->assertNotFound();
});
