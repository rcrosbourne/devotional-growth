import { cn } from '@/lib/utils';

interface ProgressBarProps {
    value: number;
    max?: number;
    label?: string;
    showPercentage?: boolean;
    className?: string;
}

export function ProgressBar({
    value,
    max = 100,
    label,
    showPercentage = false,
    className,
}: ProgressBarProps) {
    const percentage = max > 0 ? Math.round((value / max) * 100) : 0;

    return (
        <div className={cn('space-y-1.5', className)}>
            {(label || showPercentage) && (
                <div className="flex items-center justify-between">
                    {label && (
                        <span className="text-xs font-medium text-on-surface-variant">
                            {label}
                        </span>
                    )}
                    {showPercentage && (
                        <span className="text-xs font-semibold text-on-surface tabular-nums">
                            {percentage}%
                        </span>
                    )}
                </div>
            )}
            <div className="h-1.5 overflow-hidden rounded-full bg-surface-container-high">
                <div
                    className="h-full rounded-full bg-moss transition-all duration-500 ease-out"
                    style={{ width: `${percentage}%` }}
                    role="progressbar"
                    aria-valuenow={value}
                    aria-valuemin={0}
                    aria-valuemax={max}
                    aria-label={label ?? 'Progress'}
                />
            </div>
        </div>
    );
}
