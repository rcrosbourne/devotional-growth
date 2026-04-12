import { home } from '@/routes';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren, useMemo } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    const year = useMemo(() => new Date().getFullYear(), []);

    return (
        <div className="relative grid min-h-dvh lg:grid-cols-2">
            {/* ── Left Panel: Forest atmosphere ── */}
            <div className="relative hidden overflow-hidden lg:block">
                {/* Layered gradient creating depth — dark teal forest */}
                <div className="absolute inset-0 bg-gradient-to-b from-[#1a3a2a] via-[#1e4535] to-[#0f2920]" />

                {/* Subtle mist/fog overlay for atmosphere */}
                <div className="absolute inset-0 bg-gradient-to-t from-[#0f2920]/80 via-transparent to-[#1a3a2a]/40" />

                {/* Tree silhouette shapes via radial gradients */}
                <div className="absolute inset-0 opacity-30">
                    <div
                        className="absolute bottom-0 left-[10%] h-[70%] w-[15%] bg-gradient-to-t from-[#0a1f16] to-transparent"
                        style={{
                            clipPath: 'polygon(50% 0%, 15% 100%, 85% 100%)',
                        }}
                    />
                    <div
                        className="absolute bottom-0 left-[25%] h-[85%] w-[12%] bg-gradient-to-t from-[#0a1f16] to-transparent"
                        style={{
                            clipPath: 'polygon(50% 0%, 20% 100%, 80% 100%)',
                        }}
                    />
                    <div
                        className="absolute bottom-0 left-[45%] h-[60%] w-[18%] bg-gradient-to-t from-[#0a1f16] to-transparent"
                        style={{
                            clipPath: 'polygon(50% 0%, 10% 100%, 90% 100%)',
                        }}
                    />
                    <div
                        className="absolute right-[20%] bottom-0 h-[75%] w-[14%] bg-gradient-to-t from-[#0a1f16] to-transparent"
                        style={{
                            clipPath: 'polygon(50% 0%, 18% 100%, 82% 100%)',
                        }}
                    />
                    <div
                        className="absolute right-[5%] bottom-0 h-[55%] w-[16%] bg-gradient-to-t from-[#0a1f16] to-transparent"
                        style={{
                            clipPath: 'polygon(50% 0%, 15% 100%, 85% 100%)',
                        }}
                    />
                </div>

                {/* Atmospheric light rays */}
                <div className="absolute top-0 right-0 h-full w-1/2 bg-gradient-to-bl from-[#2a5e4a]/20 to-transparent" />

                {/* Content */}
                <div className="relative flex h-full flex-col justify-between p-10">
                    <Link
                        href={home()}
                        className="font-serif text-xl text-[#a8c4b0]/80 italic transition-colors hover:text-[#a8c4b0]"
                    >
                        Devotional
                    </Link>

                    <blockquote className="max-w-xs">
                        <p className="font-serif text-xl leading-relaxed text-[#a8c4b0]/90 italic">
                            Where focus meets serenity.
                        </p>
                    </blockquote>
                </div>
            </div>

            {/* ── Right Panel: Form content ── */}
            <div className="flex min-h-dvh flex-col bg-background">
                <div className="flex flex-1 flex-col items-center justify-center px-6 py-12 sm:px-12">
                    <div className="w-full max-w-sm">
                        {/* Mobile logo */}
                        <div className="mb-10 lg:hidden">
                            <Link
                                href={home()}
                                className="font-serif text-xl text-foreground italic"
                            >
                                Devotional
                            </Link>
                        </div>

                        <div className="mb-8 space-y-3">
                            <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                Welcome back
                            </p>
                            <h1 className="font-serif text-4xl leading-tight text-foreground italic sm:text-[2.75rem]">
                                {title}
                            </h1>
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                {description}
                            </p>
                        </div>

                        {children}
                    </div>
                </div>

                {/* Footer */}
                <footer className="border-t border-border/50 px-6 py-5 sm:px-12">
                    <div className="mx-auto flex max-w-sm flex-col items-center justify-between gap-3 text-xs text-muted-foreground sm:flex-row">
                        <span>&copy; {year} The Curator</span>
                        {/* TODO: Update hrefs when privacy/terms pages exist */}
                        <div className="flex gap-6">
                            <a
                                href="/privacy"
                                className="transition-colors hover:text-foreground"
                            >
                                Privacy Policy
                            </a>
                            <a
                                href="/terms"
                                className="transition-colors hover:text-foreground"
                            >
                                Terms
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    );
}
