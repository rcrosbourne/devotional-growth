<?php

declare(strict_types=1);

namespace App\Http\Controllers\SabbathSchool;

use App\Models\Bookmark;
use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\LessonDayCompletion;
use App\Models\Quarterly;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LessonDayController
{
    public function show(Quarterly $quarterly, Lesson $lesson, LessonDay $lessonDay, #[CurrentUser] User $user): Response
    {
        $lessonDay->load([
            'scriptureReferences',
            'observations' => function (HasMany $query) use ($user): void {
                $userIds = [$user->id];

                if ($user->hasPartner()) {
                    $userIds[] = $user->partner_id;
                }

                $query->whereIn('user_id', $userIds)->with('user')->oldest();
            },
        ]);

        $previousDay = $this->getPreviousDay($quarterly, $lesson, $lessonDay);
        $nextDay = $this->getNextDay($quarterly, $lesson, $lessonDay);

        $isCompleted = LessonDayCompletion::query()
            ->where('user_id', $user->id)
            ->where('lesson_day_id', $lessonDay->id)
            ->exists();

        $isPartnerCompleted = false;

        if ($user->hasPartner()) {
            $isPartnerCompleted = LessonDayCompletion::query()
                ->where('user_id', $user->partner_id)
                ->where('lesson_day_id', $lessonDay->id)
                ->exists();
        }

        /** @var Bookmark|null $bookmark */
        $bookmark = $user->bookmarks()
            ->where('bookmarkable_type', LessonDay::class)
            ->where('bookmarkable_id', $lessonDay->id)
            ->first();

        return Inertia::render('sabbath-school/day', [
            'quarterly' => $quarterly,
            'lesson' => $lesson,
            'lessonDay' => $lessonDay,
            'previousDay' => $previousDay,
            'nextDay' => $nextDay,
            'isCompleted' => $isCompleted,
            'isPartnerCompleted' => $isPartnerCompleted,
            'hasPartner' => $user->hasPartner(),
            'currentUserId' => $user->id,
            'isBookmarked' => $bookmark !== null,
            'bookmarkId' => $bookmark?->id,
        ]);
    }

    /**
     * @return array{lesson_id: int, lesson_day_id: int, quarterly_id: int, day_name: string}|null
     */
    private function getPreviousDay(Quarterly $quarterly, Lesson $lesson, LessonDay $lessonDay): ?array
    {
        if ($lessonDay->day_position > 0) {
            $prevDay = LessonDay::query()
                ->where('lesson_id', $lesson->id)
                ->where('day_position', $lessonDay->day_position - 1)
                ->first();

            if ($prevDay) {
                return [
                    'lesson_id' => $lesson->id,
                    'lesson_day_id' => $prevDay->id,
                    'quarterly_id' => $quarterly->id,
                    'day_name' => $prevDay->day_name,
                ];
            }
        }

        if ($lessonDay->day_position === 0) {
            $prevLesson = Lesson::query()
                ->where('quarterly_id', $quarterly->id)
                ->where('lesson_number', $lesson->lesson_number - 1)
                ->first();

            if ($prevLesson) {
                $lastDay = LessonDay::query()
                    ->where('lesson_id', $prevLesson->id)
                    ->orderByDesc('day_position')
                    ->first();

                if ($lastDay) {
                    return [
                        'lesson_id' => $prevLesson->id,
                        'lesson_day_id' => $lastDay->id,
                        'quarterly_id' => $quarterly->id,
                        'day_name' => $lastDay->day_name,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @return array{lesson_id: int, lesson_day_id: int, quarterly_id: int, day_name: string}|null
     */
    private function getNextDay(Quarterly $quarterly, Lesson $lesson, LessonDay $lessonDay): ?array
    {
        if ($lessonDay->day_position < 6) {
            $nextDay = LessonDay::query()
                ->where('lesson_id', $lesson->id)
                ->where('day_position', $lessonDay->day_position + 1)
                ->first();

            if ($nextDay) {
                return [
                    'lesson_id' => $lesson->id,
                    'lesson_day_id' => $nextDay->id,
                    'quarterly_id' => $quarterly->id,
                    'day_name' => $nextDay->day_name,
                ];
            }
        }

        if ($lessonDay->day_position === 6) {
            $nextLesson = Lesson::query()
                ->where('quarterly_id', $quarterly->id)
                ->where('lesson_number', $lesson->lesson_number + 1)
                ->first();

            if ($nextLesson) {
                $firstDay = LessonDay::query()
                    ->where('lesson_id', $nextLesson->id)
                    ->orderBy('day_position')
                    ->first();

                if ($firstDay) {
                    return [
                        'lesson_id' => $nextLesson->id,
                        'lesson_day_id' => $firstDay->id,
                        'quarterly_id' => $quarterly->id,
                        'day_name' => $firstDay->day_name,
                    ];
                }
            }
        }

        return null;
    }
}
