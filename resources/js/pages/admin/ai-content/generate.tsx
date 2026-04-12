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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { save as aiSave, store as aiStore } from '@/routes/admin/ai-content';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    BookOpen,
    Check,
    Copy,
    ImageIcon,
    Loader2,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'AI Content', href: '#' },
    { title: 'Generate', href: '#' },
];

interface GeneratedContent {
    title?: string;
    body?: string;
    scripture_refs?: string[];
    reflection_prompts?: string[];
    adventist_insights?: string;
}

interface AiGenerationLog {
    id: number;
    status: string;
    generated_content: GeneratedContent | null;
    error_message: string | null;
}

interface ThemeOption {
    id: number;
    name: string;
}

interface Props {
    themes: ThemeOption[];
}

function getCsrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((c) => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

export default function AiContentGenerate({ themes }: Props) {
    const [prompt, setPrompt] = useState('');
    const [generating, setGenerating] = useState(false);
    const [result, setResult] = useState<AiGenerationLog | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);

    // Save dialog state
    const [saveDialogOpen, setSaveDialogOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);
    const [saved, setSaved] = useState(false);
    const [themeSelection, setThemeSelection] = useState<string>('');
    const [newThemeName, setNewThemeName] = useState('');
    const [newThemeDescription, setNewThemeDescription] = useState('');
    const [availableThemes, setAvailableThemes] =
        useState<ThemeOption[]>(themes);

    async function handleGenerate() {
        if (!prompt.trim()) return;

        setGenerating(true);
        setError(null);
        setResult(null);
        setSaved(false);

        try {
            const response = await fetch(aiStore.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ prompt }),
            });

            if (!response.ok) {
                throw new Error('Generation failed. Please try again.');
            }

            const data = await response.json();
            setResult(data.log);
        } catch (err) {
            setError(
                err instanceof Error
                    ? err.message
                    : 'An unexpected error occurred.',
            );
        } finally {
            setGenerating(false);
        }
    }

    async function handleCopyContent() {
        if (!result?.generated_content) return;
        const content = result.generated_content;
        const text = [
            content.title && `Title: ${content.title}`,
            content.scripture_refs?.length &&
                `Scripture: ${content.scripture_refs.join(', ')}`,
            content.body,
            content.reflection_prompts?.length &&
                `Reflection Prompts:\n${content.reflection_prompts.join('\n')}`,
            content.adventist_insights &&
                `Adventist Insights:\n${content.adventist_insights}`,
        ]
            .filter(Boolean)
            .join('\n\n');

        try {
            await navigator.clipboard.writeText(text);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard write failed (e.g. permissions denied)
        }
    }

    function openSaveDialog() {
        setSaveError(null);
        setThemeSelection('');
        setNewThemeName('');
        setNewThemeDescription('');
        setSaveDialogOpen(true);
    }

    async function handleSave() {
        if (!result) return;

        const isNewTheme = themeSelection === 'new';
        if (isNewTheme && !newThemeName.trim()) return;
        if (!isNewTheme && !themeSelection) return;

        setSaving(true);
        setSaveError(null);

        try {
            const body: Record<string, unknown> = {
                ai_generation_log_id: result.id,
            };

            if (isNewTheme) {
                body.new_theme_name = newThemeName.trim();
                if (newThemeDescription.trim()) {
                    body.new_theme_description = newThemeDescription.trim();
                }
            } else {
                body.theme_id = Number(themeSelection);
            }

            const response = await fetch(aiSave.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => null);
                throw new Error(
                    data?.message || 'Failed to save. Please try again.',
                );
            }

            const data = await response.json();

            // Add the new theme to available themes if one was created
            if (isNewTheme && data.entry?.theme) {
                setAvailableThemes((prev) => [
                    ...prev,
                    {
                        id: data.entry.theme.id,
                        name: data.entry.theme.name,
                    },
                ]);
            }

            setSaved(true);
            setSaveDialogOpen(false);
        } catch (err) {
            setSaveError(
                err instanceof Error
                    ? err.message
                    : 'An unexpected error occurred.',
            );
        } finally {
            setSaving(false);
        }
    }

    const content = result?.generated_content;
    const canSave =
        content && result?.status === 'completed' && !saved && !generating;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="AI Content Assistant" />

            <div className="px-6 py-6 md:px-8">
                <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                    Editorial Tools
                </p>
                <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface">
                    AI Content Assistant
                </h1>
                <p className="mt-2 max-w-xl text-sm leading-relaxed text-on-surface-variant">
                    Compose high-fidelity editorial pieces using document
                    intelligence.
                </p>

                <div className="mt-8 grid gap-8 lg:grid-cols-2">
                    {/* Left: Prompt */}
                    <div className="space-y-6">
                        <div className="rounded-lg border border-border bg-surface-container-low p-6">
                            <Label
                                htmlFor="prompt"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Generation Prompt
                            </Label>
                            <Textarea
                                id="prompt"
                                value={prompt}
                                onChange={(e) => setPrompt(e.target.value)}
                                placeholder="Describe the theme, mood, and focus of the devotional entry you'd like to generate..."
                                rows={6}
                                className="mt-3"
                            />
                            <Button
                                className="mt-4 w-full bg-moss text-moss-foreground hover:bg-moss/90"
                                disabled={generating || !prompt.trim()}
                                onClick={handleGenerate}
                            >
                                {generating ? (
                                    <>
                                        <Loader2 className="size-4 animate-spin" />
                                        Generating...
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="size-4" />
                                        Generate Content
                                    </>
                                )}
                            </Button>
                        </div>

                        {/* Curator Tip */}
                        <div className="rounded-lg border border-moss/20 bg-moss/5 p-5">
                            <p className="text-xs font-semibold tracking-[0.1em] text-moss uppercase">
                                Curator Tip
                            </p>
                            <p className="mt-2 text-sm leading-relaxed text-on-surface-variant">
                                Specify a voice, mood, and scriptural focus for
                                best results. For example: &quot;A reflective
                                devotional on Psalm 23 exploring the theme of
                                divine provision during seasons of uncertainty,
                                written in a contemplative, warm tone.&quot;
                            </p>
                        </div>
                    </div>

                    {/* Right: Preview */}
                    <div>
                        {error && (
                            <div className="rounded-lg border border-destructive/20 bg-destructive/5 p-6">
                                <p className="text-sm font-medium text-destructive">
                                    Generation Error
                                </p>
                                <p className="mt-1 text-sm text-on-surface-variant">
                                    {error}
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="mt-3"
                                    onClick={handleGenerate}
                                >
                                    Try Again
                                </Button>
                            </div>
                        )}

                        {generating && (
                            <div className="rounded-lg border border-border bg-surface-container-low p-12 text-center">
                                <Loader2 className="mx-auto size-8 animate-spin text-moss" />
                                <p className="mt-4 font-serif text-lg text-on-surface-variant">
                                    Composing devotional content...
                                </p>
                                <p className="mt-1 text-sm text-on-surface-variant">
                                    This may take a moment.
                                </p>
                            </div>
                        )}

                        {!generating && !error && !content && (
                            <div className="rounded-lg border border-dashed border-border bg-surface-container-low p-12 text-center">
                                <Sparkles className="mx-auto size-10 text-on-surface-variant/30" />
                                <p className="mt-4 font-serif text-lg text-on-surface-variant">
                                    Preview Canvas
                                </p>
                                <p className="mt-1 text-sm text-on-surface-variant">
                                    Generated content will appear here in full
                                    editorial format.
                                </p>
                            </div>
                        )}

                        {content && (
                            <div className="overflow-hidden rounded-lg border border-border bg-surface-container-low">
                                {/* Preview toolbar */}
                                <div className="flex items-center justify-between border-b border-border bg-surface-container-high/50 px-4 py-2">
                                    <span className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                        Preview
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={handleCopyContent}
                                        >
                                            {copied ? (
                                                <>
                                                    <Check className="size-3.5" />
                                                    Copied
                                                </>
                                            ) : (
                                                <>
                                                    <Copy className="size-3.5" />
                                                    Copy
                                                </>
                                            )}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={handleGenerate}
                                        >
                                            <Sparkles className="size-3.5" />
                                            Regenerate
                                        </Button>
                                        {canSave && (
                                            <Button
                                                size="sm"
                                                className="bg-moss text-moss-foreground hover:bg-moss/90"
                                                onClick={openSaveDialog}
                                            >
                                                <BookOpen className="size-3.5" />
                                                Save as Devotion
                                            </Button>
                                        )}
                                        {saved && (
                                            <Badge className="border-moss/20 bg-moss/10 text-moss hover:bg-moss/10">
                                                <Check className="mr-1 size-3" />
                                                Saved
                                            </Badge>
                                        )}
                                    </div>
                                </div>

                                {/* Preview content */}
                                <div className="space-y-6 p-6">
                                    {content.title && (
                                        <h2 className="font-serif text-3xl font-medium tracking-tight text-on-surface">
                                            {content.title}
                                        </h2>
                                    )}

                                    {content.scripture_refs &&
                                        content.scripture_refs.length > 0 && (
                                            <div className="border-l-2 border-moss/30 pl-4">
                                                <p className="text-xs font-medium tracking-[0.1em] text-moss uppercase">
                                                    Scripture References
                                                </p>
                                                <p className="mt-1 font-serif text-sm text-on-surface-variant italic">
                                                    {content.scripture_refs.join(
                                                        ' / ',
                                                    )}
                                                </p>
                                            </div>
                                        )}

                                    {content.body && (
                                        <div className="prose-serenity">
                                            {content.body
                                                .split('\n\n')
                                                .map((paragraph) => (
                                                    <p
                                                        key={paragraph}
                                                        className="text-sm leading-relaxed text-on-surface"
                                                    >
                                                        {paragraph}
                                                    </p>
                                                ))}
                                        </div>
                                    )}

                                    {content.reflection_prompts &&
                                        content.reflection_prompts.length >
                                            0 && (
                                            <div className="rounded-lg bg-surface-container p-4">
                                                <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                                    Reflection Prompts
                                                </p>
                                                <ol className="mt-2 space-y-2">
                                                    {content.reflection_prompts.map(
                                                        (prompt, idx) => (
                                                            <li
                                                                key={prompt}
                                                                className="flex gap-2 text-sm text-on-surface"
                                                            >
                                                                <span className="shrink-0 font-serif font-medium text-moss">
                                                                    {idx + 1}.
                                                                </span>
                                                                {prompt}
                                                            </li>
                                                        ),
                                                    )}
                                                </ol>
                                            </div>
                                        )}

                                    {content.adventist_insights && (
                                        <div className="rounded-lg bg-surface-container p-4">
                                            <p className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase">
                                                Adventist Insights
                                            </p>
                                            <p className="mt-2 text-sm leading-relaxed text-on-surface">
                                                {content.adventist_insights}
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {/* Status footer */}
                                {result?.status && (
                                    <div className="border-t border-border px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                className={
                                                    result.status ===
                                                    'completed'
                                                        ? 'border-moss/20 bg-moss/10 text-moss hover:bg-moss/10'
                                                        : result.status ===
                                                            'failed'
                                                          ? 'border-destructive/20 bg-destructive/10 text-destructive hover:bg-destructive/10'
                                                          : ''
                                                }
                                            >
                                                {result.status}
                                            </Badge>
                                            {result.error_message && (
                                                <span className="text-xs text-destructive">
                                                    {result.error_message}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Save as Devotion Dialog */}
            <Dialog open={saveDialogOpen} onOpenChange={setSaveDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Save as Devotion</DialogTitle>
                        <DialogDescription>
                            Choose an existing theme or create a new one. An
                            image will be generated automatically.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="theme-select">Theme</Label>
                            <Select
                                value={themeSelection}
                                onValueChange={setThemeSelection}
                            >
                                <SelectTrigger id="theme-select">
                                    <SelectValue placeholder="Select a theme..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="new">
                                        + Create new theme
                                    </SelectItem>
                                    {availableThemes.map((theme) => (
                                        <SelectItem
                                            key={theme.id}
                                            value={String(theme.id)}
                                        >
                                            {theme.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {themeSelection === 'new' && (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="new-theme-name">
                                        Theme Name
                                    </Label>
                                    <Input
                                        id="new-theme-name"
                                        value={newThemeName}
                                        onChange={(e) =>
                                            setNewThemeName(e.target.value)
                                        }
                                        placeholder="e.g. Walking in Wisdom"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="new-theme-desc">
                                        Description{' '}
                                        <span className="text-muted-foreground">
                                            (optional)
                                        </span>
                                    </Label>
                                    <Textarea
                                        id="new-theme-desc"
                                        value={newThemeDescription}
                                        onChange={(e) =>
                                            setNewThemeDescription(
                                                e.target.value,
                                            )
                                        }
                                        placeholder="A brief description of this devotional theme..."
                                        rows={2}
                                    />
                                </div>
                            </>
                        )}

                        <div className="flex items-center gap-2 rounded-md bg-surface-container p-3 text-sm text-on-surface-variant">
                            <ImageIcon className="size-4 shrink-0 text-moss" />
                            An image will be generated for this devotion
                            automatically.
                        </div>

                        {saveError && (
                            <p className="text-sm text-destructive">
                                {saveError}
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setSaveDialogOpen(false)}
                            disabled={saving}
                        >
                            Cancel
                        </Button>
                        <Button
                            className="bg-moss text-moss-foreground hover:bg-moss/90"
                            disabled={
                                saving ||
                                !themeSelection ||
                                (themeSelection === 'new' &&
                                    !newThemeName.trim())
                            }
                            onClick={handleSave}
                        >
                            {saving ? (
                                <>
                                    <Loader2 className="size-4 animate-spin" />
                                    Saving...
                                </>
                            ) : (
                                <>
                                    <BookOpen className="size-4" />
                                    Save Devotion
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
