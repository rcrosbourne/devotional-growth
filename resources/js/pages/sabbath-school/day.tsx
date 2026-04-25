import {
    extractReferencesFromHtml,
    HtmlScriptureBody,
} from '@/components/devotional/html-scripture-body';
import type { ParsedReference } from '@/components/devotional/scripture-body';
import { Button } from '@/components/ui/button';
import DevotionalLayout from '@/layouts/devotional-layout';
import { complete, uncomplete } from '@/routes/sabbath-school/days';
import { show as lessonShow } from '@/routes/sabbath-school/lessons';
import {
    destroy as destroyObservation,
    store as storeObservation,
    update as updateObservation,
} from '@/routes/sabbath-school/observations';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Bookmark,
    BookOpen,
    Check,
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    MessageSquare,
    Pencil,
    Trash2,
    Users,
} from 'lucide-react';
import { useState } from 'react';

const BIBLE_VERSIONS = ['KJV', 'NKJV', 'ESV', 'NLT', 'NASB', 'NIV'];

interface Observation {
    id: number;
    user_id: number;
    body: string;
    edited_at: string | null;
    created_at: string;
    user: { id: number; name: string };
}

interface LessonDay {
    id: number;
    day_position: number;
    day_name: string;
    title: string;
    body: string;
    discussion_questions: string[] | null;
    observations: Observation[];
}

interface Lesson {
    id: number;
    lesson_number: number;
    title: string;
    date_start: string;
}

interface Quarterly {
    id: number;
    title: string;
}

interface DayNav {
    lesson_id: number;
    lesson_day_id: number;
    quarterly_id: number;
    day_name: string;
}

interface Props {
    quarterly: Quarterly;
    lesson: Lesson;
    lessonDay: LessonDay;
    previousDay: DayNav | null;
    nextDay: DayNav | null;
    isCompleted: boolean;
    isPartnerCompleted: boolean;
    hasPartner: boolean;
    currentUserId: number;
    isBookmarked: boolean;
    bookmarkId: number | null;
}

function buildDayUrl(nav: DayNav) {
    return `/sabbath-school/${nav.quarterly_id}/lessons/${nav.lesson_id}/days/${nav.lesson_day_id}`;
}

function buildScriptureUrl(ref: ParsedReference, version: string) {
    return `/scripture?book=${encodeURIComponent(ref.book)}&chapter=${ref.chapter}&verse_start=${ref.verseStart}${ref.verseEnd ? `&verse_end=${ref.verseEnd}` : ''}&bible_version=${version}`;
}

function formatDayDate(lessonStart: string, dayPosition: number): string {
    const dateOnly = lessonStart.split('T')[0];
    const date = new Date(dateOnly + 'T00:00:00');
    date.setDate(date.getDate() + dayPosition);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    });
}

