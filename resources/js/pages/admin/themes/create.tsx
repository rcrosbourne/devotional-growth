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
    {
        title: 'Create',
        href: '/admin/themes/create',
    },
];

export default function ThemesCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Theme" />
            <div className="p-4">
                <h1 className="text-2xl font-bold">Create Theme</h1>
            </div>
        </AppLayout>
    );
}
