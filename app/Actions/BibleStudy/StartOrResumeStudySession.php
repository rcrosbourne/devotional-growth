<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Models\BibleStudySession;
use App\Models\BibleStudyTheme;
use App\Models\User;

final readonly class StartOrResumeStudySession
{
    public function handle(User $user, ?BibleStudyTheme $theme, string $book, int $chapter, int $verseStart, ?int $verseEnd): BibleStudySession
    {
        $now = now();

        return BibleStudySession::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'bible_study_theme_id' => $theme?->id,
                'current_book' => $book,
                'current_chapter' => $chapter,
                'current_verse_start' => $verseStart,
                'current_verse_end' => $verseEnd,
                'started_at' => $now,
                'last_accessed_at' => $now,
            ],
        );
    }
}
