import { ConfirmationDialog } from '@/components/devotional/confirmation-dialog';
import DevotionalLayout from '@/layouts/devotional-layout';
import { destroy } from '@/routes/bookmarks';
import { Head, router } from '@inertiajs/react';
import { BookMarked, BookOpen, Languages, Sparkles } from 'lucide-react';
import { useState } from 'react';

interface DevotionalEntryBookmark {
    id: number;
    title: string;
    body: string;
    theme_id: number;
}

interface ScriptureReferenceBookmark {
    id: number;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    raw_reference: string;
}

interface WordStudyBookmark {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
}

interface Bookmark<T = unknown> {
    id: number;
    bookmarkable_type: string;
    bookmarkable_id: number;
    created_at: string;
    bookmarkable: T;
}

interface Props {
    devotionalEntries: Bookmark<DevotionalEntryBookmark>[];
    scriptureReferences: Bookmark<ScriptureReferenceBookmark>[];
    wordStudies: Bookmark<WordStudyBookmark>[];
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function stripHtml(html: string): string {
    return html.replace(/<[^>]*>/g, '');
}

function SectionHeader({
    icon,
    title,
    count,
}: {
    icon: React.ReactNode;
    title: string;
    count: number;
}) {
    return (
        <div className="mb-8 flex items-center justify-between border-b border-border/30 pb-4">
            <div className="flex items-center gap-3">
                {icon}
                <h2 className="font-serif text-2xl italic">{title}</h2>
            </div>
            <span className="text-[10px] font-medium tracking-widest text-on-surface-variant/50 uppercase">
                {count} saved
            </span>
        </div>
    );
}

function DevotionalEntryCard({
    bookmark,
    onRemove,
}: {
    bookmark: Bookmark<DevotionalEntryBookmark>;
    onRemove: (id: number) => void;
}) {
    const entry = bookmark.bookmarkable;
    const cleanBody = stripHtml(entry.body);
    const excerpt =
        cleanBody.length > 180 ? `${cleanBody.slice(0, 180)}...` : cleanBody;

    return (
        <div className="group flex h-full flex-col rounded-xl bg-surface-container-low p-8 transition-all duration-500 hover:bg-surface-container hover:shadow-ambient">
            <span className="mb-4 text-[10px] font-semibold tracking-widest text-moss uppercase">
                Saved {formatDate(bookmark.created_at)}
            </span>
            <h3 className="mb-3 font-serif text-xl font-medium text-on-surface transition-colors group-hover:text-moss">
                {entry.title}
            </h3>
            <p className="mb-8 flex-1 text-sm leading-relaxed text-on-surface-variant">
                {excerpt}
            </p>
            <div className="flex items-center justify-end">
                <button
                    type="button"
                    onClick={() => onRemove(bookmark.id)}
                    className="rounded-full p-2 text-on-surface-variant transition-colors hover:bg-surface-container-highest hover:text-destructive-foreground"
                    aria-label="Remove bookmark"
                >
                    <BookMarked className="size-5 fill-current" />
                </button>
            </div>
        </div>
    );
}

function ScriptureReferenceRow({
    bookmark,
    onRemove,
}: {
    bookmark: Bookmark<ScriptureReferenceBookmark>;
    onRemove: (id: number) => void;
}) {
    const ref = bookmark.bookmarkable;

    return (
        <div className="flex flex-col gap-4 rounded-lg border border-border/10 bg-surface-container-lowest p-6 transition-all hover:bg-white md:flex-row md:items-center dark:hover:bg-surface-container-low">
            <div className="md:w-1/4">
                <span className="font-serif text-lg text-moss italic">
                    {ref.raw_reference}
                </span>
            </div>
            <div className="flex-1 md:px-4">
                <p className="font-serif text-sm leading-relaxed text-on-surface italic">
                    {ref.book} {ref.chapter}:{ref.verse_start}
                    {ref.verse_end ? `–${ref.verse_end}` : ''}
                </p>
            </div>
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => onRemove(bookmark.id)}
                    className="rounded-full p-2 text-on-surface-variant transition-colors hover:bg-surface-container-highest hover:text-destructive-foreground"
                    aria-label="Remove bookmark"
                >
                    <BookMarked className="size-4 fill-current" />
                </button>
            </div>
        </div>
    );
}

