import { cn } from '@/lib/utils';
import { WifiOff } from 'lucide-react';
import { useCallback, useSyncExternalStore } from 'react';

interface OfflineIndicatorProps {
    className?: string;
}

function useIsOffline() {
    const subscribe = useCallback((callback: () => void) => {
        window.addEventListener('online', callback);
        window.addEventListener('offline', callback);
        return () => {
            window.removeEventListener('online', callback);
            window.removeEventListener('offline', callback);
        };
    }, []);

    return useSyncExternalStore(
        subscribe,
        () => !navigator.onLine,
        () => false,
    );
}

export function OfflineIndicator({ className }: OfflineIndicatorProps) {
    const isOffline = useIsOffline();

    if (!isOffline) {
        return null;
    }

    return (
        <div
            className={cn(
                'flex items-center gap-2 bg-surface-container-highest px-4 py-2 text-sm text-on-surface-variant',
                className,
            )}
        >
            <WifiOff className="size-4 shrink-0" />
            <span>You are offline. Some content may be unavailable.</span>
        </div>
    );
}
