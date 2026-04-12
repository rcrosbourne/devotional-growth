import { login, register } from '@/routes';
import { index as themesIndex } from '@/routes/themes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    Heart,
    ImageIcon,
    Layers,
    Sparkles,
    TrendingUp,
    Users,
} from 'lucide-react';

function LeafAccent({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 120 120"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
        >
            <path
                d="M60 10C60 10 20 40 20 70C20 90 38 110 60 110C82 110 100 90 100 70C100 40 60 10 60 10Z"
                stroke="currentColor"
                strokeWidth="1"
                fill="none"
                opacity="0.15"
            />
            <path
                d="M60 25C60 25 35 48 35 70C35 85 46 98 60 98C74 98 85 85 85 70C85 48 60 25 60 25Z"
                stroke="currentColor"
                strokeWidth="0.75"
                fill="none"
                opacity="0.1"
            />
            <line
                x1="60"
                y1="30"
                x2="60"
                y2="100"
                stroke="currentColor"
                strokeWidth="0.5"
                opacity="0.12"
            />
            <path
                d="M60 50C50 45 40 55 40 65"
                stroke="currentColor"
                strokeWidth="0.5"
                fill="none"
                opacity="0.1"
            />
            <path
                d="M60 60C70 55 80 65 80 75"
                stroke="currentColor"
                strokeWidth="0.5"
                fill="none"
                opacity="0.1"
            />
        </svg>
    );
}

function EditorialDivider() {
    return (
        <div className="flex items-center justify-center gap-4 py-2">
            <div className="h-px w-12 bg-border" />
            <Sparkles className="size-3 text-moss/40" />
            <div className="h-px w-12 bg-border" />
        </div>
    );
}

