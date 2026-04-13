import { Button } from '@/components/ui/button';
import DevotionalLayout from '@/layouts/devotional-layout';
import { importMethod } from '@/routes/admin/sabbath-school';
import { show } from '@/routes/sabbath-school';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { BookOpen, ChevronRight, Download } from 'lucide-react';

interface Quarterly {
    id: number;
    title: string;
    quarter_code: string;
    year: number;
    quarter_number: number;
    is_active: boolean;
    description: string | null;
    lessons_count: number;
}

interface QuarterProgress {
    completed_days: number;
    total_days: number;
}

interface Props {
    activeQuarterly: Quarterly | null;
    pastQuarterlies: Quarterly[];
    activeProgress: QuarterProgress | null;
}

function quarterLabel(q: Quarterly) {
    return `Q${q.quarter_number} ${q.year}`;
}

export default function SabbathSchoolIndex({
    activeQuarterly,
    pastQuarterlies,
    activeProgress,
}: Props) {
    const { auth } = usePage<SharedData>().props;
    const isAdmin = auth.user?.is_admin ?? false;

    function handleAdminImport() {
        router.post(importMethod.url());
    }

    return (
        <DevotionalLayout>
            <Head title="Sabbath School" />

            <div className="mx-auto max-w-2xl px-4 py-6 md:px-0">
                {/* Header */}
                <div>
                    <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Weekly Study
                    </p>
                    <h1 className="mt-1 font-serif text-3xl font-medium tracking-tight text-on-surface md:text-4xl">
                        Sabbath School
                    </h1>
                </div>

                {/* Active Quarter */}
                {activeQuarterly ? (
                    <Link
                        href={show.url(activeQuarterly.id)}
                        className="mt-6 block"
                    >
                        <div className="group overflow-hidden rounded-xl border border-moss/30 bg-gradient-to-br from-moss/5 to-transparent p-6 transition-all hover:border-moss/50 hover:shadow-sm">
                            <p className="text-xs font-medium tracking-[0.12em] text-moss uppercase">
                                Current Quarter &middot;{' '}
                                {quarterLabel(activeQuarterly)}
                            </p>
                            <h2 className="mt-2 font-serif text-2xl font-medium tracking-tight text-on-surface transition-colors group-hover:text-moss md:text-3xl">
                                {activeQuarterly.title}
                            </h2>
                            {activeQuarterly.description && (
                                <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-on-surface-variant">
                                    {activeQuarterly.description}
                                </p>
                            )}
                            {activeProgress &&
                                activeProgress.total_days > 0 && (
                                    <div className="mt-4">
                                        <div className="flex items-center justify-between text-xs text-on-surface-variant">
                                            <span>
                                                {activeProgress.completed_days}/
                                                {activeProgress.total_days} days
                                                completed
                                            </span>
                                            <span>
                                                {Math.round(
                                                    (activeProgress.completed_days /
                                                        activeProgress.total_days) *
                                                        100,
                                                )}
                                                %
                                            </span>
                                        </div>
                                        <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-moss/10">
                                            <div
                                                className="h-full rounded-full bg-moss transition-all"
                                                style={{
                                                    width: `${(activeProgress.completed_days / activeProgress.total_days) * 100}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                )}
                            <div className="mt-4 flex items-center justify-between">
                                <span className="text-sm text-on-surface-variant">
                                    {activeQuarterly.lessons_count} lessons
                                </span>
                                <span className="inline-flex items-center gap-1 text-sm font-medium text-moss transition-transform group-hover:translate-x-0.5">
                                    Start studying
                                    <ChevronRight className="size-4" />
                                </span>
                            </div>
                        </div>
                    </Link>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-border bg-surface-container-low p-10 text-center">
                        <BookOpen className="mx-auto size-10 text-on-surface-variant/40" />
                        <p className="mt-4 font-serif text-xl text-on-surface-variant">
                            No Sabbath School lessons available yet
                        </p>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Check back soon for the latest quarterly.
                        </p>
                        {isAdmin && (
                            <Button
                                onClick={handleAdminImport}
                                className="mt-6 bg-moss text-moss-foreground hover:bg-moss/90"
                            >
                                <Download className="size-4" />
                                Import Current Quarter
                            </Button>
                        )}
                    </div>
                )}

                {/* Past Quarters */}
                {pastQuarterlies.length > 0 && (
                    <div className="mt-10">
                        <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Previous Quarters
                        </h2>
                        <div className="mt-4 space-y-3">
                            {pastQuarterlies.map((quarterly) => (
                                <Link
                                    key={quarterly.id}
                                    href={show.url(quarterly.id)}
                                    className="group block"
                                >
                                    <div className="hover:border-border-hover flex items-center justify-between rounded-lg border border-border bg-surface-container-low p-4 transition-all hover:shadow-sm">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-xs font-medium text-on-surface-variant">
                                                {quarterLabel(quarterly)}
                                            </p>
                                            <h3 className="mt-0.5 font-serif text-base font-medium text-on-surface transition-colors group-hover:text-moss">
                                                {quarterly.title}
                                            </h3>
                                        </div>
                                        <div className="ml-4 flex items-center gap-2 text-sm text-on-surface-variant">
                                            <span>
                                                {quarterly.lessons_count}{' '}
                                                lessons
                                            </span>
                                            <ChevronRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </DevotionalLayout>
    );
}
