<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Builder;

final readonly class ResolvePassageEnrichment
{
    public function handle(string $book, int $chapter, int $verseStart, ?int $verseEnd): ?BibleStudyThemePassage
    {
        $query = BibleStudyThemePassage::query()
            ->whereHas('theme', fn (Builder $q) => $q->where('status', BibleStudyThemeStatus::Approved))
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', $verseStart);

        if ($verseEnd === null) {
            $query->whereNull('verse_end');
        } else {
            $query->where('verse_end', $verseEnd);
        }

        return $query->with(['theme', 'insight', 'historicalContext', 'wordHighlights.wordStudy'])->first();
    }
}
