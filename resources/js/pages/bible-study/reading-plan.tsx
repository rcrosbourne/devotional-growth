import DevotionalLayout from '@/layouts/devotional-layout';
import { cn } from '@/lib/utils';
import { index as bibleStudyIndex } from '@/routes/bible-study';
import { activate, completeDay } from '@/routes/bible-study/reading-plan';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    ChevronRight,
    Circle,
    Clock,
    Loader2,
    Play,
    Zap,
} from 'lucide-react';
import { useState } from 'react';

interface ReadingPlanDay {
    id: number;
    reading_plan_id: number;
    day_number: number;
    passages: string[];
}

interface ReadingPlan {
    id: number;
    name: string;
    description: string | null;
    total_days: number;
    is_default: boolean;
    days: ReadingPlanDay[];
}

interface Progress {
    completed_days: number;
    total_days: number;
    percentage: number;
    current_day_number: number | null;
    started_at: string | null;
}

interface Props {
    readingPlan: ReadingPlan;
    progress: Progress;
    completedDayIds: number[];
    missedDays: number[];
    currentDay: ReadingPlanDay | null;
}

function CircularProgress({
    percentage,
    size = 160,
    strokeWidth = 3,
}: {
    percentage: number;
    size?: number;
    strokeWidth?: number;
}) {
    const radius = (size - strokeWidth * 2) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percentage / 100) * circumference;
    const center = size / 2;

    return (
        <div
            className="relative flex items-center justify-center"
            style={{ width: size, height: size }}
        >
            <svg className="-rotate-90" width={size} height={size}>
                <circle
                    cx={center}
                    cy={center}
                    r={radius}
                    fill="transparent"
                    stroke="currentColor"
                    strokeWidth={strokeWidth}
                    className="text-surface-container-high"
                />
                <circle
                    cx={center}
                    cy={center}
                    r={radius}
                    fill="transparent"
                    stroke="currentColor"
                    strokeWidth={strokeWidth + 1}
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                    strokeLinecap="round"
                    className="text-primary transition-all duration-1000"
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="font-serif text-3xl font-semibold tabular-nums">
                    {percentage}%
                </span>
                <span className="text-[9px] font-medium tracking-widest text-on-surface-variant uppercase">
                    Completed
                </span>
            </div>
        </div>
    );
}

type DayStatus = 'completed' | 'current' | 'missed' | 'upcoming';

