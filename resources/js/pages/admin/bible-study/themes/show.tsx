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
import DevotionalLayout from '@/layouts/devotional-layout';
import { update as historicalContextUpdate } from '@/routes/admin/bible-study/passages/historical-context';
import { update as insightUpdate } from '@/routes/admin/bible-study/passages/insight';
import {
    destroy as wordHighlightDestroy,
    store as wordHighlightStore,
} from '@/routes/admin/bible-study/passages/word-highlights';
import {
    destroy as themeDestroy,
    publish as themePublish,
    index as themesIndex,
    update as themeUpdate,
} from '@/routes/admin/bible-study/themes';
import {
    destroy as passageDestroy,
    update as passageUpdate,
} from '@/routes/admin/bible-study/themes/passages';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Send, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

type ThemeStatus = 'draft' | 'approved' | 'archived';

type CrossRef = {
    book: string;
    chapter: number;
    verse_start: number;
    verse_end?: number;
    note?: string;
};

type WordStudy = {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
};

type WordHighlight = {
    id: number;
    verse_number: number;
    word_index_in_verse: number;
    display_word: string;
    word_study: WordStudy | null;
};

type Insight = {
    id: number;
    interpretation: string;
    application: string;
    cross_references: CrossRef[];
    literary_context: string;
};

type HistoricalContext = {
    id: number;
    setting: string;
    author: string;
    date_range: string;
    audience: string;
    historical_events: string;
};

type Passage = {
    id: number;
    position: number;
    is_guided_path: boolean;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    passage_intro: string | null;
    insight: Insight | null;
    historical_context: HistoricalContext | null;
    word_highlights: WordHighlight[];
};

type Theme = {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    long_intro: string;
    status: ThemeStatus;
    requested_count: number;
    approved_at: string | null;
    passages: Passage[];
};

interface Props {
    theme: Theme;
}

function ThemeStatusBadge({ status }: { status: ThemeStatus }) {
    if (status === 'approved') {
        return (
            <Badge className="border-moss/20 bg-moss/10 text-moss hover:bg-moss/10">
                Approved
            </Badge>
        );
    }

    if (status === 'archived') {
        return (
            <Badge
                variant="secondary"
                className="text-on-surface-variant/60 line-through"
            >
                Archived
            </Badge>
        );
    }

    return (
        <Badge variant="secondary" className="text-on-surface-variant">
            Draft
        </Badge>
    );
}

