import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

export default function ThemesIndex({ themes }: { themes: unknown[] }) {
    return (
        <DevotionalLayout>
            <Head title="Themes" />
            <div className="px-6 py-6 md:px-8">
                <p className="text-xs font-medium uppercase tracking-[0.15em] text-on-surface-variant">
                    Curated Collection
                </p>
                <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                    Explore Thematic Devotions
                </h1>

                {themes.length === 0 && (
                    <div className="mt-12 rounded-lg bg-surface-container p-8 text-center">
                        <p className="font-serif text-xl text-on-surface-variant">
                            No themes available yet
                        </p>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Check back soon for new devotional content.
                        </p>
                    </div>
                )}
            </div>
        </DevotionalLayout>
    );
}
