import { ProgressBar } from '@/components/devotional/progress-bar';
import DevotionalLayout from '@/layouts/devotional-layout';
import { cn } from '@/lib/utils';
import { show as showEntry } from '@/routes/themes/entries';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Lock,
    Quote,
    Zap,
} from 'lucide-react';

interface ScriptureRef {
    id: number;
    raw_reference: string;
}

interface Entry {
    id: number;
    title: string;
    body: string;
    display_order: number;
    scripture_references: ScriptureRef[];
    completions: { id: number }[];
}

interface Progress {
    total: number;
    completed: number;
    percentage: number;
}

interface Props {
    theme: {
        id: number;
        name: string;
        description: string | null;
    };
    entries: Entry[];
    progress: Progress;
}

type EntryStatus = 'completed' | 'current' | 'upcoming';

function EntryCard({
    entry,
    status,
    themeId,
}: {
    entry: Entry;
    status: EntryStatus;
    themeId: number;
}) {
    const isCurrent = status === 'current';
    const isCompleted = status === 'completed';
    const isUpcoming = status === 'upcoming';

    const scriptureLabel = entry.scripture_references
        .map((ref) => ref.raw_reference)
        .join(', ');

    const cleanBody = entry.body.replace(/<[^>]*>/g, '');
    const bodySnippet =
        cleanBody.length > 200 ? `${cleanBody.slice(0, 200)}...` : cleanBody;

    return (
        <Link
            href={showEntry.url({ theme: themeId, entry: entry.id })}
            prefetch
            className={cn(
                'group flex flex-col gap-6 rounded-3xl p-6 transition-all duration-500 md:flex-row md:items-center md:justify-between md:p-8',
                isCurrent &&
                    'relative overflow-hidden bg-surface-container-highest shadow-ambient-lg ring-1 ring-moss/20',
                isCompleted &&
                    'cursor-pointer bg-surface-container-low hover:bg-surface-container-highest',
                isUpcoming &&
                    'cursor-pointer bg-surface-container/50 opacity-60',
            )}
        >
            {/* Left accent bar for current entry */}
            {isCurrent && (
                <div className="absolute top-0 left-0 h-full w-1.5 bg-moss" />
            )}

            {/* Day number + Content */}
            <div className="flex flex-1 items-start gap-6 md:gap-10">
                {/* Day Counter */}
                <div className="flex flex-col items-center justify-center">
                    <span
                        className={cn(
                            'mb-1 text-xs tracking-tighter uppercase',
                            isCurrent
                                ? 'text-moss'
                                : 'text-on-surface-variant/40',
                        )}
                    >
                        Day
                    </span>
                    <span
                        className={cn(
                            'font-serif text-3xl',
                            isCurrent
                                ? 'font-bold text-moss'
                                : isCompleted
                                  ? 'font-light text-moss'
                                  : 'font-light text-on-surface-variant/50',
                        )}
                    >
                        {String(entry.display_order).padStart(2, '0')}
                    </span>
                </div>

                {/* Entry details */}
                <div className="flex-1 space-y-2">
                    <div className="flex items-center gap-3">
                        <h3
                            className={cn(
                                'font-serif text-xl md:text-2xl',
                                isCurrent
                                    ? 'font-bold text-on-surface'
                                    : isCompleted
                                      ? 'font-semibold text-on-surface'
                                      : 'font-semibold text-on-surface-variant/60',
                            )}
                        >
                            {entry.title}
                        </h3>
                        {isCompleted && (
                            <CheckCircle2 className="size-[18px] shrink-0 fill-moss text-moss-foreground" />
                        )}
                        {isCurrent && (
                            <Zap className="size-[18px] shrink-0 fill-moss text-moss-foreground" />
                        )}
                        {isUpcoming && (
                            <Lock className="size-4 shrink-0 text-on-surface-variant/40" />
                        )}
                    </div>

                    {scriptureLabel && (
                        <p className="text-xs font-medium tracking-widest text-on-surface-variant/50 uppercase">
                            {scriptureLabel}
                        </p>
                    )}

                    <p
                        className={cn(
                            'line-clamp-2 max-w-xl leading-relaxed',
                            isCurrent
                                ? 'font-medium text-on-surface'
                                : isUpcoming
                                  ? 'text-sm text-on-surface-variant/50 italic'
                                  : 'text-sm text-on-surface-variant',
                        )}
                    >
                        {isUpcoming
                            ? 'Continue your journey to unlock this entry.'
                            : bodySnippet}
                    </p>
                </div>
            </div>

            {/* Right side - Status / CTA */}
            <div className="flex items-center gap-4 pl-16 md:pl-0">
                {isCurrent && (
                    <span className="rounded-full bg-primary px-5 py-2 text-[10px] font-bold tracking-[0.2em] text-primary-foreground uppercase shadow-lg shadow-primary/20">
                        Continue Reading
                    </span>
                )}
                {isCompleted && (
                    <span className="rounded-full bg-moss/10 px-4 py-1.5 text-[10px] font-bold tracking-widest text-moss uppercase">
                        Completed
                    </span>
                )}
                {isUpcoming && (
                    <span className="rounded-full bg-surface-container-highest px-4 py-1.5 text-[10px] font-bold tracking-widest text-on-surface-variant/50 uppercase">
                        Upcoming
                    </span>
                )}
                <ArrowRight
                    className={cn(
                        'size-5 transition-transform duration-300 group-hover:translate-x-1',
                        isUpcoming
                            ? 'text-on-surface-variant/20'
                            : 'text-on-surface-variant/30',
                    )}
                />
            </div>
        </Link>
    );
}