function ObservationsSection({
    lessonDay,
    currentUserId,
}: {
    lessonDay: LessonDay;
    currentUserId: number;
}) {
    const form = useForm({ body: '' });
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editBody, setEditBody] = useState('');

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(storeObservation.url(lessonDay.id), {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    }

    function handleUpdate(observation: Observation) {
        router.put(
            updateObservation.url(observation.id),
            { body: editBody },
            {
                preserveScroll: true,
                onSuccess: () => setEditingId(null),
            },
        );
    }

    function handleDelete(observation: Observation) {
        router.delete(destroyObservation.url(observation.id), {
            preserveScroll: true,
        });
    }

    function startEditing(observation: Observation) {
        setEditingId(observation.id);
        setEditBody(observation.body);
    }

    function formatTime(dateString: string) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    return (
        <div className="mt-8">
            <h2 className="flex items-center gap-2 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                <MessageSquare className="size-3.5" />
                Observations
            </h2>

            {/* Observation List */}
            {lessonDay.observations.length > 0 && (
                <div className="mt-4 space-y-3">
                    {lessonDay.observations.map((obs) => {
                        const isOwn = obs.user_id === currentUserId;
                        const isEditing = editingId === obs.id;

                        return (
                            <div
                                key={obs.id}
                                className="rounded-lg border border-border bg-surface-container-low p-4"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <p className="text-xs font-medium text-on-surface">
                                            {obs.user.name}
                                        </p>
                                        <p className="text-[10px] text-on-surface-variant/60">
                                            {formatTime(obs.created_at)}
                                            {obs.edited_at && ' (edited)'}
                                        </p>
                                    </div>
                                    {isOwn && !isEditing && (
                                        <div className="flex items-center gap-1">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    startEditing(obs)
                                                }
                                                className="rounded p-1 text-on-surface-variant/50 hover:text-on-surface"
                                            >
                                                <Pencil className="size-3" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleDelete(obs)
                                                }
                                                className="rounded p-1 text-on-surface-variant/50 hover:text-destructive"
                                            >
                                                <Trash2 className="size-3" />
                                            </button>
                                        </div>
                                    )}
                                </div>
                                {isEditing ? (
                                    <div className="mt-2">
                                        <textarea
                                            value={editBody}
                                            onChange={(e) =>
                                                setEditBody(e.target.value)
                                            }
                                            className="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-on-surface focus:border-moss focus:ring-1 focus:ring-moss focus:outline-none"
                                            rows={3}
                                        />
                                        <div className="mt-2 flex gap-2">
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    handleUpdate(obs)
                                                }
                                                className="bg-moss text-moss-foreground hover:bg-moss/90"
                                            >
                                                Save
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    setEditingId(null)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="mt-2 text-sm leading-relaxed text-on-surface-variant">
                                        {obs.body}
                                    </p>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Add Observation Form */}
            <form onSubmit={handleSubmit} className="mt-4">
                <textarea
                    value={form.data.body}
                    onChange={(e) => form.setData('body', e.target.value)}
                    placeholder="Add your thoughts or reflections..."
                    className="w-full rounded-lg border border-border bg-surface px-4 py-3 text-sm text-on-surface placeholder:text-on-surface-variant/50 focus:border-moss focus:ring-1 focus:ring-moss focus:outline-none"
                    rows={3}
                />
                {form.errors.body && (
                    <p className="mt-1 text-sm text-destructive">
                        {form.errors.body}
                    </p>
                )}
                <div className="mt-2 flex justify-end">
                    <Button
                        type="submit"
                        size="sm"
                        disabled={form.processing || !form.data.body.trim()}
                        className="bg-moss text-moss-foreground hover:bg-moss/90"
                    >
                        <MessageSquare className="size-3.5" />
                        Add Observation
                    </Button>
                </div>
            </form>
        </div>
    );
}

export default function DayView({
    quarterly,
    lesson,
    lessonDay,
    previousDay,
    nextDay,
    isCompleted,
    isPartnerCompleted,
    hasPartner,
    currentUserId,
    isBookmarked,
    bookmarkId,
}: Props) {
    const [selectedVersion, setSelectedVersion] = useState('KJV');
    const [expandedRef, setExpandedRef] = useState<number | null>(null);
    const [scriptureTexts, setScriptureTexts] = useState<
        Record<string, string>
    >({});
    const [loadingRef, setLoadingRef] = useState<string | null>(null);

    const bodyRefs = extractReferencesFromHtml(lessonDay.body);

    async function fetchScripture(
        ref: ParsedReference,
        index: number,
        version: string,
    ) {
        const cacheKey = `${index}-${version}`;
        if (scriptureTexts[cacheKey]) {
            setExpandedRef(expandedRef === index ? null : index);
            return;
        }

        setLoadingRef(cacheKey);
        setExpandedRef(index);

        try {
            const response = await fetch(buildScriptureUrl(ref, version));
            const data = await response.json();
            setScriptureTexts((prev) => ({
                ...prev,
                [cacheKey]: data.text || 'Unable to load passage.',
            }));
        } catch {
            setScriptureTexts((prev) => ({
                ...prev,
                [cacheKey]: 'Unable to load passage. Please try again.',
            }));
        } finally {
            setLoadingRef(null);
        }
    }

    function handleVersionChange(version: string) {
        setSelectedVersion(version);
        if (expandedRef !== null) {
            const ref = bodyRefs[expandedRef];
            if (ref) {
                fetchScripture(ref, expandedRef, version);
            }
        }
    }

    function handleToggleBookmark() {
        if (isBookmarked && bookmarkId) {
            router.delete(`/bookmarks/${bookmarkId}`, {
                preserveScroll: true,
            });
        } else {
            router.post(
                '/bookmarks',
                {
                    bookmarkable_type: 'App\\Models\\LessonDay',
                    bookmarkable_id: lessonDay.id,
                },
                { preserveScroll: true },
            );
        }
    }

    function handleToggleComplete() {
        if (isCompleted) {
            router.delete(uncomplete.url(lessonDay.id), {
                preserveScroll: true,
            });
        } else {
            router.post(
                complete.url(lessonDay.id),
                {},
                {
                    preserveScroll: true,
                },
            );
        }
    }

    const completedTogether = isCompleted && isPartnerCompleted;

    return (
        <DevotionalLayout>
            <Head
                title={`${lessonDay.day_name}: ${lessonDay.title} - ${lesson.title}`}
            />

            <div className="mx-auto max-w-2xl px-4 py-6 md:px-0">
                {/* Back link */}
                <Link
                    href={lessonShow.url({
                        quarterly: quarterly.id,
                        lesson: lesson.id,
                    })}
                    className="inline-flex items-center gap-1 text-sm text-on-surface-variant transition-colors hover:text-moss"
                >
                    <ArrowLeft className="size-3.5" />
                    Lesson {lesson.lesson_number}: {lesson.title}
                </Link>

                {/* Day Header */}
                <div className="mt-4">
                    <p className="text-xs font-medium tracking-[0.12em] text-moss uppercase">
                        {lessonDay.day_name}
                    </p>
                    <h1 className="mt-1 font-serif text-3xl font-medium tracking-tight text-on-surface md:text-4xl">
                        {lessonDay.title !== lessonDay.day_name
                            ? lessonDay.title
                            : `${lessonDay.day_name} Study`}
                    </h1>
                    <p className="mt-1.5 text-sm text-on-surface-variant">
                        {formatDayDate(
                            lesson.date_start,
                            lessonDay.day_position,
                        )}
                    </p>
                </div>

                {/* Body Content */}
                <HtmlScriptureBody
                    html={lessonDay.body}
                    className="lesson-body mt-8"
                />

                {/* Discussion Questions (Friday) */}
                {lessonDay.discussion_questions &&
                    lessonDay.discussion_questions.length > 0 && (
                        <div className="mt-8 rounded-xl border border-amber-200/50 bg-amber-50/30 p-5 dark:border-amber-900/30 dark:bg-amber-950/20">
                            <h2 className="font-serif text-lg font-medium text-on-surface">
                                Discussion Questions
                            </h2>
                            <ol className="mt-3 list-inside list-decimal space-y-3">
                                {lessonDay.discussion_questions.map(
                                    (q, index) => (
                                        <li
                                            // Discussion questions come from a
                                            // frozen JSON column; index keys are
                                            // stable here.
                                            // eslint-disable-next-line @eslint-react/no-array-index-key
                                            key={index}
                                            className="text-sm leading-relaxed text-on-surface-variant"
                                        >
                                            {q}
                                        </li>
                                    ),
                                )}
                            </ol>
                        </div>
                    )}

                {/* Scripture References */}
                {bodyRefs.length > 0 && (
                    <div className="mt-8">
                        <div className="flex items-center justify-between">
                            <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                                Scripture References
                            </h2>
                            <select
                                value={selectedVersion}
                                onChange={(e) =>
                                    handleVersionChange(e.target.value)
                                }
                                className="rounded-md border border-border bg-surface px-2 py-1 text-xs text-on-surface focus:border-moss focus:ring-1 focus:ring-moss focus:outline-none"
                            >
                                {BIBLE_VERSIONS.map((v) => (
                                    <option key={v} value={v}>
                                        {v}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="mt-3 space-y-2">
                            {bodyRefs.map((ref, index) => {
                                const cacheKey = `${index}-${selectedVersion}`;
                                const isExpanded = expandedRef === index;
                                const isLoading = loadingRef === cacheKey;
                                const text = scriptureTexts[cacheKey];
                                const label = `${ref.book} ${ref.chapter}:${ref.verseStart}${ref.verseEnd ? `\u2013${ref.verseEnd}` : ''}`;

                                return (
                                    <div
                                        key={label}
                                        className="overflow-hidden rounded-lg border border-border"
                                    >
                                        <button
                                            type="button"
                                            onClick={() =>
                                                fetchScripture(
                                                    ref,
                                                    index,
                                                    selectedVersion,
                                                )
                                            }
                                            className="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-surface-container-low"
                                        >
                                            <span className="flex items-center gap-2 text-sm font-medium text-on-surface">
                                                <BookOpen className="size-3.5 text-moss" />
                                                {label}
                                            </span>
                                            <ChevronRight
                                                className={`size-4 text-on-surface-variant/50 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
                                            />
                                        </button>
                                        {isExpanded && (
                                            <div className="border-t border-border bg-surface-container-low/50 px-4 py-3">
                                                {isLoading ? (
                                                    <div className="flex items-center gap-2 text-sm text-on-surface-variant">
                                                        <div className="size-3 animate-spin rounded-full border-2 border-moss border-t-transparent" />
                                                        Loading passage...
                                                    </div>
                                                ) : text ? (
                                                    <p className="font-serif text-sm leading-relaxed text-on-surface">
                                                        {text}
                                                    </p>
                                                ) : (
                                                    <p className="text-sm text-on-surface-variant">
                                                        Click to load passage
                                                    </p>
                                                )}
                                                <p className="mt-2 text-[10px] text-on-surface-variant/60">
                                                    {selectedVersion}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Completion */}
                <div className="mt-8 flex items-center gap-3">
                    <Button
                        onClick={handleToggleComplete}
                        className={
                            isCompleted
                                ? 'bg-moss text-moss-foreground hover:bg-moss/80'
                                : 'bg-surface-container-high text-on-surface hover:bg-surface-container-high/80'
                        }
                    >
                        {isCompleted ? (
                            <CheckCircle className="size-4" />
                        ) : (
                            <Check className="size-4" />
                        )}
                        {isCompleted ? 'Completed' : 'Mark as Complete'}
                    </Button>
                    <Button
                        variant="outline"
                        onClick={handleToggleBookmark}
                        className={
                            isBookmarked ? 'border-moss/30 text-moss' : ''
                        }
                    >
                        <Bookmark
                            className={`size-4 ${isBookmarked ? 'fill-current' : ''}`}
                        />
                        {isBookmarked ? 'Bookmarked' : 'Bookmark'}
                    </Button>
                    {completedTogether && hasPartner && (
                        <span className="inline-flex items-center gap-1.5 text-sm text-moss">
                            <Users className="size-3.5" />
                            Completed together
                        </span>
                    )}
                </div>

                {/* Observations */}
                <ObservationsSection
                    lessonDay={lessonDay}
                    currentUserId={currentUserId}
                />

                {/* Day Navigation */}
                <div className="mt-8 flex items-center justify-between border-t border-border pt-6">
                    {previousDay ? (
                        <Link
                            href={buildDayUrl(previousDay)}
                            className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-moss"
                        >
                            <ChevronLeft className="size-4" />
                            {previousDay.day_name}
                        </Link>
                    ) : (
                        <div />
                    )}
                    {nextDay ? (
                        <Link
                            href={buildDayUrl(nextDay)}
                            className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-moss"
                        >
                            {nextDay.day_name}
                            <ChevronRight className="size-4" />
                        </Link>
                    ) : (
                        <div />
                    )}
                </div>

                {/* Attribution */}
                <p className="mt-6 text-center text-[10px] text-on-surface-variant/50">
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
