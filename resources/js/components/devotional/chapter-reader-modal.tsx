import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    type BibleVersionKey,
    BIBLE_VERSIONS,
    getPreferredVersion,
    setPreferredVersion,
} from '@/lib/bible-versions';
import { cn } from '@/lib/utils';
import { chapter as chapterRoute } from '@/routes/scripture';
import { BookOpen } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface ChapterReaderModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    book: string;
    chapter: number;
}

export function ChapterReaderModal({
    open,
    onOpenChange,
    book,
    chapter,
}: ChapterReaderModalProps) {
    const [version, setVersion] =
        useState<BibleVersionKey>(getPreferredVersion);
    const [text, setText] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchChapter = useCallback(
        async (ver: string, signal?: AbortSignal) => {
            setLoading(true);
            setText(null);

            try {
                const url = chapterRoute.url({
                    query: {
                        book,
                        chapter: String(chapter),
                        bible_version: ver,
                    },
                });

                const response = await fetch(url, {
                    signal,
                    headers: { Accept: 'application/json' },
                });

                if (response.ok) {
                    const data = await response.json();
                    if (!signal?.aborted) {
                        setText(data.text);
                    }
                } else if (!signal?.aborted) {
                    setText('Unable to load this chapter.');
                }
            } catch (err: unknown) {
                if (err instanceof DOMException && err.name === 'AbortError') {
                    return;
                }
                setText('Unable to load this chapter.');
            } finally {
                if (!signal?.aborted) {
                    setLoading(false);
                }
            }
        },
        [book, chapter],
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        fetchChapter(version, controller.signal);
        return () => controller.abort();
    }, [open, version, fetchChapter]);

    function handleVersionChange(ver: BibleVersionKey) {
        setVersion(ver);
        setPreferredVersion(ver);
    }

    const reference = `${book} ${chapter}`;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[85vh] max-w-3xl flex-col overflow-hidden p-0">
                <DialogHeader className="border-b border-border/40 px-6 pt-6 pb-4">
                    <div className="flex items-center gap-2">
                        <BookOpen className="size-4 text-moss" />
                        <DialogTitle className="font-serif text-xl tracking-tight">
                            {reference}
                        </DialogTitle>
                    </div>

                    {/* Version selector */}
                    <div className="flex flex-wrap gap-1.5 pt-2">
                        {BIBLE_VERSIONS.map((v) => (
                            <button
                                key={v.value}
                                type="button"
                                onClick={() =>
                                    handleVersionChange(
                                        v.value as BibleVersionKey,
                                    )
                                }
                                className={cn(
                                    'rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wider uppercase transition-all',
                                    version === v.value
                                        ? 'bg-moss text-moss-foreground'
                                        : 'bg-surface-container-highest/60 text-on-surface-variant/60 hover:bg-surface-container-highest hover:text-on-surface-variant',
                                )}
                            >
                                {v.value}
                            </button>
                        ))}
                    </div>
                </DialogHeader>

                <div className="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                    {loading ? (
                        <div className="space-y-3 py-8">
                            <div className="h-4 w-full animate-pulse rounded bg-surface-container-highest" />
                            <div className="h-4 w-11/12 animate-pulse rounded bg-surface-container-highest" />
                            <div className="h-4 w-4/5 animate-pulse rounded bg-surface-container-highest" />
                            <div className="h-4 w-full animate-pulse rounded bg-surface-container-highest" />
                            <div className="h-4 w-3/5 animate-pulse rounded bg-surface-container-highest" />
                            <div className="h-4 w-full animate-pulse rounded bg-surface-container-highest" />
                            <div className="h-4 w-10/12 animate-pulse rounded bg-surface-container-highest" />
                        </div>
                    ) : text ? (
                        <div className="font-serif text-lg leading-relaxed whitespace-pre-line text-on-surface/90">
                            {text}
                        </div>
                    ) : null}
                </div>
            </DialogContent>
        </Dialog>
    );
}
