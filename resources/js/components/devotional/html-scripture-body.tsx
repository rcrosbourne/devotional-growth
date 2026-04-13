import { cn } from '@/lib/utils';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
    BIBLE_BOOKS,
    type ParsedReference,
    ScripturePopover,
} from './scripture-body';

interface HtmlScriptureBodyProps {
    html: string;
    className?: string;
}

/* ─────────────────────────────────────────────
   Abbreviation map → canonical book name
   ───────────────────────────────────────────── */

const BOOK_ABBREVIATIONS: Record<string, string> = {
    'Gen.': 'Genesis',
    'Exod.': 'Exodus',
    'Ex.': 'Exodus',
    'Lev.': 'Leviticus',
    'Num.': 'Numbers',
    'Deut.': 'Deuteronomy',
    'Josh.': 'Joshua',
    'Judg.': 'Judges',
    '1 Sam.': '1 Samuel',
    '2 Sam.': '2 Samuel',
    '1 Kgs.': '1 Kings',
    '2 Kgs.': '2 Kings',
    '1 Chron.': '1 Chronicles',
    '2 Chron.': '2 Chronicles',
    'Neh.': 'Nehemiah',
    'Est.': 'Esther',
    'Ps.': 'Psalms',
    'Pss.': 'Psalms',
    'Prov.': 'Proverbs',
    'Eccles.': 'Ecclesiastes',
    'Isa.': 'Isaiah',
    'Jer.': 'Jeremiah',
    'Lam.': 'Lamentations',
    'Ezek.': 'Ezekiel',
    'Dan.': 'Daniel',
    'Hos.': 'Hosea',
    'Mic.': 'Micah',
    'Nah.': 'Nahum',
    'Hab.': 'Habakkuk',
    'Zeph.': 'Zephaniah',
    'Hag.': 'Haggai',
    'Zech.': 'Zechariah',
    'Mal.': 'Malachi',
    'Matt.': 'Matthew',
    'Rom.': 'Romans',
    '1 Cor.': '1 Corinthians',
    '2 Cor.': '2 Corinthians',
    'Gal.': 'Galatians',
    'Eph.': 'Ephesians',
    'Phil.': 'Philippians',
    'Col.': 'Colossians',
    '1 Thess.': '1 Thessalonians',
    '2 Thess.': '2 Thessalonians',
    '1 Tim.': '1 Timothy',
    '2 Tim.': '2 Timothy',
    'Heb.': 'Hebrews',
    'Jas.': 'James',
    '1 Pet.': '1 Peter',
    '2 Pet.': '2 Peter',
    '1 Jn.': '1 John',
    '2 Jn.': '2 John',
    '3 Jn.': '3 John',
    'Rev.': 'Revelation',
};

/* ─────────────────────────────────────────────
   Build regex from known book names
   ───────────────────────────────────────────── */

