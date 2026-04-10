import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/themes',
    },
    {
        title: 'Themes',
        href: '/admin/themes',
    },
];

interface Theme {
    id: number;
    name: string;
    description: string | null;
    status: string;
    entries_count: number;
    created_at: string;
}

interface Props {
    themes: Theme[];
}

export default function ThemesIndex({ themes }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Themes" />
            <div className="p-4">
                <h1 className="text-2xl font-bold">Themes</h1>
                <div className="mt-4 space-y-4">
                    {themes.map((theme) => (
                        <div key={theme.id} className="rounded-lg border p-4">
                            <h2 className="text-lg font-semibold">
                                {theme.name}
                            </h2>
                            {theme.description && (
                                <p className="text-sm text-gray-600">
                                    {theme.description}
                                </p>
                            )}
                            <p className="text-sm text-gray-500">
                                Status: {theme.status} | Entries:{' '}
                                {theme.entries_count}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
