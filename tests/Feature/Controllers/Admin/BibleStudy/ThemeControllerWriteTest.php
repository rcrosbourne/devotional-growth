<?php

declare(strict_types=1);

use App\Ai\Agents\BibleStudyThemeDrafter;
use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('triggers a draft via the action and redirects to the index', function (): void {
    $admin = User::factory()->admin()->create();

    BibleStudyThemeDrafter::fake([
        [
            'slug' => 'resilience',
            'short_description' => 'Faith under pressure.',
            'long_intro' => 'Resilience in scripture is not stoicism.',
            'passages' => [[
                'book' => 'Job',
                'chapter' => 1,
                'verse_start' => 13,
                'verse_end' => 22,
                'position' => 1,
                'is_guided_path' => true,
                'passage_intro' => 'Job responds to catastrophic loss with lament and worship.',
                'insights' => [
                    'interpretation' => 'Job does not charge God with wrongdoing.',
                    'application' => 'Lament and worship can coexist.',
                    'cross_references' => [],
                    'literary_context' => 'Prologue to the book of Job.',
                ],
                'historical_context' => [
                    'setting' => 'Land of Uz.',
                    'author' => 'Unknown',
                    'date_range' => 'Pre-exilic',
                    'audience' => 'Israelite wisdom audience.',
                    'historical_events' => 'Job loses family and possessions.',
                ],
                'suggested_word_highlights' => [],
            ]],
        ],
    ]);

    $response = $this->actingAs($admin)->post(route('admin.bible-study.themes.store'), [
        'title' => 'Resilience',
    ]);

    $response->assertRedirect(route('admin.bible-study.themes.index', absolute: false));

    expect(BibleStudyTheme::query()->where('slug', 'resilience')->exists())->toBeTrue();
});

it('updates theme meta', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.update', $theme), [
        'title' => 'Wisdom (edited)',
        'short_description' => 'New',
        'long_intro' => 'New intro.',
    ]);

    $response->assertRedirect();

    expect($theme->fresh()->title)->toBe('Wisdom (edited)');
});

it('publishes a draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme));

    $response->assertRedirect();

    expect($theme->fresh()->status)->toBe(BibleStudyThemeStatus::Approved);
});

it('rejects publishing a non-draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme));

    $response->assertSessionHasErrors();
});

it('deletes a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.bible-study.themes.destroy', $theme));

    $response->assertRedirect();

    expect(BibleStudyTheme::query()->find($theme->id))->toBeNull();
});
