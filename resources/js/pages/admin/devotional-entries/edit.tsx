import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/themes',
    },
    {
        title: 'Edit Entry',
        href: '#',
    },
];

interface Theme {
    id: number;
    name: string;
}

interface Entry {
    id: number;
    title: string;
    body: string;
}

interface Props {
    theme: Theme;
    entry: Entry;
}

export default function EditEntry({ theme, entry }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${entry.title} - ${theme.name}`} />
            <div className="p-4">
                <h1 className="text-2xl font-bold">Edit: {entry.title}</h1>
            </div>
        </AppLayout>
    );
}
