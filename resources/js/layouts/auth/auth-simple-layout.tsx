import { home } from '@/routes';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren, useMemo } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    const year = useMemo(() => new Date().getFullYear(), []);

    return (
        <div className="flex min-h-dvh flex-col bg-background">
            {/* Header */}
            <header className="px-6 py-6 sm:px-10">
                <Link
                    href={home()}
                    className="font-serif text-xl text-foreground italic transition-opacity hover:opacity-70"
                >
                    Devotional
                </Link>
            </header>

            {/* Main content */}
            <main className="flex flex-1 items-center justify-center px-6 pb-12">
                <div className="w-full max-w-md">
                    <div className="rounded-xl border border-border/40 bg-card/50 px-6 py-10 shadow-ambient sm:px-10 sm:py-12">
                        {/* Heading area */}
                        <div className="mb-8 space-y-3 text-center">
                            {title && (
                                <h1 className="font-serif text-3xl leading-tight text-foreground sm:text-4xl">
                                    {title}
                                </h1>
                            )}
                            {description && (
                                <p className="mx-auto max-w-xs text-sm leading-relaxed text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>

                        {children}
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="px-6 py-5 sm:px-10">
                <div className="flex flex-col items-center justify-between gap-3 text-xs text-muted-foreground sm:flex-row">
                    <span>
                        &copy; {year} Devotional. Curated, Protected &amp;
                        Verified.
                    </span>
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
                            Terms of Service
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    );
}
