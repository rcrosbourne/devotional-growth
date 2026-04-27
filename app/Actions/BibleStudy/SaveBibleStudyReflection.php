<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

final readonly class SaveBibleStudyReflection
{
    public function handle(
        User $user,
        ?BibleStudyTheme $theme,
        string $book,
        int $chapter,
        int $verseStart,
        ?int $verseEnd,
        ?int $verseNumber,
        string $body,
        bool $shareWithPartner,
    ): BibleStudyReflection {
        $key = [
            'user_id' => $user->id,
            'book' => $book,
            'chapter' => $chapter,
            'verse_start' => $verseStart,
            'verse_end' => $verseEnd,
            'verse_number' => $verseNumber,
        ];

        $values = [
            'bible_study_theme_id' => $theme?->id,
            'body' => $body,
            'is_shared_with_partner' => $shareWithPartner,
        ];

        return BibleStudyReflection::query()->updateOrCreate($key, $values);
    }
}
