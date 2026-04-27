<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ActivateReadingPlan;
use App\Actions\CompleteReadingDay;
use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudySession;
use App\Models\BibleStudyTheme;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ReadingPlanController
{
    public function index(#[CurrentUser] User $user): Response
    {
        $plans = ReadingPlan::query()
            ->withCount('days')
            ->get();

        $activeProgress = ReadingPlanProgress::query()
            ->where('user_id', $user->id)
            ->get();

        $activePlanIds = $activeProgress->pluck('reading_plan_id')->unique();

        /** @var array<int, array{completed_days: int, total_days: int, percentage: float|int, current_day_number: int, started_at: mixed}> $progressByPlan */
        $progressByPlan = [];
        foreach ($activePlanIds as $planId) {
            /** @var int $planId */
            $planProgress = $activeProgress->where('reading_plan_id', $planId);
            $plan = $plans->firstWhere('id', $planId);
            $completedDays = $planProgress->whereNotNull('completed_at')->count();
            $totalDays = $plan->total_days ?? 0;
            $startedAt = $planProgress->first()?->started_at;

            $currentDayNumber = (int) Date::parse($startedAt)->diffInDays(Date::today()) + 1;

            $progressByPlan[$planId] = [
                'completed_days' => $completedDays,
                'total_days' => $totalDays,
                'percentage' => $totalDays > 0 ? round(($completedDays / $totalDays) * 100) : 0,
                'current_day_number' => min($currentDayNumber, $totalDays),
                'started_at' => $startedAt,
            ];
        }

        $themes = BibleStudyTheme::query()
            ->where('status', BibleStudyThemeStatus::Approved)
            ->withCount('passages')
            ->orderBy('title')
            ->get()
            ->map(fn (BibleStudyTheme $t): array => [
                'id' => $t->id,
                'slug' => $t->slug,
                'title' => $t->title,
                'short_description' => $t->short_description,
                'passage_count' => $t->passages_count,
            ])->all();

        $recentPassages = BibleStudySession::query()
            ->where('user_id', $user->id)
            ->latest('last_accessed_at')
            ->limit(5)
            ->get()
            ->map(fn (BibleStudySession $s): array => [
                'theme_id' => $s->bible_study_theme_id,
                'book' => $s->current_book,
                'chapter' => $s->current_chapter,
                'verse_start' => $s->current_verse_start,
                'verse_end' => $s->current_verse_end,
                'last_accessed_at' => $s->last_accessed_at,
            ])->all();

        return Inertia::render('bible-study/index', [
            'plans' => $plans,
            'activePlanIds' => $activePlanIds->values(),
            'progressByPlan' => $progressByPlan,
            'themes' => $themes,
            'recentPassages' => $recentPassages,
        ]);
    }

    public function show(ReadingPlan $readingPlan, #[CurrentUser] User $user): Response
    {
        $readingPlan->load('days');

        $progress = ReadingPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('reading_plan_id', $readingPlan->id)
            ->get();

        $startedAt = $progress->first()?->started_at;
        $completedDayIds = $progress->whereNotNull('completed_at')->pluck('reading_plan_day_id');
        $completedDays = $completedDayIds->count();

        $currentDayNumber = $startedAt
            ? (int) Date::parse($startedAt)->diffInDays(Date::today()) + 1
            : null;

        $currentDayNumber = $currentDayNumber !== null
            ? min($currentDayNumber, $readingPlan->total_days)
            : null;

        $missedDays = [];
        if ($currentDayNumber !== null) {
            $completedDayNumbers = $readingPlan->days
                ->whereIn('id', $completedDayIds)
                ->pluck('day_number')
                ->toArray();

            for ($i = 1; $i < $currentDayNumber; $i++) {
                if (! in_array($i, $completedDayNumbers, true)) {
                    $missedDays[] = $i;
                }
            }
        }

        $currentDay = $currentDayNumber !== null
            ? $readingPlan->days->firstWhere('day_number', $currentDayNumber)
            : null;

        return Inertia::render('bible-study/reading-plan', [
            'readingPlan' => $readingPlan,
            'progress' => [
                'completed_days' => $completedDays,
                'total_days' => $readingPlan->total_days,
                'percentage' => $readingPlan->total_days > 0 ? round(($completedDays / $readingPlan->total_days) * 100) : 0,
                'current_day_number' => $currentDayNumber,
                'started_at' => $startedAt,
            ],
            'completedDayIds' => $completedDayIds->values(),
            'missedDays' => $missedDays,
            'currentDay' => $currentDay,
        ]);
    }

    public function activate(ReadingPlan $readingPlan, #[CurrentUser] User $user, ActivateReadingPlan $action): RedirectResponse
    {
        $action->handle($user, $readingPlan);

        return to_route('bible-study.reading-plan.show', $readingPlan)
            ->with('success', 'Reading plan activated.');
    }

    public function completeDay(ReadingPlanDay $day, #[CurrentUser] User $user, CompleteReadingDay $action): RedirectResponse
    {
        $action->handle($user, $day);

        return to_route('bible-study.reading-plan.show', $day->reading_plan_id)
            ->with('success', 'Day marked as complete.');
    }
}
