import { ConfirmationDialog } from '@/components/devotional/confirmation-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/hooks/use-initials';
import DevotionalLayout from '@/layouts/devotional-layout';
import {
    type BibleVersionKey,
    BIBLE_VERSIONS,
    getPreferredVersion,
    setPreferredVersion,
} from '@/lib/bible-versions';
import { cn, storageUrl } from '@/lib/utils';
import {
    destroy as destroyBookmark,
    store as storeBookmark,
} from '@/routes/bookmarks';
import { generateImage } from '@/routes/entries';
import {
    destroy as destroyObservation,
    store as storeObservation,
    update as updateObservation,
} from '@/routes/observations';
import { show as showScripture } from '@/routes/scripture';
import { show as showTheme } from '@/routes/themes';
import {
    complete as completeEntry,
    show as showEntry,
} from '@/routes/themes/entries';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Bookmark,
    BookmarkCheck,
    BookOpen,
    Check,
    Edit3,
    ImagePlus,
    Loader2,
    MessageSquarePlus,
    Quote,
    Send,
    Trash2,
    X,
} from 'lucide-react';
import { type FormEvent, useCallback, useEffect, useState } from 'react';

interface ScriptureReference {
    id: number;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    raw_reference: string;
}

interface GeneratedImage {
    id: number;
    path: string;
    prompt: string;
}

interface Observation {
    id: number;
    user_id: number;
    body: string;
    created_at: string;
    edited_at: string | null;
    user: { id: number; name: string; avatar?: string };
}

interface Props {
    theme: { id: number; name: string };
    entry: {
        id: number;
        title: string;
        body: string;
        reflection_prompts: string | null;
        adventist_insights: string | null;
        scripture_references: ScriptureReference[];
        generated_image: GeneratedImage | null;
        observations: Observation[];
    };
    isCompleted: boolean;
    previousEntry: { id: number; title: string } | null;
    nextEntry: { id: number; title: string } | null;
    hasPartner: boolean;
    isBookmarked: boolean;
    bookmarkId: number | null;
    entryPosition: number;
}

