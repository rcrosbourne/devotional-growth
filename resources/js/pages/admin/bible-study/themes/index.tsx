import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import DevotionalLayout from '@/layouts/devotional-layout';
import {
    show as bibleStudyThemesShow,
    store as bibleStudyThemesStore,
} from '@/routes/admin/bible-study/themes';
import { Head, Link, useForm } from '@inertiajs/react';
import { BookOpen, Loader2, Sparkles } from 'lucide-react';
import type { FormEvent } from 'react';

type ThemeStatus = 'draft' | 'approved' | 'archived';

type ThemeRow = {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    status: ThemeStatus;
    requested_count: number;
    created_at: string;
    approved_at: string | null;
};

interface Props {
    themes: ThemeRow[];
    statuses: string[];
}

function BibleStudyStatusBadge({ status }: { status: ThemeStatus }) {
    if (status === 'approved') {
        return (
            <Badge className="border-moss/20 bg-moss/10 text-moss hover:bg-moss/10">
                Approved
            </Badge>
        );
    }

    if (status === 'archived') {
        return (
            <Badge
                variant="secondary"
                className="text-on-surface-variant/60 line-through"
            >
                Archived
            </Badge>
        );
    }

    return (
        <Badge variant="secondary" className="text-on-surface-variant">
            Draft
        </Badge>
    );
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function BibleStudyThemesIndex({ themes }: Props) {
    const { data, setData, post, processing } = useForm({ title: '' });

    function submit(e: FormEvent): void {
        e.preventDefault();
        post(bibleStudyThemesStore.url(), {
            onSuccess: () => setData('title', ''),
        });
    }

    return (
        <DevotionalLayout>
            <Head title="Bible Study — Themes" />

            <div className="px-6 py-6 md:px-8">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Admin — Bible Study
                        </p>
                        <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                            Themes
                        </h1>
                        <p className="mt-2 max-w-xl text-sm leading-relaxed text-on-surface-variant">
                            Draft, review, and publish theme studies for the
                            Bible Study module.
                        </p>
                    </div>
                </div>

                {/* Stats Row */}
                <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Total Themes
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                            {themes.length}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Approved
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-moss">
                            {
                                themes.filter((t) => t.status === 'approved')
                                    .length
                            }
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Drafts
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                            {themes.filter((t) => t.status === 'draft').length}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Total Requests
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                            {themes.reduce(
                                (sum, t) => sum + t.requested_count,
                                0,
                            )}
                        </p>
                    </div>
                </div>

                {/* Draft with AI form */}
                <form
                    onSubmit={submit}
                    className="mt-8 flex gap-2"
                    aria-label="Draft new bible study theme"
                >
                    <Input
                        placeholder="New theme title (e.g., Forgiveness)"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        className="max-w-md"
                    />
                    <Button
                        type="submit"
                        disabled={processing || !data.title.trim()}
                        className="bg-moss text-moss-foreground hover:bg-moss/90"
                    >
                        {processing ? (
                            <>
                                <Loader2 className="size-4 animate-spin" />
                                Drafting...
                            </>
                        ) : (
                            <>
                                <Sparkles className="size-4" />
                                Draft with AI
                            </>
                        )}
                    </Button>
                </form>

                {/* Content */}
                {themes.length === 0 ? (
                    <div className="mt-12 rounded-lg border border-dashed border-border bg-surface-container-low p-12 text-center">
                        <BookOpen className="mx-auto size-10 text-on-surface-variant/40" />
                        <p className="mt-4 font-serif text-xl text-on-surface-variant">
                            No themes yet
                        </p>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Enter a title above to draft a new theme with AI.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Desktop Table */}
                        <div className="mt-8 hidden overflow-hidden rounded-lg border border-border md:block">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-border bg-surface-container-high/50">
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Title
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Requests
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Date Created
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {themes.map((theme) => (
                                        <tr
                                            key={theme.id}
                                            className="group transition-colors hover:bg-surface-container-low/50"
                                        >
                                            <td className="px-4 py-4">
                                                <Link
                                                    href={bibleStudyThemesShow.url(
                                                        theme.id,
                                                    )}
                                                    className="group/link min-w-0"
                                                >
                                                    <p className="font-serif text-base font-medium text-on-surface transition-colors group-hover/link:text-moss">
                                                        {theme.title}
                                                    </p>
                                                    {theme.short_description && (
                                                        <p className="mt-0.5 line-clamp-1 text-sm text-on-surface-variant">
                                                            {
                                                                theme.short_description
                                                            }
                                                        </p>
                                                    )}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-4">
                                                <BibleStudyStatusBadge
                                                    status={theme.status}
                                                />
                                            </td>
                                            <td className="px-4 py-4 text-sm text-on-surface-variant">
                                                {theme.requested_count}
                                            </td>
                                            <td className="px-4 py-4 text-sm text-on-surface-variant">
                                                {formatDate(theme.created_at)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile Card List */}
                        <div className="mt-6 space-y-3 md:hidden">
                            {themes.map((theme) => (
                                <Link
                                    key={theme.id}
                                    href={bibleStudyThemesShow.url(theme.id)}
                                    className="block rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:bg-surface-container"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <h3 className="font-serif text-base font-medium text-on-surface">
                                            {theme.title}
                                        </h3>
                                        <BibleStudyStatusBadge
                                            status={theme.status}
                                        />
                                    </div>
                                    {theme.short_description && (
                                        <p className="mt-1 line-clamp-2 text-sm text-on-surface-variant">
                                            {theme.short_description}
                                        </p>
                                    )}
                                    <div className="mt-3 flex items-center gap-4 text-xs text-on-surface-variant">
                                        <span>
                                            {theme.requested_count} requests
                                        </span>
                                        <span>
                                            {formatDate(theme.created_at)}
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </DevotionalLayout>
    );
}
