import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface Props {
    passage: {
        book: string;
        chapter: number;
        verse_start: number;
        verse_end: number | null;
    };
}

export default function Passage({ passage }: Props) {
    const ref = `${passage.book} ${passage.chapter}:${passage.verse_start}${passage.verse_end ? `–${passage.verse_end}` : ''}`;
    return (
        <DevotionalLayout>
            <Head title={`Reading — ${ref}`} />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-semibold">{ref}</h1>
            </div>
        </DevotionalLayout>
    );
}
