import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import DevotionalLayout from '@/layouts/devotional-layout';
import {
    BIBLE_VERSIONS,
    type BibleVersionKey,
    setPreferredVersion,
} from '@/lib/bible-versions';
import { show as showPassage } from '@/routes/bible-study/passage';
import { show as showTheme } from '@/routes/bible-study/themes';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { HistoricalContextCard } from '@/components/bible-study/historical-context-card';
import { InsightsPanel } from '@/components/bible-study/insights-panel';
import { ReflectionComposer } from '@/components/bible-study/reflection-composer';
import { ReflectionList } from '@/components/bible-study/reflection-list';
import { ScriptureReader } from '@/components/bible-study/scripture-reader';

interface CrossRef {
    book: string;
    chapter: number;
    verse_start: number;
    verse_end?: number | null;
    note?: string;
}

interface WordStudy {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
}

interface WordHighlight {
    id: number;
    verse_number: number;
    word_index_in_verse: number;
    display_word: string;
    word_study: WordStudy | null;
}

interface Insight {
    interpretation: string;
    application: string;
    cross_references: CrossRef[];
    literary_context: string;
}

interface HistoricalContext {
    setting: string;
    author: string;
    date_range: string;
    audience: string;
    historical_events: string;
}

export interface Reflection {
    id: number;
    user_id: number;
    user_name: string | null;
    is_own: boolean;
    verse_number: number | null;
    body: string;
    is_shared_with_partner: boolean;
    created_at: string;
    updated_at: string;
}

interface PassagePayload {
    theme_slug: string | null;
    theme_title: string | null;
    theme_id: number | null;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    translation: string;
    verses: Record<number, string>;
    structured: boolean;
    is_enriched: boolean;
    theme_passage_id: number | null;
    passage_intro: string | null;
    insight: Insight | null;
    historical_context: HistoricalContext | null;
    word_highlights: WordHighlight[];
    reflections: Reflection[];
    has_partner: boolean;
}

interface Props {
    passage: PassagePayload;
}

export default function Passage({ passage }: Props) {
    const ref = `${passage.book} ${passage.chapter}:${passage.verse_start}${passage.verse_end ? `–${passage.verse_end}` : ''}`;

    function changeTranslation(translation: BibleVersionKey): void {
        setPreferredVersion(translation);
        router.get(
            showPassage.url(),
            {
                ...(passage.theme_slug ? { theme: passage.theme_slug } : {}),
                book: passage.book,
                chapter: passage.chapter,
                verse_start: passage.verse_start,
                ...(passage.verse_end !== null
                    ? { verse_end: passage.verse_end }
                    : {}),
                translation,
            },
            { preserveScroll: true },
        );
    }

    const backHref = passage.theme_slug
        ? showTheme.url(passage.theme_slug)
        : '/bible-study';

    return (
        <DevotionalLayout>
            <Head title={`Reading — ${ref}`} />
            <div className="mx-auto max-w-7xl px-4 py-8 md:px-8">
                <Link
                    href={backHref}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    {passage.theme_title
                        ? `Back to ${passage.theme_title}`
                        : 'Back to Bible Study'}
                </Link>

                <div className="mt-4 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        {passage.theme_title && (
                            <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                                {passage.theme_title}
                            </p>
                        )}
                        <h1 className="mt-2 font-serif text-3xl font-medium tracking-tight text-on-surface md:text-4xl">
                            {ref}
                        </h1>
                    </div>
                    <Select
                        value={passage.translation}
                        onValueChange={(v) =>
                            changeTranslation(v as BibleVersionKey)
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {BIBLE_VERSIONS.map((v) => (
                                <SelectItem key={v.value} value={v.value}>
                                    {v.value}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {passage.passage_intro && (
                    <p className="mt-6 text-base text-on-surface-variant md:text-lg">
                        {passage.passage_intro}
                    </p>
                )}

                <div className="mt-8 grid gap-8 lg:grid-cols-[1.4fr_1fr]">
                    <div className="space-y-6">
                        <ScriptureReader
                            verses={passage.verses}
                            structured={passage.structured}
                            wordHighlights={passage.word_highlights}
                            reflections={passage.reflections}
                            partnerEnabled={passage.has_partner}
                            passageRef={{
                                themeId: passage.theme_id,
                                book: passage.book,
                                chapter: passage.chapter,
                                verseStart: passage.verse_start,
                                verseEnd: passage.verse_end,
                            }}
                        />
                        <ReflectionComposer
                            scope="passage"
                            partnerEnabled={passage.has_partner}
                            passageRef={{
                                themeId: passage.theme_id,
                                book: passage.book,
                                chapter: passage.chapter,
                                verseStart: passage.verse_start,
                                verseEnd: passage.verse_end,
                                verseNumber: null,
                            }}
                            existing={
                                passage.reflections.find(
                                    (r) => r.is_own && r.verse_number === null,
                                ) ?? null
                            }
                        />
                        <ReflectionList
                            reflections={passage.reflections.filter(
                                (r) => r.verse_number === null,
                            )}
                        />
                    </div>

                    {passage.is_enriched && (
                        <aside className="space-y-6">
                            {passage.insight && (
                                <InsightsPanel insight={passage.insight} />
                            )}
                            {passage.historical_context && (
                                <HistoricalContextCard
                                    context={passage.historical_context}
                                />
                            )}
                        </aside>
                    )}
                </div>
            </div>
        </DevotionalLayout>
    );
}
