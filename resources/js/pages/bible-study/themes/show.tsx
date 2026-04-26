import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface Props {
    theme: { slug: string; title: string };
}

export default function Show({ theme }: Props) {
    return (
        <DevotionalLayout>
            <Head title={`Theme — ${theme.title}`} />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-semibold">{theme.title}</h1>
            </div>
        </DevotionalLayout>
    );
}
