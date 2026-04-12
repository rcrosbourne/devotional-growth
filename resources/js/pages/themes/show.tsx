import { ProgressBar } from '@/components/devotional/progress-bar';
import DevotionalLayout from '@/layouts/devotional-layout';
import { cn, storageUrl } from '@/lib/utils';
import { show as showEntry } from '@/routes/themes/entries';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, Lock, Zap } from 'lucide-react';
import { useState } from 'react';

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
    image_path: string | null;
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
    coverImagePath: string | null;
    progress: Progress;
}

type EntryStatus = 'completed' | 'current' | 'upcoming';

function EntryCard({
    entry,
    status,
    themeId,
    index,
}: {
    entry: Entry;
    status: EntryStatus;
    themeId: number;
    index: number;
}) {
    const [imgFailed, setImgFailed] = useState(false);
    const isCurrent = status === 'current';
    const isCompleted = status === 'completed';
    const isUpcoming = status === 'upcoming';
    const showImage = entry.image_path && !imgFailed;

    const scriptureLabel = entry.scripture_references
        .map((ref) => ref.raw_reference)
        .join(', ');

    const cleanBody = entry.body.replace(/<[^>]*>/g, '');
    const bodySnippet =
        cleanBody.length > 160 ? `${cleanBody.slice(0, 160)}...` : cleanBody;

    return (
        <Link
            href={showEntry.url({ theme: themeId, entry: entry.id })}
            prefetch
            className={cn(
                'group relative grid overflow-hidden rounded-2xl transition-all duration-500',
                showImage
                    ? 'grid-cols-[1fr_140px] md:grid-cols-[1fr_180px]'
                    : 'grid-cols-1',
                isCurrent &&
                    'bg-surface-container-highest shadow-ambient-lg ring-1 ring-moss/20',
                isCompleted &&
                    'bg-surface-container-low hover:bg-surface-container',
                isUpcoming &&
                    'bg-surface-container/50 opacity-50 hover:opacity-70',
            )}
            style={{ animationDelay: `${index * 60}ms` }}
        >
            {/* Content */}
            <div className="flex items-start gap-5 p-5 md:gap-6 md:p-6">
                {/* Day number */}
                <div className="flex shrink-0 flex-col items-center pt-0.5">
                    <span
                        className={cn(
                            'font-serif text-2xl font-light tabular-nums md:text-3xl',
                            isCurrent
                                ? 'font-semibold text-moss'
                                : isCompleted
                                  ? 'text-moss/70'
                                  : 'text-on-surface-variant/30',
                        )}
                    >
                        {String(entry.display_order).padStart(2, '0')}
                    </span>
                </div>

                {/* Text content */}
                <div className="min-w-0 flex-1 space-y-1.5">
                    <div className="flex items-center gap-2">
                        <h3
                            className={cn(
                                'truncate font-serif text-lg md:text-xl',
                                isCurrent
                                    ? 'font-bold text-on-surface'
                                    : isCompleted
                                      ? 'font-semibold text-on-surface'
                                      : 'font-medium text-on-surface-variant/50',
                            )}
                        >
                            {entry.title}
                        </h3>
                        {isCompleted && (
                            <CheckCircle2 className="size-4 shrink-0 fill-moss text-moss-foreground" />
                        )}
                        {isCurrent && (
                            <Zap className="size-4 shrink-0 fill-moss text-moss-foreground" />
                        )}
                        {isUpcoming && (
                            <Lock className="size-3.5 shrink-0 text-on-surface-variant/30" />
                        )}
                    </div>

                    {scriptureLabel && (
                        <p className="text-[11px] font-medium tracking-widest text-on-surface-variant/50 uppercase">
                            {scriptureLabel}
                        </p>
                    )}

                    <p
                        className={cn(
                            'line-clamp-2 text-sm leading-relaxed',
                            isCurrent
                                ? 'text-on-surface/80'
                                : isUpcoming
                                  ? 'text-on-surface-variant/40 italic'
                                  : 'text-on-surface-variant/70',
                        )}
                    >
                        {isUpcoming
                            ? 'Continue your journey to unlock this entry.'
                            : bodySnippet}
                    </p>

                    {/* Status + arrow row */}
                    <div className="flex items-center gap-3 pt-1">
                        {isCurrent && (
                            <span className="rounded-full bg-primary px-4 py-1 text-[10px] font-bold tracking-[0.15em] text-primary-foreground uppercase">
                                Continue
                            </span>
                        )}
                        {isCompleted && (
                            <span className="text-[10px] font-semibold tracking-widest text-moss/70 uppercase">
                                Completed
                            </span>
                        )}
                        <ArrowRight
                            className={cn(
                                'size-4 transition-transform duration-300 group-hover:translate-x-1',
                                isCurrent
                                    ? 'text-on-surface-variant/40'
                                    : 'text-on-surface-variant/20',
                            )}
                        />
                    </div>
                </div>
            </div>

            {/* Entry thumbnail */}
            {showImage && (
                <div className="relative overflow-hidden">
                    <img
                        src={storageUrl(entry.image_path!)}
                        alt=""
                        onError={() => setImgFailed(true)}
                        className={cn(
                            'h-full w-full object-cover transition-transform duration-700 group-hover:scale-105',
                            isUpcoming && 'grayscale',
                        )}
                    />
                    <div className="absolute inset-0 bg-gradient-to-r from-surface-container-highest/40 to-transparent" />
                </div>
            )}

            {/* Left accent for current */}
            {isCurrent && (
                <div className="absolute top-0 bottom-0 left-0 w-1 bg-moss" />
            )}
        </Link>
    );
}

