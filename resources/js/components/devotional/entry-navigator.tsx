import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface EntryNavigatorProps {
    previousHref?: string;
    previousLabel?: string;
    nextHref?: string;
    nextLabel?: string;
    className?: string;
}

const navLinkClass =
    'flex h-11 items-center gap-1.5 rounded-md px-3 text-sm font-medium text-on-surface-variant transition-colors hover:bg-surface-container-high hover:text-on-surface';

export function EntryNavigator({
    previousHref,
    previousLabel = 'Previous',
    nextHref,
    nextLabel = 'Next',
    className,
}: EntryNavigatorProps) {
    return (
        <div
            className={cn('flex items-center justify-between gap-4', className)}
        >
            {previousHref ? (
                <Link href={previousHref} prefetch className={navLinkClass}>
                    <ChevronLeft className="size-4" />
                    <span>{previousLabel}</span>
                </Link>
            ) : (
                <div />
            )}

            {nextHref ? (
                <Link href={nextHref} prefetch className={navLinkClass}>
                    <span>{nextLabel}</span>
                    <ChevronRight className="size-4" />
                </Link>
            ) : (
                <div />
            )}
        </div>
    );
}
