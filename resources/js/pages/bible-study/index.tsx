import { ProgressBar } from '@/components/devotional/progress-bar';
import DevotionalLayout from '@/layouts/devotional-layout';
import { cn } from '@/lib/utils';
import { show as showReadingPlan } from '@/routes/bible-study/reading-plan';
import { search as wordStudySearch } from '@/routes/bible-study/word-study';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    ChevronRight,
    Languages,
    Play,
    Search,
    Sparkles,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface ReadingPlan {
    id: number;
    name: string;
    description: string | null;
    total_days: number;
    is_default: boolean;
    days_count: number;
}

interface PlanProgress {
    completed_days: number;
    total_days: number;
    percentage: number;
    current_day_number: number;
    started_at: string | null;
}

interface Props {
    plans: ReadingPlan[];
    activePlanIds: number[];
    progressByPlan: Record<number, PlanProgress>;
}

const DAILY_VERSES = [
    {
        text: 'The Lord is my shepherd; I shall not want. He maketh me to lie down in green pastures: he leadeth me beside the still waters.',
        reference: 'Psalm 23:1\u20132',
    },
    {
        text: 'Trust in the Lord with all thine heart; and lean not unto thine own understanding.',
        reference: 'Proverbs 3:5',
    },
    {
        text: 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.',
        reference: 'John 3:16',
    },
    {
        text: 'I can do all things through Christ which strengtheneth me.',
        reference: 'Philippians 4:13',
    },
    {
        text: 'And we know that all things work together for good to them that love God, to them who are the called according to his purpose.',
        reference: 'Romans 8:28',
    },
];

function getDailyVerse() {
    const dayOfYear = Math.floor(
        (Date.now() - new Date(new Date().getFullYear(), 0, 0).getTime()) /
            86400000,
    );
    return DAILY_VERSES[dayOfYear % DAILY_VERSES.length];
}

function WordStudySearchBox() {
    const [query, setQuery] = useState('');

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        if (!query.trim()) {
            return;
        }
        router.get(wordStudySearch.url({ query: { q: query.trim() } }));
    }

    return (
        <form onSubmit={handleSearch} className="flex gap-2">
            <input
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Enter word or Strong's #"
                className="flex-1 rounded-xl border-none bg-background px-4 py-3 text-sm text-on-surface placeholder:text-on-surface-variant/40 focus:ring-1 focus:ring-primary focus:outline-none"
            />
            <button
                type="submit"
                className="flex items-center justify-center rounded-xl bg-primary px-4 text-primary-foreground transition-opacity hover:opacity-90"
            >
                <Search className="size-5" />
            </button>
        </form>
    );
}

function ReadingPlanCard({
    plan,
    isActive,
    progress,
    featured = false,
}: {
    plan: ReadingPlan;
    isActive: boolean;
    progress?: PlanProgress;
    featured?: boolean;
}) {
    return (
        <Link
            href={showReadingPlan.url(plan.id)}
            prefetch
            className={cn(
                'group flex cursor-pointer flex-col justify-between overflow-hidden rounded-3xl transition-all duration-500',
                featured
                    ? 'relative min-h-[320px] bg-primary p-8 text-primary-foreground md:row-span-2 md:min-h-[400px]'
                    : 'border border-border/10 bg-surface-container-low p-8 hover:border-border/30 hover:shadow-ambient',
            )}
        >
            {featured && (
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" />
            )}

            <div className={cn('relative z-10', featured && 'mt-auto')}>
                {isActive && (
                    <span
                        className={cn(
                            'mb-3 inline-block rounded-full px-3 py-1 text-[10px] font-bold tracking-widest uppercase',
                            featured
                                ? 'bg-moss text-moss-foreground'
                                : 'bg-moss/10 text-moss',
                        )}
                    >
                        In Progress
                    </span>
                )}
                {!isActive && !featured && (
                    <Sparkles className="mb-4 size-5 text-moss" />
                )}
                <h4
                    className={cn(
                        'font-serif',
                        featured
                            ? 'text-2xl md:text-3xl'
                            : 'mb-2 text-lg md:text-xl',
                    )}
                >
                    {plan.name}
                </h4>
                {plan.description && (
                    <p
                        className={cn(
                            'mt-1 line-clamp-2 text-sm leading-relaxed',
                            featured
                                ? 'text-primary-foreground/70'
                                : 'text-on-surface-variant',
                        )}
                    >
                        {plan.description}
                    </p>
                )}

                {isActive && progress && (
                    <div className="mt-4">
                        <div
                            className={cn(
                                'h-1 overflow-hidden rounded-full',
                                featured
                                    ? 'bg-primary-foreground/20'
                                    : 'bg-surface-container-highest',
                            )}
                        >
                            <div
                                className={cn(
                                    'h-full rounded-full transition-all duration-700',
                                    featured ? 'bg-moss' : 'bg-moss',
                                )}
                                style={{
                                    width: `${progress.percentage}%`,
                                }}
                            />
                        </div>
                        <p
                            className={cn(
                                'mt-2 text-[11px]',
                                featured
                                    ? 'text-primary-foreground/50'
                                    : 'text-on-surface-variant/60',
                            )}
                        >
                            Day {progress.current_day_number} of{' '}
                            {progress.total_days} &middot; {progress.percentage}
                            % complete
                        </p>
                    </div>
                )}
            </div>

            {!isActive && (
                <div
                    className={cn(
                        'relative z-10 flex items-center justify-between pt-4 text-[10px] font-bold tracking-widest uppercase',
                        featured
                            ? 'text-primary-foreground/50'
                            : 'text-on-surface-variant/50',
                    )}
                >
                    <span>{plan.total_days} Days</span>
                    <ArrowRight className="size-4 transition-transform duration-300 group-hover:translate-x-1" />
                </div>
            )}
        </Link>
    );
}

