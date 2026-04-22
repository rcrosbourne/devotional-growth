import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface BibleStudyTheme {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    status: string;
    requested_count: number;
    created_at: string;
    approved_at: string | null;
}

interface Props {
    themes: BibleStudyTheme[];
    statuses: string[];
}

export default function Index({ themes, statuses }: Props) {
    return (
        <DevotionalLayout>
            <Head title="Bible Study Themes" />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-bold">Bible Study Themes</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Review queue — {themes.length} themes
                </p>
                <div className="mt-6 space-y-4">
                    {themes.map((theme) => (
                        <div key={theme.id} className="rounded-lg border p-4">
                            <div className="flex items-center justify-between">
                                <span className="font-medium">
                                    {theme.title}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {theme.status} &middot;{' '}
                                    {theme.requested_count} requests
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {theme.short_description}
                            </p>
                        </div>
                    ))}
                </div>
                <div className="mt-4 hidden">
                    {statuses.map((s) => (
                        <span key={s}>{s}</span>
                    ))}
                </div>
            </div>
        </DevotionalLayout>
    );
}
