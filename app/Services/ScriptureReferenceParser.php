<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use stdClass;

final readonly class ScriptureReferenceParser
{
    private const string PATTERN = '/^(\d?\s*[A-Za-z]+(?:\s+[A-Za-z]+)*)\s+(\d+):(\d+)(?:\s*-\s*(\d+))?$/';

    public function parse(string $raw): stdClass
    {
        $raw = mb_trim($raw);

        throw_unless(preg_match(self::PATTERN, $raw, $matches), InvalidArgumentException::class, 'Invalid scripture reference format: '.$raw);

        $book = mb_trim($matches[1]);
        $chapter = (int) $matches[2];
        $verseStart = (int) $matches[3];
        $verseEnd = isset($matches[4]) ? (int) $matches[4] : null;

        throw_if($verseEnd !== null && $verseEnd <= $verseStart, InvalidArgumentException::class, 'Verse end must be greater than verse start: '.$raw);

        return (object) [
            'book' => $book,
            'chapter' => $chapter,
            'verse_start' => $verseStart,
            'verse_end' => $verseEnd,
            'raw_reference' => $raw,
        ];
    }

    public function format(string $book, int $chapter, int $verseStart, ?int $verseEnd = null): string
    {
        $reference = sprintf('%s %d:%d', $book, $chapter, $verseStart);

        if ($verseEnd !== null) {
            $reference .= '-'.$verseEnd;
        }

        return $reference;
    }
}
