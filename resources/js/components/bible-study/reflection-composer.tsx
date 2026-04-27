import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { Reflection } from '@/pages/bible-study/passage';
import {
    store as storeReflection,
    update as updateReflection,
} from '@/routes/bible-study/reflections';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface PassageRef {
    themeId: number | null;
    book: string;
    chapter: number;
    verseStart: number;
    verseEnd: number | null;
    verseNumber: number | null;
}

interface Props {
    scope: 'passage' | 'verse';
    partnerEnabled: boolean;
    passageRef: PassageRef;
    existing: Reflection | null;
    onClose?: () => void;
}

export function ReflectionComposer({
    scope,
    partnerEnabled,
    passageRef,
    existing,
    onClose,
}: Props) {
    const form = useForm({
        body: existing?.body ?? '',
        is_shared_with_partner: existing?.is_shared_with_partner ?? false,
    });

    function submit(e: FormEvent): void {
        e.preventDefault();
        if (existing !== null) {
            form.put(updateReflection.url(existing.id), {
                preserveScroll: true,
                onSuccess: () => onClose?.(),
            });
            return;
        }
        form.transform((data) => ({
            ...data,
            theme_id: passageRef.themeId,
            book: passageRef.book,
            chapter: passageRef.chapter,
            verse_start: passageRef.verseStart,
            verse_end: passageRef.verseEnd,
            verse_number: passageRef.verseNumber,
        }));
        form.post(storeReflection.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('body');
                onClose?.();
            },
        });
    }

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-border bg-surface-container-lowest p-4"
        >
            <Textarea
                value={form.data.body}
                onChange={(e) => form.setData('body', e.target.value)}
                placeholder={
                    scope === 'passage'
                        ? 'Reflect on this passage...'
                        : 'Add a verse-level note...'
                }
                rows={3}
                className="resize-none border-border bg-surface-container-low"
            />
            <div className="mt-3 flex items-center justify-between gap-3">
                <label className="flex items-center gap-2 text-xs text-on-surface-variant">
                    <Switch
                        checked={form.data.is_shared_with_partner}
                        onCheckedChange={(v) =>
                            form.setData('is_shared_with_partner', v)
                        }
                        disabled={!partnerEnabled}
                    />
                    Share with partner
                    {!partnerEnabled && (
                        <span className="text-xs text-on-surface-variant/60">
                            (link a partner first)
                        </span>
                    )}
                </label>
                <div className="flex gap-2">
                    {onClose && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                    )}
                    <Button
                        type="submit"
                        size="sm"
                        disabled={form.processing || !form.data.body.trim()}
                    >
                        {existing ? 'Save changes' : 'Save reflection'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
