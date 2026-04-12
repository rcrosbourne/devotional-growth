import DevotionalLayout from '@/layouts/devotional-layout';
import { index as bibleStudyIndex } from '@/routes/bible-study';
import {
    show as showWordStudy,
    search as wordStudySearchRoute,
} from '@/routes/bible-study/word-study';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    BookOpen,
    Hash,
    Languages,
    Search,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';

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
    query: string;
    results: WordStudy[];
}

function SearchInput({ initialQuery }: { initialQuery: string }) {
    const [query, setQuery] = useState(initialQuery);

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        if (!query.trim()) {
            return;
        }
        router.get(
            wordStudySearchRoute.url({ query: { q: query.trim() } }),
            {},
            { preserveState: true },
        );
    }

    return (
        <form onSubmit={handleSearch} className="flex gap-2">
            <div className="relative flex-1">
                <Search className="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-on-surface-variant/40" />
                <input
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder="Search by word, Strong's number, or definition..."
                    className="w-full rounded-xl border-none bg-surface-container-low py-3 pr-4 pl-11 text-sm text-on-surface placeholder:text-on-surface-variant/40 focus:ring-1 focus:ring-primary focus:outline-none"
                />
            </div>
            <button
                type="submit"
                className="flex items-center justify-center rounded-xl bg-primary px-5 text-primary-foreground transition-opacity hover:opacity-90"
            >
                <Search className="size-4" />
            </button>
        </form>
    );
}

function WordStudyCard({ study }: { study: WordStudy }) {
    const strongsNumeric = study.strongs_number.replace(/[^\d]/g, '');
    const definitionPreview =
        study.definition.length > 120
            ? `${study.definition.slice(0, 120)}...`
            : study.definition;

    return (
        <Link
            href={showWordStudy.url(study.id)}
            prefetch
            className="group flex flex-col overflow-hidden rounded-2xl bg-surface-container-low transition-all duration-500 hover:shadow-ambient-lg"
        >
            {/* Top accent bar */}
            <div className="h-1 w-full bg-gradient-to-r from-moss/40 to-moss/10" />

            <div className="flex flex-1 flex-col gap-4 p-6 md:p-8">
                {/* Language badge */}
                <div className="flex items-center justify-between">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-moss/10 px-2.5 py-1 text-[10px] font-bold tracking-widest text-moss uppercase">
                        <Languages className="size-3" />
                        {study.language}
                    </span>
                    <span className="flex items-center gap-1 text-xs font-semibold text-on-surface-variant/40 tabular-nums">
                        <Hash className="size-3" />
                        {strongsNumeric || study.strongs_number}
                    </span>
                </div>

                {/* Original word */}
                <h3 className="font-serif text-3xl tracking-tight text-on-surface">
                    {study.original_word}
                </h3>

                {/* Transliteration */}
                <p className="font-serif text-sm text-on-surface-variant italic">
                    {study.transliteration}
                </p>

                {/* Definition preview */}
                <p className="line-clamp-3 flex-1 text-sm leading-relaxed text-on-surface-variant">
                    {definitionPreview}
                </p>

                {/* Footer */}
                <div className="flex items-center justify-between border-t border-border/10 pt-4">
                    <span className="text-[10px] tracking-widest text-on-surface-variant/40 uppercase">
                        {study.passages.length}{' '}
                        {study.passages.length === 1
                            ? 'occurrence'
                            : 'occurrences'}
                    </span>
                    <ArrowRight className="size-4 text-on-surface-variant/20 transition-all duration-300 group-hover:translate-x-1 group-hover:text-on-surface-variant" />
                </div>
            </div>
        </Link>
    );
}

export default function WordStudySearch({ query, results }: Props) {
    return (
        <DevotionalLayout>
            <Head
                title={query ? `"${query}" - Word Study` : 'Word Study Search'}
            />

            <div className="mx-auto max-w-5xl px-6 py-8 pb-24 md:px-12 md:py-16">
                {/* Back Navigation */}
                <Link
                    href={bibleStudyIndex.url()}
                    prefetch
                    className="mb-8 inline-flex items-center gap-2 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Bible Study
                </Link>

                {/* Header */}
                <header className="mb-10">
                    <span className="mb-3 block text-[10px] font-semibold tracking-[0.3em] text-moss uppercase">
                        Lexicon Search
                    </span>
                    <h1 className="mb-6 font-serif text-3xl tracking-tight text-on-surface md:text-4xl">
                        Word Study
                    </h1>
                    <div className="max-w-xl">
                        <SearchInput initialQuery={query} />
                    </div>
                </header>

                {/* Results */}
                {query && results.length > 0 && (
                    <>
                        <p className="mb-6 text-sm text-on-surface-variant">
                            {results.length}{' '}
                            {results.length === 1 ? 'result' : 'results'} for
                            &ldquo;{query}&rdquo;
                        </p>
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {results.map((study) => (
                                <WordStudyCard key={study.id} study={study} />
                            ))}
                        </div>
                    </>
                )}

                {/* No results */}
                {query && results.length === 0 && (
                    <div className="flex flex-col items-center justify-center rounded-2xl bg-surface-container p-12 text-center">
                        <BookOpen className="mb-4 size-10 text-on-surface-variant/30" />
                        <p className="font-serif text-2xl text-on-surface-variant">
                            No results found
                        </p>
                        <p className="mt-2 max-w-sm text-sm text-on-surface-variant/60">
                            No word studies match &ldquo;{query}&rdquo;. Try
                            searching with a different term, Strong&rsquo;s
                            number, or original language word.
                        </p>
                    </div>
                )}

                {/* Initial state */}
                {!query && (
                    <div className="flex flex-col items-center justify-center rounded-2xl bg-surface-container p-12 text-center">
                        <Search className="mb-4 size-10 text-on-surface-variant/30" />
                        <p className="font-serif text-2xl text-on-surface-variant">
                            Search the Lexicon
                        </p>
                        <p className="mt-2 max-w-sm text-sm text-on-surface-variant/60">
                            Enter an English word, Greek or Hebrew term, or
                            Strong&rsquo;s Concordance number to explore
                            original-language word studies.
                        </p>
                    </div>
                )}
            </div>
        </DevotionalLayout>
    );
}
