import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { destroy, index, publish, update } from '@/routes/admin/themes';
import { index as entriesIndex } from '@/routes/admin/themes/entries';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, Send, Trash2 } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

interface Theme {
    id: number;
    name: string;
    description: string | null;
    status: 'draft' | 'published';
}

interface Props {
    theme: Theme;
}

export default function ThemesEdit({ theme }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Themes', href: index.url() },
        { title: theme.name, href: '#' },
    ];

    const { data, setData, put, processing, errors, isDirty } = useForm({
        name: theme.name,
        description: theme.description ?? '',
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        put(update.url(theme.id));
    };

    function handleDelete() {
        router.delete(destroy.url(theme.id), {
            onFinish: () => setShowDeleteDialog(false),
        });
    }

    function handlePublish() {
        router.put(publish.url(theme.id));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit: ${theme.name}`} />

            <div className="px-6 py-6 md:px-8">
                <Link
                    href={index.url()}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to Themes
                </Link>

                <div className="mt-4 flex items-start justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Edit Theme
                        </p>
                        <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface">
                            {theme.name}
                        </h1>
                        <div className="mt-3 flex items-center gap-3">
                            {theme.status === 'published' ? (
                                <Badge className="border-moss/20 bg-moss/10 text-moss hover:bg-moss/10">
                                    Published
                                </Badge>
                            ) : (
                                <Badge
                                    variant="secondary"
                                    className="text-on-surface-variant"
                                >
                                    Draft
                                </Badge>
                            )}
                            <Link
                                href={entriesIndex.url(theme.id)}
                                className="inline-flex items-center gap-1 text-sm text-moss hover:underline"
                            >
                                <ExternalLink className="size-3.5" />
                                Manage Entries
                            </Link>
                        </div>
                    </div>

                    {theme.status === 'draft' && (
                        <Button
                            className="hidden bg-moss text-moss-foreground hover:bg-moss/90 sm:inline-flex"
                            onClick={handlePublish}
                        >
                            <Send className="size-4" />
                            Publish
                        </Button>
                    )}
                </div>

                <form onSubmit={handleSubmit} className="mt-8 max-w-2xl">
                    <div className="space-y-6">
                        <div className="space-y-2">
                            <Label
                                htmlFor="name"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Theme Title
                            </Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                className="font-serif text-lg"
                            />
                            {errors.name && (
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label
                                htmlFor="description"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Description
                            </Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                rows={4}
                            />
                            {errors.description && (
                                <p className="text-sm text-destructive">
                                    {errors.description}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-8 flex items-center gap-3">
                        <Button
                            type="submit"
                            disabled={processing || !isDirty}
                            className="bg-moss text-moss-foreground hover:bg-moss/90"
                        >
                            Save Changes
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={index.url()}>Cancel</Link>
                        </Button>
                    </div>

                    {/* Mobile publish */}
                    {theme.status === 'draft' && (
                        <div className="mt-6 sm:hidden">
                            <Button
                                type="button"
                                className="w-full bg-moss text-moss-foreground hover:bg-moss/90"
                                onClick={handlePublish}
                            >
                                <Send className="size-4" />
                                Publish Theme
                            </Button>
                        </div>
                    )}
                </form>

                {/* Danger Zone */}
                <div className="mt-12 max-w-2xl rounded-lg border border-destructive/20 bg-destructive/5 p-6">
                    <h3 className="font-serif text-lg font-medium text-destructive">
                        Danger Zone
                    </h3>
                    <p className="mt-1 text-sm text-on-surface-variant">
                        Deleting this theme will permanently remove all
                        associated devotional entries.
                    </p>
                    <Button
                        variant="destructive"
                        className="mt-4"
                        onClick={() => setShowDeleteDialog(true)}
                    >
                        <Trash2 className="size-4" />
                        Delete Theme
                    </Button>
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Theme</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium text-on-surface">
                                "{theme.name}"
                            </span>
                            ? All associated devotional entries will be
                            permanently removed. This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowDeleteDialog(false)}
                        >
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="size-4" />
                            Delete Theme
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
