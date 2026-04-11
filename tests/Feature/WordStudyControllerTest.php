<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WordStudy;
use App\Models\WordStudyPassage;

// Show

it('renders the word study show page for authenticated verified users', function (): void {
    $user = User::factory()->create();
    $wordStudy = WordStudy::factory()->create();
    WordStudyPassage::factory()->count(3)->create(['word_study_id' => $wordStudy->id]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.show', $wordStudy));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('bible-study/word-study'));
});

it('includes word study details with all required fields', function (): void {
    $user = User::factory()->create();
    $wordStudy = WordStudy::factory()->greek()->create([
        'original_word' => 'ἀγάπη',
        'transliteration' => 'agape',
        'definition' => 'Unconditional love',
        'strongs_number' => 'G26',
    ]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.show', $wordStudy));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study')
            ->where('wordStudy.original_word', 'ἀγάπη')
            ->where('wordStudy.transliteration', 'agape')
            ->where('wordStudy.definition', 'Unconditional love')
            ->where('wordStudy.strongs_number', 'G26')
            ->where('wordStudy.language', 'greek')
        );
});

it('includes associated passages for a word study', function (): void {
    $user = User::factory()->create();
    $wordStudy = WordStudy::factory()->create();
    WordStudyPassage::factory()->create([
        'word_study_id' => $wordStudy->id,
        'book' => 'John',
        'chapter' => 3,
        'verse' => 16,
        'english_word' => 'love',
    ]);
    WordStudyPassage::factory()->create([
        'word_study_id' => $wordStudy->id,
        'book' => 'Romans',
        'chapter' => 5,
        'verse' => 8,
        'english_word' => 'love',
    ]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.show', $wordStudy));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study')
            ->has('wordStudy.passages', 2)
            ->where('wordStudy.passages.0.book', 'John')
            ->where('wordStudy.passages.0.chapter', 3)
            ->where('wordStudy.passages.0.verse', 16)
            ->where('wordStudy.passages.0.english_word', 'love')
        );
});

it('shows a word study with no passages', function (): void {
    $user = User::factory()->create();
    $wordStudy = WordStudy::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.show', $wordStudy));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study')
            ->has('wordStudy.passages', 0)
        );
});

it('returns 404 for a non-existent word study', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.show', 99999));

    $response->assertNotFound();
});

it('redirects unauthenticated users to login from word study show', function (): void {
    $wordStudy = WordStudy::factory()->create();

    $response = $this->get(route('bible-study.word-study.show', $wordStudy));

    $response->assertRedirectToRoute('login');
});

it('redirects unverified users from word study show', function (): void {
    $user = User::factory()->unverified()->create();
    $wordStudy = WordStudy::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.show', $wordStudy));

    $response->assertRedirect(route('verification.notice'));
});

// Search

it('renders the word study search page for authenticated verified users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('bible-study/word-study-search'));
});

it('returns empty results when no query is provided', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->where('query', '')
            ->where('results', [])
        );
});

it('returns empty results for an empty query string', function (): void {
    $user = User::factory()->create();
    WordStudy::factory()->count(3)->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search', ['q' => '']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->where('query', '')
            ->where('results', [])
        );
});

it('searches by strongs number', function (): void {
    $user = User::factory()->create();
    $target = WordStudy::factory()->create(['strongs_number' => 'G26']);
    WordStudy::factory()->create(['strongs_number' => 'G4102']);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search', ['q' => 'G26']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->where('query', 'G26')
            ->has('results', 1)
            ->where('results.0.id', $target->id)
        );
});

it('searches by transliteration', function (): void {
    $user = User::factory()->create();
    $target = WordStudy::factory()->create(['transliteration' => 'agape']);
    WordStudy::factory()->create(['transliteration' => 'pistis']);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search', ['q' => 'agape']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->has('results', 1)
            ->where('results.0.id', $target->id)
        );
});

it('searches by definition keywords', function (): void {
    $user = User::factory()->create();
    $target = WordStudy::factory()->create(['definition' => 'Unconditional love for humanity']);
    WordStudy::factory()->create(['definition' => 'Firm trust and confidence']);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search', ['q' => 'love']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->has('results', 1)
            ->where('results.0.id', $target->id)
        );
});

it('searches by english word in passages', function (): void {
    $user = User::factory()->create();
    $target = WordStudy::factory()->create();
    WordStudyPassage::factory()->create([
        'word_study_id' => $target->id,
        'english_word' => 'grace',
    ]);
    $other = WordStudy::factory()->create();
    WordStudyPassage::factory()->create([
        'word_study_id' => $other->id,
        'english_word' => 'hope',
    ]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search', ['q' => 'grace']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->has('results', 1)
            ->where('results.0.id', $target->id)
        );
});

it('includes passages in search results', function (): void {
    $user = User::factory()->create();
    $wordStudy = WordStudy::factory()->create(['strongs_number' => 'G26']);
    WordStudyPassage::factory()->count(2)->create(['word_study_id' => $wordStudy->id]);

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search', ['q' => 'G26']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->has('results.0.passages', 2)
        );
});

it('returns no results when query matches nothing', function (): void {
    $user = User::factory()->create();
    WordStudy::factory()->count(3)->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search', ['q' => 'xyznonexistent']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible-study/word-study-search')
            ->where('query', 'xyznonexistent')
            ->has('results', 0)
        );
});

it('redirects unauthenticated users to login from word study search', function (): void {
    $response = $this->get(route('bible-study.word-study.search'));

    $response->assertRedirectToRoute('login');
});

it('redirects unverified users from word study search', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('bible-study.word-study.search'));

    $response->assertRedirect(route('verification.notice'));
});