function BibleVersionSelector({
    value,
    onChange,
}: {
    value: BibleVersionKey;
    onChange: (version: BibleVersionKey) => void;
}) {
    return (
        <Select
            value={value}
            onValueChange={(v) => onChange(v as BibleVersionKey)}
        >
            <SelectTrigger className="inline-flex h-auto w-auto gap-1.5 rounded-full border-border/60 bg-surface-container-lowest px-3 py-1 text-[10px] font-bold tracking-[0.15em] text-on-surface-variant/70 uppercase shadow-none transition-all hover:border-moss/40 hover:text-moss">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {BIBLE_VERSIONS.map((v) => (
                    <SelectItem key={v.value} value={v.value}>
                        <span className="font-bold tracking-widest uppercase">
                            {v.value}
                        </span>{' '}
                        — {v.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function splitReflectionPrompts(prompts: string): string[] {
    return prompts
        .split(/\n+/)
        .map((p) => p.trim())
        .filter(Boolean);
}

/* ─────────────────────────────────────────────
   Observation Components
   ───────────────────────────────────────────── */

function ObservationForm({ entryId }: { entryId: number }) {
    const [body, setBody] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        if (!body.trim() || submitting) {
            return;
        }
        setSubmitting(true);
        router.post(
            storeObservation.url(entryId),
            { body: body.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setBody('');
                    setSubmitting(false);
                },
                onError: () => setSubmitting(false),
            },
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <textarea
                value={body}
                onChange={(e) => setBody(e.target.value)}
                placeholder="Share your reflection..."
                rows={3}
                className="w-full resize-none rounded-xl border border-border bg-surface-container-low px-4 py-3 text-sm text-on-surface placeholder:text-on-surface-variant/40 focus:border-moss focus:ring-1 focus:ring-moss focus:outline-none"
            />
            <div className="flex justify-end">
                <button
                    type="submit"
                    disabled={!body.trim() || submitting}
                    className="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2 text-xs font-bold tracking-widest text-primary-foreground uppercase transition-opacity disabled:opacity-40"
                >
                    {submitting ? (
                        <Loader2 className="size-3.5 animate-spin" />
                    ) : (
                        <Send className="size-3.5" />
                    )}
                    Save Note
                </button>
            </div>
        </form>
    );
}

function ObservationItem({
    observation,
    isOwn,
}: {
    observation: Observation;
    isOwn: boolean;
}) {
    const [editing, setEditing] = useState(false);
    const [editBody, setEditBody] = useState(observation.body);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [saving, setSaving] = useState(false);
    const getInitials = useInitials();

    function handleUpdate(e: FormEvent) {
        e.preventDefault();
        if (!editBody.trim() || saving) {
            return;
        }
        setSaving(true);
        router.put(
            updateObservation.url(observation.id),
            { body: editBody.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditing(false);
                    setSaving(false);
                },
                onError: () => setSaving(false),
            },
        );
    }

    const [deleting, setDeleting] = useState(false);

    function handleDelete() {
        if (deleting) {
            return;
        }
        setDeleting(true);
        router.delete(destroyObservation.url(observation.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(false),
        });
    }

    return (
        <>
            <div className="group rounded-xl bg-surface-container-low p-5">
                {/* Author line */}
                <div className="mb-3 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Avatar className="size-7">
                            <AvatarImage
                                src={observation.user.avatar}
                                alt={observation.user.name}
                            />
                            <AvatarFallback className="bg-moss text-[9px] font-medium text-moss-foreground">
                                {getInitials(observation.user.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <span className="text-sm font-medium text-on-surface">
                                {observation.user.name}
                            </span>
                            <span className="ml-2 text-[10px] text-on-surface-variant/50">
                                {formatDate(observation.created_at)}
                                {observation.edited_at && ' (edited)'}
                            </span>
                        </div>
                    </div>

                    {isOwn && !editing && (
                        <div className="flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                            <button
                                type="button"
                                onClick={() => {
                                    setEditBody(observation.body);
                                    setEditing(true);
                                }}
                                className="rounded-md p-1.5 text-on-surface-variant/50 hover:bg-surface-container-high hover:text-on-surface"
                            >
                                <Edit3 className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                onClick={() => setConfirmDelete(true)}
                                className="rounded-md p-1.5 text-on-surface-variant/50 hover:bg-surface-container-high hover:text-destructive"
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </div>
                    )}
                </div>

                {/* Body */}
                {editing ? (
                    <form onSubmit={handleUpdate} className="space-y-2">
                        <textarea
                            value={editBody}
                            onChange={(e) => setEditBody(e.target.value)}
                            rows={3}
                            className="w-full resize-none rounded-lg border border-border bg-background px-3 py-2 text-sm text-on-surface focus:border-moss focus:ring-1 focus:ring-moss focus:outline-none"
                        />
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setEditing(false)}
                                className="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs text-on-surface-variant hover:bg-surface-container-high"
                            >
                                <X className="size-3" />
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={!editBody.trim() || saving}
                                className="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground disabled:opacity-40"
                            >
                                {saving ? (
                                    <Loader2 className="size-3 animate-spin" />
                                ) : (
                                    <Check className="size-3" />
                                )}
                                Save
                            </button>
                        </div>
                    </form>
                ) : (
                    <p className="font-serif text-sm leading-relaxed text-on-surface/90">
                        {observation.body}
                    </p>
                )}
            </div>

            <ConfirmationDialog
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                title="Delete Observation"
                description="Are you sure you want to delete this observation? This action cannot be undone."
                confirmLabel="Delete"
                destructive
                onConfirm={handleDelete}
                loading={deleting}
            />
        </>
    );
}

/* ─────────────────────────────────────────────
   Main Page Component
   ───────────────────────────────────────────── */

export default function DevotionalEntriesShow({
    theme,
    entry,
    isCompleted,
    previousEntry,
    nextEntry,
    hasPartner,
    isBookmarked,
    bookmarkId,
    entryPosition,
}: Props) {
    const page = usePage<SharedData>();
    const currentUserId = page.props.auth.user.id;

    const [completing, setCompleting] = useState(false);
    const [bookmarking, setBookmarking] = useState(false);
    const [generatingImage, setGeneratingImage] = useState(false);
    const [confirmRegenerate, setConfirmRegenerate] = useState(false);

    const [imageFailed, setImageFailed] = useState(false);
    const [imageLoaded, setImageLoaded] = useState(false);
    const imagePath = entry.generated_image?.path ?? null;

    useEffect(() => {
        setImageFailed(false);
        setImageLoaded(false);
    }, [imagePath]);

    const [bibleVersion, setBibleVersion] = useState(getPreferredVersion);
    const [passageTexts, setPassageTexts] = useState<Record<number, string>>(
        {},
    );
    const [loadingPassages, setLoadingPassages] = useState(false);

    const fetchPassages = useCallback(
        async (version: string, signal?: AbortSignal) => {
            if (entry.scripture_references.length === 0) {
                return;
            }
            setPassageTexts({});
            setLoadingPassages(true);
            const texts: Record<number, string> = {};

            await Promise.all(
                entry.scripture_references.map(async (ref) => {
                    try {
                        const params: Record<string, string> = {
                            book: ref.book,
                            chapter: String(ref.chapter),
                            verse_start: String(ref.verse_start),
                            bible_version: version,
                        };
                        if (ref.verse_end !== null) {
                            params.verse_end = String(ref.verse_end);
                        }
                        const url = showScripture.url({ query: params });
                        const response = await fetch(url, {
                            signal,
                            headers: { Accept: 'application/json' },
                        });
                        if (response.ok) {
                            const data = await response.json();
                            texts[ref.id] = data.text;
                        }
                    } catch (err: unknown) {
                        if (
                            err instanceof DOMException &&
                            err.name === 'AbortError'
                        ) {
                            return;
                        }
                        // passage fetch failed — will show raw reference
                    }
                }),
            );

            if (!signal?.aborted) {
                setPassageTexts(texts);
                setLoadingPassages(false);
            }
        },
        [entry.scripture_references],
    );

    useEffect(() => {
        const controller = new AbortController();
        fetchPassages(bibleVersion, controller.signal);
        return () => controller.abort();
    }, [bibleVersion, fetchPassages, entry.id]);

    function handleVersionChange(version: BibleVersionKey) {
        setBibleVersion(version);
        setPreferredVersion(version);
    }

    const firstScripture = entry.scripture_references[0];

    function handleComplete() {
        if (completing || isCompleted) {
            return;
        }
        setCompleting(true);
        router.post(
            completeEntry.url({ theme: theme.id, entry: entry.id }),
            {},
            {
                preserveScroll: true,
                onFinish: () => setCompleting(false),
            },
        );
    }

    function handleBookmarkToggle() {
        if (bookmarking) {
            return;
        }
        setBookmarking(true);
        if (isBookmarked && bookmarkId) {
            router.delete(destroyBookmark.url(bookmarkId), {
                preserveScroll: true,
                onFinish: () => setBookmarking(false),
            });
        } else {
            router.post(
                storeBookmark.url(),
                {
                    bookmarkable_type: 'App\\Models\\DevotionalEntry',
                    bookmarkable_id: entry.id,
                },
                {
                    preserveScroll: true,
                    onFinish: () => setBookmarking(false),
                },
            );
        }
    }

    function handleGenerateImage() {
        if (entry.generated_image) {
            setConfirmRegenerate(true);
            return;
        }
        doGenerateImage();
    }

    function doGenerateImage() {
        setGeneratingImage(true);
        setConfirmRegenerate(false);
        router.post(
            generateImage.url(entry.id),
            { replace: entry.generated_image ? true : false },
            {
                preserveScroll: true,
                onFinish: () => setGeneratingImage(false),
            },
        );
    }

    return (
        <DevotionalLayout>
            <Head title={`${entry.title} — ${theme.name}`} />

            <section className="mx-auto max-w-4xl px-6 pt-8 pb-24 md:px-8 md:pt-16">
                {/* Header */}
                <div className="mb-16">
                    <Link
                        href={showTheme.url(theme.id)}
                        prefetch
                        className="group mb-5 inline-flex items-center gap-2 text-[10px] font-medium tracking-[0.2em] text-on-surface-variant/50 uppercase transition-colors hover:text-moss"
                    >
                        <ArrowLeft className="size-3 transition-transform duration-300 group-hover:-translate-x-0.5" />
                        {theme.name}
                    </Link>
                    <span className="mb-3 block text-[10px] font-bold tracking-[0.3em] text-moss uppercase">
                        Contemplation {entryPosition}
                    </span>
                    <h1 className="font-serif text-4xl leading-tight font-light tracking-tight text-on-surface md:text-5xl lg:text-6xl">
                        Day {String(entryPosition).padStart(2, '0')}:{' '}
                        {entry.title}
                    </h1>

                    {/* Action bar */}
                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        {/* Mark Complete */}
                        <button
                            type="button"
                            onClick={handleComplete}
                            disabled={isCompleted || completing}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full px-5 py-2 text-xs font-bold tracking-widest uppercase transition-all',
                                isCompleted
                                    ? 'bg-moss/10 text-moss'
                                    : 'bg-primary text-primary-foreground hover:opacity-90',
                            )}
                        >
                            {completing ? (
                                <Loader2 className="size-3.5 animate-spin" />
                            ) : (
                                <Check className="size-3.5" />
                            )}
                            {isCompleted ? 'Completed' : 'Mark Complete'}
                        </button>

                        {/* Bookmark Toggle */}
                        <button
                            type="button"
                            onClick={handleBookmarkToggle}
                            disabled={bookmarking}
                            className="inline-flex items-center gap-1.5 rounded-full border border-border px-4 py-2 text-xs font-medium text-on-surface-variant transition-colors hover:bg-surface-container-high"
                        >
                            {isBookmarked ? (
                                <BookmarkCheck className="size-3.5 text-moss" />
                            ) : (
                                <Bookmark className="size-3.5" />
                            )}
                            {isBookmarked ? 'Saved' : 'Save'}
                        </button>

                        {/* Generate Image */}
                        <button
                            type="button"
                            onClick={handleGenerateImage}
                            disabled={generatingImage}
                            className="inline-flex items-center gap-1.5 rounded-full border border-border px-4 py-2 text-xs font-medium text-on-surface-variant transition-colors hover:bg-surface-container-high"
                        >
                            {generatingImage ? (
                                <Loader2 className="size-3.5 animate-spin" />
                            ) : (
                                <ImagePlus className="size-3.5" />
                            )}
                            {generatingImage
                                ? 'Generating...'
                                : entry.generated_image
                                  ? 'Regenerate Image'
                                  : 'Generate Image'}
                        </button>
                    </div>
                </div>

                {/* Hero Image */}
                {entry.generated_image && !imageFailed && (
                    <div
                        className={cn(
                            'relative -mx-6 mb-16 overflow-hidden rounded-3xl transition-all duration-700 md:-mx-8',
                            imageLoaded
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-4 opacity-0',
                        )}
                    >
                        <img
                            src={storageUrl(entry.generated_image.path)}
                            alt={`Visual for ${entry.title}`}
                            onLoad={() => setImageLoaded(true)}
                            onError={() => setImageFailed(true)}
                            className="h-auto max-h-[520px] w-full object-cover grayscale transition-[filter] duration-700 hover:grayscale-0"
                        />
                        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-background/60 via-transparent to-transparent" />
                    </div>
                )}

                {/* Scripture Pull-Quote */}
                {firstScripture && (
                    <div className="relative mb-16 py-12">
                        <div
                            className="-z-10 -mx-6 rounded-3xl bg-surface-container/40 md:-mx-8"
                            style={{ position: 'absolute', inset: 0 }}
                        />
                        <div className="relative mx-auto max-w-2xl px-4 text-center">
                            <Quote className="mx-auto mb-4 size-8 fill-moss/30 text-moss/40" />
                            {loadingPassages ? (
                                <div className="flex items-center justify-center py-4">
                                    <Loader2 className="size-5 animate-spin text-moss/40" />
                                </div>
                            ) : passageTexts[firstScripture.id] ? (
                                <p className="font-serif text-xl leading-relaxed text-on-surface italic md:text-2xl lg:text-3xl">
                                    &ldquo;{passageTexts[firstScripture.id]}
                                    &rdquo;
                                </p>
                            ) : (
                                <p className="font-serif text-xl leading-relaxed text-on-surface-variant/70 md:text-2xl lg:text-3xl">
                                    {firstScripture.raw_reference}
                                </p>
                            )}
                            <span className="mt-4 block text-xs font-bold tracking-widest text-on-surface-variant uppercase">
                                &mdash; {firstScripture.book}{' '}
                                {firstScripture.chapter}:
                                {firstScripture.verse_start}
                                {firstScripture.verse_end
                                    ? `–${firstScripture.verse_end}`
                                    : ''}
                            </span>
                            <div className="mt-5">
                                <BibleVersionSelector
                                    value={bibleVersion}
                                    onChange={handleVersionChange}
                                />
                            </div>
                        </div>
                    </div>
                )}

                {/* Additional Scripture References */}
                {entry.scripture_references.length > 1 && (
                    <div className="mb-10 flex flex-wrap gap-2">
                        {entry.scripture_references.slice(1).map((ref) => (
                            <span
                                key={ref.id}
                                className="rounded-full bg-surface-container-high px-3 py-1 text-[11px] font-medium text-on-surface-variant"
                            >
                                {ref.raw_reference}
                            </span>
                        ))}
                    </div>
                )}

                {/* Main Body Content */}
                <div className="mx-auto mb-20 max-w-2xl md:mx-0 md:mr-auto md:ml-auto">
                    <div
                        className="prose-editorial font-serif text-lg leading-relaxed text-on-surface/90 md:text-xl [&>p]:mb-6"
                        dangerouslySetInnerHTML={{ __html: entry.body }}
                    />
                </div>

                {/* Reflection Prompts */}
                {entry.reflection_prompts && (
                    <div className="relative mb-20 overflow-hidden rounded-[2rem] bg-surface-container-low p-8 md:p-10">
                        <div className="relative z-10">
                            <div className="mb-6 flex items-center gap-3">
                                <MessageSquarePlus className="size-5 text-moss" />
                                <h2 className="text-sm font-bold tracking-[0.2em] uppercase">
                                    Personal Reflections
                                </h2>
                            </div>
                            <div className="space-y-8">
                                {splitReflectionPrompts(
                                    entry.reflection_prompts,
                                ).map((prompt, i) => (
                                    <div
                                        key={i}
                                        className="border-l-2 border-moss/20 pl-6 md:pl-8"
                                    >
                                        <p className="font-serif text-lg text-on-surface italic md:text-xl">
                                            {prompt}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="pointer-events-none absolute -right-20 -bottom-20 size-80 rounded-full bg-moss/5 blur-3xl" />
                    </div>
                )}

                {/* Adventist Insights */}
                {entry.adventist_insights && (
                    <div className="mb-20 rounded-3xl bg-primary p-8 text-primary-foreground md:p-12">
                        <div className="max-w-xl">
                            <h3 className="mb-4 flex items-center gap-2 text-[10px] font-bold tracking-[0.3em] text-primary-foreground/60 uppercase">
                                <BookOpen className="size-3.5" />
                                Theological Context
                            </h3>
                            <div
                                className="font-serif text-base leading-relaxed md:text-lg [&>p]:mb-4"
                                dangerouslySetInnerHTML={{
                                    __html: entry.adventist_insights,
                                }}
                            />
                        </div>
                    </div>
                )}

                {/* Observations Section */}
                <div className="mb-20">
                    <div className="mb-6 flex items-center gap-3">
                        <MessageSquarePlus className="size-5 text-moss" />
                        <h2 className="text-sm font-bold tracking-[0.2em] uppercase">
                            {hasPartner
                                ? 'Shared Reflections'
                                : 'Your Reflections'}
                        </h2>
                        <span className="ml-auto text-xs text-on-surface-variant/50">
                            {entry.observations.length}{' '}
                            {entry.observations.length === 1 ? 'note' : 'notes'}
                        </span>
                    </div>

                    <ObservationForm entryId={entry.id} />

                    {entry.observations.length > 0 && (
                        <div className="mt-6 space-y-3">
                            {entry.observations.map((obs) => (
                                <ObservationItem
                                    key={obs.id}
                                    observation={obs}
                                    isOwn={obs.user_id === currentUserId}
                                />
                            ))}
                        </div>
                    )}
                </div>

                {/* Entry Navigation */}
                <div className="flex items-center justify-between border-t border-border/30 pt-10">
                    {previousEntry ? (
                        <Link
                            href={showEntry.url({
                                theme: theme.id,
                                entry: previousEntry.id,
                            })}
                            prefetch
                            className="group flex items-center gap-4"
                        >
                            <div className="flex size-10 items-center justify-center rounded-full border border-border transition-all group-hover:bg-primary group-hover:text-primary-foreground">
                                <ArrowLeft className="size-4" />
                            </div>
                            <div className="text-left">
                                <span className="block text-[10px] font-bold tracking-widest text-on-surface-variant uppercase">
                                    Previous Day
                                </span>
                                <span className="block font-serif text-base transition-colors group-hover:text-moss md:text-lg">
                                    {previousEntry.title}
                                </span>
                            </div>
                        </Link>
                    ) : (
                        <div />
                    )}

                    {nextEntry ? (
                        <Link
                            href={showEntry.url({
                                theme: theme.id,
                                entry: nextEntry.id,
                            })}
                            prefetch
                            className="group flex items-center gap-4 text-right"
                        >
                            <div>
                                <span className="block text-[10px] font-bold tracking-widest text-on-surface-variant uppercase">
                                    Next Day
                                </span>
                                <span className="block font-serif text-base transition-colors group-hover:text-moss md:text-lg">
                                    {nextEntry.title}
                                </span>
                            </div>
                            <div className="flex size-10 items-center justify-center rounded-full border border-border transition-all group-hover:bg-primary group-hover:text-primary-foreground">
                                <ArrowRight className="size-4" />
                            </div>
                        </Link>
                    ) : (
                        <div />
                    )}
                </div>
            </section>

            {/* Regenerate Confirmation Dialog */}
            <ConfirmationDialog
                open={confirmRegenerate}
                onOpenChange={setConfirmRegenerate}
                title="Regenerate Image"
                description="This will replace the existing image with a newly generated one. Continue?"
                confirmLabel="Regenerate"
                onConfirm={doGenerateImage}
                loading={generatingImage}
            />
        </DevotionalLayout>
    );
}
