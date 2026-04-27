import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { show as showPassage } from '@/routes/bible-study/passage';
import { router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

const BOOKS = [
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
];

export function PassageSearchBar() {
    const [book, setBook] = useState('Job');
    const [chapter, setChapter] = useState('1');
    const [verseStart, setVerseStart] = useState('1');
    const [verseEnd, setVerseEnd] = useState('');

    function submit(e: FormEvent): void {
        e.preventDefault();
        router.get(
            showPassage.url(),
            {
                book,
                chapter,
                verse_start: verseStart,
                ...(verseEnd ? { verse_end: verseEnd } : {}),
            },
            { preserveScroll: false },
        );
    }

    return (
        <form
            onSubmit={submit}
            className="grid gap-2 md:grid-cols-[2fr_1fr_1fr_1fr_auto]"
        >
            <select
                value={book}
                onChange={(e) => setBook(e.target.value)}
                className="rounded-md border border-border bg-surface-container-low px-3 py-2 text-sm"
            >
                {BOOKS.map((b) => (
                    <option key={b} value={b}>
                        {b}
                    </option>
                ))}
            </select>
            <Input
                inputMode="numeric"
                placeholder="Chapter"
                value={chapter}
                onChange={(e) => setChapter(e.target.value)}
            />
            <Input
                inputMode="numeric"
                placeholder="Verse"
                value={verseStart}
                onChange={(e) => setVerseStart(e.target.value)}
            />
            <Input
                inputMode="numeric"
                placeholder="To (optional)"
                value={verseEnd}
                onChange={(e) => setVerseEnd(e.target.value)}
            />
            <Button type="submit">Open</Button>
        </form>
    );
}
