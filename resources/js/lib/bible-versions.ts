export const BIBLE_VERSIONS = [
    { value: 'KJV', label: 'King James Version' },
    { value: 'NKJV', label: 'New King James Version' },
    { value: 'NIV', label: 'New International Version' },
    { value: 'ESV', label: 'English Standard Version' },
    { value: 'NLT', label: 'New Living Translation' },
    { value: 'ASV', label: 'American Standard Version' },
    { value: 'WEB', label: 'World English Bible' },
] as const;

export type BibleVersionKey = (typeof BIBLE_VERSIONS)[number]['value'];

const VALID_VERSIONS = new Set<string>(BIBLE_VERSIONS.map((v) => v.value));

const PREFERRED_VERSION_KEY = 'preferred_bible_version';

export const DEFAULT_BIBLE_VERSION: BibleVersionKey = 'KJV';

function isValidVersion(value: string): value is BibleVersionKey {
    return VALID_VERSIONS.has(value);
}

export function getPreferredVersion(): BibleVersionKey {
    if (typeof window === 'undefined') {
        return DEFAULT_BIBLE_VERSION;
    }
    try {
        const stored = localStorage.getItem(PREFERRED_VERSION_KEY);
        return stored && isValidVersion(stored)
            ? stored
            : DEFAULT_BIBLE_VERSION;
    } catch {
        return DEFAULT_BIBLE_VERSION;
    }
}

export function setPreferredVersion(version: BibleVersionKey): void {
    try {
        localStorage.setItem(PREFERRED_VERSION_KEY, version);
    } catch {
        // localStorage unavailable
    }
}