export default function ThemesShow({
    theme,
    entries,
    coverImagePath,
    progress,
}: Props) {
    const [coverFailed, setCoverFailed] = useState(false);
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
        ? `Day ${currentEntry.display_order} — ${currentEntry.title}`
        : progress.percentage === 100
          ? 'All entries completed'
          : 'Begin your journey';

    return (
        <DevotionalLayout>
            <Head title={theme.name} />

            {/* Compact Hero */}
            <header className="relative overflow-hidden bg-surface-container">
                {/* Cover image as background */}
                {coverImagePath && !coverFailed && (
                    <div className="absolute inset-0">
                        <img
                            src={storageUrl(coverImagePath)}
                            alt=""
                            onError={() => setCoverFailed(true)}
                            className="h-full w-full object-cover opacity-20"
                        />
                        <div className="absolute inset-0 bg-gradient-to-r from-surface-container via-surface-container/95 to-surface-container/70" />
                    </div>
                )}

                <div className="relative px-6 py-8 md:px-10 md:py-10 lg:px-14">
                    <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                        {/* Left: Theme info */}
                        <div className="max-w-2xl space-y-3">
                            <p className="text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                                {progress.total}-Day Journey
                            </p>
                            <h1 className="font-serif text-4xl leading-[1.1] font-bold tracking-tight text-on-surface md:text-5xl">
                                {theme.name}
                            </h1>
                            {theme.description && (
                                <p className="max-w-lg text-sm leading-relaxed text-on-surface-variant md:text-base">
                                    {theme.description}
                                </p>
                            )}
                        </div>

                        {/* Right: Progress */}
                        <div className="w-full max-w-xs shrink-0 rounded-xl border border-border/50 bg-background/50 p-4 backdrop-blur-sm">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-[10px] font-medium tracking-widest text-on-surface-variant/60 uppercase">
                                    Progress
                                </span>
                                <span className="text-sm font-bold text-moss tabular-nums">
                                    {progress.completed}/{progress.total}
                                </span>
                            </div>
                            <ProgressBar
                                value={progress.completed}
                                max={progress.total}
                            />
                            <p className="mt-2 text-[11px] text-on-surface-variant italic">
                                {currentDayLabel}
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            {/* Entries */}
            <section className="px-6 py-8 md:px-10 md:py-10 lg:px-14">
                <div className="space-y-3">
                    {entries.map((entry, index) => (
                        <EntryCard
                            key={entry.id}
                            entry={entry}
                            status={entryStatuses[index]}
                            themeId={theme.id}
                            index={index}
                        />
                    ))}
                </div>

                {/* Completion state */}
                {progress.percentage === 100 && (
                    <div className="mt-10 flex flex-col items-center rounded-2xl bg-moss/5 p-8 text-center">
                        <CheckCircle2 className="mb-3 size-10 text-moss" />
                        <h2 className="font-serif text-2xl font-medium text-on-surface">
                            Journey Complete
                        </h2>
                        <p className="mt-1 text-sm text-on-surface-variant">
                            All {progress.total} entries finished.
                        </p>
                        <Link
                            href={showEntry.url({
                                theme: theme.id,
                                entry: entries[0].id,
                            })}
                            prefetch
                            className="mt-5 inline-flex items-center gap-2 rounded-full bg-primary px-6 py-2.5 text-xs font-bold tracking-widest text-primary-foreground uppercase"
                        >
                            <ArrowLeft className="size-4" />
                            Review from Beginning
                        </Link>
                    </div>
                )}
            </section>
        </DevotionalLayout>
    );
}
