import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/themes',
    },
    {
        title: 'AI Content',
        href: '/admin/ai-content/generate',
    },
    {
        title: 'Generate',
        href: '/admin/ai-content/generate',
    },
];

export default function AiContentGenerate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Generate AI Content" />
            <div className="p-4">
                <h1 className="text-2xl font-bold">Generate AI Content</h1>
            </div>
        </AppLayout>
    );
}