function ReadingDayItem({
    day,
    status,
    onComplete,
    completing,
}: {
    day: ReadingPlanDay;
    status: DayStatus;
    onComplete: () => void;
    completing: boolean;
}) {
    const isCurrent = status === 'current';
    const isCompleted = status === 'completed';
    const isMissed = status === 'missed';

    return (
        <div
            className={cn(
                'group flex items-center gap-6 rounded-2xl p-4 transition-all duration-300',
                isCurrent &&
                    'scale-[1.02] bg-surface-container-highest p-6 shadow-ambient ring-1 ring-border/20',
                isCompleted && 'hover:bg-surface-container',
                isMissed && 'hover:bg-surface-container',
                !isCurrent &&
                    !isCompleted &&
                    !isMissed &&
                    'opacity-50 hover:bg-surface-container hover:opacity-70',
            )}
        >
            {/* Day Number */}
            <div className="flex w-12 shrink-0 flex-col items-center">
                <span
                    className={cn(
                        'text-[10px] font-bold uppercase',
                        isCurrent
                            ? 'text-moss'
                            : isCompleted
                              ? 'text-on-surface-variant'
                              : 'text-on-surface-variant/40',
                    )}
                >
                    {isCurrent ? 'Today' : 'Day'}
                </span>
                <span
                    className={cn(
                        'font-serif',
                        isCurrent
                            ? 'text-2xl font-bold'
                            : 'text-xl font-normal',
                    )}
                >
                    {day.day_number}
                </span>
            </div>

            {/* Passages */}
            <div className="min-w-0 flex-1">
                <h4
                    className={cn(
                        'font-medium tracking-wide',
                        isCurrent && 'font-serif text-lg',
                        isCompleted &&
                            'text-sm text-on-surface-variant line-through',
                        isMissed && 'text-sm text-on-surface',
                        !isCurrent &&
                            !isCompleted &&
                            !isMissed &&
                            'text-sm text-on-surface',
                    )}
                >
                    {day.passages.join(', ')}
                </h4>
                {isCompleted && (
                    <p className="mt-0.5 text-xs text-moss italic">Completed</p>
                )}
                {isMissed && (
                    <p className="mt-0.5 text-xs text-on-surface-variant/60 italic">
                        Missed
                    </p>
                )}
            </div>

            {/* Action/Status */}
            {isCompleted && (
                <div className="flex size-8 items-center justify-center rounded-full bg-moss text-moss-foreground">
                    <Check className="size-4" />
                </div>
            )}
            {isCurrent && (
                <button
                    type="button"
                    onClick={onComplete}
                    disabled={completing}
                    className="flex size-10 items-center justify-center rounded-full border-2 border-primary text-primary transition-all hover:bg-primary hover:text-primary-foreground active:scale-95"
                >
                    {completing ? (
                        <Loader2 className="size-5 animate-spin" />
                    ) : (
                        <Check className="size-5" />
                    )}
                </button>
            )}
            {isMissed && (
                <button
                    type="button"
                    onClick={onComplete}
                    disabled={completing}
                    className="flex size-8 items-center justify-center rounded-full border border-border text-on-surface-variant/40 transition-colors hover:border-primary hover:text-primary"
                >
                    {completing ? (
                        <Loader2 className="size-4 animate-spin" />
                    ) : (
                        <Circle className="size-4" />
                    )}
                </button>
            )}
            {!isCompleted && !isCurrent && !isMissed && (
                <div className="flex size-8 items-center justify-center rounded-full border border-border/40 text-on-surface-variant/20">
                    <Circle className="size-4" />
                </div>
            )}
        </div>
    );
}

