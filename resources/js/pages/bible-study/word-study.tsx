import DevotionalLayout from '@/layouts/devotional-layout';
import { index as bibleStudyIndex } from '@/routes/bible-study';
import {
    destroy as destroyBookmark,
    store as storeBookmark,
} from '@/routes/bookmarks';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Bookmark,
    BookmarkCheck,
    BookOpen,
    Hash,
    Languages,
    Loader2,
    Quote,
} from 'lucide-react';
import { useState } from 'react';

interface WordStudyPassage {
    id: number;
    word_study_id: number;
    book: string;
    chapter: number;
    verse: number;
    english_word: string;
}

interface WordStudy {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
    passages: WordStudyPassage[];
}

interface Props {
    wordStudy: WordStudy;
    isBookmarked?: boolean;
    bookmarkId?: number | null;
}

export default function WordStudyShow({
    wordStudy,
    isBookmarked = false,
    bookmarkId = null,
}: Props) {
    const [bookmarking, setBookmarking] = useState(false);

    const strongsNumeric = wordStudy.strongs_number.replace(/[^\d]/g, '');
    const isGreek = wordStudy.language.toLowerCase() === 'greek';
    const languageLabel = isGreek ? 'Greek' : 'Hebrew';

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
                    bookmarkable_type: 'App\\Models\\WordStudy',
                    bookmarkable_id: wordStudy.id,
                },
                {
                    preserveScroll: true,
                    onFinish: () => setBookmarking(false),
                },
            );
        }
    }

    return (
        <DevotionalLayout>
            <Head title={`${wordStudy.transliteration} - Word Study`} />

            <div className="mx-auto max-w-4xl px-6 py-8 pb-24 md:px-8 md:py-16">
                {/* Back Navigation */}
                <Link
                    href={bibleStudyIndex.url()}
                    prefetch
                    className="mb-8 inline-flex items-center gap-2 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Bible Study
                </Link>

                {/* Hero Section */}
                <header className="mb-16">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="rounded-full bg-moss/10 px-3 py-1 text-[10px] font-bold tracking-widest text-moss uppercase">
                            {languageLabel} &middot; Word Study
                        </span>
                    </div>

                    {/* Original Word */}
                    <h1 className="mb-4 font-serif text-6xl leading-none tracking-tight text-on-surface md:text-7xl lg:text-8xl">
                        {wordStudy.original_word}
                    </h1>

                    {/* Transliteration */}
                    <p className="mb-8 font-serif text-xl text-on-surface-variant italic md:text-2xl">
                        {wordStudy.transliteration}
                    </p>

                    {/* Actions */}
                    <div className="flex gap-3">
                        <button
                            type="button"
                            onClick={handleBookmarkToggle}
                            disabled={bookmarking}
                            className="inline-flex items-center gap-1.5 rounded-full border border-border px-4 py-2 text-xs font-medium text-on-surface-variant transition-colors hover:bg-surface-container-high"
                        >
                            {bookmarking ? (
                                <Loader2 className="size-3.5 animate-spin" />
                            ) : isBookmarked ? (
                                <BookmarkCheck className="size-3.5 text-moss" />
                            ) : (
                                <Bookmark className="size-3.5" />
                            )}
                            {isBookmarked ? 'Saved' : 'Save'}
                        </button>
                    </div>
                </header>

                {/* Content Grid */}
                <div className="mb-16 grid grid-cols-1 gap-6 md:grid-cols-12">
                    {/* Definition Card */}
                    <div className="rounded-3xl bg-surface-container-low p-8 md:col-span-8 md:p-10">
                        <div className="mb-4 flex items-center gap-2">
                            <BookOpen className="size-4 text-moss" />
                            <h2 className="text-[10px] font-bold tracking-[0.3em] text-on-surface uppercase">
                                Definition
                            </h2>
                        </div>
                        <p className="font-serif text-lg leading-relaxed text-on-surface/90 md:text-xl">
                            {wordStudy.definition}
                        </p>
                    </div>

                    {/* Strong's Number Card */}
                    <div className="flex flex-col items-center justify-center rounded-3xl bg-primary p-8 text-primary-foreground md:col-span-4">
                        <Hash className="mb-3 size-5 text-primary-foreground/30" />
                        <span className="font-serif text-5xl font-bold tabular-nums">
                            {strongsNumeric || wordStudy.strongs_number}
                        </span>
                        <span className="mt-2 text-[9px] font-medium tracking-widest text-primary-foreground/50 uppercase">
                            Strong&rsquo;s Number
                        </span>
                        <span className="mt-1 text-xs text-primary-foreground/40">
                            {wordStudy.strongs_number}
                        </span>
                    </div>
                </div>

                {/* Language & Grammar */}
                <div className="mb-16 grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div className="rounded-2xl bg-surface-container-high p-6">
                        <Languages className="mb-2 size-4 text-moss" />
                        <span className="text-[10px] font-bold tracking-widest text-on-surface-variant uppercase">
                            Language
                        </span>
                        <p className="mt-1 font-serif text-lg text-on-surface">
                            {wordStudy.language}
                        </p>
                    </div>
                    <div className="rounded-2xl bg-surface-container-high p-6">
                        <Hash className="mb-2 size-4 text-on-surface-variant/40" />
                        <span className="text-[10px] font-bold tracking-widest text-on-surface-variant uppercase">
                            Occurrences
                        </span>
                        <p className="mt-1 font-serif text-lg text-on-surface">
                            {wordStudy.passages.length}
                        </p>
                    </div>
                    <div className="col-span-2 rounded-2xl bg-surface-container-high p-6 md:col-span-1">
                        <Quote className="mb-2 size-4 text-on-surface-variant/40" />
                        <span className="text-[10px] font-bold tracking-widest text-on-surface-variant uppercase">
                            Transliteration
                        </span>
                        <p className="mt-1 font-serif text-lg text-on-surface italic">
                            {wordStudy.transliteration}
                        </p>
                    </div>
                </div>

                {/* Scriptural Occurrences */}
                {wordStudy.passages.length > 0 && (
                    <section>
                        <div className="mb-8">
                            <h2 className="font-serif text-2xl tracking-tight md:text-3xl">
                                Key Scriptural Occurrences
                            </h2>
                            <p className="mt-2 text-sm text-on-surface-variant">
                                Passages where{' '}
                                <span className="font-serif italic">
                                    {wordStudy.transliteration}
                                </span>{' '}
                                appears in scripture.
                            </p>
                        </div>

                        <div className="space-y-3">
                            {wordStudy.passages.map((passage) => (
                                <div
                                    key={passage.id}
                                    className="group flex items-center gap-6 rounded-2xl bg-surface-container-low p-5 transition-colors hover:bg-surface-container"
                                >
                                    {/* Reference */}
                                    <div className="min-w-0 flex-1">
                                        <p className="text-xs font-medium tracking-widest text-on-surface-variant/50 uppercase">
                                            {passage.book} {passage.chapter}:
                                            {passage.verse}
                                        </p>
                                        <p className="mt-1 text-sm text-on-surface">
                                            &ldquo;...
                                            <span className="font-semibold text-moss">
                                                {passage.english_word}
                                            </span>
                                            ...&rdquo;
                                        </p>
                                    </div>

                                    {/* Word chip */}
                                    <span className="shrink-0 rounded-full bg-moss/10 px-3 py-1 text-[10px] font-bold tracking-widest text-moss uppercase">
                                        {passage.english_word}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                {/* Empty state for passages */}
                {wordStudy.passages.length === 0 && (
                    <div className="flex flex-col items-center justify-center rounded-2xl bg-surface-container p-12 text-center">
                        <BookOpen className="mb-4 size-10 text-on-surface-variant/30" />
                        <p className="font-serif text-2xl text-on-surface-variant">
                            No passages recorded
                        </p>
                        <p className="mt-2 max-w-sm text-sm text-on-surface-variant/60">
                            Scriptural occurrences for this word have not been
                            catalogued yet.
                        </p>
                    </div>
                )}
            </div>
        </DevotionalLayout>
    );
}
