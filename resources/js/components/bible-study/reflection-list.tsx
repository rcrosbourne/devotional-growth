import { Button } from '@/components/ui/button';
import type { Reflection } from '@/pages/bible-study/passage';
import { destroy as destroyReflection } from '@/routes/bible-study/reflections';
import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

interface Props {
    reflections: Reflection[];
}

export function ReflectionList({ reflections }: Props) {
    if (reflections.length === 0) {
        return null;
    }

    function destroy(id: number): void {
        if (!confirm('Delete this reflection?')) {
            return;
        }
        router.delete(destroyReflection.url(id), { preserveScroll: true });
    }

    return (
        <ul className="space-y-3">
            {reflections.map((r) => (
                <li
                    key={r.id}
                    className="rounded-xl border border-border bg-surface-container-low p-4"
                >
                    <div className="mb-2 flex items-center justify-between">
                        <div className="text-xs font-medium tracking-wide text-on-surface-variant uppercase">
                            {r.is_own ? 'You' : (r.user_name ?? 'Partner')}
                            {r.is_shared_with_partner && (
                                <span className="ml-2 rounded-full bg-moss/15 px-2 py-0.5 text-[10px] text-moss">
                                    shared
                                </span>
                            )}
                        </div>
                        {r.is_own && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => destroy(r.id)}
                                aria-label="Delete reflection"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        )}
                    </div>
                    <p className="text-sm leading-relaxed text-on-surface">
                        {r.body}
                    </p>
                </li>
            ))}
        </ul>
    );
}