function escapeForRegex(s: string): string {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Combine full names + abbreviations, sort longest-first so e.g. "1 Corinthians" matches before "Corinthians"
const ALL_BOOK_NAMES = [
    ...BIBLE_BOOKS,
    ...Object.keys(BOOK_ABBREVIATIONS),
].sort((a, b) => b.length - a.length);

const BOOK_ALTERNATION = ALL_BOOK_NAMES.map(escapeForRegex).join('|');

const HTML_REF_REGEX = new RegExp(
    `\\(?(${BOOK_ALTERNATION})\\s+(\\d+):(\\d+)(?:[-–](\\d+))?\\)?`,
    'g',
);

function resolveBook(matched: string): string {
    return BOOK_ABBREVIATIONS[matched] ?? matched;
}

/**
 * Extract all unique scripture references from an HTML string.
 * Returns ParsedReference[] suitable for building a references panel.
 */
export function extractReferencesFromHtml(html: string): ParsedReference[] {
    const text = html.replace(/<[^>]+>/g, ' ');
    const regex = new RegExp(HTML_REF_REGEX.source, HTML_REF_REGEX.flags);
    regex.lastIndex = 0;

    const seen = new Set<string>();
    const refs: ParsedReference[] = [];
    let match: RegExpExecArray | null;

    while ((match = regex.exec(text)) !== null) {
        const book = resolveBook(match[1]);
        if (!BIBLE_BOOKS.has(book)) {
            continue;
        }
        const key = `${book} ${match[2]}:${match[3]}${match[4] ? `-${match[4]}` : ''}`;
        if (seen.has(key)) {
            continue;
        }
        seen.add(key);
        refs.push({
            book,
            chapter: parseInt(match[2], 10),
            verseStart: parseInt(match[3], 10),
            verseEnd: match[4] ? parseInt(match[4], 10) : null,
            raw: match[0],
            parenthesized: match[0].startsWith('('),
        });
    }

    return refs;
}

/* ─────────────────────────────────────────────
   DOM processing
   ───────────────────────────────────────────── */

function processTextNode(node: Text): boolean {
    const text = node.textContent || '';

    const regex = new RegExp(HTML_REF_REGEX.source, HTML_REF_REGEX.flags);
    regex.lastIndex = 0;

    const matches: {
        start: number;
        end: number;
        ref: ParsedReference;
    }[] = [];
    let match: RegExpExecArray | null;

    while ((match = regex.exec(text)) !== null) {
        const book = resolveBook(match[1]);
        if (!BIBLE_BOOKS.has(book)) {
            continue;
        }

        matches.push({
            start: match.index,
            end: match.index + match[0].length,
            ref: {
                book,
                chapter: parseInt(match[2], 10),
                verseStart: parseInt(match[3], 10),
                verseEnd: match[4] ? parseInt(match[4], 10) : null,
                raw: match[0],
                parenthesized: match[0].startsWith('('),
            },
        });
    }

    if (matches.length === 0) {
        return false;
    }

    const fragment = document.createDocumentFragment();
    let lastIndex = 0;

    for (const { start, end, ref } of matches) {
        if (start > lastIndex) {
            fragment.appendChild(
                document.createTextNode(text.slice(lastIndex, start)),
            );
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className =
            'inline cursor-pointer border-b border-moss/30 font-serif text-moss/80 transition-colors duration-200 hover:border-moss hover:text-moss';
        button.setAttribute('data-scripture-ref', JSON.stringify(ref));
        const label = `${ref.book} ${ref.chapter}:${ref.verseStart}${ref.verseEnd ? `\u2013${ref.verseEnd}` : ''}`;
        button.textContent = ref.parenthesized ? `(${label})` : label;
        fragment.appendChild(button);

        lastIndex = end;
    }

    if (lastIndex < text.length) {
        fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
    }

    node.replaceWith(fragment);
    return true;
}

function classifyParagraphs(container: HTMLElement): void {
    const emphasisSpans =
        container.querySelectorAll<HTMLElement>('.a-emphasis');
    const specialParagraphs = new Set<Element>();

    emphasisSpans.forEach((span) => {
        const p = span.closest('p');
        if (!p) {
            return;
        }
        const text = span.textContent || '';
        if (text.includes('Read for This Week')) {
            p.classList.add('read-for-study');
            specialParagraphs.add(p);
        } else if (text.includes('Memory Text')) {
            p.classList.add('memory-text');
            specialParagraphs.add(p);
        }
    });

    // Mark scripture-prompt and study-note as special
    container
        .querySelectorAll('.scripture-prompt, .study-note, .reflection')
        .forEach((el) => specialParagraphs.add(el));

    // Add drop cap to the first regular body paragraph
    const paragraphs = container.querySelectorAll('p');
    for (const p of paragraphs) {
        if (
            !specialParagraphs.has(p) &&
            (p.textContent || '').trim().length > 0
        ) {
            p.classList.add('first-body-para');
            break;
        }
    }
}

function processContainer(container: HTMLElement): void {
    // Classify paragraphs for CSS styling (read-for-study, memory-text, drop cap)
    classifyParagraphs(container);

    // Replace <a class="bibleref"> with spans so their text becomes processable
    const biblerefLinks = container.querySelectorAll('a.bibleref');
    biblerefLinks.forEach((link) => {
        const span = document.createElement('span');
        span.innerHTML = link.innerHTML;
        link.replaceWith(span);
    });

    // Also strip href-less <a> tags that wrap scripture text
    const deadLinks = container.querySelectorAll('a:not([href])');
    deadLinks.forEach((link) => {
        const span = document.createElement('span');
        span.innerHTML = link.innerHTML;
        link.replaceWith(span);
    });

    // Collect all text nodes first (can't modify during iteration)
    const walker = document.createTreeWalker(
        container,
        NodeFilter.SHOW_TEXT,
        null,
    );
    const textNodes: Text[] = [];
    while (walker.nextNode()) {
        textNodes.push(walker.currentNode as Text);
    }

    textNodes.forEach(processTextNode);
}

/* ─────────────────────────────────────────────
   Component
   ───────────────────────────────────────────── */

export function HtmlScriptureBody({ html, className }: HtmlScriptureBodyProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [activeRef, setActiveRef] = useState<ParsedReference | null>(null);
    const [anchorRect, setAnchorRect] = useState<DOMRect | null>(null);

    // Set innerHTML and process scripture references imperatively.
    // By NOT using dangerouslySetInnerHTML, React won't reset the container
    // on re-renders caused by popover state changes.
    useEffect(() => {
        const container = containerRef.current;
        if (!container) {
            return;
        }
        container.innerHTML = html;
        processContainer(container);
    }, [html]);

    // Delegated click handler for scripture ref buttons
    useEffect(() => {
        const container = containerRef.current;
        if (!container) {
            return;
        }

        function handleClick(e: MouseEvent) {
            const button = (e.target as HTMLElement).closest(
                '[data-scripture-ref]',
            );
            if (!button) {
                return;
            }

            const ref: ParsedReference = JSON.parse(
                button.getAttribute('data-scripture-ref')!,
            );
            const rect = button.getBoundingClientRect();

            setActiveRef((prev) => {
                if (prev?.raw === ref.raw) {
                    setAnchorRect(null);
                    return null;
                }
                setAnchorRect(rect);
                return ref;
            });
        }

        container.addEventListener('click', handleClick);
        return () => container.removeEventListener('click', handleClick);
    }, []);

    const handleClose = useCallback(() => {
        setActiveRef(null);
        setAnchorRect(null);
    }, []);

    return (
        <div className={cn('relative', className)}>
            <div ref={containerRef} />
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
