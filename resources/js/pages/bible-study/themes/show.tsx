import DevotionalLayout from '@/layouts/devotional-layout';
import { show as showPassage } from '@/routes/bible-study/passage';
import { index as themesIndex } from '@/routes/bible-study/themes';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen } from 'lucide-react';

interface Passage {
    id: number;
    position: number;
    is_guided_path: boolean;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    passage_intro: string | null;
}

interface Theme {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    long_intro: string;
    passages: Passage[];
}

interface Props {
    theme: Theme;
}

export default function Show({ theme }: Props) {
    const guided = theme.passages.filter((p) => p.is_guided_path);
    const all = theme.passages;

    function passageUrl(p: Passage): string {
        return showPassage.url({
            query: {
                theme: theme.slug,
                book: p.book,
                chapter: String(p.chapter),
                verse_start: String(p.verse_start),
                ...(p.verse_end !== null
                    ? { verse_end: String(p.verse_end) }
                    : {}),
            },
        });
    }

    function passageRef(p: Passage): string {
        return `${p.book} ${p.chapter}:${p.verse_start}${p.verse_end ? `–${p.verse_end}` : ''}`;
    }

    return (
        <DevotionalLayout>
            <Head title={`Theme — ${theme.title}`} />
            <div className="mx-auto max-w-4xl px-4 py-8 md:px-8">
                <Link
                    href={themesIndex.url()}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to Themes
                </Link>

                <div className="mt-4">
                    <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Bible Study Theme
                    </p>
                    <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                        {theme.title}
                    </h1>
                    <p className="mt-3 text-base text-on-surface-variant">
                        {theme.short_description}
                    </p>
                    <div className="mt-6 space-y-4 text-on-surface/90 md:text-lg">
                        {theme.long_intro
                            .split(/\n+/)
                            .filter(Boolean)
                            .map((para) => (
                                <p key={para.slice(0, 32)}>{para}</p>
                            ))}
                    </div>
                </div>

                {guided.length > 0 && (
                    <section className="mt-12">
                        <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Guided Path
                        </h2>
                        <ol className="mt-4 space-y-3">
                            {guided.map((p, i) => (
                                <li key={p.id} className="flex gap-3">
                                    <span className="mt-1 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-moss text-xs text-moss-foreground">
                                        {i + 1}
                                    </span>
                                    <Link
                                        href={passageUrl(p)}
                                        className="group flex-1 rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                                    >
                                        <div className="font-medium">
                                            {passageRef(p)}
                                        </div>
                                        {p.passage_intro && (
                                            <p className="mt-1 text-sm text-on-surface-variant">
                                                {p.passage_intro}
                                            </p>
                                        )}
                                    </Link>
                                </li>
                            ))}
                        </ol>
                    </section>
                )}

                <section className="mt-12">
                    <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        All Passages
                    </h2>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        {all.map((p) => (
                            <Link
                                key={p.id}
                                href={passageUrl(p)}
                                className="group rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="font-medium">
                                        {passageRef(p)}
                                    </div>
                                    <BookOpen className="size-4 text-on-surface-variant transition-transform group-hover:translate-x-1" />
                                </div>
                                {p.passage_intro && (
                                    <p className="mt-1 line-clamp-2 text-sm text-on-surface-variant">
                                        {p.passage_intro}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </DevotionalLayout>
    );
}