export default function ReadingPlanShow({
    readingPlan,
    progress,
    completedDayIds,
    missedDays,
    currentDay,
}: Props) {
    const [completingDayId, setCompletingDayId] = useState<number | null>(null);
    const [activating, setActivating] = useState(false);

    const isActivated = progress.started_at !== null;
    const remaining = progress.total_days - progress.completed_days;

    function handleCompleteDay(day: ReadingPlanDay) {
        if (completingDayId !== null) {
            return;
        }
        setCompletingDayId(day.id);
        router.post(
            completeDay.url(day.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setCompletingDayId(null),
            },
        );
    }

    function handleActivate() {
        if (activating) {
            return;
        }
        setActivating(true);
        router.post(
            activate.url(readingPlan.id),
            {},
            {
                onFinish: () => setActivating(false),
            },
        );
    }

    function getDayStatus(day: ReadingPlanDay): DayStatus {
        if (completedDayIds.includes(day.id)) {
            return 'completed';
        }
        if (currentDay && day.day_number === currentDay.day_number) {
            return 'current';
        }
        if (missedDays.includes(day.day_number)) {
            return 'missed';
        }
        return 'upcoming';
    }

    // Show a window of days around the current day
    const currentDayNumber = progress.current_day_number ?? 1;
    const windowStart = Math.max(0, currentDayNumber - 3);
    const windowEnd = Math.min(readingPlan.days.length, currentDayNumber + 4);
    const visibleDays = readingPlan.days
        .sort((a, b) => a.day_number - b.day_number)
        .slice(windowStart, windowEnd);

    const [showFullSchedule, setShowFullSchedule] = useState(false);
    const displayDays = showFullSchedule
        ? readingPlan.days.sort((a, b) => a.day_number - b.day_number)
        : visibleDays;

    // Missed days details
    const missedDayDetails = readingPlan.days
        .filter((d) => missedDays.includes(d.day_number))
        .sort((a, b) => a.day_number - b.day_number);

    return (
        <DevotionalLayout>
            <Head title={readingPlan.name} />

            <div className="mx-auto max-w-6xl px-6 py-8 md:py-16 lg:px-12">
                {/* Back Navigation */}
                <Link
                    href={bibleStudyIndex.url()}
                    prefetch
                    className="mb-8 inline-flex items-center gap-2 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Bible Study
                </Link>

                {/* Hero Header */}
                <div className="mb-16 flex flex-col gap-10 md:flex-row md:items-end md:justify-between">
                    <div className="flex-1">
                        <span className="mb-3 block text-[10px] font-semibold tracking-[0.3em] text-moss uppercase">
                            {isActivated ? 'Current Journey' : 'Reading Plan'}
                        </span>
                        <h1 className="font-serif text-4xl font-light tracking-tight text-on-surface md:text-5xl lg:text-6xl">
                            {readingPlan.name}
                        </h1>
                        {readingPlan.description && (
                            <p className="mt-6 max-w-xl leading-relaxed font-light text-on-surface-variant">
                                {readingPlan.description}
                            </p>
                        )}

                        {/* Activate Button (if not activated) */}
                        {!isActivated && (
                            <button
                                type="button"
                                onClick={handleActivate}
                                disabled={activating}
                                className="mt-8 inline-flex items-center gap-2 rounded-full bg-primary px-8 py-3 text-xs font-bold tracking-widest text-primary-foreground uppercase transition-opacity hover:opacity-90"
                            >
                                {activating ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : (
                                    <Play className="size-4" />
                                )}
                                Start This Plan
                            </button>
                        )}
                    </div>

                    {/* Circular Progress */}
                    {isActivated && (
                        <div className="shrink-0">
                            <CircularProgress
                                percentage={progress.percentage}
                            />
                        </div>
                    )}
                </div>

                {/* Content Grid */}
                {isActivated && (
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-12">
                        {/* Left: Daily Readings */}
                        <div className="space-y-12 lg:col-span-8">
                            <div className="rounded-3xl border border-border/10 bg-surface-container-low p-8 lg:p-10">
                                <div className="mb-8 flex items-center justify-between">
                                    <h3 className="font-serif text-2xl">
                                        Daily Reading List
                                    </h3>
                                </div>

                                <div className="space-y-2">
                                    {displayDays.map((day) => {
                                        const status = getDayStatus(day);
                                        return (
                                            <ReadingDayItem
                                                key={day.id}
                                                day={day}
                                                status={status}
                                                onComplete={() =>
                                                    handleCompleteDay(day)
                                                }
                                                completing={
                                                    completingDayId === day.id
                                                }
                                            />
                                        );
                                    })}
                                </div>

                                {!showFullSchedule &&
                                    readingPlan.days.length >
                                        visibleDays.length && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowFullSchedule(true)
                                            }
                                            className="mt-8 flex w-full items-center justify-center gap-2 border-t border-border/10 pt-6 text-xs font-bold tracking-widest text-on-surface-variant uppercase transition-colors hover:text-primary"
                                        >
                                            View full schedule
                                            <ChevronRight className="size-4" />
                                        </button>
                                    )}

                                {showFullSchedule && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowFullSchedule(false)
                                        }
                                        className="mt-8 flex w-full items-center justify-center gap-2 border-t border-border/10 pt-6 text-xs font-bold tracking-widest text-on-surface-variant uppercase transition-colors hover:text-primary"
                                    >
                                        Show fewer days
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Right Sidebar */}
                        <div className="space-y-8 lg:col-span-4">
                            {/* Grace Period - Missed Days */}
                            {missedDayDetails.length > 0 && (
                                <div className="rounded-3xl bg-primary p-8 text-primary-foreground shadow-ambient-lg">
                                    <div className="mb-6 flex items-start justify-between">
                                        <div>
                                            <h3 className="font-serif text-xl">
                                                Grace Period
                                            </h3>
                                            <p className="mt-1 text-[10px] tracking-widest text-primary-foreground/50 uppercase">
                                                Missed Readings
                                            </p>
                                        </div>
                                        <Clock className="size-6 text-primary-foreground/30" />
                                    </div>

                                    <div className="mb-6 space-y-3">
                                        {missedDayDetails
                                            .slice(0, 5)
                                            .map((day) => (
                                                <button
                                                    key={day.id}
                                                    type="button"
                                                    onClick={() =>
                                                        handleCompleteDay(day)
                                                    }
                                                    disabled={
                                                        completingDayId ===
                                                        day.id
                                                    }
                                                    className="group flex w-full items-center justify-between rounded-xl border border-primary-foreground/10 bg-primary-foreground/5 p-4 transition-colors hover:bg-primary-foreground/10"
                                                >
                                                    <div className="text-left">
                                                        <span className="text-[10px] tracking-widest text-primary-foreground/50 uppercase">
                                                            Day {day.day_number}
                                                        </span>
                                                        <p className="text-sm text-primary-foreground">
                                                            {day.passages.join(
                                                                ', ',
                                                            )}
                                                        </p>
                                                    </div>
                                                    {completingDayId ===
                                                    day.id ? (
                                                        <Loader2 className="size-4 animate-spin text-primary-foreground" />
                                                    ) : (
                                                        <ChevronRight className="size-4 text-primary-foreground/50 transition-transform group-hover:translate-x-1" />
                                                    )}
                                                </button>
                                            ))}
                                    </div>

                                    {missedDayDetails.length > 5 && (
                                        <p className="mb-4 text-center text-xs text-primary-foreground/50">
                                            +{missedDayDetails.length - 5} more
                                            missed
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Today's Reflection */}
                            {currentDay && (
                                <div className="relative overflow-hidden rounded-3xl bg-surface-container p-8">
                                    <div className="relative z-10">
                                        <span className="text-[9px] font-bold tracking-widest text-moss uppercase">
                                            Today&rsquo;s Reading
                                        </span>
                                        <p className="mt-3 font-serif text-lg leading-snug text-on-surface italic">
                                            {currentDay.passages.join(', ')}
                                        </p>
                                        <p className="mt-2 text-xs text-on-surface-variant">
                                            Day {currentDay.day_number} of{' '}
                                            {readingPlan.total_days}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Stats */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="flex flex-col items-center rounded-2xl bg-surface-container-high p-6">
                                    <Zap className="mb-2 size-4 text-moss" />
                                    <span className="font-serif text-2xl font-bold tabular-nums">
                                        {progress.completed_days}
                                    </span>
                                    <span className="mt-1 text-center text-[9px] tracking-widest text-on-surface-variant uppercase">
                                        Days Done
                                    </span>
                                </div>
                                <div className="flex flex-col items-center rounded-2xl bg-surface-container-high p-6">
                                    <Clock className="mb-2 size-4 text-on-surface-variant/40" />
                                    <span className="font-serif text-2xl font-bold tabular-nums">
                                        {remaining}
                                    </span>
                                    <span className="mt-1 text-center text-[9px] tracking-widest text-on-surface-variant uppercase">
                                        Remaining
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Not Yet Activated - Show Plan Overview */}
                {!isActivated && readingPlan.days.length > 0 && (
                    <div className="rounded-3xl border border-border/10 bg-surface-container-low p-8 lg:p-10">
                        <h3 className="mb-6 font-serif text-2xl">
                            Plan Overview
                        </h3>
                        <p className="mb-6 text-sm text-on-surface-variant">
                            This plan covers {readingPlan.total_days} days of
                            structured Bible reading. Activate the plan to begin
                            tracking your progress.
                        </p>
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {readingPlan.days.slice(0, 9).map((day) => (
                                <div
                                    key={day.id}
                                    className="rounded-xl bg-surface-container p-4"
                                >
                                    <span className="text-[10px] font-bold tracking-widest text-on-surface-variant/50 uppercase">
                                        Day {day.day_number}
                                    </span>
                                    <p className="mt-1 text-sm text-on-surface">
                                        {day.passages.join(', ')}
                                    </p>
                                </div>
                            ))}
                        </div>
                        {readingPlan.days.length > 9 && (
                            <p className="mt-6 text-center text-xs text-on-surface-variant/50">
                                + {readingPlan.days.length - 9} more days
                            </p>
                        )}
                    </div>
                )}
            </div>
        </DevotionalLayout>
    );
}
