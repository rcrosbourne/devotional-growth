export const BIBLE_VERSIONS = [
    { value: 'KJV', label: 'King James Version' },
    { value: 'NKJV', label: 'New King James Version' },
    { value: 'NIV', label: 'New International Version' },
    { value: 'ESV', label: 'English Standard Version' },
    { value: 'NLT', label: 'New Living Translation' },
    { value: 'ASV', label: 'American Standard Version' },
    { value: 'WEB', label: 'World English Bible' },
] as const;

const PREFERRED_VERSION_KEY = 'preferred_bible_version';

export const DEFAULT_BIBLE_VERSION = 'KJV';

export function getPreferredVersion(): string {
    if (typeof window === 'undefined') {
        return DEFAULT_BIBLE_VERSION;
    }
    try {
        return (
            localStorage.getItem(PREFERRED_VERSION_KEY) || DEFAULT_BIBLE_VERSION
        );
    } catch {
        return DEFAULT_BIBLE_VERSION;
    }
}

export function setPreferredVersion(version: string): void {
    try {
        localStorage.setItem(PREFERRED_VERSION_KEY, version);
    } catch {
        // localStorage unavailable
    }
}
