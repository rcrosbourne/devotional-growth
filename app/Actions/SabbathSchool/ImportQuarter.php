<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Jobs\GenerateLessonImageJob;
use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\LessonDayScriptureReference;
use App\Models\Quarterly;
use App\Services\ScriptureReferenceParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final readonly class ImportQuarter
{
    private const string BASE_URL = 'https://ssnet.org/lessons';

    private const int TOTAL_LESSONS = 13;

    /** @var array<int, string> */
    private const array QUARTER_LETTERS = ['a', 'b', 'c', 'd'];

    public function __construct(private ParseQuarterlyLesson $parser) {}

    /**
     * Import a full quarter from ssnet.org.
     *
     * @return array{quarterly: Quarterly, lessons_imported: int, lessons_failed: int, errors: array<int, string>}
     */
    public function handle(?string $quarterCode = null): array
    {
        $quarterCode = $quarterCode ?: $this->resolveCurrentQuarterCode();
        [$year, $quarterNumber] = $this->parseQuarterCode($quarterCode);

        $errors = [];
        $lessonsImported = 0;
        $lessonsFailed = 0;

        $quarterTitle = $this->fetchQuarterTitle($quarterCode);

        $quarterly = $this->upsertQuarterly($quarterCode, $year, $quarterNumber, $quarterTitle);

        for ($lessonNum = 1; $lessonNum <= self::TOTAL_LESSONS; $lessonNum++) {
            $html = $this->fetchLessonPage($quarterCode, $lessonNum);

            if ($html === null) {
                $lessonsFailed++;
                $errors[] = sprintf('Lesson %d: Failed to fetch page', $lessonNum);
                Log::warning('ImportQuarter: Failed to fetch lesson page', [
                    'quarter_code' => $quarterCode,
                    'lesson_number' => $lessonNum,
                ]);

                continue;
            }

            try {
                $parsed = $this->parser->handle($html, $lessonNum);
                $this->upsertLesson($quarterly, $parsed);
                $lessonsImported++;
            } catch (Throwable $e) { // @codeCoverageIgnoreStart
                $lessonsFailed++;
                $errors[] = sprintf('Lesson %d: %s', $lessonNum, $e->getMessage());
                Log::error('ImportQuarter: Failed to parse lesson', [
                    'quarter_code' => $quarterCode,
                    'lesson_number' => $lessonNum,
                    'error' => $e->getMessage(),
                ]);
            } // @codeCoverageIgnoreEnd
        }

        $quarterly->update(['last_synced_at' => now()]);

        Quarterly::query()
            ->where('id', '!=', $quarterly->id)
            ->update(['is_active' => false]);

        $quarterly->update(['is_active' => true]);

        $this->dispatchImageGeneration($quarterly);

        return [
            'quarterly' => $quarterly->refresh(),
            'lessons_imported' => $lessonsImported,
            'lessons_failed' => $lessonsFailed,
            'errors' => $errors,
        ];
    }

    public function resolveCurrentQuarterCode(): string
    {
        $now = \Illuminate\Support\Facades\Date::now();
        $yearShort = $now->year % 100;
        $quarterIndex = (int) ceil($now->month / 3) - 1;

        return sprintf('%02d%s', $yearShort, self::QUARTER_LETTERS[$quarterIndex]);
    }

    private function dispatchImageGeneration(Quarterly $quarterly): void
    {
        $quarterly->lessons()
            ->whereNull('image_path')
            ->each(function (Lesson $lesson): void {
                dispatch(new GenerateLessonImageJob($lesson));
            });
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseQuarterCode(string $code): array
    {
        $yearShort = (int) mb_substr($code, 0, 2);
        $letter = mb_substr($code, 2, 1);
        $quarterNumber = array_search($letter, self::QUARTER_LETTERS, true);

        if ($quarterNumber === false) {
            $quarterNumber = 0;
        }

        $year = 2000 + $yearShort;

        return [$year, $quarterNumber + 1];
    }

    private function fetchQuarterTitle(string $quarterCode): string
    {
        try {
            $url = sprintf('%s/%s/less01.html', self::BASE_URL, $quarterCode);
            $response = Http::retry(3, 500, throw: false)
                ->withOptions(['decode_content' => true])
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();

                if (preg_match('/<strong>\s*<em>([^<]+)<\/em>/i', $html, $matches)) {
                    return mb_trim($matches[1]);
                }

                if (preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
                    $titleParts = explode(' - Sabbath School', $matches[1]);

                    return mb_trim($titleParts[0]);
                }
            }
        } catch (ConnectionException|RequestException $e) {
            Log::warning('ImportQuarter: Failed to fetch quarter title', [
                'quarter_code' => $quarterCode,
                'error' => $e->getMessage(),
            ]);
        }

        return 'Quarter '.$quarterCode;
    }

    private function fetchLessonPage(string $quarterCode, int $lessonNumber): ?string
    {
        $url = sprintf('%s/%s/less%02d.html', self::BASE_URL, $quarterCode, $lessonNumber);

        try {
            $response = Http::retry(3, 500, throw: false)
                ->withOptions(['decode_content' => true])
                ->timeout(15)
                ->get($url);

            if ($response->failed()) {
                return null;
            }

            $body = $response->body();

            if (mb_strlen($body) < 2000) {
                return null;
            }

            return $body;
        } catch (ConnectionException|RequestException $e) {
            Log::warning('ImportQuarter: HTTP error fetching lesson', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function upsertQuarterly(string $quarterCode, int $year, int $quarterNumber, string $title): Quarterly
    {
        return Quarterly::query()->updateOrCreate(
            ['quarter_code' => $quarterCode],
            [
                'title' => $title,
                'year' => $year,
                'quarter_number' => $quarterNumber,
                'source_url' => sprintf('%s/%s/', self::BASE_URL, $quarterCode),
            ],
        );
    }

    /**
     * @param  array{lesson_number: int, title: string, date_start: string, date_end: string, memory_text: string, memory_text_reference: string, has_parse_warnings: bool, days: array<int, array{day_position: int, day_name: string, title: string, body: string, discussion_questions: array<int, string>|null, scripture_references: array<int, string>, has_parse_warning: bool}>}  $parsed
     */
    private function upsertLesson(Quarterly $quarterly, array $parsed): void
    {
        $parser = new ScriptureReferenceParser();

        DB::transaction(function () use ($quarterly, $parsed, $parser): void {
            $lesson = Lesson::query()->updateOrCreate(
                [
                    'quarterly_id' => $quarterly->id,
                    'lesson_number' => $parsed['lesson_number'],
                ],
                [
                    'title' => $parsed['title'],
                    'date_start' => $this->parseDateString($parsed['date_start'], $quarterly->year),
                    'date_end' => $this->parseDateString($parsed['date_end'], $quarterly->year, $parsed['date_start']),
                    'memory_text' => $parsed['memory_text'],
                    'memory_text_reference' => $parsed['memory_text_reference'],
                    'has_parse_warnings' => $parsed['has_parse_warnings'],
                ],
            );

            foreach ($parsed['days'] as $dayData) {
                $lessonDay = LessonDay::query()->updateOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                        'day_position' => $dayData['day_position'],
                    ],
                    [
                        'day_name' => $dayData['day_name'],
                        'title' => $dayData['title'],
                        'body' => $dayData['body'],
                        'discussion_questions' => $dayData['discussion_questions'],
                        'has_parse_warning' => $dayData['has_parse_warning'],
                    ],
                );

                $this->upsertScriptureReferences($lessonDay, $dayData['scripture_references'], $parser);
            }
        });
    }

    /**
     * @param  array<int, string>  $references
     */
    private function upsertScriptureReferences(LessonDay $lessonDay, array $references, ScriptureReferenceParser $parser): void
    {
        $lessonDay->scriptureReferences()->delete();

        foreach ($references as $rawRef) {
            try {
                $parsed = $parser->parse($rawRef);
                LessonDayScriptureReference::query()->create([
                    'lesson_day_id' => $lessonDay->id,
                    'book' => $parsed->book,
                    'chapter' => $parsed->chapter,
                    'verse_start' => $parsed->verse_start,
                    'verse_end' => $parsed->verse_end,
                    'raw_reference' => $rawRef,
                ]);
            } catch (InvalidArgumentException) {
                Log::info('ImportQuarter: Could not parse scripture reference', ['reference' => $rawRef]);
            }
        }
    }

    private function parseDateString(string $dateStr, int $year, ?string $referenceDate = null): string
    {
        $dateStr = mb_trim($dateStr);

        if ($dateStr === '') {
            return \Illuminate\Support\Facades\Date::now()->toDateString();
        }

        if (preg_match('/^\d{1,2}$/', $dateStr) && $referenceDate !== null && preg_match('/^(\w+)\s+\d+/', $referenceDate, $matches)) {
            $dateStr = $matches[1].' '.$dateStr;
        }

        try {
            return \Illuminate\Support\Facades\Date::parse(sprintf('%s, %d', $dateStr, $year))->toDateString();
        } catch (Throwable) {
            return \Illuminate\Support\Facades\Date::now()->toDateString();
        }
    }
}
