import DevotionalLayout from '@/layouts/devotional-layout';
import { show as quarterlyShow } from '@/routes/sabbath-school';
import { show as lessonShow } from '@/routes/sabbath-school/lessons';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    ChevronLeft,
    ChevronRight,
    Trophy,
} from 'lucide-react';

interface LessonDay {
    id: number;
    day_position: number;
    day_name: string;
    title: string;
}

interface Lesson {
    id: number;
    lesson_number: number;
    title: string;
    date_start: string;
    date_end: string;
    memory_text: string;
    memory_text_reference: string;
    image_path: string | null;
    days: LessonDay[];
}

interface Quarterly {
    id: number;
    title: string;
    quarter_code: string;
}

interface Props {
    quarterly: Quarterly;
    lesson: Lesson;
    previousLesson: { id: number; lesson_number: number; title: string } | null;
    nextLesson: { id: number; lesson_number: number; title: string } | null;
    completedDayIds: number[];
}

function formatDateRange(start: string, end: string) {
    const startDate = new Date(start + 'T00:00:00');
    const endDate = new Date(end + 'T00:00:00');
    const startStr = startDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
    const endStr = endDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
    return `${startStr} – ${endStr}`;
}

export default function LessonView({
    quarterly,
    lesson,
    previousLesson,
    nextLesson,
    completedDayIds,
}: Props) {
    const completedCount = completedDayIds.length;
    const totalDays = lesson.days.length;
    const isLessonComplete = totalDays > 0 && completedCount === totalDays;
    return (
        <DevotionalLayout>
            <Head title={`${lesson.title} - ${quarterly.title}`} />

            <div className="mx-auto max-w-2xl px-4 py-6 md:px-0">
                {/* Back link */}
                <Link
                    href={quarterlyShow.url(quarterly.id)}
                    className="inline-flex items-center gap-1 text-sm text-on-surface-variant transition-colors hover:text-moss"
                >
                    <ArrowLeft className="size-3.5" />
                    {quarterly.title}
                </Link>

                {/* Lesson Header */}
                <div className="mt-4">
                    <p className="text-xs font-medium tracking-[0.12em] text-on-surface-variant uppercase">
                        Lesson {lesson.lesson_number} &middot;{' '}
                        {formatDateRange(lesson.date_start, lesson.date_end)}
                    </p>
                    <h1 className="mt-1 font-serif text-3xl font-medium tracking-tight text-on-surface md:text-4xl">
                        {lesson.title}
                    </h1>
                </div>

                {/* Hero Image */}
                {lesson.image_path && (
                    <div className="mt-6 overflow-hidden rounded-xl">
                        <img
                            src={`/storage/${lesson.image_path}`}
                            alt={lesson.title}
                            className="aspect-[2/1] w-full object-cover"
                        />
                    </div>
                )}

                {/* Memory Text Card */}
                <div className="mt-6 rounded-xl border border-moss/20 bg-gradient-to-br from-moss/5 to-transparent p-5">
                    <p className="text-xs font-medium tracking-[0.12em] text-moss uppercase">
                        Memory Text
                    </p>
                    <p className="mt-2 font-serif text-lg leading-relaxed text-on-surface italic">
                        &ldquo;{lesson.memory_text}&rdquo;
                    </p>
                    <p className="mt-2 text-sm font-medium text-on-surface-variant">
                        {lesson.memory_text_reference}
                    </p>
                </div>

                {/* Progress */}
                {totalDays > 0 && (
                    <div className="mt-6">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-on-surface-variant">
                                {completedCount}/{totalDays} days completed
                            </span>
                            {isLessonComplete && (
                                <span className="inline-flex items-center gap-1 font-medium text-moss">
                                    <Trophy className="size-3.5" />
                                    Lesson Complete
                                </span>
                            )}
                        </div>
                        <div className="mt-2 h-2 overflow-hidden rounded-full bg-surface-container-high">
                            <div
                                className="h-full rounded-full bg-moss transition-all duration-300"
                                style={{
                                    width: `${(completedCount / totalDays) * 100}%`,
                                }}
                            />
                        </div>
                    </div>
                )}

                {/* Day Cards */}
                <div className="mt-8">
                    <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Daily Study
                    </h2>
                    <div className="mt-4 space-y-2">
                        {lesson.days.map((day) => {
                            const isDayCompleted = completedDayIds.includes(
                                day.id,
                            );
                            return (
                                <Link
                                    key={day.id}
                                    href={`/sabbath-school/${quarterly.id}/lessons/${lesson.id}/days/${day.id}`}
                                    className="group block"
                                >
                                    <div className="flex items-center gap-4 rounded-lg border border-border bg-surface-container-low p-4 transition-all hover:border-moss/30 hover:shadow-sm">
                                        <div
                                            className={`flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${isDayCompleted ? 'bg-moss text-moss-foreground' : 'bg-surface-container-high text-on-surface-variant'}`}
                                        >
                                            {isDayCompleted ? (
                                                <Check className="size-4" />
                                            ) : (
                                                day.day_position + 1
                                            )}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-xs font-medium text-on-surface-variant">
                                                {day.day_name}
                                            </p>
                                            <p className="font-serif text-base font-medium text-on-surface transition-colors group-hover:text-moss">
                                                {day.title !== day.day_name
                                                    ? day.title
                                                    : 'Introduction'}
                                            </p>
                                        </div>
                                        <ChevronRight className="size-4 shrink-0 text-on-surface-variant/40 transition-transform group-hover:translate-x-0.5 group-hover:text-moss" />
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                </div>

                {/* Lesson Navigation */}
                <div className="mt-8 flex items-center justify-between">
                    {previousLesson ? (
                        <Link
                            href={lessonShow.url({
                                quarterly: quarterly.id,
                                lesson: previousLesson.id,
                            })}
                            className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-moss"
                        >
                            <ChevronLeft className="size-4" />
                            <span className="hidden sm:inline">
                                Lesson {previousLesson.lesson_number}
                            </span>
                            <span className="sm:hidden">Previous</span>
                        </Link>
                    ) : (
                        <div />
                    )}
                    {nextLesson ? (
                        <Link
                            href={lessonShow.url({
                                quarterly: quarterly.id,
                                lesson: nextLesson.id,
                            })}
                            className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-moss"
                        >
                            <span className="hidden sm:inline">
                                Lesson {nextLesson.lesson_number}
                            </span>
                            <span className="sm:hidden">Next</span>
                            <ChevronRight className="size-4" />
                        </Link>
                    ) : (
                        <div />
                    )}
                </div>

                {/* Attribution */}
                <p className="mt-8 text-center text-[10px] text-on-surface-variant/50">
                    Content sourced from{' '}
                    <a
                        href="https://ssnet.org"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="underline hover:text-on-surface-variant"
                    >
                        ssnet.org
                    </a>
                </p>
            </div>
        </DevotionalLayout>
    );
}