export default function ThemesShow({ theme, entries, progress }: Props) {
    let foundFirstIncomplete = false;
    const entryStatuses = entries.map((entry) => {
        if (entry.completions.length > 0) {
            return 'completed' as EntryStatus;
        }
        if (!foundFirstIncomplete) {
            foundFirstIncomplete = true;
            return 'current' as EntryStatus;
        }
        return 'upcoming' as EntryStatus;
    });

    const currentEntry = entries.find((_, i) => entryStatuses[i] === 'current');
    const currentDayLabel = currentEntry
        ? `You are currently at Day ${String(currentEntry.display_order).padStart(2, '0')}: ${currentEntry.title}`
        : progress.percentage === 100
          ? 'All entries completed!'
          : 'Begin your journey';

    return (
        <DevotionalLayout>
            <Head title={theme.name} />

            {/* Hero Header */}
            <header className="bg-surface-container px-6 pt-10 pb-20 md:px-12 md:pt-16 md:pb-28 lg:px-20">
                <div className="mx-auto flex max-w-6xl flex-col items-end gap-10 md:flex-row md:gap-20">
                    <div className="w-full md:w-1/2">
                        <p className="mb-4 text-xs font-semibold tracking-[0.3em] text-moss uppercase">
                            Series Journey
                        </p>
                        <h1 className="mb-6 font-serif text-4xl leading-tight font-bold tracking-tight text-on-surface md:text-5xl lg:text-7xl">
                            {theme.name}
                        </h1>
                        {theme.description && (
                            <p className="mb-10 max-w-lg font-serif text-lg leading-relaxed text-on-surface-variant italic opacity-80 md:text-xl">
                                {theme.description}
                            </p>
                        )}

                        {/* Progress Card */}
                        <div className="max-w-sm rounded-2xl border border-border/50 bg-background/40 p-6 backdrop-blur-sm">
                            <div className="mb-3 flex items-center justify-between">
                                <span className="text-xs font-medium tracking-widest text-on-surface uppercase">
                                    Journey Progress
                                </span>
                                <span className="text-xs font-bold text-moss">
                                    {String(progress.completed).padStart(
                                        2,
                                        '0',
                                    )}{' '}
                                    / {String(progress.total).padStart(2, '0')}
                                </span>
                            </div>
                            <ProgressBar
                                value={progress.completed}
                                max={progress.total}
                            />
                            <p className="mt-3 text-xs font-medium text-on-surface-variant italic">
                                {currentDayLabel}
                            </p>
                        </div>
                    </div>

                    {/* Optional hero image placeholder */}
                    <div className="hidden w-full md:block md:w-1/2">
                        <div className="aspect-[3/4] overflow-hidden rounded-2xl bg-surface-container-high">
                            {entries[0]?.body && (
                                <div className="flex h-full items-center justify-center p-8">
                                    <Quote className="size-20 text-on-surface-variant/10" />
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Entry List */}
            <section className="relative z-20 mx-auto -mt-12 max-w-5xl px-6 pb-20">
                <div className="space-y-4">
                    {entries.map((entry, index) => (
                        <EntryCard
                            key={entry.id}
                            entry={entry}
                            status={entryStatuses[index]}
                            themeId={theme.id}
                        />
                    ))}
                </div>

                {/* Completion Summary */}
                {progress.percentage === 100 && (
                    <div className="mt-16 rounded-3xl bg-moss/5 p-10 text-center">
                        <CheckCircle2 className="mx-auto mb-4 size-12 text-moss" />
                        <h2 className="font-serif text-3xl font-medium text-on-surface">
                            Journey Complete
                        </h2>
                        <p className="mt-2 text-on-surface-variant">
                            You have completed all {progress.total} entries in
                            this theme.
                        </p>
                        <Link
                            href={showEntry.url({
                                theme: theme.id,
                                entry: entries[0].id,
                            })}
                            prefetch
                            className="mt-6 inline-flex items-center gap-2 rounded-full bg-primary px-8 py-3 text-xs font-bold tracking-widest text-primary-foreground uppercase"
                        >
                            <ArrowLeft className="size-4" />
                            Review from the Beginning
                        </Link>
                    </div>
                )}

                {/* Footer Quote */}
                <div className="mx-auto mt-24 max-w-2xl border-t border-border/40 py-16 text-center">
                    <Quote className="mx-auto mb-6 size-10 fill-moss/20 text-moss" />
                    <p className="font-serif text-2xl leading-snug text-on-surface italic md:text-3xl">
                        &ldquo;Silence is the sleep that nourishes
                        wisdom.&rdquo;
                    </p>
                    <p className="mt-4 text-xs tracking-widest text-on-surface-variant/40 uppercase">
                        &mdash; Francis Bacon
                    </p>
                </div>
            </section>
        </DevotionalLayout>
    );
}
