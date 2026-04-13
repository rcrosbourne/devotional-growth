<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FetchScripturePassage;
use App\Http\Requests\FetchChapterRequest;
use App\Http\Requests\FetchScriptureRequest;
use Illuminate\Http\JsonResponse;

final readonly class ScriptureController
{
    public function __construct(private FetchScripturePassage $fetchScripturePassage) {}

    public function show(FetchScriptureRequest $request): JsonResponse
    {
        /** @var array{book: string, chapter: string, verse_start: string, verse_end?: string|null, bible_version?: string|null} $validated */
        $validated = $request->validated();

        $book = $validated['book'];
        $chapter = (int) $validated['chapter'];
        $verseStart = (int) $validated['verse_start'];
        $verseEnd = isset($validated['verse_end']) ? (int) $validated['verse_end'] : null;
        $bibleVersion = $validated['bible_version'] ?? 'KJV';

        $text = $this->fetchScripturePassage->handle($book, $chapter, $verseStart, $verseEnd, $bibleVersion);

        return response()->json([
            'text' => $text,
            'reference' => $this->formatReference($book, $chapter, $verseStart, $verseEnd),
            'bible_version' => $bibleVersion,
        ]);
    }

    public function chapter(FetchChapterRequest $request): JsonResponse
    {
        /** @var array{book: string, chapter: string, bible_version?: string|null} $validated */
        $validated = $request->validated();

        $book = $validated['book'];
        $chapter = (int) $validated['chapter'];
        $bibleVersion = $validated['bible_version'] ?? 'KJV';

        $text = $this->fetchScripturePassage->handleChapter($book, $chapter, $bibleVersion);

        return response()->json([
            'text' => $text,
            'reference' => sprintf('%s %d', $book, $chapter),
            'bible_version' => $bibleVersion,
        ]);
    }

    private function formatReference(string $book, int $chapter, int $verseStart, ?int $verseEnd): string
    {
        $reference = sprintf('%s %d:%d', $book, $chapter, $verseStart);

        if ($verseEnd !== null && $verseEnd !== $verseStart) {
            $reference .= '-'.$verseEnd;
        }

        return $reference;
    }
}
