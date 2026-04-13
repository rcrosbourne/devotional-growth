<?php

declare(strict_types=1);

use App\Models\ScriptureCache;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('requires authentication to fetch scripture', function (): void {
    $response = $this->getJson(route('scripture.show', [
        'book' => 'John',
        'chapter' => 3,
        'verse_start' => 16,
    ]));

    $response->assertUnauthorized();
});

it('validates required fields', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show'));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['book', 'chapter', 'verse_start']);
});

it('validates chapter and verse are positive integers', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show', [
            'book' => 'John',
            'chapter' => 0,
            'verse_start' => -1,
        ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['chapter', 'verse_start']);
});

it('validates bible version is in allowed list', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show', [
            'book' => 'John',
            'chapter' => 3,
            'verse_start' => 16,
            'bible_version' => 'INVALID',
        ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['bible_version']);
});

it('returns scripture passage from cache', function (): void {
    $user = User::factory()->create();

    ScriptureCache::factory()->create([
        'book' => 'John',
        'chapter' => 3,
        'verse_start' => 16,
        'verse_end' => null,
        'bible_version' => 'KJV',
        'text' => 'For God so loved the world.',
    ]);

    Http::fake();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show', [
            'book' => 'John',
            'chapter' => 3,
            'verse_start' => 16,
        ]));

    $response->assertOk()
        ->assertJson([
            'text' => 'For God so loved the world.',
            'reference' => 'John 3:16',
            'bible_version' => 'KJV',
        ]);

    Http::assertNothingSent();
});

it('fetches from api and returns passage', function (): void {
    $user = User::factory()->create();

    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Romans 8:28',
            'text' => 'And we know that all things work together for good.',
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show', [
            'book' => 'Romans',
            'chapter' => 8,
            'verse_start' => 28,
        ]));

    $response->assertOk()
        ->assertJson([
            'text' => 'And we know that all things work together for good.',
            'reference' => 'Romans 8:28',
            'bible_version' => 'KJV',
        ]);
});

it('respects the bible_version parameter', function (): void {
    $user = User::factory()->create();
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => ['content' => 'For God so loved the world (NIV).'],
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show', [
            'book' => 'John',
            'chapter' => 3,
            'verse_start' => 16,
            'bible_version' => 'NIV',
        ]));

    $response->assertOk()
        ->assertJson([
            'bible_version' => 'NIV',
        ]);
});

it('includes verse range in reference', function (): void {
    $user = User::factory()->create();

    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Psalm 23:1-6',
            'text' => 'The Lord is my shepherd.',
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show', [
            'book' => 'Psalm',
            'chapter' => 23,
            'verse_start' => 1,
            'verse_end' => 6,
        ]));

    $response->assertOk()
        ->assertJson([
            'reference' => 'Psalm 23:1-6',
        ]);
});

it('returns error message when api is unavailable', function (): void {
    $user = User::factory()->create();

    Http::fake([
        'bible-api.com/*' => Http::response(null, 500),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.show', [
            'book' => 'John',
            'chapter' => 3,
            'verse_start' => 16,
        ]));

    $response->assertOk()
        ->assertJsonFragment([
            'reference' => 'John 3:16',
        ]);

    expect($response->json('text'))->toContain('Unable to load scripture passage');
});

// ── Chapter endpoint ──

it('requires authentication to fetch a chapter', function (): void {
    $response = $this->getJson(route('scripture.chapter', [
        'book' => 'Psalm',
        'chapter' => 23,
    ]));

    $response->assertUnauthorized();
});

it('validates required fields for chapter', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter'));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['book', 'chapter']);
});

it('validates bible version for chapter endpoint', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
            'bible_version' => 'INVALID',
        ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['bible_version']);
});

it('returns full chapter from cache', function (): void {
    $user = User::factory()->create();

    ScriptureCache::factory()->create([
        'book' => 'Psalm',
        'chapter' => 23,
        'verse_start' => 0,
        'verse_end' => null,
        'bible_version' => 'KJV',
        'text' => 'The Lord is my shepherd; I shall not want.',
    ]);

    Http::fake();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
        ]));

    $response->assertOk()
        ->assertJson([
            'text' => 'The Lord is my shepherd; I shall not want.',
            'reference' => 'Psalm 23',
            'bible_version' => 'KJV',
        ]);

    Http::assertNothingSent();
});

it('fetches full chapter from api', function (): void {
    $user = User::factory()->create();

    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Psalm 23',
            'text' => 'The Lord is my shepherd; I shall not want. He maketh me to lie down in green pastures.',
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
        ]));

    $response->assertOk()
        ->assertJson([
            'reference' => 'Psalm 23',
            'bible_version' => 'KJV',
        ]);

    expect($response->json('text'))->toContain('The Lord is my shepherd');
});

it('fetches full chapter from api.bible for non-KJV versions', function (): void {
    $user = User::factory()->create();
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => ['content' => 'The LORD is my shepherd; I shall not want. (NIV)'],
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
            'bible_version' => 'NIV',
        ]));

    $response->assertOk()
        ->assertJson([
            'reference' => 'Psalm 23',
            'bible_version' => 'NIV',
        ]);

    expect($response->json('text'))->toContain('The LORD is my shepherd');
});

it('handles api.bible chapter failure gracefully', function (): void {
    $user = User::factory()->create();
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response(null, 500),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
            'bible_version' => 'NIV',
        ]));

    $response->assertOk();

    expect($response->json('text'))->toContain('Unable to load');
});

it('handles missing api.bible key for chapter', function (): void {
    $user = User::factory()->create();
    config()->set('services.api_bible.key', '');

    Http::fake();

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
            'bible_version' => 'NIV',
        ]));

    $response->assertOk();

    expect($response->json('text'))->toContain('Unable to load');

    Http::assertNothingSent();
});

it('handles api.bible chapter returning invalid data', function (): void {
    $user = User::factory()->create();
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => ['content' => 123],
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
            'bible_version' => 'NIV',
        ]));

    $response->assertOk();

    expect($response->json('text'))->toContain('Unable to load');
});

it('handles api.bible chapter returning no data key', function (): void {
    $user = User::factory()->create();
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'meta' => ['something' => true],
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
            'bible_version' => 'NIV',
        ]));

    $response->assertOk();

    expect($response->json('text'))->toContain('Unable to load');
});

it('handles connection error during chapter fetch', function (): void {
    $user = User::factory()->create();

    Http::fake([
        'bible-api.com/*' => fn () => throw new Illuminate\Http\Client\ConnectionException('Connection timed out'),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
        ]));

    $response->assertOk();

    expect($response->json('text'))->toContain('Unable to load');
});

it('returns error message when chapter api is unavailable', function (): void {
    $user = User::factory()->create();

    Http::fake([
        'bible-api.com/*' => Http::response(null, 500),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('scripture.chapter', [
            'book' => 'Psalm',
            'chapter' => 23,
        ]));

    $response->assertOk()
        ->assertJsonFragment([
            'reference' => 'Psalm 23',
        ]);

    expect($response->json('text'))->toContain('Unable to load');
});
