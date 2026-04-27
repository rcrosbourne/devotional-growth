import { ReflectionComposer } from '@/components/bible-study/reflection-composer';
import { WordStudySheet } from '@/components/bible-study/word-study-sheet';
import { cn } from '@/lib/utils';
import type { Reflection } from '@/pages/bible-study/passage';
import { useState } from 'react';

interface WordStudy {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
}

interface WordHighlight {
    id: number;
    verse_number: number;
    word_index_in_verse: number;
    display_word: string;
    word_study: WordStudy | null;
}

interface PassageRef {
    themeId: number | null;
    book: string;
    chapter: number;
    verseStart: number;
    verseEnd: number | null;
}

interface Props {
    verses: Record<number, string>;
    structured: boolean;
    wordHighlights: WordHighlight[];
    reflections: Reflection[];
    partnerEnabled: boolean;
    passageRef: PassageRef;
}

export function ScriptureReader({
    verses,
    structured,
    wordHighlights,
    reflections,
    partnerEnabled,
    passageRef,
}: Props) {
    const [activeWord, setActiveWord] = useState<WordStudy | null>(null);
    const [activeVerse, setActiveVerse] = useState<number | null>(null);

    const verseNumbers = Object.keys(verses)
        .map((n) => Number(n))
        .sort((a, b) => a - b);

    return (
        <div className="space-y-3 font-serif text-lg leading-relaxed text-on-surface md:text-xl">
            {verseNumbers.map((vn) => {
                const text = verses[vn];
                const tokens = text.split(/(\s+)/);
                const verseHighlights = wordHighlights.filter(
                    (h) => h.verse_number === vn,
                );

                return (
                    <div key={vn} className="group relative">
                        {structured && (
                            <button
                                type="button"
                                onClick={() => setActiveVerse(vn)}
                                className="float-left mt-1.5 mr-2 inline-block text-xs font-bold text-moss/70 transition-colors hover:text-moss"
                                aria-label={`Add note on verse ${vn}`}
                            >
                                {vn}
                            </button>
                        )}
                        <p className="inline">
                            {tokens.map((tok, idx) => {
                                if (/^\s+$/.test(tok)) {
                                    return tok;
                                }
                                const wordIdx = tokensToWordIndex(tokens, idx);
                                const highlight = verseHighlights.find(
                                    (h) => h.word_index_in_verse === wordIdx,
                                );
                                if (highlight && highlight.word_study) {
                                    const ws = highlight.word_study;
                                    return (
                                        <button
                                            // eslint-disable-next-line @eslint-react/no-array-index-key
                                            key={idx}
                                            type="button"
                                            onClick={() => setActiveWord(ws)}
                                            className={cn(
                                                'inline border-b border-dashed border-moss/60 bg-moss/10 px-0.5 transition-colors',
                                                'hover:bg-moss/20',
                                            )}
                                        >
                                            {tok}
                                        </button>
                                    );
                                }
                                return (
                                    <span
                                        // eslint-disable-next-line @eslint-react/no-array-index-key
                                        key={idx}
                                    >
                                        {tok}
                                    </span>
                                );
                            })}
                        </p>

                        {activeVerse === vn && (
                            <div className="mt-3 rounded-lg border border-border bg-surface-container-low p-4">
                                <ReflectionComposer
                                    scope="verse"
                                    partnerEnabled={partnerEnabled}
                                    passageRef={{
                                        themeId: passageRef.themeId,
                                        book: passageRef.book,
                                        chapter: passageRef.chapter,
                                        verseStart: passageRef.verseStart,
                                        verseEnd: passageRef.verseEnd,
                                        verseNumber: vn,
                                    }}
                                    existing={
                                        reflections.find(
                                            (r) =>
                                                r.is_own &&
                                                r.verse_number === vn,
                                        ) ?? null
                                    }
                                    onClose={() => setActiveVerse(null)}
                                />
                                <VerseAnnotations
                                    reflections={reflections.filter(
                                        (r) => r.verse_number === vn,
                                    )}
                                />
                            </div>
                        )}
                    </div>
                );
            })}

            <WordStudySheet
                wordStudy={activeWord}
                onClose={() => setActiveWord(null)}
            />
        </div>
    );
}

function VerseAnnotations({ reflections }: { reflections: Reflection[] }) {
    if (reflections.length === 0) {
        return null;
    }
    return (
        <ul className="mt-3 space-y-2 border-t border-border pt-3 text-sm">
            {reflections.map((r) => (
                <li
                    key={r.id}
                    className="rounded-md bg-surface-container-lowest p-2 text-on-surface-variant"
                >
                    <div className="text-xs tracking-wide text-on-surface-variant/70 uppercase">
                        {r.is_own ? 'You' : (r.user_name ?? 'Partner')}
                    </div>
                    <p className="mt-1 text-on-surface">{r.body}</p>
                </li>
            ))}
        </ul>
    );
}

function tokensToWordIndex(tokens: string[], index: number): number {
    let count = 0;
    for (let i = 0; i < index; i++) {
        if (!/^\s+$/.test(tokens[i])) {
            count++;
        }
    }
    return count;
}
