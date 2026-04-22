import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

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
    id: number;
    interpretation: string;
    application: string;
    cross_references: Array<{
        book: string;
        chapter: number;
        verse_start: number;
        verse_end?: number;
        note?: string;
    }>;
    literary_context: string;
}

interface HistoricalContext {
    id: number;
    setting: string;
    author: string;
    date_range: string;
    audience: string;
    historical_events: string;
}

interface Passage {
    id: number;
    position: number;
    is_guided_path: boolean;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    passage_intro: string | null;
    insight: Insight | null;
    historical_context: HistoricalContext | null;
    word_highlights: WordHighlight[];
}

interface Theme {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    long_intro: string;
    status: string;
    requested_count: number;
    approved_at: string | null;
    passages: Passage[];
}

interface Props {
    theme: Theme;
}

export default function Show({ theme }: Props) {
    return (
        <DevotionalLayout>
            <Head title={`Review — ${theme.title}`} />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-bold">{theme.title}</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {theme.status} · {theme.passages.length} passages
                </p>
            </div>
        </DevotionalLayout>
    );
}