export default function BibleStudyIndex({
    plans,
    activePlanIds,
    progressByPlan,
}: Props) {
    const verse = getDailyVerse();
    const activePlans = plans.filter((p) => activePlanIds.includes(p.id));
    const inactivePlans = plans.filter((p) => !activePlanIds.includes(p.id));

    return (
        <DevotionalLayout>
            <Head title="Bible Study" />

            <div className="px-6 py-8 md:px-12 lg:px-16">
                <div className="mx-auto max-w-7xl space-y-16">
                    {/* ── Verse of the Day ── */}
                    <section className="grid grid-cols-1 items-center gap-8 lg:grid-cols-12">
                        <div className="lg:col-span-8">
                            <p className="mb-4 text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                                Verse of the Day
                            </p>
                            <blockquote className="space-y-6">
                                <p className="font-serif text-3xl leading-snug tracking-tight text-on-surface md:text-4xl lg:text-5xl">
                                    &ldquo;{verse.text}&rdquo;
                                </p>
                                <cite className="block text-sm font-medium tracking-widest text-on-surface-variant uppercase not-italic">
                                    &mdash; {verse.reference}
                                </cite>
                            </blockquote>
                            <div className="mt-8 flex gap-3">
                                <button
                                    type="button"
                                    className="inline-flex items-center gap-2 rounded-lg bg-moss px-6 py-3 text-xs font-bold tracking-widest text-moss-foreground uppercase transition-opacity hover:opacity-90"
                                >
                                    <BookOpen className="size-4" />
                                    Read Chapter
                                </button>
                            </div>
                        </div>
                        <div className="hidden lg:col-span-4 lg:block">
                            <div className="aspect-[4/5] overflow-hidden rounded-3xl bg-gradient-to-br from-surface-container-high to-surface-container-highest">
                                <div className="flex h-full w-full items-center justify-center">
                                    <BookOpen className="size-20 text-on-surface-variant/10" />
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* ── Deep Word Study ── */}
                    <section className="overflow-hidden rounded-[2rem] bg-surface-container-low p-8 lg:p-12">
                        <div className="grid grid-cols-1 items-center gap-12 md:grid-cols-2">
                            <div>
                                <p className="mb-2 text-[10px] font-semibold tracking-[0.2em] text-on-surface-variant uppercase">
                                    Lexicon Tool
                                </p>
                                <h3 className="mb-4 font-serif text-3xl tracking-tight">
                                    Deep Word Study
                                </h3>
                                <p className="mb-6 max-w-sm leading-relaxed text-on-surface-variant">
                                    Unpack the original Hebrew and Greek
                                    meanings. Discover the cultural context
                                    behind every verse.
                                </p>
                                <WordStudySearchBox />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <Link
                                    href={wordStudySearch.url({
                                        query: { q: 'logos' },
                                    })}
                                    prefetch
                                    className="group rounded-2xl bg-surface-container-highest p-6 transition-colors hover:bg-surface-container-high"
                                >
                                    <span className="font-serif text-2xl text-moss italic">
                                        Logos
                                    </span>
                                    <p className="mt-2 text-xs tracking-tight text-on-surface-variant uppercase">
                                        Greek: Word, Reason
                                    </p>
                                    <Languages className="mt-3 size-4 text-on-surface-variant/30 transition-colors group-hover:text-moss" />
                                </Link>
                                <Link
                                    href={wordStudySearch.url({
                                        query: { q: 'hesed' },
                                    })}
                                    prefetch
                                    className="group rounded-2xl bg-surface-container-highest p-6 transition-colors hover:bg-surface-container-high"
                                >
                                    <span className="font-serif text-2xl text-moss italic">
                                        Hesed
                                    </span>
                                    <p className="mt-2 text-xs tracking-tight text-on-surface-variant uppercase">
                                        Hebrew: Lovingkindness
                                    </p>
                                    <Languages className="mt-3 size-4 text-on-surface-variant/30 transition-colors group-hover:text-moss" />
                                </Link>
                            </div>
                        </div>
                    </section>

                    {/* ── Reading Journeys ── */}
                    <section>
                        <div className="mb-8 flex items-end justify-between">
                            <div>
                                <p className="mb-2 text-[10px] font-semibold tracking-[0.2em] text-on-surface-variant uppercase">
                                    {activePlans.length > 0
                                        ? 'Your Journeys'
                                        : 'Curated for You'}
                                </p>
                                <h3 className="font-serif text-3xl tracking-tight">
                                    Reading Journeys
                                </h3>
                            </div>
                        </div>

                        {plans.length === 0 ? (
                            <div className="flex flex-col items-center justify-center rounded-2xl bg-surface-container p-12 text-center">
                                <BookOpen className="mb-4 size-10 text-on-surface-variant/30" />
                                <p className="font-serif text-2xl text-on-surface-variant">
                                    No reading plans available yet
                                </p>
                                <p className="mt-2 max-w-sm text-sm text-on-surface-variant/60">
                                    Reading plans are being prepared. Check back
                                    soon for structured Bible reading journeys.
                                </p>
                            </div>
                        ) : (
                            <>
                                {/* Active Plans */}
                                {activePlans.length > 0 && (
                                    <div className="mb-8 space-y-4">
                                        {activePlans.map((plan) => (
                                            <Link
                                                key={plan.id}
                                                href={showReadingPlan.url(
                                                    plan.id,
                                                )}
                                                prefetch
                                                className="group flex items-center gap-6 rounded-3xl bg-surface-container-highest p-6 transition-all duration-300 hover:shadow-ambient-lg md:p-8"
                                            >
                                                <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                                                    <Play className="size-6" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-[10px] font-bold tracking-widest text-moss uppercase">
                                                        Continue Reading
                                                    </p>
                                                    <h4 className="font-serif text-xl md:text-2xl">
                                                        {plan.name}
                                                    </h4>
                                                    {progressByPlan[
                                                        plan.id
                                                    ] && (
                                                        <div className="mt-3 max-w-md">
                                                            <ProgressBar
                                                                value={
                                                                    progressByPlan[
                                                                        plan.id
                                                                    ]
                                                                        .completed_days
                                                                }
                                                                max={
                                                                    progressByPlan[
                                                                        plan.id
                                                                    ].total_days
                                                                }
                                                                label={`Day ${progressByPlan[plan.id].current_day_number} of ${progressByPlan[plan.id].total_days}`}
                                                                showPercentage
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                                <ChevronRight className="size-5 shrink-0 text-on-surface-variant/30 transition-transform duration-300 group-hover:translate-x-1 group-hover:text-on-surface-variant" />
                                            </Link>
                                        ))}
                                    </div>
                                )}

                                {/* All / Inactive Plans Grid */}
                                {inactivePlans.length > 0 && (
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                        {inactivePlans.map((plan, i) => (
                                            <ReadingPlanCard
                                                key={plan.id}
                                                plan={plan}
                                                isActive={false}
                                                featured={
                                                    i === 0 &&
                                                    inactivePlans.length >= 3
                                                }
                                            />
                                        ))}
                                    </div>
                                )}
                            </>
                        )}
                    </section>
                </div>
            </div>
        </DevotionalLayout>
    );
}