function WordStudyCard({
    bookmark,
    featured,
    onRemove,
}: {
    bookmark: Bookmark<WordStudyBookmark>;
    featured: boolean;
    onRemove: (id: number) => void;
}) {
    const study = bookmark.bookmarkable;

    if (featured) {
        return (
            <div className="relative overflow-hidden rounded-2xl bg-primary p-10 text-primary-foreground md:col-span-2">
                <div className="relative z-10">
                    <span className="mb-4 block text-[10px] font-semibold tracking-[0.3em] text-primary-foreground/60 uppercase">
                        {study.language} origin
                    </span>
                    <h3 className="mb-2 font-serif text-4xl text-primary-foreground italic">
                        {study.original_word}{' '}
                        <span className="text-2xl font-light not-italic">
                            ({study.transliteration})
                        </span>
                    </h3>
                    <p className="mb-8 max-w-md text-lg text-primary-foreground/80">
                        {study.definition}
                    </p>
                    <div className="flex items-center justify-between">
                        <span className="text-[10px] tracking-widest text-primary-foreground/40 uppercase">
                            Strong&apos;s {study.strongs_number}
                        </span>
                        <button
                            type="button"
                            onClick={() => onRemove(bookmark.id)}
                            className="rounded-full p-2 text-primary-foreground/60 transition-colors hover:text-primary-foreground"
                            aria-label="Remove bookmark"
                        >
                            <BookMarked className="size-5 fill-current" />
                        </button>
                    </div>
                </div>
                {/* Decorative background element */}
                <div className="absolute -right-8 -bottom-8 opacity-5">
                    <Languages className="size-44" />
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col rounded-2xl bg-moss/10 p-8 transition-all duration-500 hover:bg-moss/15">
            <span className="mb-3 block text-[10px] font-semibold tracking-[0.3em] text-moss uppercase">
                {study.language}
            </span>
            <h3 className="mb-2 font-serif text-2xl text-on-surface">
                {study.original_word}
            </h3>
            <p className="mb-1 text-sm font-medium text-on-surface-variant">
                {study.transliteration}
            </p>
            <p className="flex-1 text-sm leading-relaxed text-on-surface-variant">
                {study.definition}
            </p>
            <div className="mt-6 flex items-center justify-between">
                <span className="text-[10px] tracking-widest text-on-surface-variant/40 uppercase">
                    {study.strongs_number}
                </span>
                <button
                    type="button"
                    onClick={() => onRemove(bookmark.id)}
                    className="rounded-full p-2 text-on-surface-variant transition-colors hover:text-destructive-foreground"
                    aria-label="Remove bookmark"
                >
                    <BookMarked className="size-4 fill-current" />
                </button>
            </div>
        </div>
    );
}

export default function BookmarksIndex({
    devotionalEntries,
    scriptureReferences,
    wordStudies,
}: Props) {
    const [confirmingRemoval, setConfirmingRemoval] = useState<number | null>(
        null,
    );
    const [removing, setRemoving] = useState(false);

    const totalBookmarks =
        devotionalEntries.length +
        scriptureReferences.length +
        wordStudies.length;

    function handleRemove() {
        if (confirmingRemoval === null) {
            return;
        }
        setRemoving(true);
        router.delete(destroy.url(confirmingRemoval), {
            preserveScroll: true,
            onFinish: () => {
                setRemoving(false);
                setConfirmingRemoval(null);
            },
        });
    }

    return (
        <DevotionalLayout>
            <Head title="Bookmarks" />

            <div className="px-6 py-8 md:px-12 lg:px-16">
                {/* Header */}
                <header className="mb-16 max-w-5xl">
                    <span className="text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                        Curated Collection
                    </span>
                    <h1 className="mt-3 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl lg:text-7xl">
                        Your Bookmarks
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg leading-relaxed text-on-surface-variant">
                        A quiet archive of the words, verses, and reflections
                        that have moved you. Organized for contemplation.
                    </p>
                </header>

                {/* Empty state */}
                {totalBookmarks === 0 ? (
                    <div className="mt-12 flex flex-col items-center justify-center rounded-2xl bg-surface-container p-12 text-center">
                        <Sparkles className="mb-4 size-10 text-on-surface-variant/30" />
                        <p className="font-serif text-2xl text-on-surface-variant">
                            No bookmarks yet
                        </p>
                        <p className="mt-2 max-w-sm text-sm text-on-surface-variant/60">
                            As you explore devotions, scriptures, and word
                            studies, bookmark the ones that resonate with you.
                        </p>
                    </div>
                ) : (
                    <div className="max-w-7xl space-y-20 pb-20">
                        {/* Devotional Entries */}
                        {devotionalEntries.length > 0 && (
                            <section>
                                <SectionHeader
                                    icon={
                                        <BookOpen className="size-5 text-moss" />
                                    }
                                    title="Devotional Entries"
                                    count={devotionalEntries.length}
                                />
                                <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                                    {devotionalEntries.map((bookmark) => (
                                        <DevotionalEntryCard
                                            key={bookmark.id}
                                            bookmark={bookmark}
                                            onRemove={setConfirmingRemoval}
                                        />
                                    ))}
                                </div>
                            </section>
                        )}

                        {/* Scripture References */}
                        {scriptureReferences.length > 0 && (
                            <section>
                                <SectionHeader
                                    icon={
                                        <BookMarked className="size-5 text-moss" />
                                    }
                                    title="Scripture References"
                                    count={scriptureReferences.length}
                                />
                                <div className="space-y-3">
                                    {scriptureReferences.map((bookmark) => (
                                        <ScriptureReferenceRow
                                            key={bookmark.id}
                                            bookmark={bookmark}
                                            onRemove={setConfirmingRemoval}
                                        />
                                    ))}
                                </div>
                            </section>
                        )}

                        {/* Word Studies */}
                        {wordStudies.length > 0 && (
                            <section>
                                <SectionHeader
                                    icon={
                                        <Languages className="size-5 text-moss" />
                                    }
                                    title="Word Studies"
                                    count={wordStudies.length}
                                />
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    {wordStudies.map((bookmark, index) => (
                                        <WordStudyCard
                                            key={bookmark.id}
                                            bookmark={bookmark}
                                            featured={index === 0}
                                            onRemove={setConfirmingRemoval}
                                        />
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>
                )}
            </div>

            {/* Confirmation Dialog */}
            <ConfirmationDialog
                open={confirmingRemoval !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setConfirmingRemoval(null);
                    }
                }}
                title="Remove Bookmark"
                description="Are you sure you want to remove this bookmark? You can always bookmark it again later."
                confirmLabel="Remove"
                destructive
                onConfirm={handleRemove}
                loading={removing}
            />
        </DevotionalLayout>
    );
}
