<?php

declare(strict_types=1);

use App\Models\ScriptureCache;

test('to array', function (): void {
    $cache = ScriptureCache::factory()->create()->refresh();

    expect(array_keys($cache->toArray()))
        ->toBe([
            'id',
            'book',
            'chapter',
            'verse_start',
            'verse_end',
            'bible_version',
            'text',
            'created_at',
            'updated_at',
        ]);
});

test('factory defaults to kjv bible version', function (): void {
    $cache = ScriptureCache::factory()->create();

    expect($cache->bible_version)->toBe('KJV');
});

test('factory defaults verse end to null', function (): void {
    $cache = ScriptureCache::factory()->create();

    expect($cache->verse_end)->toBeNull();
});

test('factory with verse range sets verse end', function (): void {
    $cache = ScriptureCache::factory()->withVerseRange()->create();

    expect($cache->verse_end)->toBeGreaterThan($cache->verse_start);
});

test('factory with version overrides bible version', function (): void {
    $cache = ScriptureCache::factory()->withVersion('ASV')->create();

    expect($cache->bible_version)->toBe('ASV');
});
