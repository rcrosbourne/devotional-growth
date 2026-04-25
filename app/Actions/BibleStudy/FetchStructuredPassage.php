<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Actions\FetchScripturePassage;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class FetchStructuredPassage
{
    /**
     * @var array<int, string> Versions exposed via bible-api.com (returns structured JSON).
     */
    private const array BIBLE_API_VERSIONS = ['KJV', 'ASV', 'WEB', 'BBE', 'DARBY'];

    public function __construct(private FetchScripturePassage $fallback) {}

    /**
     * @return array{verses: array<int, string>, structured: bool}
     */
    public function handle(string $book, int $chapter, int $verseStart, ?int $verseEnd, string $bibleVersion = 'KJV'): array
    {
        if (in_array($bibleVersion, self::BIBLE_API_VERSIONS, true)) {
            $structured = $this->fetchStructured($book, $chapter, $verseStart, $verseEnd, $bibleVersion);
            if ($structured !== null) {
                return ['verses' => $structured, 'structured' => true];
            }
        }

        $text = $this->fallback->handle($book, $chapter, $verseStart, $verseEnd, $bibleVersion);

        return [
            'verses' => [$verseStart => $text],
            'structured' => false,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function fetchStructured(string $book, int $chapter, int $verseStart, ?int $verseEnd, string $bibleVersion): ?array
    {
        $reference = sprintf('%s %d:%d', $book, $chapter, $verseStart);
        if ($verseEnd !== null && $verseEnd !== $verseStart) {
            $reference .= '-'.$verseEnd;
        }

        $url = sprintf(
            'https://bible-api.com/%s?translation=%s',
            rawurlencode($reference),
            mb_strtolower($bibleVersion),
        );

        try {
            $response = Http::timeout(10)->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array{verses?: array<int, array{verse: int, text: string}>} $body */
        $body = $response->json();
        $verses = $body['verses'] ?? [];

        if ($verses === []) {
            return null;
        }

        $structured = [];
        foreach ($verses as $verse) {
            $structured[(int) $verse['verse']] = mb_trim($verse['text']);
        }

        return $structured;
    }
}
