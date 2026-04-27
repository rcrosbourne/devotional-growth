<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'verses' => [
                ['verse' => 13, 'text' => 'And there was a day...'],
                ['verse' => 14, 'text' => 'And there came a messenger...'],
            ],
        ]),
    ]);
});

it('renders the reader view for a theme passage with full enrichment', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]);
    BibleStudyInsight::factory()->for($passage, 'passage')->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'theme' => 'resilience', 'book' => 'Job', 'chapter' => 1,
        'verse_start' => 13, 'verse_end' => 14, 'translation' => 'KJV',
    ]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('bible-study/passage')
        ->where('passage.book', 'Job')
        ->where('passage.is_enriched', true)
        ->has('passage.verses', 2)
        ->has('passage.insight')
        ->has('passage.historical_context')
        ->where('passage.translation', 'KJV')
    );
});

it('renders an ad-hoc passage with enrichment promotion when the reference matches an approved theme passage', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]);

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('passage.is_enriched', true)
        ->where('passage.theme_slug', 'resilience')
    );
});

it('renders an ad-hoc passage without enrichment when no theme matches', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('passage.is_enriched', false)
        ->where('passage.insight', null)
        ->where('passage.historical_context', null)
        ->where('passage.theme_slug', null)
    );
});

it("loads the user's own and the partner's shared reflections", function (): void {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $user->update(['partner_id' => $partner->id]);
    $partner->update(['partner_id' => $user->id]);

    BibleStudyReflection::factory()->for($user)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
        'verse_number' => null, 'body' => 'mine', 'is_shared_with_partner' => true,
    ]);
    BibleStudyReflection::factory()->for($partner)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
        'verse_number' => null, 'body' => 'partner-shared', 'is_shared_with_partner' => true,
    ]);
    BibleStudyReflection::factory()->for($partner)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
        'verse_number' => null, 'body' => 'partner-private', 'is_shared_with_partner' => false,
    ]);

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]));

    $response->assertInertia(fn ($page) => $page
        ->has('passage.reflections', 2)
        ->where('passage.has_partner', true)
    );
});

it('includes word study data when a word highlight has an associated word study', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]);
    App\Models\BibleStudyWordHighlight::factory()->for($passage, 'passage')->create([
        'verse_number' => 13,
        'word_index_in_verse' => 0,
        'display_word' => 'day',
    ]);

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'theme' => 'resilience', 'book' => 'Job', 'chapter' => 1,
        'verse_start' => 13, 'verse_end' => 14,
    ]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->has('passage.word_highlights', 1)
        ->where('passage.word_highlights.0.display_word', 'day')
        ->has('passage.word_highlights.0.word_study')
    );
});

it('redirects unauthenticated users', function (): void {
    $this->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]))->assertRedirectToRoute('login');
});
