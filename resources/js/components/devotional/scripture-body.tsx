import {
    type BibleVersionKey,
    BIBLE_VERSIONS,
    getPreferredVersion,
    setPreferredVersion,
} from '@/lib/bible-versions';
import { cn } from '@/lib/utils';
import { show as showScripture } from '@/routes/scripture';
import { BookOpen, X } from 'lucide-react';
import {
    type ReactNode,
    useCallback,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';

/* ─────────────────────────────────────────────
   Types
   ───────────────────────────────────────────── */

export interface ParsedReference {
    book: string;
    chapter: number;
    verseStart: number;
    verseEnd: number | null;
    raw: string;
    parenthesized: boolean;
}

interface ScriptureBodyProps {
    body: string;
    className?: string;
}

/* ─────────────────────────────────────────────
   Reference Parsing
   ───────────────────────────────────────────── */

export const BOOK_PATTERN =
    '(?:(?:[123]\\s)?[A-Z][a-zA-Z]+(?:\\s(?:of\\s)?[A-Z][a-zA-Z]+)*)';
export const REF_REGEX = new RegExp(
    `\\(?\\s*(${BOOK_PATTERN})\\s+(\\d+):(\\d+)(?:[-–](\\d+))?\\s*\\)?`,
    'g',
);

/**
 * Filter out false positives — the book name must be a known Bible book.
 */
export const BIBLE_BOOKS = new Set([
    'Genesis',
    'Exodus',
    'Leviticus',
    'Numbers',
    'Deuteronomy',
    'Joshua',
    'Judges',
    'Ruth',
    '1 Samuel',
    '2 Samuel',
    '1 Kings',
    '2 Kings',
    '1 Chronicles',
    '2 Chronicles',
    'Ezra',
    'Nehemiah',
    'Esther',
    'Job',
    'Psalms',
    'Psalm',
    'Proverbs',
    'Ecclesiastes',
    'Song of Solomon',
    'Isaiah',
    'Jeremiah',
    'Lamentations',
    'Ezekiel',
    'Daniel',
    'Hosea',
    'Joel',
    'Amos',
    'Obadiah',
    'Jonah',
    'Micah',
    'Nahum',
    'Habakkuk',
    'Zephaniah',
    'Haggai',
    'Zechariah',
    'Malachi',
    'Matthew',
    'Mark',
    'Luke',
    'John',
    'Acts',
    'Romans',
    '1 Corinthians',
    '2 Corinthians',
    'Galatians',
    'Ephesians',
    'Philippians',
    'Colossians',
    '1 Thessalonians',
    '2 Thessalonians',
    '1 Timothy',
    '2 Timothy',
    'Titus',
    'Philemon',
    'Hebrews',
    'James',
    '1 Peter',
    '2 Peter',
    '1 John',
    '2 John',
    '3 John',
    'Jude',
    'Revelation',
]);

export function parseReference(match: RegExpExecArray): ParsedReference | null {
    if (!BIBLE_BOOKS.has(match[1])) {
        return null;
    }
    return {
        book: match[1],
        chapter: parseInt(match[2], 10),
        verseStart: parseInt(match[3], 10),
        verseEnd: match[4] ? parseInt(match[4], 10) : null,
        raw: match[0],
        parenthesized: match[0].startsWith('('),
    };
}

type TextSegment =
    | { type: 'text'; content: string }
    | { type: 'reference'; ref: ParsedReference };

function parseBodyText(text: string): TextSegment[] {
    const segments: TextSegment[] = [];
    let lastIndex = 0;

    REF_REGEX.lastIndex = 0;
    let match: RegExpExecArray | null;

    while ((match = REF_REGEX.exec(text)) !== null) {
        const ref = parseReference(match);
        if (ref === null) {
            continue;
        }
        if (match.index > lastIndex) {
            segments.push({
                type: 'text',
                content: text.slice(lastIndex, match.index),
            });
        }
        segments.push({ type: 'reference', ref });
        lastIndex = match.index + match[0].length;
    }

    if (lastIndex < text.length) {
        segments.push({ type: 'text', content: text.slice(lastIndex) });
    }

    return segments;
}

/* ─────────────────────────────────────────────
   Scripture Popover
   ───────────────────────────────────────────── */

export function ScripturePopover({
    reference,
    onClose,
    anchorRect,
}: {
    reference: ParsedReference;
    onClose: () => void;
    anchorRect: DOMRect;
}) {
    const [version, setVersion] =
        useState<BibleVersionKey>(getPreferredVersion);
    const [text, setText] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const popoverRef = useRef<HTMLDivElement>(null);
    const [position, setPosition] = useState({ top: 0, left: 0 });

    // Position the popover. We measure DOM after render and set state — that's
    // the documented use case for useLayoutEffect, but the lint rule fires on
    // any state set in an effect. Setting position any other way would race
    // the render.
    useLayoutEffect(() => {
        const popover = popoverRef.current;
        if (!popover) {
            return;
        }

        const updatePosition = () => {
            const popRect = popover.getBoundingClientRect();
            const viewW = window.innerWidth;
            const viewH = window.innerHeight;

            let top = anchorRect.bottom + 8;
            let left =
                anchorRect.left + anchorRect.width / 2 - popRect.width / 2;

            // Keep within horizontal bounds
            if (left < 12) {
                left = 12;
            }
            if (left + popRect.width > viewW - 12) {
                left = viewW - 12 - popRect.width;
            }

            // Flip above if no room below
            if (top + popRect.height > viewH - 12) {
                top = anchorRect.top - popRect.height - 8;
            }

            // eslint-disable-next-line @eslint-react/set-state-in-effect
            setPosition({ top, left });
        };

        updatePosition();

        // Re-measure after content loads
        const observer = new ResizeObserver(updatePosition);
        observer.observe(popover);
        return () => observer.disconnect();
    }, [anchorRect, text, loading]);

    // Close on outside click
    useEffect(() => {
        function handleClick(e: MouseEvent) {
            if (
                popoverRef.current &&
                !popoverRef.current.contains(e.target as Node)
            ) {
                onClose();
            }
        }
        function handleEscape(e: KeyboardEvent) {
            if (e.key === 'Escape') {
                onClose();
            }
        }
        document.addEventListener('mousedown', handleClick);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleClick);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [onClose]);

    // Fetch passage
    const fetchPassage = useCallback(
        async (ver: string, signal?: AbortSignal) => {
            setLoading(true);
            setText(null);

            const params: Record<string, string> = {
                book: reference.book,
                chapter: String(reference.chapter),
                verse_start: String(reference.verseStart),
                bible_version: ver,
            };
            if (reference.verseEnd !== null) {
                params.verse_end = String(reference.verseEnd);
            }

            try {
                const url = showScripture.url({ query: params });
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
                    setText('Unable to load this passage.');
                }
            } catch (err: unknown) {
                if (err instanceof DOMException && err.name === 'AbortError') {
                    return;
                }
                setText('Unable to load this passage.');
            } finally {
                if (!signal?.aborted) {
                    setLoading(false);
                }
            }
        },
        [reference],
    );

    useEffect(() => {
        const controller = new AbortController();
        fetchPassage(version, controller.signal);
        return () => controller.abort();
    }, [version, fetchPassage]);

    function handleVersionChange(ver: BibleVersionKey) {
        setVersion(ver);
        setPreferredVersion(ver);
    }

    const refLabel = `${reference.book} ${reference.chapter}:${reference.verseStart}${reference.verseEnd ? `\u2013${reference.verseEnd}` : ''}`;

    return (
        <div
            ref={popoverRef}
            role="dialog"
            aria-label={`Scripture: ${refLabel}`}
            className="fixed z-50 w-[340px] max-w-[calc(100vw-24px)] rounded-2xl border border-border/60 bg-surface-container-high shadow-ambient-lg animate-in fade-in-0 zoom-in-95"
            style={{ top: position.top, left: position.left }}
        >
            {/* Header */}
            <div className="flex items-center justify-between border-b border-border/40 px-5 py-3">
                <div className="flex items-center gap-2">
                    <BookOpen className="size-3.5 text-moss" />
                    <span className="text-xs font-bold tracking-[0.15em] text-on-surface uppercase">
                        {refLabel}
                    </span>
                </div>
                <button
                    type="button"
                    onClick={onClose}
                    className="flex size-6 items-center justify-center rounded-md text-on-surface-variant/50 transition-colors hover:bg-surface-container-highest hover:text-on-surface"
                >
                    <X className="size-3.5" />
                </button>
            </div>

            {/* Verse content */}
            <div className="px-5 py-4">
                {loading ? (
                    <div className="space-y-2">
                        <div className="h-4 w-full animate-pulse rounded bg-surface-container-highest" />
                        <div className="h-4 w-4/5 animate-pulse rounded bg-surface-container-highest" />
                        <div className="h-4 w-3/5 animate-pulse rounded bg-surface-container-highest" />
                    </div>
                ) : (
                    <p className="font-serif text-sm leading-relaxed text-on-surface/90 italic">
                        &ldquo;{text}&rdquo;
                    </p>
                )}
            </div>

            {/* Version selector */}
            <div className="border-t border-border/40 px-5 py-3">
                <div className="flex flex-wrap gap-1.5">
                    {BIBLE_VERSIONS.map((v) => (
                        <button
                            key={v.value}
                            type="button"
                            onClick={() =>
                                handleVersionChange(v.value as BibleVersionKey)
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
            </div>
        </div>
    );
}

/* ─────────────────────────────────────────────
   Inline Reference Link
   ───────────────────────────────────────────── */

function InlineReference({
    reference,
    isActive,
    onActivate,
}: {
    reference: ParsedReference;
    isActive: boolean;
    onActivate: (ref: ParsedReference, rect: DOMRect) => void;
}) {
    const spanRef = useRef<HTMLButtonElement>(null);

    function handleClick() {
        if (spanRef.current) {
            onActivate(reference, spanRef.current.getBoundingClientRect());
        }
    }

    const label = `${reference.book} ${reference.chapter}:${reference.verseStart}${reference.verseEnd ? `\u2013${reference.verseEnd}` : ''}`;

    return (
        <button
            ref={spanRef}
            type="button"
            onClick={handleClick}
            className={cn(
                'inline cursor-pointer border-b font-serif transition-colors duration-200',
                isActive
                    ? 'border-moss text-moss'
                    : 'border-moss/30 text-moss/80 hover:border-moss hover:text-moss',
            )}
        >
            {reference.parenthesized ? `(${label})` : label}
        </button>
    );
}

/* ─────────────────────────────────────────────
   Paragraph Renderer
   ───────────────────────────────────────────── */

function RenderedParagraph({
    text,
    isFirst,
    activeRef,
    onActivate,
}: {
    text: string;
    isFirst: boolean;
    activeRef: ParsedReference | null;
    onActivate: (ref: ParsedReference, rect: DOMRect) => void;
}) {
    const segments = parseBodyText(text);

    // The segments array is parsed from a fixed body string and never
    // reorders. Position-based keys are appropriate here.
    /* eslint-disable @eslint-react/no-array-index-key */
    const nodes: ReactNode[] = segments.map((seg, i) => {
        if (seg.type === 'text') {
            return <span key={i}>{seg.content}</span>;
        }
        return (
            <InlineReference
                key={i}
                reference={seg.ref}
                isActive={activeRef?.raw === seg.ref.raw}
                onActivate={onActivate}
            />
        );
    });
    /* eslint-enable @eslint-react/no-array-index-key */

    return (
        <p
            className={cn(
                'font-serif text-lg leading-relaxed text-on-surface/90 md:text-xl',
                isFirst &&
                    'first-letter:float-left first-letter:mr-2 first-letter:font-serif first-letter:text-5xl first-letter:leading-[0.8] first-letter:font-bold first-letter:text-moss/70 md:first-letter:text-6xl',
            )}
        >
            {nodes}
        </p>
    );
}

/* ─────────────────────────────────────────────
   Main Component
   ───────────────────────────────────────────── */

export function ScriptureBody({ body, className }: ScriptureBodyProps) {
    const [activeRef, setActiveRef] = useState<ParsedReference | null>(null);
    const [anchorRect, setAnchorRect] = useState<DOMRect | null>(null);

    const paragraphs = body
        .split(/\n\n+/)
        .map((p) => p.trim())
        .filter(Boolean);

    const handleActivate = useCallback(
        (ref: ParsedReference, rect: DOMRect) => {
            if (activeRef?.raw === ref.raw) {
                setActiveRef(null);
                setAnchorRect(null);
            } else {
                setActiveRef(ref);
                setAnchorRect(rect);
            }
        },
        [activeRef],
    );

    const handleClose = useCallback(() => {
        setActiveRef(null);
        setAnchorRect(null);
    }, []);

    return (
        <div className={cn('space-y-6', className)}>
            {paragraphs.map((paragraph, i) => (
                <RenderedParagraph
                    // Paragraphs are derived from a fixed body string and
                    // never reorder; index keys are correct here.
                    // eslint-disable-next-line @eslint-react/no-array-index-key
                    key={i}
                    text={paragraph}
                    isFirst={i === 0}
                    activeRef={activeRef}
                    onActivate={handleActivate}
                />
            ))}

            {activeRef && anchorRect && (
                <ScripturePopover
                    reference={activeRef}
                    onClose={handleClose}
                    anchorRect={anchorRect}
                />
            )}
        </div>
    );
}
