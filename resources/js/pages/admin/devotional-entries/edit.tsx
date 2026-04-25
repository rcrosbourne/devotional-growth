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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import DevotionalLayout from '@/layouts/devotional-layout';
import { storageUrl } from '@/lib/utils';
import {
    destroy as entriesDestroy,
    index as entriesIndex,
    publish as entriesPublish,
    update as entriesUpdate,
} from '@/routes/admin/themes/entries';
import { generateImage } from '@/routes/entries';
import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    ImagePlus,
    Loader2,
    Plus,
    Send,
    Trash2,
    X,
} from 'lucide-react';
import { type FormEventHandler, useEffect, useRef, useState } from 'react';

interface ScriptureRefItem {
    key: number;
    value: string;
}

interface ScriptureReference {
    id: number;
    raw_reference: string;
}

interface GeneratedImage {
    id: number;
    path: string;
    prompt: string;
}

interface Entry {
    id: number;
    title: string;
    body: string;
    reflection_prompts: string | null;
    adventist_insights: string | null;
    status: 'draft' | 'published';
    display_order: number;
    created_at: string;
    updated_at: string;
    scripture_references: ScriptureReference[];
    generated_image: GeneratedImage | null;
}

interface Theme {
    id: number;
    name: string;
}

interface Props {
    theme: Theme;
    entry: Entry;
}

function toRefItems(refs: string[]): ScriptureRefItem[] {
    return refs.map((value, i) => ({ key: i, value }));
}

