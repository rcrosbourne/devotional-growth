<?php

declare(strict_types=1);

use App\Actions\BibleStudy\FetchStructuredPassage;
use Illuminate\Support\Facades\Http;

it('returns a verse-keyed array for KJV using bible-api.com', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Job 1:13-15',
            'verses' => [
                ['book_id' => 'JOB', 'book_name' => 'Job', 'chapter' => 1, 'verse' => 13, 'text' => "And there was a day...\n"],
                ['book_id' => 'JOB', 'book_name' => 'Job', 'chapter' => 1, 'verse' => 14, 'text' => "And there came a messenger...\n"],
                ['book_id' => 'JOB', 'book_name' => 'Job', 'chapter' => 1, 'verse' => 15, 'text' => "And the Sabeans fell upon them...\n"],
            ],
            'translation_id' => 'kjv',
            'translation_name' => 'King James Version',
        ]),
    ]);

    $result = resolve(FetchStructuredPassage::class)->handle('Job', 1, 13, 15, 'KJV');

    expect($result['structured'])->toBeTrue()
        ->and($result['verses'])->toHaveKey(13)
        ->and($result['verses'][13])->toContain('And there was a day')
        ->and($result['verses'][14])->toContain('messenger')
        ->and($result['verses'][15])->toContain('Sabeans');
});

it('falls back to a single-key map for API.Bible-only versions', function (): void {
    config()->set('services.api_bible.key', 'test-api-key');

    Http::fake([
        'rest.api.bible/*' => Http::response([
            'data' => [
                'content' => 'In the beginning God created the heavens and the earth...',
            ],
        ], 200),
    ]);

    $result = resolve(FetchStructuredPassage::class)->handle('Genesis', 1, 1, 3, 'NIV');

    expect($result['structured'])->toBeFalse()
        ->and($result['verses'])->toHaveKey(1)
        ->and($result['verses'][1])->toContain('In the beginning');
});

it('returns a single-key error placeholder when bible-api.com fails', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response('', 500),
    ]);

    $result = resolve(FetchStructuredPassage::class)->handle('Job', 1, 13, 22, 'KJV');

    expect($result['structured'])->toBeFalse()
        ->and($result['verses'])->toHaveKey(13);
});

it('falls back when bible-api.com throws a connection exception', function (): void {
    Http::fake([
        'bible-api.com/*' => fn () => throw new Illuminate\Http\Client\ConnectionException('Connection timed out'),
    ]);

    $result = resolve(FetchStructuredPassage::class)->handle('Job', 1, 13, 15, 'KJV');

    expect($result['structured'])->toBeFalse()
        ->and($result['verses'])->toHaveKey(13);
});

it('falls back when bible-api.com returns a response with no verses key', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Job 1:13',
            'text' => 'And there was a day...',
            'translation_id' => 'kjv',
        ]),
    ]);

    $result = resolve(FetchStructuredPassage::class)->handle('Job', 1, 13, null, 'KJV');

    expect($result['structured'])->toBeFalse()
        ->and($result['verses'])->toHaveKey(13);
});
