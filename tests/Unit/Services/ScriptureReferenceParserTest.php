<?php

declare(strict_types=1);

use App\Services\ScriptureReferenceParser;

beforeEach(function (): void {
    $this->parser = new ScriptureReferenceParser;
});

it('parses a single verse reference', function (): void {
    $result = $this->parser->parse('John 3:16');

    expect($result->book)->toBe('John')
        ->and($result->chapter)->toBe(3)
        ->and($result->verse_start)->toBe(16)
        ->and($result->verse_end)->toBeNull()
        ->and($result->raw_reference)->toBe('John 3:16');
});

it('parses a verse range reference', function (): void {
    $result = $this->parser->parse('Psalm 23:1-6');

    expect($result->book)->toBe('Psalm')
        ->and($result->chapter)->toBe(23)
        ->and($result->verse_start)->toBe(1)
        ->and($result->verse_end)->toBe(6);
});

it('parses a numbered book reference', function (): void {
    $result = $this->parser->parse('1 Corinthians 13:4-7');

    expect($result->book)->toBe('1 Corinthians')
        ->and($result->chapter)->toBe(13)
        ->and($result->verse_start)->toBe(4)
        ->and($result->verse_end)->toBe(7);
});

it('parses a multi-word book reference', function (): void {
    $result = $this->parser->parse('Song of Solomon 2:1');

    expect($result->book)->toBe('Song of Solomon')
        ->and($result->chapter)->toBe(2)
        ->and($result->verse_start)->toBe(1);
});

it('handles whitespace around the reference', function (): void {
    $result = $this->parser->parse('  Romans 8:28  ');

    expect($result->book)->toBe('Romans')
        ->and($result->chapter)->toBe(8)
        ->and($result->verse_start)->toBe(28);
});

it('handles verse range with spaces around dash', function (): void {
    $result = $this->parser->parse('Romans 8:28 - 39');

    expect($result->verse_start)->toBe(28)
        ->and($result->verse_end)->toBe(39);
});

it('throws on invalid format', function (): void {
    $this->parser->parse('not a reference');
})->throws(InvalidArgumentException::class);

it('throws when verse end is less than verse start', function (): void {
    $this->parser->parse('John 3:16-10');
})->throws(InvalidArgumentException::class);

it('throws when verse end equals verse start', function (): void {
    $this->parser->parse('John 3:16-16');
})->throws(InvalidArgumentException::class);

it('formats a single verse', function (): void {
    $result = $this->parser->format('John', 3, 16);

    expect($result)->toBe('John 3:16');
});

it('formats a verse range', function (): void {
    $result = $this->parser->format('Psalm', 23, 1, 6);

    expect($result)->toBe('Psalm 23:1-6');
});
