import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface Theme {
    id: number;
    name: string;
    description: string | null;
    status: string;
    entries_count: number;
    completed_entries_count: number;
}

export default function ThemesIndex({ themes }: { themes: Theme[] }) {
    return (
        <DevotionalLayout>
            <Head title="Themes" />
            <div className="px-6 py-6 md:px-8">
                <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                    Curated Collection
                </p>
                <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                    Explore Thematic Devotions
                </h1>

                {themes.length === 0 ? (
                    <div className="mt-12 rounded-lg bg-surface-container p-8 text-center">
                        <p className="font-serif text-xl text-on-surface-variant">
                            No themes available yet
                        </p>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Check back soon for new devotional content.
                        </p>
                    </div>
                ) : (
                    <div className="mt-8 grid gap-6 sm:grid-cols-2">
                        {themes.map((theme) => (
                            <div
                                key={theme.id}
                                className="rounded-lg bg-surface-container p-6"
                            >
                                <h2 className="font-serif text-xl font-medium text-on-surface">
                                    {theme.name}
                                </h2>
                                {theme.description && (
                                    <p className="mt-2 line-clamp-2 text-sm text-on-surface-variant">
                                        {theme.description}
                                    </p>
                                )}
                                <div className="mt-4 flex items-center justify-between text-xs text-on-surface-variant">
                                    <span>
                                        {theme.completed_entries_count} of{' '}
                                        {theme.entries_count} completed
                                    </span>
                                    <span>
                                        {theme.entries_count > 0
                                            ? Math.round(
                                                  (theme.completed_entries_count /
                                                      theme.entries_count) *
                                                      100,
                                              )
                                            : 0}
                                        %
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </DevotionalLayout>
    );
}
