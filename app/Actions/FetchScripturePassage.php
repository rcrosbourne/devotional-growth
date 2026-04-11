<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ScriptureCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class FetchScripturePassage
{
    /**
     * Fetch a scripture passage, checking cache first then calling the Bible API.
     */
    public function handle(string $book, int $chapter, int $verseStart, ?int $verseEnd, string $bibleVersion = 'KJV'): string
    {
        $cached = ScriptureCache::query()
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', $verseStart)
            ->where('verse_end', $verseEnd)
            ->where('bible_version', $bibleVersion)
            ->first();

        if ($cached) {
            return $cached->text;
        }

        $reference = $this->buildReference($book, $chapter, $verseStart, $verseEnd);

        try {
            $response = Http::retry(3, 500, throw: false)
                ->timeout(10)
                ->get($this->buildApiUrl($reference, $bibleVersion));

            if ($response->failed()) {
                Log::warning('Bible API returned error response', [
                    'reference' => $reference,
                    'version' => $bibleVersion,
                    'status' => $response->status(),
                ]);

                return sprintf('Unable to load scripture passage for %s. Please try again later.', $reference);
            }

            /** @var array<string, mixed>|null $json */
            $json = $response->json();
            $text = $this->extractText($json);

            if ($text === '') {
                Log::warning('Bible API returned empty text', [
                    'reference' => $reference,
                    'version' => $bibleVersion,
                ]);

                return sprintf('Unable to load scripture passage for %s. Please try again later.', $reference);
            }

            ScriptureCache::query()->create([
                'book' => $book,
                'chapter' => $chapter,
                'verse_start' => $verseStart,
                'verse_end' => $verseEnd,
                'bible_version' => $bibleVersion,
                'text' => $text,
            ]);

            return $text;
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Bible API connection failed', [
                'reference' => $reference,
                'version' => $bibleVersion,
                'error' => $e->getMessage(),
            ]);

            return sprintf('Unable to load scripture passage for %s. Please try again later.', $reference);
        }
    }

    private function buildReference(string $book, int $chapter, int $verseStart, ?int $verseEnd): string
    {
        $reference = sprintf('%s %d:%d', $book, $chapter, $verseStart);

        if ($verseEnd !== null && $verseEnd !== $verseStart) {
            $reference .= '-'.$verseEnd;
        }

        return $reference;
    }

    private function buildApiUrl(string $reference, string $bibleVersion): string
    {
        $encodedReference = urlencode($reference);
        $translation = mb_strtolower($bibleVersion);

        return sprintf('https://bible-api.com/%s?translation=%s', $encodedReference, $translation);
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractText(?array $data): string
    {
        if ($data === null || ! isset($data['text']) || ! is_string($data['text'])) {
            return '';
        }

        return mb_trim($data['text']);
    }
}
