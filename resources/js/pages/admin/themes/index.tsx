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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import DevotionalLayout from '@/layouts/devotional-layout';
import { storageUrl } from '@/lib/utils';
import {
    create,
    destroy,
    edit,
    publish,
    unpublish,
} from '@/routes/admin/themes';
import { index as entriesIndex } from '@/routes/admin/themes/entries';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDownToLine,
    BookOpen,
    ChevronLeft,
    ChevronRight,
    Eye,
    FileText,
    Pencil,
    Plus,
    Send,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

interface Theme {
    id: number;
    name: string;
    description: string | null;
    status: 'draft' | 'published';
    entries_count: number;
    created_at: string;
    cover_image_path: string | null;
}

interface Props {
    themes: Theme[];
}

const ITEMS_PER_PAGE = 8;

export default function ThemesIndex({ themes }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<Theme | null>(null);
    const [currentPage, setCurrentPage] = useState(1);

    const publishedCount = themes.filter(
        (t) => t.status === 'published',
    ).length;
    const draftCount = themes.filter((t) => t.status === 'draft').length;
    const totalEntries = themes.reduce((sum, t) => sum + t.entries_count, 0);

    const totalPages = Math.max(1, Math.ceil(themes.length / ITEMS_PER_PAGE));
    const paginatedThemes = themes.slice(
        (currentPage - 1) * ITEMS_PER_PAGE,
        currentPage * ITEMS_PER_PAGE,
    );
    const showingFrom =
        themes.length > 0 ? (currentPage - 1) * ITEMS_PER_PAGE + 1 : 0;
    const showingTo = Math.min(currentPage * ITEMS_PER_PAGE, themes.length);

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(destroy.url(deleteTarget.id), {
            onFinish: () => setDeleteTarget(null),
        });
    }

    function handlePublish(theme: Theme) {
        router.put(publish.url(theme.id));
    }

    function handleUnpublish(theme: Theme) {
        router.put(unpublish.url(theme.id));
    }

    function formatDate(dateString: string) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }

    return (
        <DevotionalLayout>
            <Head title="Manage Themes" />

            <div className="px-6 py-6 md:px-8">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Curator Dashboard
                        </p>
                        <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                            Managed Themes
                        </h1>
                        <p className="mt-2 max-w-xl text-sm leading-relaxed text-on-surface-variant">
                            Organize your spiritual content into curated
                            editorial journeys.
                        </p>
                    </div>
                    <Button
                        asChild
                        className="hidden bg-moss text-moss-foreground hover:bg-moss/90 sm:inline-flex"
                    >
                        <Link href={create.url()}>
                            <Plus className="size-4" />
                            New Theme
                        </Link>
                    </Button>
                </div>

                {/* Stats Row */}
                <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Total Themes
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                            {themes.length}
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
                    <div className="rounded-lg border border-border bg-surface-container-low px-4 py-3">
                        <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                            Total Entries
                        </p>
                        <p className="mt-1 font-serif text-2xl font-semibold text-on-surface">
                            {totalEntries}
                        </p>
                    </div>
                </div>

                {/* Mobile FAB */}
                <div className="mt-6 sm:hidden">
                    <Button
                        asChild
                        className="w-full bg-moss text-moss-foreground hover:bg-moss/90"
                    >
                        <Link href={create.url()}>
                            <Plus className="size-4" />
                            New Theme
                        </Link>
                    </Button>
                </div>

                {/* Content */}
                {themes.length === 0 ? (
                    <div className="mt-12 rounded-lg border border-dashed border-border bg-surface-container-low p-12 text-center">
                        <BookOpen className="mx-auto size-10 text-on-surface-variant/40" />
                        <p className="mt-4 font-serif text-xl text-on-surface-variant">
                            No themes created yet
                        </p>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            Create your first theme to begin curating devotional
                            content.
                        </p>
                        <Button
                            asChild
                            className="mt-6 bg-moss text-moss-foreground hover:bg-moss/90"
                        >
                            <Link href={create.url()}>
                                <Plus className="size-4" />
                                Create Theme
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
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Theme Title
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Entries
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Date Created
                                        </th>
                                        <th className="px-4 py-3 text-right text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {paginatedThemes.map((theme) => (
                                        <tr
                                            key={theme.id}
                                            className="group transition-colors hover:bg-surface-container-low/50"
                                        >
                                            <td className="px-4 py-4">
                                                <Link
                                                    href={entriesIndex.url(
                                                        theme.id,
                                                    )}
                                                    className="group/link flex items-center gap-4"
                                                >
                                                    <div className="size-12 shrink-0 overflow-hidden rounded-lg bg-surface-container-high">
                                                        {theme.cover_image_path ? (
                                                            <img
                                                                src={storageUrl(
                                                                    theme.cover_image_path,
                                                                )}
                                                                alt=""
                                                                onError={(
                                                                    e,
                                                                ) => {
                                                                    e.currentTarget.style.display =
                                                                        'none';
                                                                }}
                                                                className="size-full object-cover"
                                                            />
                                                        ) : (
                                                            <div className="flex size-full items-center justify-center">
                                                                <BookOpen className="size-4 text-on-surface-variant/30" />
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="font-serif text-base font-medium text-on-surface transition-colors group-hover/link:text-moss">
                                                            {theme.name}
                                                        </p>
                                                        {theme.description && (
                                                            <p className="mt-0.5 line-clamp-1 text-sm text-on-surface-variant">
                                                                {
                                                                    theme.description
                                                                }
                                                            </p>
                                                        )}
                                                    </div>
                                                </Link>
                                            </td>
                                            <td className="px-4 py-4">
                                                <StatusBadge
                                                    status={theme.status}
                                                />
                                            </td>
                                            <td className="px-4 py-4">
                                                <Link
                                                    href={entriesIndex.url(
                                                        theme.id,
                                                    )}
                                                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-moss"
                                                >
                                                    <FileText className="size-3.5" />
                                                    {theme.entries_count}{' '}
                                                    {theme.entries_count === 1
                                                        ? 'Entry'
                                                        : 'Entries'}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-on-surface-variant">
                                                {formatDate(theme.created_at)}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                asChild
                                                                className="size-8"
                                                            >
                                                                <Link
                                                                    href={entriesIndex.url(
                                                                        theme.id,
                                                                    )}
                                                                >
                                                                    <Eye className="size-4" />
                                                                    <span className="sr-only">
                                                                        View
                                                                        entries
                                                                    </span>
                                                                </Link>
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            View entries
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                asChild
                                                                className="size-8"
                                                            >
                                                                <Link
                                                                    href={edit.url(
                                                                        theme.id,
                                                                    )}
                                                                >
                                                                    <Pencil className="size-4" />
                                                                    <span className="sr-only">
                                                                        Edit
                                                                    </span>
                                                                </Link>
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            Edit theme
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    {theme.status ===
                                                        'draft' && (
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8 text-moss hover:text-moss"
                                                                    onClick={() =>
                                                                        handlePublish(
                                                                            theme,
                                                                        )
                                                                    }
                                                                >
                                                                    <Send className="size-4" />
                                                                    <span className="sr-only">
                                                                        Publish
                                                                    </span>
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Publish theme
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {theme.status ===
                                                        'published' && (
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8 text-on-surface-variant hover:text-on-surface"
                                                                    onClick={() =>
                                                                        handleUnpublish(
                                                                            theme,
                                                                        )
                                                                    }
                                                                >
                                                                    <ArrowDownToLine className="size-4" />
                                                                    <span className="sr-only">
                                                                        Unpublish
                                                                    </span>
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Unpublish theme
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="size-8 text-destructive hover:text-destructive"
                                                                onClick={() =>
                                                                    setDeleteTarget(
                                                                        theme,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="size-4" />
                                                                <span className="sr-only">
                                                                    Delete
                                                                </span>
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            Delete theme
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {/* Pagination */}
                            {themes.length > ITEMS_PER_PAGE && (
                                <div className="flex items-center justify-between border-t border-border bg-surface-container-high/30 px-4 py-3">
                                    <p className="text-sm text-on-surface-variant">
                                        Showing {showingFrom}–{showingTo} of{' '}
                                        {themes.length}
                                    </p>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={currentPage === 1}
                                            onClick={() =>
                                                setCurrentPage((p) => p - 1)
                                            }
                                        >
                                            <ChevronLeft className="size-4" />
                                            Previous
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                currentPage === totalPages
                                            }
                                            onClick={() =>
                                                setCurrentPage((p) => p + 1)
                                            }
                                        >
                                            Next
                                            <ChevronRight className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Mobile Card List */}
                        <div className="mt-6 space-y-3 md:hidden">
                            {paginatedThemes.map((theme) => (
                                <div
                                    key={theme.id}
                                    className="rounded-lg border border-border bg-surface-container-low p-4"
                                >
                                    <div className="flex items-start gap-3">
                                        {theme.cover_image_path && (
                                            <div className="size-14 shrink-0 overflow-hidden rounded-lg bg-surface-container-high">
                                                <img
                                                    src={storageUrl(
                                                        theme.cover_image_path,
                                                    )}
                                                    alt=""
                                                    onError={(e) => {
                                                        (
                                                            e.currentTarget
                                                                .parentElement as HTMLElement
                                                        ).style.display =
                                                            'none';
                                                    }}
                                                    className="size-full object-cover"
                                                />
                                            </div>
                                        )}
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-start justify-between gap-2">
                                                <Link
                                                    href={entriesIndex.url(
                                                        theme.id,
                                                    )}
                                                >
                                                    <h3 className="font-serif text-lg font-medium text-on-surface">
                                                        {theme.name}
                                                    </h3>
                                                </Link>
                                                <StatusBadge
                                                    status={theme.status}
                                                />
                                            </div>
                                            {theme.description && (
                                                <p className="mt-1 line-clamp-2 text-sm text-on-surface-variant">
                                                    {theme.description}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center justify-between">
                                        <div className="flex items-center gap-4 text-sm text-on-surface-variant">
                                            <span className="inline-flex items-center gap-1">
                                                <FileText className="size-3.5" />
                                                {theme.entries_count}{' '}
                                                {theme.entries_count === 1
                                                    ? 'Entry'
                                                    : 'Entries'}
                                            </span>
                                            <span>
                                                {formatDate(theme.created_at)}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                asChild
                                            >
                                                <Link href={edit.url(theme.id)}>
                                                    <Pencil className="size-3.5" />
                                                </Link>
                                            </Button>
                                            {theme.status === 'draft' && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-moss"
                                                    onClick={() =>
                                                        handlePublish(theme)
                                                    }
                                                >
                                                    <Send className="size-3.5" />
                                                </Button>
                                            )}
                                            {theme.status === 'published' && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-on-surface-variant"
                                                    onClick={() =>
                                                        handleUnpublish(theme)
                                                    }
                                                >
                                                    <ArrowDownToLine className="size-3.5" />
                                                </Button>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-destructive"
                                                onClick={() =>
                                                    setDeleteTarget(theme)
                                                }
                                            >
                                                <Trash2 className="size-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {/* Mobile Pagination */}
                            {themes.length > ITEMS_PER_PAGE && (
                                <div className="flex items-center justify-between pt-2">
                                    <p className="text-sm text-on-surface-variant">
                                        {showingFrom}–{showingTo} of{' '}
                                        {themes.length}
                                    </p>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={currentPage === 1}
                                            onClick={() =>
                                                setCurrentPage((p) => p - 1)
                                            }
                                        >
                                            <ChevronLeft className="size-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                currentPage === totalPages
                                            }
                                            onClick={() =>
                                                setCurrentPage((p) => p + 1)
                                            }
                                        >
                                            <ChevronRight className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            )}
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
                        <DialogTitle>Delete Theme</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium text-on-surface">
                                "{deleteTarget?.name}"
                            </span>
                            ? All associated devotional entries will be
                            permanently removed. This action cannot be undone.
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
                            Delete Theme
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DevotionalLayout>
    );
}