export default function EditEntry({ theme, entry }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isPublishing, setIsPublishing] = useState(false);
    const [generatingImage, setGeneratingImage] = useState(false);
    const [imageIdAtStart, setImageIdAtStart] = useState<number | null>(null);
    const [confirmRegenerate, setConfirmRegenerate] = useState(false);
    const [imageFailed, setImageFailed] = useState(false);

    const { start: startPolling, stop: stopPolling } = usePoll(
        3000,
        { only: ['entry'] },
        { autoStart: false },
    );

    // When polling refreshes `entry`, end the polling/loading state if the
    // generated image id has changed. Setting state inside the effect is the
    // simplest way to react to async server-pushed prop changes here.

    useEffect(() => {
        if (!generatingImage) {
            return;
        }

        const currentImageId = entry.generated_image?.id ?? null;
        if (currentImageId !== null && currentImageId !== imageIdAtStart) {
            stopPolling();
            // eslint-disable-next-line @eslint-react/set-state-in-effect
            setGeneratingImage(false);
        }
    }, [
        generatingImage,
        entry.generated_image?.id,
        imageIdAtStart,
        stopPolling,
    ]);
    const nextKeyRef = useRef(
        entry.scripture_references.length > 0
            ? entry.scripture_references.length
            : 1,
    );

    const initialRefs =
        entry.scripture_references.length > 0
            ? entry.scripture_references.map((ref) => ref.raw_reference)
            : [''];

    const [refItems, setRefItems] = useState<ScriptureRefItem[]>(() =>
        toRefItems(initialRefs),
    );

    const { data, setData, put, processing, errors, isDirty } = useForm({
        title: entry.title,
        body: entry.body,
        scripture_references: initialRefs,
        reflection_prompts: entry.reflection_prompts ?? '',
        adventist_insights: entry.adventist_insights ?? '',
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        put(entriesUpdate.url({ theme: theme.id, entry: entry.id }));
    };

    function handleDelete() {
        setIsDeleting(true);
        router.delete(
            entriesDestroy.url({ theme: theme.id, entry: entry.id }),
            {
                onSuccess: () => setShowDeleteDialog(false),
                onFinish: () => setIsDeleting(false),
            },
        );
    }

    function handlePublish() {
        setIsPublishing(true);
        router.put(
            entriesPublish.url({ theme: theme.id, entry: entry.id }),
            {},
            { onFinish: () => setIsPublishing(false) },
        );
    }

    function handleGenerateImage() {
        if (entry.generated_image) {
            setConfirmRegenerate(true);
            return;
        }
        doGenerateImage();
    }

    function doGenerateImage() {
        setGeneratingImage(true);
        setImageIdAtStart(entry.generated_image?.id ?? null);
        setConfirmRegenerate(false);
        setImageFailed(false);
        router.post(
            generateImage.url(entry.id),
            { replace: entry.generated_image ? true : false },
            {
                preserveScroll: true,
                onSuccess: () => startPolling(),
                onError: () => setGeneratingImage(false),
            },
        );
    }

    function syncRefs(items: ScriptureRefItem[]) {
        setRefItems(items);
        setData(
            'scripture_references',
            items.map((r) => r.value),
        );
    }

    function addScriptureReference() {
        const key = nextKeyRef.current++;
        syncRefs([...refItems, { key, value: '' }]);
    }

    function removeScriptureReference(key: number) {
        syncRefs(refItems.filter((r) => r.key !== key));
    }

    function updateScriptureReference(key: number, value: string) {
        syncRefs(refItems.map((r) => (r.key === key ? { ...r, value } : r)));
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
            <Head title={`Edit: ${entry.title}`} />

            <div className="px-6 py-6 md:px-8">
                <Link
                    href={entriesIndex.url(theme.id)}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to {theme.name}
                </Link>

                <div className="mt-4 flex items-start justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Edit Devotional Entry
                        </p>
                        <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface">
                            {entry.title}
                        </h1>
                        <div className="mt-3 flex items-center gap-3">
                            <StatusBadge status={entry.status} />
                            <span className="text-sm text-on-surface-variant">
                                Last updated {formatDate(entry.updated_at)}
                            </span>
                        </div>
                    </div>
                    {entry.status === 'draft' && (
                        <Button
                            className="hidden bg-moss text-moss-foreground hover:bg-moss/90 sm:inline-flex"
                            disabled={isPublishing}
                            onClick={handlePublish}
                        >
                            <Send className="size-4" />
                            Publish
                        </Button>
                    )}
                </div>

                <form onSubmit={handleSubmit} className="mt-8">
                    <div className="grid gap-8 lg:grid-cols-3">
                        {/* Main content */}
                        <div className="space-y-6 lg:col-span-2">
                            {/* Title */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="title"
                                    className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                                >
                                    Entry Title
                                </Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                    className="font-serif text-lg"
                                />
                                {errors.title && (
                                    <p className="text-sm text-destructive">
                                        {errors.title}
                                    </p>
                                )}
                            </div>

                            {/* Scripture References */}
                            <div className="space-y-3">
                                <Label className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                    Scripture References
                                </Label>
                                {refItems.map((ref) => (
                                    <div
                                        key={ref.key}
                                        className="flex items-center gap-2"
                                    >
                                        <div className="flex flex-1 items-center gap-2">
                                            <BookOpen className="size-4 shrink-0 text-on-surface-variant" />
                                            <Input
                                                value={ref.value}
                                                onChange={(e) =>
                                                    updateScriptureReference(
                                                        ref.key,
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="e.g. Psalm 46:10"
                                            />
                                        </div>
                                        {refItems.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 shrink-0 text-destructive hover:text-destructive"
                                                onClick={() =>
                                                    removeScriptureReference(
                                                        ref.key,
                                                    )
                                                }
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        )}
                                    </div>
                                ))}
                                {errors.scripture_references && (
                                    <p className="text-sm text-destructive">
                                        {errors.scripture_references}
                                    </p>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={addScriptureReference}
                                >
                                    <Plus className="size-4" />
                                    Add Reference
                                </Button>
                            </div>

                            {/* Body */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="body"
                                    className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                                >
                                    Body Content
                                </Label>
                                <Textarea
                                    id="body"
                                    value={data.body}
                                    onChange={(e) =>
                                        setData('body', e.target.value)
                                    }
                                    rows={12}
                                />
                                {errors.body && (
                                    <p className="text-sm text-destructive">
                                        {errors.body}
                                    </p>
                                )}
                            </div>

                            {/* Reflection Prompts */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="reflection_prompts"
                                    className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                                >
                                    Reflection Prompts
                                </Label>
                                <Textarea
                                    id="reflection_prompts"
                                    value={data.reflection_prompts}
                                    onChange={(e) =>
                                        setData(
                                            'reflection_prompts',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="One prompt per line..."
                                    rows={4}
                                />
                                <p className="text-xs text-on-surface-variant">
                                    One prompt per line. These guide the
                                    reader's personal reflection.
                                </p>
                                {errors.reflection_prompts && (
                                    <p className="text-sm text-destructive">
                                        {errors.reflection_prompts}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Publication Status */}
                            <div className="rounded-lg border border-border bg-surface-container-low p-5">
                                <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                    Publication Status
                                </p>
                                <p className="mt-2 font-serif text-lg text-on-surface capitalize">
                                    {entry.status}
                                </p>
                                <div className="mt-4 space-y-2">
                                    <Button
                                        type="submit"
                                        disabled={processing || !isDirty}
                                        className="w-full bg-moss text-moss-foreground hover:bg-moss/90"
                                    >
                                        Save Changes
                                    </Button>
                                    {entry.status === 'draft' && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="w-full"
                                            disabled={isPublishing}
                                            onClick={handlePublish}
                                        >
                                            <Send className="size-4" />
                                            Publish Entry
                                        </Button>
                                    )}
                                    <Button
                                        variant="outline"
                                        asChild
                                        className="w-full"
                                    >
                                        <Link href={entriesIndex.url(theme.id)}>
                                            Discard Changes
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            {/* Adventist Insights */}
                            <div className="rounded-lg border border-border bg-surface-container-low p-5">
                                <Label
                                    htmlFor="adventist_insights"
                                    className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                                >
                                    Adventist Insights
                                </Label>
                                <Textarea
                                    id="adventist_insights"
                                    value={data.adventist_insights}
                                    onChange={(e) =>
                                        setData(
                                            'adventist_insights',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Sabbath connection, prophetic context..."
                                    rows={4}
                                    className="mt-2"
                                />
                                {errors.adventist_insights && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.adventist_insights}
                                    </p>
                                )}
                            </div>

                            {/* Generated Image */}
                            <div className="rounded-lg border border-border bg-surface-container-low p-5">
                                <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                    Generated Image
                                </p>
                                {entry.generated_image && !imageFailed && (
                                    <div className="mt-3 overflow-hidden rounded-lg">
                                        <img
                                            src={storageUrl(
                                                entry.generated_image.path,
                                            )}
                                            alt={`Image for ${entry.title}`}
                                            onError={() => setImageFailed(true)}
                                            className="w-full object-cover"
                                        />
                                    </div>
                                )}
                                {(!entry.generated_image || imageFailed) && (
                                    <p className="mt-2 text-sm text-on-surface-variant/60 italic">
                                        No image generated yet.
                                    </p>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="mt-3 w-full"
                                    disabled={generatingImage}
                                    onClick={handleGenerateImage}
                                >
                                    {generatingImage ? (
                                        <Loader2 className="size-4 animate-spin" />
                                    ) : (
                                        <ImagePlus className="size-4" />
                                    )}
                                    {generatingImage
                                        ? 'Generating...'
                                        : entry.generated_image
                                          ? 'Regenerate Image'
                                          : 'Generate Image'}
                                </Button>
                            </div>

                            {/* Danger Zone */}
                            <div className="rounded-lg border border-destructive/20 bg-destructive/5 p-5">
                                <p className="text-xs font-medium tracking-[0.1em] text-destructive uppercase">
                                    Danger Zone
                                </p>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    className="mt-3"
                                    onClick={() => setShowDeleteDialog(true)}
                                >
                                    <Trash2 className="size-4" />
                                    Delete Entry
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Entry</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium text-on-surface">
                                "{entry.title}"
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowDeleteDialog(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={isDeleting}
                            onClick={handleDelete}
                        >
                            <Trash2 className="size-4" />
                            Delete Entry
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Regenerate Confirmation Dialog */}
            <Dialog
                open={confirmRegenerate}
                onOpenChange={setConfirmRegenerate}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Regenerate Image</DialogTitle>
                        <DialogDescription>
                            This will replace the existing image with a newly
                            generated one. Continue?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirmRegenerate(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            className="bg-moss text-moss-foreground hover:bg-moss/90"
                            disabled={generatingImage}
                            onClick={doGenerateImage}
                        >
                            {generatingImage ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <ImagePlus className="size-4" />
                            )}
                            Regenerate
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DevotionalLayout>
    );
}
