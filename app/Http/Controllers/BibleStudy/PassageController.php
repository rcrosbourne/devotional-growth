<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Actions\BibleStudy\FetchStructuredPassage;
use App\Actions\BibleStudy\ResolvePassageEnrichment;
use App\Actions\BibleStudy\StartOrResumeStudySession;
use App\Enums\BibleStudyThemeStatus;
use App\Http\Requests\BibleStudy\PassageQueryRequest;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PassageController
{
    public function show(
        PassageQueryRequest $request,
        #[CurrentUser] User $user,
        FetchStructuredPassage $fetcher,
        ResolvePassageEnrichment $resolver,
        StartOrResumeStudySession $sessionAction,
    ): Response {
        $book = $request->string('book')->value();
        $chapter = (int) $request->integer('chapter');
        $verseStart = (int) $request->integer('verse_start');
        $verseEnd = $request->filled('verse_end') ? (int) $request->integer('verse_end') : null;
        $translation = $request->string('translation', 'KJV')->upper()->value();

        $theme = $this->resolveTheme($request->string('theme', '')->value());
        $themePassage = $theme instanceof BibleStudyTheme
            ? $this->themePassage($theme, $book, $chapter, $verseStart, $verseEnd)
            : $resolver->handle($book, $chapter, $verseStart, $verseEnd);

        $passageTheme = $themePassage instanceof BibleStudyThemePassage ? ($themePassage->theme ?? $theme) : $theme;

        $sessionAction->handle($user, $passageTheme, $book, $chapter, $verseStart, $verseEnd);

        $scripture = $fetcher->handle($book, $chapter, $verseStart, $verseEnd, $translation);

        $reflectionUserIds = $user->hasPartner() ? [$user->id, $user->partner_id] : [$user->id];

        $reflections = BibleStudyReflection::query()
            ->whereIn('user_id', $reflectionUserIds)
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', $verseStart)
            ->when(
                $verseEnd === null,
                fn (Builder $q) => $q->whereNull('verse_end'),
                fn (Builder $q) => $q->where('verse_end', $verseEnd),
            )
            ->where(function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('is_shared_with_partner', true);
            })
            ->with('user:id,name')
            ->oldest()
            ->get();

        return Inertia::render('bible-study/passage', [
            'passage' => [
                'theme_slug' => $passageTheme?->slug,
                'theme_title' => $passageTheme?->title,
                'theme_id' => $passageTheme?->id,
                'book' => $book,
                'chapter' => $chapter,
                'verse_start' => $verseStart,
                'verse_end' => $verseEnd,
                'translation' => $translation,
                'verses' => $scripture['verses'],
                'structured' => $scripture['structured'],
                'is_enriched' => $themePassage instanceof BibleStudyThemePassage,
                'theme_passage_id' => $themePassage?->id,
                'passage_intro' => $themePassage?->passage_intro,
                'insight' => $this->insightPayload($themePassage),
                'historical_context' => $this->historicalContextPayload($themePassage),
                'word_highlights' => $this->wordHighlightsPayload($themePassage),
                'reflections' => $reflections->map(fn (BibleStudyReflection $r): array => [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'user_name' => $r->user?->name,
                    'is_own' => $r->user_id === $user->id,
                    'verse_number' => $r->verse_number,
                    'body' => $r->body,
                    'is_shared_with_partner' => $r->is_shared_with_partner,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ])->all(),
                'has_partner' => $user->hasPartner(),
            ],
        ]);
    }

    private function resolveTheme(string $slug): ?BibleStudyTheme
    {
        if ($slug === '') {
            return null;
        }

        return BibleStudyTheme::query()
            ->where('slug', $slug)
            ->where('status', BibleStudyThemeStatus::Approved)
            ->first();
    }

    private function themePassage(BibleStudyTheme $theme, string $book, int $chapter, int $verseStart, ?int $verseEnd): ?BibleStudyThemePassage
    {
        return BibleStudyThemePassage::query()
            ->where('bible_study_theme_id', $theme->id)
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', $verseStart)
            ->when(
                $verseEnd === null,
                fn (Builder $q) => $q->whereNull('verse_end'),
                fn (Builder $q) => $q->where('verse_end', $verseEnd),
            )
            ->with(['theme', 'insight', 'historicalContext', 'wordHighlights.wordStudy'])
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function insightPayload(?BibleStudyThemePassage $passage): ?array
    {
        if ($passage?->insight === null) {
            return null;
        }

        return [
            'interpretation' => $passage->insight->interpretation,
            'application' => $passage->insight->application,
            'cross_references' => $passage->insight->cross_references,
            'literary_context' => $passage->insight->literary_context,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function historicalContextPayload(?BibleStudyThemePassage $passage): ?array
    {
        if ($passage?->historicalContext === null) {
            return null;
        }

        return [
            'setting' => $passage->historicalContext->setting,
            'author' => $passage->historicalContext->author,
            'date_range' => $passage->historicalContext->date_range,
            'audience' => $passage->historicalContext->audience,
            'historical_events' => $passage->historicalContext->historical_events,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function wordHighlightsPayload(?BibleStudyThemePassage $passage): array
    {
        if (! $passage instanceof BibleStudyThemePassage) {
            return [];
        }

        return $passage->wordHighlights->map(fn (BibleStudyWordHighlight $wh): array => [
            'id' => $wh->id,
            'verse_number' => $wh->verse_number,
            'word_index_in_verse' => $wh->word_index_in_verse,
            'display_word' => $wh->display_word,
            'word_study' => $wh->wordStudy === null ? null : [
                'id' => $wh->wordStudy->id,
                'original_word' => $wh->wordStudy->original_word,
                'transliteration' => $wh->wordStudy->transliteration,
                'language' => $wh->wordStudy->language,
                'definition' => $wh->wordStudy->definition,
                'strongs_number' => $wh->wordStudy->strongs_number,
            ],
        ])->all();
    }
}
