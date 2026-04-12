<?php

declare(strict_types=1);

use App\Actions\FetchScripturePassage;
use App\Models\ScriptureCache;
use Illuminate\Support\Facades\Http;

it('returns cached text when scripture is already cached', function (): void {
    ScriptureCache::factory()->create([
        'book' => 'John',
        'chapter' => 3,
        'verse_start' => 16,
        'verse_end' => null,
        'bible_version' => 'KJV',
        'text' => 'For God so loved the world, that he gave his only begotten Son.',
    ]);

    Http::fake();

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'KJV');

    expect($result)->toBe('For God so loved the world, that he gave his only begotten Son.');
    Http::assertNothingSent();
});

it('fetches from bible api when not cached', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'John 3:16',
            'text' => 'For God so loved the world, that he gave his only begotten Son.',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'KJV');

    expect($result)->toBe('For God so loved the world, that he gave his only begotten Son.');
    Http::assertSentCount(1);
});

it('caches the fetched passage in the database', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Psalm 23:1-6',
            'text' => 'The Lord is my shepherd; I shall not want.',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $action->handle('Psalm', 23, 1, 6, 'KJV');

    $cached = ScriptureCache::query()
        ->where('book', 'Psalm')
        ->where('chapter', 23)
        ->where('verse_start', 1)
        ->where('verse_end', 6)
        ->where('bible_version', 'KJV')
        ->first();

    expect($cached)->not->toBeNull()
        ->and($cached->text)->toBe('The Lord is my shepherd; I shall not want.');
});

it('returns error message when api fails', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response(null, 500),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'KJV');

    expect($result)->toContain('Unable to load scripture passage')
        ->and($result)->toContain('John 3:16');
});

it('returns error message on connection failure', function (): void {
    Http::fake([
        'bible-api.com/*' => fn () => throw new Illuminate\Http\Client\ConnectionException('Connection timed out'),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('Romans', 8, 28, 39, 'KJV');

    expect($result)->toContain('Unable to load scripture passage')
        ->and($result)->toContain('Romans 8:28-39');
});

it('does not cache failed responses', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response(null, 500),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $action->handle('John', 3, 16, null, 'KJV');

    expect(ScriptureCache::query()->count())->toBe(0);
});

it('uses the correct bible version in api request', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'John 3:16',
            'text' => 'For God so loved the world...',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $action->handle('John', 3, 16, null, 'ASV');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'translation=asv'));
});

it('defaults to kjv when no version specified', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'John 3:16',
            'text' => 'For God so loved the world...',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $action->handle('John', 3, 16, null);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'translation=kjv'));
});

it('distinguishes cache by bible version', function (): void {
    ScriptureCache::factory()->create([
        'book' => 'John',
        'chapter' => 3,
        'verse_start' => 16,
        'verse_end' => null,
        'bible_version' => 'KJV',
        'text' => 'KJV text here.',
    ]);

    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'John 3:16',
            'text' => 'ASV text here.',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);

    $kjvResult = $action->handle('John', 3, 16, null, 'KJV');
    $nivResult = $action->handle('John', 3, 16, null, 'ASV');

    expect($kjvResult)->toBe('KJV text here.')
        ->and($nivResult)->toBe('ASV text here.');
});

it('returns error message when api returns empty text', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'John 3:16',
            'text' => '',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'KJV');

    expect($result)->toContain('Unable to load scripture passage')
        ->and($result)->toContain('John 3:16');
    expect(ScriptureCache::query()->count())->toBe(0);
});

it('returns error message when api response has no text key', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'John 3:16',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'KJV');

    expect($result)->toContain('Unable to load scripture passage');
    expect(ScriptureCache::query()->count())->toBe(0);
});

it('returns error message when api returns null body', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response(null, 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'KJV');

    expect($result)->toContain('Unable to load scripture passage');
    expect(ScriptureCache::query()->count())->toBe(0);
});

// API.Bible integration

it('fetches from api.bible for NIV version', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => [
                'content' => 'The fear of the Lord is the beginning of wisdom.',
            ],
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('Proverbs', 9, 10, null, 'NIV');

    expect($result)->toBe('The fear of the Lord is the beginning of wisdom.');

    Http::assertSent(function ($request): bool {
        $url = (string) $request->url();

        return str_contains($url, 'rest.api.bible')
            && str_contains($url, 'PRO.9.10')
            && $request->header('api-key')[0] === 'test-api-key';
    });
});

it('sends correct passage id with verse range to api.bible', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => ['content' => 'Trust in the Lord with all your heart.'],
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $action->handle('Proverbs', 3, 5, 7, 'NKJV');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'PRO.3.5-PRO.3.7'));
});

it('returns empty string when api.bible key is not configured', function (): void {
    config()->set('services.api_bible.key');

    Http::fake();

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'NIV');

    expect($result)->toContain('Unable to load scripture passage');
    Http::assertNothingSent();
});

it('caches api.bible responses', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => ['content' => 'For God so loved the world.'],
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $action->handle('John', 3, 16, null, 'NIV');

    $cached = ScriptureCache::query()
        ->where('book', 'John')
        ->where('bible_version', 'NIV')
        ->first();

    expect($cached)->not->toBeNull()
        ->and($cached->text)->toBe('For God so loved the world.');
});

it('uses bible-api.com for KJV and api.bible for NIV', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'John 3:16',
            'text' => 'KJV text.',
        ], 200),
        'rest.api.bible/*' => Http::response([
            'data' => ['content' => 'NIV text.'],
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);

    $kjv = $action->handle('John', 3, 16, null, 'KJV');
    $niv = $action->handle('John', 3, 16, null, 'NIV');

    expect($kjv)->toBe('KJV text.')
        ->and($niv)->toBe('NIV text.');
});

it('returns error message when api.bible returns failure status', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response(null, 500),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'NIV');

    expect($result)->toContain('Unable to load scripture passage');
    expect(ScriptureCache::query()->count())->toBe(0);
});

it('returns error message when api.bible returns invalid data structure', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => 'not-an-array',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'NIV');

    expect($result)->toContain('Unable to load scripture passage');
    expect(ScriptureCache::query()->count())->toBe(0);
});

it('returns error message when api.bible returns no content', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => ['content' => null],
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('John', 3, 16, null, 'NIV');

    expect($result)->toContain('Unable to load scripture passage');
    expect(ScriptureCache::query()->count())->toBe(0);
});

it('handles verse range in reference format', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Romans 8:28-39',
            'text' => 'And we know that all things work together for good.',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);
    $result = $action->handle('Romans', 8, 28, 39, 'KJV');

    expect($result)->toBe('And we know that all things work together for good.');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), urlencode('Romans 8:28-39')));
});
