import { ProgressBar } from '@/components/devotional/progress-bar';
import DevotionalLayout from '@/layouts/devotional-layout';
import { storageUrl } from '@/lib/utils';
import { show } from '@/routes/themes';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Sparkles } from 'lucide-react';

interface Theme {
    id: number;
    name: string;
    description: string | null;
    status: string;
    entries_count: number;
    completed_entries_count: number;
    cover_image_path: string | null;
}

function getThemeStatus(
    theme: Theme,
): 'completed' | 'in_progress' | 'not_started' {
    if (theme.entries_count === 0) {
        return 'not_started';
    }
    if (theme.completed_entries_count >= theme.entries_count) {
        return 'completed';
    }
    if (theme.completed_entries_count > 0) {
        return 'in_progress';
    }
    return 'not_started';
}

function StatusChip({ status }: { status: ReturnType<typeof getThemeStatus> }) {
    const config = {
        completed: {
            label: 'Completed',
            className: 'bg-moss text-moss-foreground',
        },
        in_progress: {
            label: 'In Progress',
            className: 'bg-moss/80 text-moss-foreground',
        },
        not_started: {
            label: 'Published',
            className: 'bg-surface-container-highest text-on-surface',
        },
    };

    const { label, className } = config[status];

    return (
        <span
            className={`inline-flex items-center rounded-full px-3 py-1 text-[10px] font-bold tracking-widest uppercase ${className}`}
        >
            {label}
        </span>
    );
}

function ThemeCard({ theme }: { theme: Theme }) {
    const status = getThemeStatus(theme);
    const isCompleted = status === 'completed';

    return (
        <Link
            href={show.url(theme.id)}
            prefetch
            className="group flex flex-col overflow-hidden rounded-2xl bg-surface-container-low transition-all duration-500 hover:shadow-ambient-lg"
        >
            {/* Cover Image */}
            <div className="relative aspect-[16/9] overflow-hidden bg-surface-container">
                {theme.cover_image_path ? (
                    <img
                        src={storageUrl(theme.cover_image_path)}
                        alt={theme.name}
                        className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-surface-container-high to-surface-container-highest">
                        <Sparkles className="size-12 text-on-surface-variant/20" />
                    </div>
                )}
            </div>

            {/* Card Content */}
            <div className="flex flex-1 flex-col gap-4 px-8 pt-5 pb-8">
                <div className="flex items-start justify-between">
                    <StatusChip status={status} />
                    <span className="text-[10px] tracking-widest text-on-surface-variant/50 uppercase">
                        {theme.entries_count}{' '}
                        {theme.entries_count === 1 ? 'Day' : 'Days'} Journey
                    </span>
                </div>

                <h3 className="font-serif text-2xl font-medium tracking-tight text-on-surface md:text-3xl">
                    {theme.name}
                </h3>

                {theme.description && (
                    <p className="line-clamp-2 text-sm leading-relaxed text-on-surface-variant">
                        {theme.description}
                    </p>
                )}

                <div className="mt-auto pt-2">
                    <ProgressBar
                        value={theme.completed_entries_count}
                        max={theme.entries_count}
                        label={`${theme.completed_entries_count} of ${theme.entries_count} completed`}
                        showPercentage
                    />
                </div>

                <div className="flex items-center justify-between pt-2">
                    <span className="inline-flex items-center bg-primary px-6 py-2.5 text-xs font-medium tracking-widest text-primary-foreground uppercase transition-opacity group-hover:opacity-90">
                        {isCompleted ? 'Review Theme' : 'Open Theme'}
                    </span>
                    {isCompleted ? (
                        <CheckCircle2 className="size-5 fill-moss text-moss-foreground" />
                    ) : (
                        <ArrowRight className="size-5 text-on-surface-variant/20 transition-all duration-300 group-hover:translate-x-1 group-hover:text-on-surface-variant" />
                    )}
                </div>
            </div>
        </Link>
    );
}

export default function ThemesIndex({ themes }: { themes: Theme[] }) {
    const totalEntries = themes.reduce((sum, t) => sum + t.entries_count, 0);
    const totalCompleted = themes.reduce(
        (sum, t) => sum + t.completed_entries_count,
        0,
    );
    const overallPercentage =
        totalEntries > 0
            ? Math.round((totalCompleted / totalEntries) * 100)
            : 0;
    const themesStarted = themes.filter(
        (t) => t.completed_entries_count > 0,
    ).length;

    return (
        <DevotionalLayout>
            <Head title="Themes" />
            <div className="px-6 py-8 md:px-12 lg:px-16">
                {/* Header */}
                <header className="mb-12 max-w-5xl md:mb-16">
                    <div className="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                        <div className="space-y-3">
                            <span className="text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                                Curated Collection
                            </span>
                            <h1 className="font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl lg:text-7xl">
                                Explore Thematic
                                <br />
                                Devotions
                            </h1>
                        </div>

                        {/* Overall Progress Card */}
                        {themes.length > 0 && (
                            <div className="min-w-[260px] rounded-xl border border-border/50 bg-surface-container p-5">
                                <div className="mb-3 flex items-center justify-between">
                                    <span className="text-[10px] font-medium tracking-widest text-on-surface-variant/60 uppercase">
                                        Overall Progress
                                    </span>
                                    <span className="font-serif text-xl font-bold text-on-surface">
                                        {overallPercentage}%
                                    </span>
                                </div>
                                <div className="h-1 overflow-hidden rounded-full bg-surface-container-highest">
                                    <div
                                        className="h-full rounded-full bg-moss transition-all duration-700"
                                        style={{
                                            width: `${overallPercentage}%`,
                                        }}
                                    />
                                </div>
                                <p className="mt-2.5 text-[11px] text-on-surface-variant italic">
                                    {themesStarted} of {themes.length} themes
                                    initiated
                                </p>
                            </div>
                        )}
                    </div>
                </header>

                {/* Empty State */}
                {themes.length === 0 ? (
                    <div className="mt-12 flex flex-col items-center justify-center rounded-2xl bg-surface-container p-12 text-center">
                        <Sparkles className="mb-4 size-10 text-on-surface-variant/30" />
                        <p className="font-serif text-2xl text-on-surface-variant">
                            No themes available yet
                        </p>
                        <p className="mt-2 max-w-sm text-sm text-on-surface-variant/60">
                            Devotional themes are being curated. Check back soon
                            for new content to explore.
                        </p>
                    </div>
                ) : (
                    /* Theme Grid */
                    <section className="grid max-w-7xl grid-cols-1 gap-8 md:grid-cols-2 md:gap-10">
                        {themes.map((theme) => (
                            <ThemeCard key={theme.id} theme={theme} />
                        ))}
                    </section>
                )}
            </div>
        </DevotionalLayout>
    );
}