const features = [
    {
        icon: Layers,
        title: 'Thematic Devotions',
        description:
            'Explore curated devotional themes that guide you through scripture with intention and depth.',
    },
    {
        icon: TrendingUp,
        title: 'Track Your Journey',
        description:
            'Follow your progress through each theme, building a consistent rhythm of daily reflection.',
    },
    {
        icon: Users,
        title: 'Partner Together',
        description:
            'Share your devotional journey with a partner for encouragement and accountability.',
    },
    {
        icon: ImageIcon,
        title: 'Visual Reflections',
        description:
            'Generate beautiful AI-powered images that bring your devotional moments to life.',
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;
    const isAuthenticated = !!auth.user;

    return (
        <>
            <Head title="Welcome" />

            <div className="relative min-h-screen bg-parchment text-on-surface dark:bg-background">
                {/* Grain overlay */}
                <div
                    className="pointer-events-none fixed inset-0 z-50 opacity-[0.03] dark:opacity-[0.05]"
                    style={{
                        backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E")`,
                    }}
                />

                {/* Navigation */}
                <nav className="relative z-10 mx-auto flex max-w-6xl items-center justify-between px-6 py-6 lg:px-8 lg:py-8">
                    <Link href="/" className="flex items-center gap-2.5">
                        <BookOpen className="size-5 text-moss" />
                        <span className="font-serif text-lg font-medium tracking-tight">
                            Devotional Growth
                        </span>
                    </Link>

                    <div className="flex items-center gap-3">
                        {isAuthenticated ? (
                            <Link
                                href={themesIndex.url()}
                                className="inline-flex items-center gap-2 bg-moss px-5 py-2.5 text-xs font-semibold tracking-widest text-moss-foreground uppercase transition-opacity hover:opacity-90"
                            >
                                Continue
                                <ArrowRight className="size-3.5" />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login.url()}
                                    className="px-4 py-2 text-xs font-medium tracking-wider text-on-surface-variant uppercase transition-colors hover:text-on-surface"
                                >
                                    Sign In
                                </Link>
                                <Link
                                    href={register.url()}
                                    className="inline-flex items-center gap-2 bg-moss px-5 py-2.5 text-xs font-semibold tracking-widest text-moss-foreground uppercase transition-opacity hover:opacity-90"
                                >
                                    Get Started
                                </Link>
                            </>
                        )}
                    </div>
                </nav>

                {/* Hero */}
                <section className="relative z-10 mx-auto max-w-6xl px-6 pt-16 pb-24 lg:px-8 lg:pt-28 lg:pb-36">
                    <LeafAccent className="absolute top-8 right-8 size-48 text-moss opacity-60 lg:top-0 lg:right-16 lg:size-72" />

                    <div className="relative max-w-3xl">
                        <span className="mb-6 inline-block text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                            Daily Devotional Study
                        </span>

                        <h1 className="mb-8 font-serif text-5xl leading-[1.1] font-medium tracking-tight md:text-6xl lg:text-7xl">
                            Nurture your faith
                            <br />
                            <span className="text-on-surface-variant italic">
                                one day at a time
                            </span>
                        </h1>

                        <p className="mb-10 max-w-lg text-base leading-relaxed text-on-surface-variant lg:text-lg">
                            A thoughtfully crafted devotional experience that
                            guides you through thematic scripture studies,
                            helping you grow deeper in understanding and closer
                            in faith.
                        </p>

                        <div className="flex flex-wrap items-center gap-4">
                            {isAuthenticated ? (
                                <Link
                                    href={themesIndex.url()}
                                    className="group inline-flex items-center gap-3 bg-primary px-8 py-4 text-xs font-semibold tracking-widest text-primary-foreground uppercase transition-opacity hover:opacity-90"
                                >
                                    Continue to Themes
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={register.url()}
                                        className="group inline-flex items-center gap-3 bg-primary px-8 py-4 text-xs font-semibold tracking-widest text-primary-foreground uppercase transition-opacity hover:opacity-90"
                                    >
                                        Begin Your Journey
                                        <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                                    </Link>
                                    <Link
                                        href={login.url()}
                                        className="inline-flex items-center gap-2 border border-border px-8 py-4 text-xs font-medium tracking-widest text-on-surface uppercase transition-colors hover:bg-bone"
                                    >
                                        Sign In
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </section>

                {/* Scripture quote divider */}
                <section className="relative z-10 border-y border-border/50 bg-bone/50 py-16 dark:bg-surface-variant/30">
                    <div className="mx-auto max-w-3xl px-6 text-center lg:px-8">
                        <EditorialDivider />
                        <blockquote className="mt-6 mb-4 font-serif text-xl leading-relaxed font-light text-on-surface italic md:text-2xl lg:text-3xl">
                            &ldquo;Your word is a lamp to my feet and a light to
                            my path.&rdquo;
                        </blockquote>
                        <cite className="text-[11px] font-medium tracking-[0.25em] text-on-surface-variant/60 uppercase not-italic">
                            Psalm 119:105
                        </cite>
                        <div className="mt-6">
                            <EditorialDivider />
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="relative z-10 mx-auto max-w-6xl px-6 py-24 lg:px-8 lg:py-32">
                    <div className="mb-16 max-w-xl">
                        <span className="mb-4 inline-block text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                            How It Works
                        </span>
                        <h2 className="font-serif text-3xl font-medium tracking-tight md:text-4xl lg:text-5xl">
                            A deeper way
                            <br />
                            to study scripture
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:gap-8">
                        {features.map((feature, i) => (
                            <div
                                key={feature.title}
                                className="group rounded-2xl border border-border/50 bg-surface-container-low p-8 transition-all duration-500 hover:border-border hover:shadow-ambient lg:p-10"
                            >
                                <div className="mb-6 flex items-center gap-4">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-moss/10 text-moss dark:bg-moss/20">
                                        <feature.icon className="size-5" />
                                    </div>
                                    <span className="text-[10px] font-semibold tracking-widest text-on-surface-variant/40 uppercase">
                                        0{i + 1}
                                    </span>
                                </div>
                                <h3 className="mb-3 font-serif text-xl font-medium tracking-tight md:text-2xl">
                                    {feature.title}
                                </h3>
                                <p className="text-sm leading-relaxed text-on-surface-variant">
                                    {feature.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Daily rhythm section */}
                <section className="relative z-10 border-t border-border/50 bg-bone/50 py-24 lg:py-32 dark:bg-surface-variant/30">
                    <div className="mx-auto max-w-6xl px-6 lg:px-8">
                        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
                            <div>
                                <span className="mb-4 inline-block text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                                    Your Daily Rhythm
                                </span>
                                <h2 className="mb-6 font-serif text-3xl font-medium tracking-tight md:text-4xl">
                                    Read. Reflect. Grow.
                                </h2>
                                <p className="mb-8 text-base leading-relaxed text-on-surface-variant">
                                    Each devotional entry guides you through
                                    scripture with thoughtful commentary, space
                                    for personal observations, and tools to
                                    deepen your understanding.
                                </p>
                                <ul className="space-y-4">
                                    {[
                                        'Read curated scripture passages in context',
                                        'Record personal observations and insights',
                                        'Track your progress through each theme',
                                        'Share your journey with a devotional partner',
                                    ].map((item) => (
                                        <li
                                            key={item}
                                            className="flex items-start gap-3 text-sm text-on-surface-variant"
                                        >
                                            <Heart className="mt-0.5 size-4 shrink-0 text-moss" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {/* Decorative card stack */}
                            <div className="relative flex justify-center">
                                <div className="relative w-full max-w-sm">
                                    {/* Background cards */}
                                    <div className="absolute -top-3 left-3 h-full w-full rounded-2xl border border-border/30 bg-surface-container-high/50" />
                                    <div className="absolute -top-1.5 left-1.5 h-full w-full rounded-2xl border border-border/40 bg-surface-container/70" />

                                    {/* Main card */}
                                    <div className="relative rounded-2xl border border-border bg-surface-container-lowest p-8 shadow-ambient lg:p-10">
                                        <div className="mb-6 flex items-center justify-between">
                                            <span className="text-[10px] font-semibold tracking-widest text-moss uppercase">
                                                Day 3 of 14
                                            </span>
                                            <span className="rounded-full bg-moss/10 px-3 py-1 text-[10px] font-bold tracking-wider text-moss uppercase">
                                                In Progress
                                            </span>
                                        </div>
                                        <h4 className="mb-3 font-serif text-xl font-medium">
                                            Walking in Faithfulness
                                        </h4>
                                        <p className="mb-6 text-sm leading-relaxed text-on-surface-variant">
                                            Discover how daily faithfulness in
                                            small things builds a foundation for
                                            extraordinary growth...
                                        </p>
                                        <div className="mb-3 flex items-center justify-between text-[10px] text-on-surface-variant/60">
                                            <span>Progress</span>
                                            <span className="font-medium">
                                                21%
                                            </span>
                                        </div>
                                        <div className="h-1 overflow-hidden rounded-full bg-surface-container-highest">
                                            <div
                                                className="h-full rounded-full bg-moss"
                                                style={{ width: '21%' }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="relative z-10 mx-auto max-w-6xl px-6 py-24 lg:px-8 lg:py-32">
                    <div className="text-center">
                        <EditorialDivider />
                        <h2 className="mt-8 font-serif text-3xl font-medium tracking-tight md:text-4xl lg:text-5xl">
                            Begin your devotional
                            <br />
                            <span className="text-on-surface-variant italic">
                                journey today
                            </span>
                        </h2>
                        <p className="mx-auto mt-6 max-w-md text-base leading-relaxed text-on-surface-variant">
                            Join a growing community of believers deepening
                            their understanding of scripture through guided
                            daily devotions.
                        </p>
                        <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                            {isAuthenticated ? (
                                <Link
                                    href={themesIndex.url()}
                                    className="group inline-flex items-center gap-3 bg-moss px-8 py-4 text-xs font-semibold tracking-widest text-moss-foreground uppercase transition-opacity hover:opacity-90"
                                >
                                    Explore Themes
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={register.url()}
                                        className="group inline-flex items-center gap-3 bg-moss px-8 py-4 text-xs font-semibold tracking-widest text-moss-foreground uppercase transition-opacity hover:opacity-90"
                                    >
                                        Get Started Free
                                        <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                                    </Link>
                                    <Link
                                        href={login.url()}
                                        className="inline-flex items-center gap-2 border border-border px-8 py-4 text-xs font-medium tracking-widest text-on-surface uppercase transition-colors hover:bg-bone"
                                    >
                                        Sign In
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="relative z-10 border-t border-border/50 py-10">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 md:flex-row lg:px-8">
                        <div className="flex items-center gap-2.5">
                            <BookOpen className="size-4 text-moss" />
                            <span className="font-serif text-sm font-medium">
                                Devotional Growth
                            </span>
                        </div>
                        <p className="text-[11px] tracking-wider text-on-surface-variant/50">
                            Grow deeper in faith, one day at a time.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
