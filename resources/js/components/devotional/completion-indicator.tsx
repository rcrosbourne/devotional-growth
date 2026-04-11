import { cn } from '@/lib/utils';
import { Check, Users } from 'lucide-react';

type CompletionStatus = 'none' | 'self' | 'partner' | 'both';

interface CompletionIndicatorProps {
    status: CompletionStatus;
    className?: string;
}

export function CompletionIndicator({
    status,
    className,
}: CompletionIndicatorProps) {
    if (status === 'none') {
        return (
            <div
                className={cn(
                    'flex size-6 items-center justify-center rounded-full bg-surface-container-high',
                    className,
                )}
            >
                <div className="size-2 rounded-full bg-on-surface-variant/30" />
            </div>
        );
    }

    if (status === 'both') {
        return (
            <div
                className={cn(
                    'flex size-6 items-center justify-center rounded-full bg-moss',
                    className,
                )}
            >
                <Users className="size-3.5 text-moss-foreground" />
            </div>
        );
    }

    return (
        <div
            className={cn(
                'flex size-6 items-center justify-center rounded-full',
                status === 'self' ? 'bg-moss' : 'bg-moss/40',
                className,
            )}
        >
            <Check
                className={cn(
                    'size-3.5',
                    status === 'self' ? 'text-moss-foreground' : 'text-moss',
                )}
            />
        </div>
    );
}
