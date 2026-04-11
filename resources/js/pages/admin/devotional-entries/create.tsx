import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/themes',
    },
    {
        title: 'Create Entry',
        href: '#',
    },
];

interface Theme {
    id: number;
    name: string;
}

interface Props {
    theme: Theme;
}

export default function CreateEntry({ theme }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Create Entry - ${theme.name}`} />
            <div className="p-4">
                <h1 className="text-2xl font-bold">
                    Create Entry for {theme.name}
                </h1>
            </div>
        </AppLayout>
    );
}
