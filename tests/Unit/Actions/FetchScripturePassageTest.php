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
    $action->handle('John', 3, 16, null, 'NIV');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'translation=niv'));
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
            'text' => 'NIV text here.',
        ], 200),
    ]);

    $action = resolve(FetchScripturePassage::class);

    $kjvResult = $action->handle('John', 3, 16, null, 'KJV');
    $nivResult = $action->handle('John', 3, 16, null, 'NIV');

    expect($kjvResult)->toBe('KJV text here.')
        ->and($nivResult)->toBe('NIV text here.');
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