export default function BibleStudyThemeShow({ theme }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const metaForm = useForm({
        title: theme.title,
        short_description: theme.short_description,
        long_intro: theme.long_intro,
    });

    function saveMeta(e: FormEvent): void {
        e.preventDefault();
        metaForm.put(themeUpdate.url(theme.id));
    }

    function handlePublish(): void {
        router.put(themePublish.url(theme.id));
    }

    function handleDelete(): void {
        router.delete(themeDestroy.url(theme.id), {
            onFinish: () => setShowDeleteDialog(false),
        });
    }

    return (
        <DevotionalLayout>
            <Head title={`Review — ${theme.title}`} />

            <div className="px-6 py-6 md:px-8">
                <Link
                    href={themesIndex.url()}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to Themes
                </Link>

                {/* Page Header */}
                <div className="mt-4 flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Admin — Bible Study
                        </p>
                        <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                            {theme.title}
                        </h1>
                        <div className="mt-3 flex items-center gap-3">
                            <ThemeStatusBadge status={theme.status} />
                            <span className="text-sm text-on-surface-variant">
                                {theme.passages.length}{' '}
                                {theme.passages.length === 1
                                    ? 'passage'
                                    : 'passages'}
                            </span>
                            <span className="text-sm text-on-surface-variant">
                                {theme.requested_count} requests
                            </span>
                        </div>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {theme.status === 'draft' && (
                            <Button
                                className="bg-moss text-moss-foreground hover:bg-moss/90"
                                onClick={handlePublish}
                            >
                                <Send className="size-4" />
                                Publish
                            </Button>
                        )}
                        <Button
                            variant="destructive"
                            onClick={() => setShowDeleteDialog(true)}
                        >
                            <Trash2 className="size-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                {/* Meta Form */}
                <div className="mt-8 max-w-2xl rounded-lg border border-border bg-surface-container-low p-6">
                    <h2 className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                        Theme Metadata
                    </h2>
                    <form onSubmit={saveMeta} className="mt-4 space-y-4">
                        <div className="space-y-2">
                            <Label
                                htmlFor="title"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Title
                            </Label>
                            <Input
                                id="title"
                                value={metaForm.data.title}
                                onChange={(e) =>
                                    metaForm.setData('title', e.target.value)
                                }
                                className="font-serif text-lg"
                            />
                            {metaForm.errors.title && (
                                <p className="text-sm text-destructive">
                                    {metaForm.errors.title}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label
                                htmlFor="short_description"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Short Description
                            </Label>
                            <Input
                                id="short_description"
                                value={metaForm.data.short_description}
                                onChange={(e) =>
                                    metaForm.setData(
                                        'short_description',
                                        e.target.value,
                                    )
                                }
                            />
                            {metaForm.errors.short_description && (
                                <p className="text-sm text-destructive">
                                    {metaForm.errors.short_description}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label
                                htmlFor="long_intro"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Long Introduction
                            </Label>
                            <Textarea
                                id="long_intro"
                                value={metaForm.data.long_intro}
                                onChange={(e) =>
                                    metaForm.setData(
                                        'long_intro',
                                        e.target.value,
                                    )
                                }
                                rows={8}
                            />
                            {metaForm.errors.long_intro && (
                                <p className="text-sm text-destructive">
                                    {metaForm.errors.long_intro}
                                </p>
                            )}
                        </div>
                        <Button
                            type="submit"
                            disabled={metaForm.processing || !metaForm.isDirty}
                            className="bg-moss text-moss-foreground hover:bg-moss/90"
                        >
                            Save Metadata
                        </Button>
                    </form>
                </div>

                {/* Passages */}
                <div className="mt-10">
                    <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Passages
                    </h2>

                    {theme.passages.length === 0 ? (
                        <div className="mt-4 rounded-lg border border-dashed border-border bg-surface-container-low p-12 text-center">
                            <BookOpen className="mx-auto size-10 text-on-surface-variant/40" />
                            <p className="mt-4 font-serif text-xl text-on-surface-variant">
                                No passages yet
                            </p>
                            <p className="mt-2 text-sm text-on-surface-variant">
                                Passages will appear here once they are added to
                                this theme.
                            </p>
                        </div>
                    ) : (
                        <div className="mt-4 space-y-6">
                            {theme.passages.map((passage) => (
                                <PassageBlock
                                    key={passage.id}
                                    themeId={theme.id}
                                    passage={passage}
                                />
                            ))}
                        </div>
                    )}
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
                                "{theme.title}"
                            </span>
                            ? All associated passages and content will be
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
        </DevotionalLayout>
    );
}

function PassageBlock({
    themeId,
    passage,
}: {
    themeId: number;
    passage: Passage;
}) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const passageForm = useForm({
        position: passage.position,
        is_guided_path: passage.is_guided_path,
        book: passage.book,
        chapter: passage.chapter,
        verse_start: passage.verse_start,
        verse_end: passage.verse_end ?? ('' as number | ''),
        passage_intro: passage.passage_intro ?? '',
    });

    function save(e: FormEvent): void {
        e.preventDefault();
        passageForm.put(passageUpdate.url([themeId, passage.id]));
    }

    function handleDelete(): void {
        router.delete(passageDestroy.url([themeId, passage.id]), {
            onFinish: () => setShowDeleteDialog(false),
        });
    }

    return (
        <div className="rounded-lg border border-border bg-surface-container-low">
            {/* Passage header */}
            <div className="flex items-center justify-between border-b border-border px-6 py-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                        Position {passage.position}
                    </p>
                    <h3 className="mt-1 font-serif text-xl font-medium text-on-surface">
                        {passage.book} {passage.chapter}:{passage.verse_start}
                        {passage.verse_end ? `–${passage.verse_end}` : ''}
                    </h3>
                    {passage.is_guided_path && (
                        <Badge className="mt-1 border-moss/20 bg-moss/10 text-moss hover:bg-moss/10">
                            Guided Path
                        </Badge>
                    )}
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                    onClick={() => setShowDeleteDialog(true)}
                >
                    <Trash2 className="size-4" />
                    Remove
                </Button>
            </div>

            <div className="space-y-6 p-6">
                {/* Passage meta form */}
                <form onSubmit={save} className="space-y-4">
                    <h4 className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                        Passage Reference
                    </h4>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div className="space-y-1">
                            <Label
                                htmlFor={`book-${passage.id}`}
                                className="text-xs text-on-surface-variant"
                            >
                                Book
                            </Label>
                            <Input
                                id={`book-${passage.id}`}
                                value={passageForm.data.book}
                                onChange={(e) =>
                                    passageForm.setData('book', e.target.value)
                                }
                                placeholder="Genesis"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label
                                htmlFor={`chapter-${passage.id}`}
                                className="text-xs text-on-surface-variant"
                            >
                                Chapter
                            </Label>
                            <Input
                                id={`chapter-${passage.id}`}
                                type="number"
                                value={passageForm.data.chapter}
                                onChange={(e) =>
                                    passageForm.setData(
                                        'chapter',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-1">
                            <Label
                                htmlFor={`verse_start-${passage.id}`}
                                className="text-xs text-on-surface-variant"
                            >
                                Verse Start
                            </Label>
                            <Input
                                id={`verse_start-${passage.id}`}
                                type="number"
                                value={passageForm.data.verse_start}
                                onChange={(e) =>
                                    passageForm.setData(
                                        'verse_start',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-1">
                            <Label
                                htmlFor={`verse_end-${passage.id}`}
                                className="text-xs text-on-surface-variant"
                            >
                                Verse End
                            </Label>
                            <Input
                                id={`verse_end-${passage.id}`}
                                type="number"
                                value={passageForm.data.verse_end}
                                onChange={(e) =>
                                    passageForm.setData(
                                        'verse_end',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                placeholder="optional"
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                            <Label
                                htmlFor={`position-${passage.id}`}
                                className="text-xs text-on-surface-variant"
                            >
                                Position
                            </Label>
                            <Input
                                id={`position-${passage.id}`}
                                type="number"
                                value={passageForm.data.position}
                                onChange={(e) =>
                                    passageForm.setData(
                                        'position',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="flex items-end pb-0.5">
                            <label className="flex cursor-pointer items-center gap-2 text-sm text-on-surface">
                                <input
                                    type="checkbox"
                                    className="size-4 rounded accent-moss"
                                    checked={passageForm.data.is_guided_path}
                                    onChange={(e) =>
                                        passageForm.setData(
                                            'is_guided_path',
                                            e.target.checked,
                                        )
                                    }
                                />
                                Guided Path
                            </label>
                        </div>
                    </div>
                    <div className="space-y-1">
                        <Label
                            htmlFor={`passage_intro-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Passage Intro
                        </Label>
                        <Textarea
                            id={`passage_intro-${passage.id}`}
                            value={passageForm.data.passage_intro}
                            onChange={(e) =>
                                passageForm.setData(
                                    'passage_intro',
                                    e.target.value,
                                )
                            }
                            placeholder="Brief intro for this passage…"
                            rows={4}
                        />
                    </div>
                    <Button
                        type="submit"
                        disabled={
                            passageForm.processing || !passageForm.isDirty
                        }
                        className="bg-moss text-moss-foreground hover:bg-moss/90"
                    >
                        Save Passage
                    </Button>
                </form>

                <InsightEditor passage={passage} />
                <HistoricalEditor passage={passage} />
                <WordHighlightsEditor passage={passage} />
            </div>

            {/* Delete passage dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove Passage</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove{' '}
                            <span className="font-medium text-on-surface">
                                {passage.book} {passage.chapter}:
                                {passage.verse_start}
                                {passage.verse_end
                                    ? `–${passage.verse_end}`
                                    : ''}
                            </span>
                            ? All associated insights, historical context, and
                            word highlights will be permanently deleted.
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
                            Remove Passage
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function InsightEditor({ passage }: { passage: Passage }) {
    const form = useForm({
        interpretation: passage.insight?.interpretation ?? '',
        application: passage.insight?.application ?? '',
        cross_references: passage.insight?.cross_references ?? [],
        literary_context: passage.insight?.literary_context ?? '',
    });

    function save(e: FormEvent): void {
        e.preventDefault();
        form.put(insightUpdate.url(passage.id));
    }

    return (
        <div className="border-t border-border pt-6">
            <h4 className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                Insight
            </h4>
            <form onSubmit={save} className="mt-4 space-y-4">
                <div className="space-y-1">
                    <Label
                        htmlFor={`interpretation-${passage.id}`}
                        className="text-xs text-on-surface-variant"
                    >
                        Interpretation
                    </Label>
                    <Textarea
                        id={`interpretation-${passage.id}`}
                        value={form.data.interpretation}
                        onChange={(e) =>
                            form.setData('interpretation', e.target.value)
                        }
                        placeholder="Scholarly interpretation of this passage…"
                        rows={5}
                    />
                    {form.errors.interpretation && (
                        <p className="text-sm text-destructive">
                            {form.errors.interpretation}
                        </p>
                    )}
                </div>
                <div className="space-y-1">
                    <Label
                        htmlFor={`application-${passage.id}`}
                        className="text-xs text-on-surface-variant"
                    >
                        Application
                    </Label>
                    <Textarea
                        id={`application-${passage.id}`}
                        value={form.data.application}
                        onChange={(e) =>
                            form.setData('application', e.target.value)
                        }
                        placeholder="Practical application for today…"
                        rows={5}
                    />
                    {form.errors.application && (
                        <p className="text-sm text-destructive">
                            {form.errors.application}
                        </p>
                    )}
                </div>
                <div className="space-y-1">
                    <Label
                        htmlFor={`literary_context-${passage.id}`}
                        className="text-xs text-on-surface-variant"
                    >
                        Literary Context
                    </Label>
                    <Textarea
                        id={`literary_context-${passage.id}`}
                        value={form.data.literary_context}
                        onChange={(e) =>
                            form.setData('literary_context', e.target.value)
                        }
                        placeholder="Literary context and genre notes…"
                        rows={4}
                    />
                    {form.errors.literary_context && (
                        <p className="text-sm text-destructive">
                            {form.errors.literary_context}
                        </p>
                    )}
                </div>
                <Button
                    type="submit"
                    disabled={form.processing || !form.isDirty}
                    className="bg-moss text-moss-foreground hover:bg-moss/90"
                >
                    Save Insight
                </Button>
            </form>
        </div>
    );
}

function HistoricalEditor({ passage }: { passage: Passage }) {
    const form = useForm({
        setting: passage.historical_context?.setting ?? '',
        author: passage.historical_context?.author ?? '',
        date_range: passage.historical_context?.date_range ?? '',
        audience: passage.historical_context?.audience ?? '',
        historical_events: passage.historical_context?.historical_events ?? '',
    });

    function save(e: FormEvent): void {
        e.preventDefault();
        form.put(historicalContextUpdate.url(passage.id));
    }

    return (
        <div className="border-t border-border pt-6">
            <h4 className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                Historical Context
            </h4>
            <form onSubmit={save} className="mt-4 space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Label
                            htmlFor={`setting-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Setting
                        </Label>
                        <Input
                            id={`setting-${passage.id}`}
                            value={form.data.setting}
                            onChange={(e) =>
                                form.setData('setting', e.target.value)
                            }
                            placeholder="Geographic or cultural setting"
                        />
                        {form.errors.setting && (
                            <p className="text-sm text-destructive">
                                {form.errors.setting}
                            </p>
                        )}
                    </div>
                    <div className="space-y-1">
                        <Label
                            htmlFor={`author-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Author
                        </Label>
                        <Input
                            id={`author-${passage.id}`}
                            value={form.data.author}
                            onChange={(e) =>
                                form.setData('author', e.target.value)
                            }
                            placeholder="Book author"
                        />
                        {form.errors.author && (
                            <p className="text-sm text-destructive">
                                {form.errors.author}
                            </p>
                        )}
                    </div>
                    <div className="space-y-1">
                        <Label
                            htmlFor={`date_range-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Date Range
                        </Label>
                        <Input
                            id={`date_range-${passage.id}`}
                            value={form.data.date_range}
                            onChange={(e) =>
                                form.setData('date_range', e.target.value)
                            }
                            placeholder="e.g. 586–539 BC"
                        />
                        {form.errors.date_range && (
                            <p className="text-sm text-destructive">
                                {form.errors.date_range}
                            </p>
                        )}
                    </div>
                    <div className="space-y-1">
                        <Label
                            htmlFor={`audience-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Audience
                        </Label>
                        <Input
                            id={`audience-${passage.id}`}
                            value={form.data.audience}
                            onChange={(e) =>
                                form.setData('audience', e.target.value)
                            }
                            placeholder="Original audience"
                        />
                        {form.errors.audience && (
                            <p className="text-sm text-destructive">
                                {form.errors.audience}
                            </p>
                        )}
                    </div>
                </div>
                <div className="space-y-1">
                    <Label
                        htmlFor={`historical_events-${passage.id}`}
                        className="text-xs text-on-surface-variant"
                    >
                        Historical Events
                    </Label>
                    <Textarea
                        id={`historical_events-${passage.id}`}
                        value={form.data.historical_events}
                        onChange={(e) =>
                            form.setData('historical_events', e.target.value)
                        }
                        placeholder="Relevant historical events and background…"
                        rows={4}
                    />
                    {form.errors.historical_events && (
                        <p className="text-sm text-destructive">
                            {form.errors.historical_events}
                        </p>
                    )}
                </div>
                <Button
                    type="submit"
                    disabled={form.processing || !form.isDirty}
                    className="bg-moss text-moss-foreground hover:bg-moss/90"
                >
                    Save Context
                </Button>
            </form>
        </div>
    );
}

function WordHighlightsEditor({ passage }: { passage: Passage }) {
    const addForm = useForm<{
        word_study_id: number | '';
        verse_number: number | '';
        word_index_in_verse: number | '';
        display_word: string;
    }>({
        word_study_id: '',
        verse_number: '',
        word_index_in_verse: '',
        display_word: '',
    });

    function add(e: FormEvent): void {
        e.preventDefault();
        addForm.post(wordHighlightStore.url(passage.id), {
            onSuccess: () => addForm.reset(),
        });
    }

    function remove(highlightId: number): void {
        router.delete(wordHighlightDestroy.url([passage.id, highlightId]));
    }

    return (
        <div className="border-t border-border pt-6">
            <h4 className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                Word Highlights
            </h4>

            {passage.word_highlights.length === 0 ? (
                <p className="mt-3 text-sm text-on-surface-variant/60 italic">
                    No word highlights added yet.
                </p>
            ) : (
                <ul className="mt-3 divide-y divide-border rounded-lg border border-border">
                    {passage.word_highlights.map((h) => (
                        <li
                            key={h.id}
                            className="flex items-center justify-between px-4 py-3 text-sm"
                        >
                            <span className="text-on-surface">
                                <span className="text-xs text-on-surface-variant">
                                    v{h.verse_number} idx{' '}
                                    {h.word_index_in_verse}
                                </span>{' '}
                                —{' '}
                                <strong className="font-medium">
                                    {h.display_word}
                                </strong>
                                {h.word_study &&
                                    ` (${h.word_study.transliteration}, ${h.word_study.strongs_number})`}
                            </span>
                            <button
                                type="button"
                                onClick={() => remove(h.id)}
                                className="ml-4 shrink-0 text-xs text-destructive hover:underline"
                            >
                                Remove
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <form onSubmit={add} className="mt-4 space-y-3">
                <h5 className="text-xs text-on-surface-variant">
                    Add word highlight
                </h5>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div className="space-y-1">
                        <Label
                            htmlFor={`wh-word_study_id-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Word Study ID
                        </Label>
                        <Input
                            id={`wh-word_study_id-${passage.id}`}
                            type="number"
                            placeholder="optional"
                            value={addForm.data.word_study_id}
                            onChange={(e) =>
                                addForm.setData(
                                    'word_study_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                        />
                    </div>
                    <div className="space-y-1">
                        <Label
                            htmlFor={`wh-verse-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Verse
                        </Label>
                        <Input
                            id={`wh-verse-${passage.id}`}
                            type="number"
                            placeholder="e.g. 1"
                            value={addForm.data.verse_number}
                            onChange={(e) =>
                                addForm.setData(
                                    'verse_number',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                        />
                    </div>
                    <div className="space-y-1">
                        <Label
                            htmlFor={`wh-word_idx-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Word Index
                        </Label>
                        <Input
                            id={`wh-word_idx-${passage.id}`}
                            type="number"
                            placeholder="e.g. 0"
                            value={addForm.data.word_index_in_verse}
                            onChange={(e) =>
                                addForm.setData(
                                    'word_index_in_verse',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                        />
                    </div>
                    <div className="space-y-1">
                        <Label
                            htmlFor={`wh-display_word-${passage.id}`}
                            className="text-xs text-on-surface-variant"
                        >
                            Display Word
                        </Label>
                        <Input
                            id={`wh-display_word-${passage.id}`}
                            placeholder="e.g. grace"
                            value={addForm.data.display_word}
                            onChange={(e) =>
                                addForm.setData('display_word', e.target.value)
                            }
                        />
                    </div>
                </div>
                {(addForm.errors.word_study_id ||
                    addForm.errors.verse_number ||
                    addForm.errors.word_index_in_verse ||
                    addForm.errors.display_word) && (
                    <div className="space-y-1 text-sm text-destructive">
                        {addForm.errors.word_study_id && (
                            <p>{addForm.errors.word_study_id}</p>
                        )}
                        {addForm.errors.verse_number && (
                            <p>{addForm.errors.verse_number}</p>
                        )}
                        {addForm.errors.word_index_in_verse && (
                            <p>{addForm.errors.word_index_in_verse}</p>
                        )}
                        {addForm.errors.display_word && (
                            <p>{addForm.errors.display_word}</p>
                        )}
                    </div>
                )}
                <Button
                    type="submit"
                    variant="outline"
                    disabled={addForm.processing}
                >
                    Add Highlight
                </Button>
            </form>
        </div>
    );
}
