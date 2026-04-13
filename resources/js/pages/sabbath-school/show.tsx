import DevotionalLayout from '@/layouts/devotional-layout';
import { index } from '@/routes/sabbath-school';
import { show as lessonShow } from '@/routes/sabbath-school/lessons';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen, ChevronRight } from 'lucide-react';
import { useState } from 'react';

interface Lesson {
    id: number;
    lesson_number: number;
    title: string;
    date_start: string;
    date_end: string;
    memory_text: string;
    memory_text_reference: string;
    image_path: string | null;
    has_parse_warnings: boolean;
}

interface Quarterly {
    id: number;
    title: string;
    quarter_code: string;
    year: number;
    quarter_number: number;
    lessons_count: number;
    lessons: Lesson[];
}

interface Props {
    quarterly: Quarterly;
    lessonProgress: Record<number, number>;
}

function parseDate(value: string): Date {
    const dateOnly = value.split('T')[0];
    return new Date(dateOnly + 'T00:00:00');
}

function formatDateRange(start: string, end: string) {
    const startDate = parseDate(start);
    const endDate = parseDate(end);
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

function isCurrentLesson(start: string, end: string) {
    const now = new Date();
    const startDate = parseDate(start);
    const endDate = new Date(end.split('T')[0] + 'T23:59:59');
    return now >= startDate && now <= endDate;
}

function LessonThumbnail({
    lesson,
    isCurrent,
}: {
    lesson: Lesson;
    isCurrent: boolean;
}) {
    const [imgFailed, setImgFailed] = useState(false);

    if (lesson.image_path && !imgFailed) {
        return (
            <img
                src={`/storage/${lesson.image_path}`}
                alt={lesson.title}
                onError={() => setImgFailed(true)}
                className="size-12 shrink-0 rounded-lg object-cover"
            />
        );
    }

    return (
        <div
            className={`flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${
                isCurrent
                    ? 'bg-moss text-moss-foreground'
                    : 'bg-surface-container-high text-on-surface-variant'
            }`}
        >
            {lesson.lesson_number}
        </div>
    );
}

export default function QuarterlyShow({ quarterly, lessonProgress }: Props) {
    return (
        <DevotionalLayout>
            <Head title={quarterly.title} />

            <div className="mx-auto max-w-2xl px-4 py-6 md:px-0">
                {/* Back link */}
                <Link
                    href={index.url()}
                    className="inline-flex items-center gap-1 text-sm text-on-surface-variant transition-colors hover:text-moss"
                >
                    <ArrowLeft className="size-3.5" />
                    Sabbath School
                </Link>

                {/* Quarter Header */}
                <div className="mt-4">
                    <p className="text-xs font-medium tracking-[0.12em] text-on-surface-variant uppercase">
                        Q{quarterly.quarter_number} {quarterly.year} &middot;{' '}
                        {quarterly.lessons_count} lessons
                    </p>
                    <h1 className="mt-1 font-serif text-3xl font-medium tracking-tight text-on-surface md:text-4xl">
                        {quarterly.title}
                    </h1>
                </div>

                {/* Lessons Grid */}
                <div className="mt-8 space-y-3">
                    {quarterly.lessons.map((lesson) => {
                        const isCurrent = isCurrentLesson(
                            lesson.date_start,
                            lesson.date_end,
                        );

                        return (
                            <Link
                                key={lesson.id}
                                href={lessonShow.url({
                                    quarterly: quarterly.id,
                                    lesson: lesson.id,
                                })}
                                className={`group block overflow-hidden rounded-lg border transition-all hover:shadow-sm ${
                                    isCurrent
                                        ? 'border-moss/40 bg-moss/5'
                                        : 'border-border bg-surface-container-low'
                                }`}
                            >
                                <div className="flex items-center gap-4 p-4">
                                    {/* Lesson image or number */}
                                    <LessonThumbnail
                                        lesson={lesson}
                                        isCurrent={isCurrent}
                                    />

                                    {/* Content */}
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <h3 className="font-serif text-base font-medium text-on-surface transition-colors group-hover:text-moss">
                                                {lesson.title}
                                            </h3>
                                            {isCurrent && (
                                                <span className="inline-flex items-center rounded-full bg-moss/15 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-moss uppercase">
                                                    This Week
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-0.5 flex items-center gap-2 text-xs text-on-surface-variant">
                                            <span>
                                                {formatDateRange(
                                                    lesson.date_start,
                                                    lesson.date_end,
                                                )}
                                            </span>
                                            {(lessonProgress[lesson.id] ?? 0) >
                                                0 && (
                                                <span className="font-medium text-moss">
                                                    {lessonProgress[lesson.id]}
                                                    /7
                                                </span>
                                            )}
                                        </p>
                                        <p className="mt-1 line-clamp-1 text-xs text-on-surface-variant/70 italic">
                                            {lesson.memory_text_reference}:{' '}
                                            &ldquo;
                                            {lesson.memory_text.substring(
                                                0,
                                                80,
                                            )}
                                            {lesson.memory_text.length > 80
                                                ? '...'
                                                : ''}
                                            &rdquo;
                                        </p>
                                    </div>

                                    {/* Arrow */}
                                    <ChevronRight className="size-5 shrink-0 text-on-surface-variant/40 transition-transform group-hover:translate-x-0.5 group-hover:text-moss" />
                                </div>
                            </Link>
                        );
                    })}
                </div>

                {/* Empty state */}
                {quarterly.lessons.length === 0 && (
                    <div className="mt-8 rounded-lg border border-dashed border-border bg-surface-container-low p-10 text-center">
                        <BookOpen className="mx-auto size-10 text-on-surface-variant/40" />
                        <p className="mt-4 font-serif text-lg text-on-surface-variant">
                            Lessons for this quarter are being prepared
                        </p>
                        <p className="mt-1 text-sm text-on-surface-variant">
                            Check back soon for the latest content.
                        </p>
                    </div>
                )}

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
