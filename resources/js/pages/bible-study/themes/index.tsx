import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface Props {
    themes: Array<{ id: number; slug: string; title: string }>;
}

export default function Index({ themes }: Props) {
    return (
        <DevotionalLayout>
            <Head title="Bible Study Themes" />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-semibold">Themes</h1>
                <p className="mt-1 text-sm text-on-surface-variant">
                    {themes.length} themes available
                </p>
            </div>
        </DevotionalLayout>
    );
}
