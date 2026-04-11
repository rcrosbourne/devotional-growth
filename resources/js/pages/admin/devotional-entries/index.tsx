import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/themes',
    },
    {
        title: 'Entries',
        href: '#',
    },
];

interface Entry {
    id: number;
    title: string;
    status: string;
    display_order: number;
}

interface Theme {
    id: number;
    name: string;
}

interface Props {
    theme: Theme;
    entries: Entry[];
}

export default function EntriesIndex({ theme, entries }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Entries - ${theme.name}`} />
            <div className="p-4">
                <h1 className="text-2xl font-bold">Entries for {theme.name}</h1>
                <div className="mt-4 space-y-4">
                    {entries.map((entry) => (
                        <div key={entry.id} className="rounded-lg border p-4">
                            <h2 className="text-lg font-semibold">
                                {entry.title}
                            </h2>
                            <p className="text-sm text-gray-500">
                                Status: {entry.status} | Order:{' '}
                                {entry.display_order}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
