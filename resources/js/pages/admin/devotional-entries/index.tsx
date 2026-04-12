import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { index as themesIndex } from '@/routes/admin/themes';
import {
    create as entriesCreate,
    destroy as entriesDestroy,
    edit as entriesEdit,
    publish as entriesPublish,
    reorder as entriesReorder,
} from '@/routes/admin/themes/entries';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ChevronDown,
    ChevronUp,
    FileText,
    Pencil,
    Plus,
    Send,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

interface ScriptureReference {
    id: number;
    raw_reference: string;
}

interface Entry {
    id: number;
    title: string;
    status: 'draft' | 'published';
    display_order: number;
    created_at: string;
    updated_at: string;
    scripture_references: ScriptureReference[];
}

interface Theme {
    id: number;
    name: string;
    status: 'draft' | 'published';
}

interface Props {
    theme: Theme;
    entries: Entry[];
}

export default function EntriesIndex({ theme, entries }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<Entry | null>(null);
    const [reordering, setReordering] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Themes', href: themesIndex.url() },
        { title: theme.name, href: '#' },
    ];

    const publishedCount = entries.filter(
        (e) => e.status === 'published',
    ).length;
    const draftCount = entries.filter((e) => e.status === 'draft').length;

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(
            entriesDestroy.url({ theme: theme.id, entry: deleteTarget.id }),
            { onFinish: () => setDeleteTarget(null) },
        );
    }

    function handlePublish(entry: Entry) {
        router.put(entriesPublish.url({ theme: theme.id, entry: entry.id }));
    }

    function handleMoveEntry(index: number, direction: 'up' | 'down') {
        if (reordering) return;
        const swapIndex = direction === 'up' ? index - 1 : index + 1;
        if (swapIndex < 0 || swapIndex >= entries.length) return;

        const reordered = [...entries];
        [reordered[index], reordered[swapIndex]] = [
            reordered[swapIndex],
            reordered[index],
        ];

        setReordering(true);
        router.put(
            entriesReorder.url(theme.id),
            { ordered_ids: reordered.map((e) => e.id) },
            {
                preserveScroll: true,
                onFinish: () => setReordering(false),
                onError: () => setReordering(false),
            },
        );
    }

    function formatDate(dateString: string) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Entries - ${theme.name}`} />

            <div className="px-6 py-6 md:px-8">
                <Link
                    href={themesIndex.url()}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to Themes
                </Link>

                {/* Header */}
                <div className="mt-4 flex items-start justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Theme Collection
                        </p>
                        <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                            {theme.name}
                        </h1>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Managing {entries.length} devotional{' '}
                            {entries.length === 1 ? 'entry' : 'entries'}.
                        </p>
                    </div>
                    <Button
                        asChild
                        className="hidden bg-moss text-moss-foreground hover:bg-moss/90 sm:inline-flex"
                    >
                        <Link href={entriesCreate.url(theme.id)}>
                            <Plus className="size-4" />
                            Add New Entry
                        </Link>
                    </Button>
                </div>

                {/* Stats */}
                <div className="mt-8 grid grid-cols-3 gap-4">
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Total
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                            {entries.length}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Published
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-moss">
                            {publishedCount}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Drafts
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                            {draftCount}
                        </p>
                    </div>
                </div>

                {/* Mobile button */}
                <div className="mt-6 sm:hidden">
                    <Button
                        asChild
                        className="w-full bg-moss text-moss-foreground hover:bg-moss/90"
                    >
                        <Link href={entriesCreate.url(theme.id)}>
                            <Plus className="size-4" />
                            Add New Entry
                        </Link>
                    </Button>
                </div>

                {/* Content */}
                {entries.length === 0 ? (
                    <div className="mt-12 rounded-lg border border-dashed border-border bg-surface-container-low p-12 text-center">
                        <FileText className="mx-auto size-10 text-on-surface-variant/40" />
                        <p className="mt-4 font-serif text-xl text-on-surface-variant">
                            No entries yet
                        </p>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Create your first devotional entry for this theme.
                        </p>
                        <Button
                            asChild
                            className="mt-6 bg-moss text-moss-foreground hover:bg-moss/90"
                        >
                            <Link href={entriesCreate.url(theme.id)}>
                                <Plus className="size-4" />
                                Create Entry
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        {/* Desktop Table */}
                        <div className="mt-8 hidden overflow-hidden rounded-lg border border-border md:block">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-border bg-surface-container-high/50">
                                        <th className="w-16 px-2 py-3 text-center text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Order
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Entry Title
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Scripture
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Last Modified
                                        </th>
                                        <th className="px-4 py-3 text-right text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {entries.map((entry, index) => (
                                        <tr
                                            key={entry.id}
                                            className="group transition-colors hover:bg-surface-container-low/50"
                                        >
                                            <td className="px-2 py-4">
                                                <div className="flex flex-col items-center gap-0.5">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-6"
                                                        disabled={
                                                            index === 0 ||
                                                            reordering
                                                        }
                                                        onClick={() =>
                                                            handleMoveEntry(
                                                                index,
                                                                'up',
                                                            )
                                                        }
                                                    >
                                                        <ChevronUp className="size-3.5" />
                                                        <span className="sr-only">
                                                            Move up
                                                        </span>
                                                    </Button>
                                                    <span className="text-xs font-medium text-on-surface-variant/60">
                                                        {index + 1}
                                                    </span>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-6"
                                                        disabled={
                                                            index ===
                                                                entries.length -
                                                                    1 ||
                                                            reordering
                                                        }
                                                        onClick={() =>
                                                            handleMoveEntry(
                                                                index,
                                                                'down',
                                                            )
                                                        }
                                                    >
                                                        <ChevronDown className="size-3.5" />
                                                        <span className="sr-only">
                                                            Move down
                                                        </span>
                                                    </Button>
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Link
                                                    href={entriesEdit.url({
                                                        theme: theme.id,
                                                        entry: entry.id,
                                                    })}
                                                    className="group/link"
                                                >
                                                    <p className="font-serif text-base font-medium text-on-surface transition-colors group-hover/link:text-moss">
                                                        {entry.title}
                                                    </p>
                                                </Link>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-on-surface-variant">
                                                {entry.scripture_references
                                                    .length > 0
                                                    ? entry.scripture_references
                                                          .map(
                                                              (ref) =>
                                                                  ref.raw_reference,
                                                          )
                                                          .join(', ')
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-4">
                                                <StatusBadge
                                                    status={entry.status}
                                                />
                                            </td>
                                            <td className="px-4 py-4 text-sm text-on-surface-variant">
                                                {formatDate(entry.updated_at)}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                        className="size-8"
                                                    >
                                                        <Link
                                                            href={entriesEdit.url(
                                                                {
                                                                    theme: theme.id,
                                                                    entry: entry.id,
                                                                },
                                                            )}
                                                        >
                                                            <Pencil className="size-4" />
                                                            <span className="sr-only">
                                                                Edit
                                                            </span>
                                                        </Link>
                                                    </Button>
                                                    {entry.status ===
                                                        'draft' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 text-moss hover:text-moss"
                                                            onClick={() =>
                                                                handlePublish(
                                                                    entry,
                                                                )
                                                            }
                                                        >
                                                            <Send className="size-4" />
                                                            <span className="sr-only">
                                                                Publish
                                                            </span>
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            setDeleteTarget(
                                                                entry,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                        <span className="sr-only">
                                                            Delete
                                                        </span>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile Card List */}
                        <div className="mt-6 space-y-3 md:hidden">
                            {entries.map((entry, idx) => (
                                <div
                                    key={entry.id}
                                    className="rounded-lg border border-border bg-surface-container-low p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex shrink-0 flex-col items-center gap-0.5">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-6"
                                                disabled={
                                                    idx === 0 || reordering
                                                }
                                                onClick={() =>
                                                    handleMoveEntry(idx, 'up')
                                                }
                                            >
                                                <ChevronUp className="size-3" />
                                                <span className="sr-only">
                                                    Move entry {idx + 1} up
                                                </span>
                                            </Button>
                                            <span className="text-xs font-medium text-on-surface-variant/60">
                                                {idx + 1}
                                            </span>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-6"
                                                disabled={
                                                    idx ===
                                                        entries.length - 1 ||
                                                    reordering
                                                }
                                                onClick={() =>
                                                    handleMoveEntry(idx, 'down')
                                                }
                                            >
                                                <ChevronDown className="size-3" />
                                                <span className="sr-only">
                                                    Move entry {idx + 1} down
                                                </span>
                                            </Button>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <StatusBadge
                                                    status={entry.status}
                                                />
                                            </div>
                                            <Link
                                                href={entriesEdit.url({
                                                    theme: theme.id,
                                                    entry: entry.id,
                                                })}
                                            >
                                                <h3 className="mt-1 font-serif text-lg font-medium text-on-surface">
                                                    {entry.title}
                                                </h3>
                                            </Link>
                                            {entry.scripture_references.length >
                                                0 && (
                                                <p className="mt-1 text-sm text-on-surface-variant">
                                                    {entry.scripture_references
                                                        .map(
                                                            (ref) =>
                                                                ref.raw_reference,
                                                        )
                                                        .join(', ')}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center justify-between">
                                        <span className="text-xs text-on-surface-variant">
                                            {formatDate(entry.updated_at)}
                                        </span>
                                        <div className="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                asChild
                                            >
                                                <Link
                                                    href={entriesEdit.url({
                                                        theme: theme.id,
                                                        entry: entry.id,
                                                    })}
                                                >
                                                    <Pencil className="size-3.5" />
                                                </Link>
                                            </Button>
                                            {entry.status === 'draft' && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-moss"
                                                    onClick={() =>
                                                        handlePublish(entry)
                                                    }
                                                >
                                                    <Send className="size-3.5" />
                                                </Button>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-destructive"
                                                onClick={() =>
                                                    setDeleteTarget(entry)
                                                }
                                            >
                                                <Trash2 className="size-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </>
                )}
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Entry</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium text-on-surface">
                                "{deleteTarget?.title}"
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteTarget(null)}
                        >
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="size-4" />
                            Delete Entry
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
