import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';

interface WordStudy {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
}

interface Props {
    wordStudy: WordStudy | null;
    onClose: () => void;
}

export function WordStudySheet({ wordStudy, onClose }: Props) {
    if (wordStudy === null) {
        return null;
    }

    return (
        <>
            <button
                type="button"
                aria-label="Close word study"
                onClick={onClose}
                className="fixed inset-0 z-40 bg-black/40"
            />
            <div className="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl border-t border-border bg-surface-container-highest p-6 shadow-ambient-lg md:right-8 md:bottom-8 md:left-auto md:w-96 md:rounded-2xl">
                <div className="mb-4 flex items-start justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            {wordStudy.language}
                        </p>
                        <p className="mt-1 font-serif text-3xl text-on-surface">
                            {wordStudy.original_word}
                        </p>
                        <p className="mt-1 text-sm text-on-surface-variant italic">
                            {wordStudy.transliteration} ·{' '}
                            <span className="font-mono text-xs">
                                {wordStudy.strongs_number}
                            </span>
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={onClose}
                        aria-label="Close"
                    >
                        <X className="size-4" />
                    </Button>
                </div>
                <p className="text-sm leading-relaxed text-on-surface">
                    {wordStudy.definition}
                </p>
            </div>
        </>
    );
}
