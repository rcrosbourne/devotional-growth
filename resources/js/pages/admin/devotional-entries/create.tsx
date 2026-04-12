import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { index as themesIndex } from '@/routes/admin/themes';
import {
    index as entriesIndex,
    store as entriesStore,
} from '@/routes/admin/themes/entries';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Plus, X } from 'lucide-react';
import { type FormEventHandler, useRef, useState } from 'react';

interface ScriptureRefItem {
    key: number;
    value: string;
}

interface Theme {
    id: number;
    name: string;
}

interface Props {
    theme: Theme;
}

export default function CreateEntry({ theme }: Props) {
    const nextKeyRef = useRef(1);
    const [refItems, setRefItems] = useState<ScriptureRefItem[]>([
        { key: 0, value: '' },
    ]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Themes', href: themesIndex.url() },
        { title: theme.name, href: entriesIndex.url(theme.id) },
        { title: 'New Entry', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        body: '',
        scripture_references: [''] as string[],
        reflection_prompts: '',
        adventist_insights: '',
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post(entriesStore.url(theme.id));
    };

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`New Entry - ${theme.name}`} />

            <div className="px-6 py-6 md:px-8">
                <Link
                    href={entriesIndex.url(theme.id)}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to {theme.name}
                </Link>

                <div className="mt-4">
                    <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Manual Entry
                    </p>
                    <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface">
                        Create New Devotional
                    </h1>
                </div>

                <form onSubmit={handleSubmit} className="mt-8">
                    <div className="grid gap-8 lg:grid-cols-3">
                        {/* Main content — left 2/3 */}
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
                                    placeholder="The Silence of the Morning..."
                                    className="font-serif text-lg"
                                    autoFocus
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
                                    placeholder="Begin the narrative journey..."
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
                                    placeholder="How does stillness change your perspective on control?&#10;What part of today's scripture challenged your priorities?"
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

                        {/* Sidebar — right 1/3 */}
                        <div className="space-y-6">
                            {/* Publication Status */}
                            <div className="rounded-lg border border-border bg-surface-container-low p-5">
                                <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                    Publication Status
                                </p>
                                <p className="mt-2 font-serif text-lg text-on-surface">
                                    Unsaved Draft
                                </p>
                                <div className="mt-4 space-y-2">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full bg-moss text-moss-foreground hover:bg-moss/90"
                                    >
                                        Save as Draft
                                    </Button>
                                    <Button
                                        variant="outline"
                                        asChild
                                        className="w-full"
                                    >
                                        <Link href={entriesIndex.url(theme.id)}>
                                            Cancel
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
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
