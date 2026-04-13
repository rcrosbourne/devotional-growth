import { Button } from '@/components/ui/button';
import DevotionalLayout from '@/layouts/devotional-layout';
import {
    activate,
    importMethod,
    index,
    sync,
} from '@/routes/admin/sabbath-school';
import { Head, router, useForm } from '@inertiajs/react';
import {
    BookOpen,
    CheckCircle,
    Download,
    Loader2,
    RefreshCw,
    Star,
} from 'lucide-react';

interface Quarterly {
    id: number;
    title: string;
    quarter_code: string;
    year: number;
    quarter_number: number;
    is_active: boolean;
    description: string | null;
    source_url: string;
    last_synced_at: string | null;
    lessons_count: number;
    lessons_with_images_count: number;
}

interface Props {
    quarterlies: Quarterly[];
}

function formatDate(dateString: string | null) {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function quarterLabel(q: Quarterly) {
    return `Q${q.quarter_number} ${q.year}`;
}

export default function SabbathSchoolIndex({ quarterlies }: Props) {
    const importForm = useForm<{ quarter_code: string }>({
        quarter_code: '',
    });

    function handleImport(e: React.FormEvent) {
        e.preventDefault();
        importForm.post(importMethod.url());
    }

    function handleSync(quarterly: Quarterly) {
        router.post(sync.url(quarterly.id));
    }

    function handleActivate(quarterly: Quarterly) {
        router.put(activate.url(quarterly.id));
    }

    return (
        <DevotionalLayout>
            <Head title="Manage Sabbath School" />

            <div className="px-6 py-6 md:px-8">
                {/* Header */}
                <div>
                    <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Curator Dashboard
                    </p>
                    <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                        Sabbath School
                    </h1>
                    <p className="mt-2 max-w-xl text-sm leading-relaxed text-on-surface-variant">
                        Import and manage quarterly lessons from ssnet.org.
                    </p>
                </div>

                {/* Import Form */}
                <div className="mt-8 rounded-lg border border-border bg-surface-container-low p-6">
                    <h2 className="font-serif text-lg font-medium text-on-surface">
                        Import Quarter
                    </h2>
                    <p className="mt-1 text-sm text-on-surface-variant">
                        Leave the code empty to import the current quarter, or
                        enter a specific code (e.g., "26b" for Q2 2026).
                    </p>
                    <form
                        onSubmit={handleImport}
                        className="mt-4 flex items-end gap-3"
                    >
                        <div className="max-w-48 flex-1">
                            <label
                                htmlFor="quarter_code"
                                className="mb-1 block text-sm font-medium text-on-surface"
                            >
                                Quarter Code
                            </label>
                            <input
                                id="quarter_code"
                                type="text"
                                value={importForm.data.quarter_code}
                                onChange={(e) =>
                                    importForm.setData(
                                        'quarter_code',
                                        e.target.value,
                                    )
                                }
                                placeholder="e.g., 26b"
                                maxLength={4}
                                className="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant/50 focus:border-moss focus:ring-1 focus:ring-moss focus:outline-none"
                            />
                            {importForm.errors.quarter_code && (
                                <p className="mt-1 text-sm text-destructive">
                                    {importForm.errors.quarter_code}
                                </p>
                            )}
                        </div>
                        <Button
                            type="submit"
                            disabled={importForm.processing}
                            className="bg-moss text-moss-foreground hover:bg-moss/90"
                        >
                            {importForm.processing ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Download className="size-4" />
                            )}
                            {importForm.processing
                                ? 'Importing...'
                                : 'Import Quarter'}
                        </Button>
                    </form>
                </div>

                {/* Stats */}
                {quarterlies.length > 0 && (
                    <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                            <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                Total Quarters
                            </p>
                            <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                                {quarterlies.length}
                            </p>
                        </div>
                        <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                            <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                Total Lessons
                            </p>
                            <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                                {quarterlies.reduce(
                                    (sum, q) => sum + q.lessons_count,
                                    0,
                                )}
                            </p>
                        </div>
                        <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                            <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                Active Quarter
                            </p>
                            <p className="mt-1 font-serif text-2xl font-semibold text-moss">
                                {quarterlies.find((q) => q.is_active)
                                    ?.quarter_code ?? 'None'}
                            </p>
                        </div>
                    </div>
                )}

                {/* Quarters List */}
                {quarterlies.length === 0 ? (
                    <div className="mt-12 rounded-lg border border-dashed border-border bg-surface-container-low p-12 text-center">
                        <BookOpen className="mx-auto size-10 text-on-surface-variant/40" />
                        <p className="mt-4 font-serif text-xl text-on-surface-variant">
                            No quarters imported yet
                        </p>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Use the import form above to get started.
                        </p>
                    </div>
                ) : (
                    <div className="mt-8 space-y-4">
                        {quarterlies.map((quarterly) => (
                            <div
                                key={quarterly.id}
                                className={`rounded-lg border bg-surface-container-low p-5 ${quarterly.is_active ? 'border-moss/50' : 'border-border'}`}
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <h3 className="font-serif text-lg font-medium text-on-surface">
                                                {quarterly.title}
                                            </h3>
                                            {quarterly.is_active && (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-moss/10 px-2 py-0.5 text-xs font-medium text-moss">
                                                    <CheckCircle className="size-3" />
                                                    Active
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-1 text-sm text-on-surface-variant">
                                            {quarterLabel(quarterly)} &middot;
                                            Code:{' '}
                                            <span className="font-mono">
                                                {quarterly.quarter_code}
                                            </span>{' '}
                                            &middot; {quarterly.lessons_count}{' '}
                                            lessons &middot;{' '}
                                            {
                                                quarterly.lessons_with_images_count
                                            }
                                            /{quarterly.lessons_count} images
                                        </p>
                                        <p className="mt-1 text-xs text-on-surface-variant/70">
                                            Last synced:{' '}
                                            {formatDate(
                                                quarterly.last_synced_at,
                                            )}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {!quarterly.is_active && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleActivate(quarterly)
                                                }
                                            >
                                                <Star className="size-3.5" />
                                                Set Active
                                            </Button>
                                        )}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                handleSync(quarterly)
                                            }
                                        >
                                            <RefreshCw className="size-3.5" />
                                            Re-sync
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </DevotionalLayout>
    );
}
