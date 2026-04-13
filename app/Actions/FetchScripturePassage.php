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
     * API.Bible version abbreviation → Bible ID mapping.
     *
     * @var array<string, string>
     */
    private const array API_BIBLE_IDS = [
        'NIV' => '78a9f6124f344018-01',
        'NKJV' => '63097d2a0a2f7db3-01',
        'NLT' => 'd6e14a625393b4da-01',
        'HEBREW' => '2c500771ea16da93-01',
    ];

    /**
     * Fetch a scripture passage, checking cache first then calling the appropriate Bible API.
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
            $text = $this->usesApiBible($bibleVersion)
                ? $this->fetchFromApiBible($book, $chapter, $verseStart, $verseEnd, $bibleVersion)
                : $this->fetchFromBibleApiCom($reference, $bibleVersion);

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

    /**
     * Fetch an entire chapter, checking cache first then calling the appropriate Bible API.
     */
    public function handleChapter(string $book, int $chapter, string $bibleVersion = 'KJV'): string
    {
        $cached = ScriptureCache::query()
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', 0)
            ->whereNull('verse_end')
            ->where('bible_version', $bibleVersion)
            ->first();

        if ($cached) {
            return $cached->text;
        }

        $reference = sprintf('%s %d', $book, $chapter);

        try {
            $text = $this->usesApiBible($bibleVersion)
                ? $this->fetchChapterFromApiBible($book, $chapter, $bibleVersion)
                : $this->fetchFromBibleApiCom($reference, $bibleVersion);

            if ($text === '') {
                Log::warning('Bible API returned empty text for chapter', [
                    'reference' => $reference,
                    'version' => $bibleVersion,
                ]);

                return sprintf('Unable to load %s. Please try again later.', $reference);
            }

            ScriptureCache::query()->create([
                'book' => $book,
                'chapter' => $chapter,
                'verse_start' => 0,
                'verse_end' => null,
                'bible_version' => $bibleVersion,
                'text' => $text,
            ]);

            return $text;
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Bible API connection failed for chapter', [
                'reference' => $reference,
                'version' => $bibleVersion,
                'error' => $e->getMessage(),
            ]);

            return sprintf('Unable to load %s. Please try again later.', $reference);
        }
    }

    private function usesApiBible(string $version): bool
    {
        return isset(self::API_BIBLE_IDS[$version]);
    }

    /**
     * @throws ConnectionException
     */
    private function fetchFromBibleApiCom(string $reference, string $bibleVersion): string
    {
        $encodedReference = urlencode($reference);
        $translation = mb_strtolower($bibleVersion);
        $url = sprintf('https://bible-api.com/%s?translation=%s', $encodedReference, $translation);

        $response = Http::retry(3, 500, throw: false)
            ->timeout(10)
            ->get($url);

        if ($response->failed()) {
            Log::warning('Bible API returned error response', [
                'reference' => $reference,
                'version' => $bibleVersion,
                'status' => $response->status(),
            ]);

            return '';
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        if ($json === null || ! isset($json['text']) || ! is_string($json['text'])) {
            return '';
        }

        return mb_trim($json['text']);
    }

    /**
     * @throws ConnectionException
     */
    private function fetchFromApiBible(string $book, int $chapter, int $verseStart, ?int $verseEnd, string $bibleVersion): string
    {
        $apiKey = config('services.api_bible.key');

        if (! is_string($apiKey) || $apiKey === '') {
            Log::warning('API.Bible key is not configured');

            return '';
        }

        $bibleId = self::API_BIBLE_IDS[$bibleVersion];
        $passageId = $this->buildApiBiblePassageId($book, $chapter, $verseStart, $verseEnd);

        $url = sprintf('https://rest.api.bible/v1/bibles/%s/passages/%s', $bibleId, $passageId);

        $response = Http::retry(3, 500, throw: false)
            ->timeout(10)
            ->withHeader('api-key', $apiKey)
            ->get($url, [
                'content-type' => 'text',
                'include-notes' => 'false',
                'include-titles' => 'false',
                'include-chapter-numbers' => 'false',
                'include-verse-numbers' => 'false',
                'include-verse-spans' => 'false',
            ]);

        if ($response->failed()) {
            Log::warning('API.Bible returned error response', [
                'passage' => $passageId,
                'version' => $bibleVersion,
                'status' => $response->status(),
            ]);

            return '';
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        $data = $json['data'] ?? null;

        if (! is_array($data)) {
            return '';
        }

        $content = $data['content'] ?? null;

        if (! is_string($content)) {
            return '';
        }

        return mb_trim($content);
    }

    /**
     * Build an API.Bible passage ID like "GEN.1.1" or "GEN.1.1-GEN.1.5".
     */
    private function buildApiBiblePassageId(string $book, int $chapter, int $verseStart, ?int $verseEnd): string
    {
        $bookId = $this->bookToApiBibleId($book);

        $passageId = sprintf('%s.%d.%d', $bookId, $chapter, $verseStart);

        if ($verseEnd !== null && $verseEnd !== $verseStart) {
            $passageId .= sprintf('-%s.%d.%d', $bookId, $chapter, $verseEnd);
        }

        return $passageId;
    }

    /**
     * Map a book name to the API.Bible 3-letter book ID.
     */
    private function bookToApiBibleId(string $book): string
    {
        /** @var array<string, string> $map */
        $map = [
            'Genesis' => 'GEN', 'Exodus' => 'EXO', 'Leviticus' => 'LEV',
            'Numbers' => 'NUM', 'Deuteronomy' => 'DEU', 'Joshua' => 'JOS',
            'Judges' => 'JDG', 'Ruth' => 'RUT', '1 Samuel' => '1SA',
            '2 Samuel' => '2SA', '1 Kings' => '1KI', '2 Kings' => '2KI',
            '1 Chronicles' => '1CH', '2 Chronicles' => '2CH', 'Ezra' => 'EZR',
            'Nehemiah' => 'NEH', 'Esther' => 'EST', 'Job' => 'JOB',
            'Psalms' => 'PSA', 'Psalm' => 'PSA', 'Proverbs' => 'PRO',
            'Ecclesiastes' => 'ECC', 'Song of Solomon' => 'SNG',
            'Isaiah' => 'ISA', 'Jeremiah' => 'JER', 'Lamentations' => 'LAM',
            'Ezekiel' => 'EZK', 'Daniel' => 'DAN', 'Hosea' => 'HOS',
            'Joel' => 'JOL', 'Amos' => 'AMO', 'Obadiah' => 'OBA',
            'Jonah' => 'JON', 'Micah' => 'MIC', 'Nahum' => 'NAM',
            'Habakkuk' => 'HAB', 'Zephaniah' => 'ZEP', 'Haggai' => 'HAG',
            'Zechariah' => 'ZEC', 'Malachi' => 'MAL',
            'Matthew' => 'MAT', 'Mark' => 'MRK', 'Luke' => 'LUK',
            'John' => 'JHN', 'Acts' => 'ACT', 'Romans' => 'ROM',
            '1 Corinthians' => '1CO', '2 Corinthians' => '2CO',
            'Galatians' => 'GAL', 'Ephesians' => 'EPH', 'Philippians' => 'PHP',
            'Colossians' => 'COL', '1 Thessalonians' => '1TH',
            '2 Thessalonians' => '2TH', '1 Timothy' => '1TI',
            '2 Timothy' => '2TI', 'Titus' => 'TIT', 'Philemon' => 'PHM',
            'Hebrews' => 'HEB', 'James' => 'JAS', '1 Peter' => '1PE',
            '2 Peter' => '2PE', '1 John' => '1JN', '2 John' => '2JN',
            '3 John' => '3JN', 'Jude' => 'JUD', 'Revelation' => 'REV',
        ];

        return $map[$book] ?? mb_strtoupper(mb_substr($book, 0, 3));
    }

    /**
     * @throws ConnectionException
     */
    private function fetchChapterFromApiBible(string $book, int $chapter, string $bibleVersion): string
    {
        $apiKey = config('services.api_bible.key');

        if (! is_string($apiKey) || $apiKey === '') {
            Log::warning('API.Bible key is not configured');

            return '';
        }

        $bibleId = self::API_BIBLE_IDS[$bibleVersion];
        $bookId = $this->bookToApiBibleId($book);
        $passageId = sprintf('%s.%d', $bookId, $chapter);

        $url = sprintf('https://rest.api.bible/v1/bibles/%s/passages/%s', $bibleId, $passageId);

        $response = Http::retry(3, 500, throw: false)
            ->timeout(10)
            ->withHeader('api-key', $apiKey)
            ->get($url, [
                'content-type' => 'text',
                'include-notes' => 'false',
                'include-titles' => 'false',
                'include-chapter-numbers' => 'false',
                'include-verse-numbers' => 'true',
                'include-verse-spans' => 'false',
            ]);

        if ($response->failed()) {
            Log::warning('API.Bible returned error response for chapter', [
                'passage' => $passageId,
                'version' => $bibleVersion,
                'status' => $response->status(),
            ]);

            return '';
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        $data = $json['data'] ?? null;

        if (! is_array($data)) {
            return '';
        }

        $content = $data['content'] ?? null;

        if (! is_string($content)) {
            return '';
        }

        return mb_trim($content);
    }

    private function buildReference(string $book, int $chapter, int $verseStart, ?int $verseEnd): string
    {
        $reference = sprintf('%s %d:%d', $book, $chapter, $verseStart);

        if ($verseEnd !== null && $verseEnd !== $verseStart) {
            $reference .= '-'.$verseEnd;
        }

        return $reference;
    }
}
