import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { show as showPassage } from '@/routes/bible-study/passage';
import { show as showTheme } from '@/routes/bible-study/themes';
import { Link } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Theme {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    passage_count: number;
}

interface RecentPassage {
    theme_id: number | null;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    last_accessed_at: string;
}

interface Props {
    themes: Theme[];
    recentPassages: RecentPassage[];
}

interface SearchResult {
    id: number;
    slug: string;
    title: string;
    short_description: string;
}

export function ThemesTab({ themes, recentPassages }: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[] | null>(null);
    const [loading, setLoading] = useState(false);

    async function handleSearch(e: FormEvent) {
        e.preventDefault();
        if (!query.trim()) {
            setResults(null);
            return;
        }
        setLoading(true);
        try {
            const r = await fetch(
                `/bible-study/search?q=${encodeURIComponent(query.trim())}`,
                { headers: { Accept: 'application/json' } },
            );
            const data = (await r.json()) as { themes: SearchResult[] };
            setResults(data.themes);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="space-y-8">
            <form onSubmit={handleSearch} className="flex gap-2">
                <div className="relative flex-1">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-on-surface-variant" />
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search themes (e.g., wisdom, resilience)"
                        className="pl-10"
                    />
                </div>
                <Button type="submit" disabled={loading || !query.trim()}>
                    Search
                </Button>
            </form>

            {results !== null && results.length === 0 && (
                <div className="rounded-lg border border-dashed border-border p-6 text-center text-sm text-on-surface-variant">
                    No themes match &ldquo;{query}&rdquo;. Try a different word
                    — fuzzy suggestions are coming in a later update.
                </div>
            )}

            {results !== null && results.length > 0 && (
                <section>
                    <h3 className="mb-3 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Search results
                    </h3>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {results.map((theme) => (
                            <Link
                                key={theme.id}
                                href={showTheme.url(theme.slug)}
                                className="group rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="font-medium">
                                        {theme.title}
                                    </div>
                                    <ArrowRight className="size-4 text-on-surface-variant transition-transform group-hover:translate-x-1" />
                                </div>
                                <p className="mt-1 text-sm text-on-surface-variant">
                                    {theme.short_description}
                                </p>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {recentPassages.length > 0 && (
                <section>
                    <h3 className="mb-3 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Recent passages
                    </h3>
                    <div className="flex flex-wrap gap-2">
                        {recentPassages.map((passage, i) => {
                            const ref = `${passage.book} ${passage.chapter}:${passage.verse_start}${passage.verse_end ? `–${passage.verse_end}` : ''}`;
                            const url = showPassage.url({
                                query: {
                                    book: passage.book,
                                    chapter: String(passage.chapter),
                                    verse_start: String(passage.verse_start),
                                    ...(passage.verse_end !== null
                                        ? {
                                              verse_end: String(
                                                  passage.verse_end,
                                              ),
                                          }
                                        : {}),
                                },
                            });
                            return (
                                <Link
                                    // eslint-disable-next-line @eslint-react/no-array-index-key
                                    key={i}
                                    href={url}
                                    className="rounded-full border border-border bg-surface-container-low px-3 py-1 text-xs text-on-surface transition-colors hover:border-moss/40"
                                >
                                    {ref}
                                </Link>
                            );
                        })}
                    </div>
                </section>
            )}

            <section>
                <h3 className="mb-3 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                    All themes
                </h3>
                {themes.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-border p-6 text-center text-sm text-on-surface-variant">
                        No themes yet. Check back soon.
                    </div>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {themes.map((theme) => (
                            <Link
                                key={theme.id}
                                href={showTheme.url(theme.slug)}
                                className="group rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="font-medium">
                                        {theme.title}
                                    </div>
                                    <ArrowRight className="size-4 text-on-surface-variant transition-transform group-hover:translate-x-1" />
                                </div>
                                <p className="mt-1 line-clamp-2 text-sm text-on-surface-variant">
                                    {theme.short_description}
                                </p>
                                <div className="mt-2 text-xs text-on-surface-variant/80">
                                    {theme.passage_count} passages
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}
