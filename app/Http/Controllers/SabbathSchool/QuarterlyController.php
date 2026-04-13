<?php

declare(strict_types=1);

namespace App\Http\Controllers\SabbathSchool;

use App\Models\LessonDayCompletion;
use App\Models\Quarterly;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;
use Inertia\Response;

final readonly class QuarterlyController
{
    public function index(#[CurrentUser] User $user): Response
    {
        $activeQuarterly = Quarterly::query()
            ->active()
            ->withCount('lessons')
            ->first();

        $pastQuarterlies = Quarterly::query()
            ->where('is_active', false)
            ->withCount('lessons')
            ->latest()
            ->get();

        $activeProgress = null;

        if ($activeQuarterly) {
            $activeProgress = $this->getQuarterProgress($activeQuarterly, $user);
        }

        return Inertia::render('sabbath-school/index', [
            'activeQuarterly' => $activeQuarterly,
            'pastQuarterlies' => $pastQuarterlies,
            'activeProgress' => $activeProgress,
        ]);
    }

    public function show(Quarterly $quarterly, #[CurrentUser] User $user): Response
    {
        $quarterly->load(['lessons' => function (HasMany $query): void {
            $query->orderBy('lesson_number')->withCount('days');
        }]);

        $quarterly->loadCount('lessons');

        $lessonProgress = $this->getLessonProgressMap($quarterly, $user);

        return Inertia::render('sabbath-school/show', [
            'quarterly' => $quarterly,
            'lessonProgress' => $lessonProgress,
        ]);
    }

    /**
     * @return array{completed_days: int, total_days: int}
     */
    private function getQuarterProgress(Quarterly $quarterly, User $user): array
    {
        $totalDays = $quarterly->lessons()
            ->join('lesson_days', 'lessons.id', '=', 'lesson_days.lesson_id')
            ->count();

        $completedDays = LessonDayCompletion::query()
            ->where('user_id', $user->id)
            ->whereHas('lessonDay', function (Builder $query) use ($quarterly): void {
                $query->whereHas('lesson', function (Builder $q) use ($quarterly): void {
                    $q->where('quarterly_id', $quarterly->id);
                });
            })
            ->count();

        return [
            'completed_days' => $completedDays,
            'total_days' => $totalDays,
        ];
    }

    /**
     * @return array<int|string, int|string>
     */
    private function getLessonProgressMap(Quarterly $quarterly, User $user): array
    {
        /** @var array<int|string, int|string> */
        return LessonDayCompletion::query()
            ->where('user_id', $user->id)
            ->whereHas('lessonDay.lesson', function (Builder $query) use ($quarterly): void {
                $query->where('quarterly_id', $quarterly->id);
            })
            ->join('lesson_days', 'lesson_day_completions.lesson_day_id', '=', 'lesson_days.id')
            ->selectRaw('lesson_days.lesson_id, count(*) as completed_count')
            ->groupBy('lesson_days.lesson_id')
            ->pluck('completed_count', 'lesson_id')
            ->all();
    }
}
