import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Theme {
    id: number;
    name: string;
    description: string | null;
    status: string;
}

interface Props {
    theme: Theme;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/themes',
    },
    {
        title: 'Themes',
        href: '/admin/themes',
    },
    {
        title: 'Edit',
        href: '#',
    },
];

export default function ThemesEdit({ theme }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Theme: ${theme.name}`} />
            <div className="p-4">
                <h1 className="text-2xl font-bold">Edit Theme: {theme.name}</h1>
            </div>
        </AppLayout>
    );
}
