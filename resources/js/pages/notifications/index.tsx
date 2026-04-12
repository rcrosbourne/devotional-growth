import DevotionalLayout from '@/layouts/devotional-layout';
import { cn } from '@/lib/utils';
import { update } from '@/routes/notifications/preferences';
import { Head, router } from '@inertiajs/react';
import { Bell, Heart, MessageSquare, Settings, Sparkles } from 'lucide-react';
import { useRef, useState } from 'react';

interface NotificationData {
    partner_id?: number;
    partner_name?: string;
    entry_id?: number;
    entry_title?: string;
    theme_id?: number;
    theme_name?: string;
    observation_id?: number;
    message: string;
}

interface AppNotification {
    id: string;
    type: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
}

interface NotificationPreference {
    id: number;
    user_id: number;
    completion_notifications: boolean;
    observation_notifications: boolean;
    new_theme_notifications: boolean;
    reminder_notifications: boolean;
}

interface Props {
    notifications: AppNotification[];
    preferences: NotificationPreference;
}

function formatRelativeTime(dateString: string): string {
    const now = new Date();
    const date = new Date(dateString);
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) {
        return 'Just now';
    }
    if (diffMins < 60) {
        return `${diffMins} min${diffMins === 1 ? '' : 's'} ago`;
    }
    if (diffHours < 24) {
        return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    }
    if (diffDays < 7) {
        return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
    }
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function getNotificationMeta(type: string): {
    icon: React.ReactNode;
    title: string;
    iconBgUnread: string;
    iconBgRead: string;
} {
    if (type.includes('PartnerCompletedEntry')) {
        return {
            icon: <Heart className="size-5" />,
            title: 'Partner completed a devotional',
            iconBgUnread: 'bg-moss/15 text-moss dark:bg-moss/25',
            iconBgRead: 'bg-surface-container-high text-on-surface-variant/60',
        };
    }
    if (type.includes('PartnerAddedObservation')) {
        return {
            icon: <MessageSquare className="size-5" />,
            title: 'Partner shared a reflection',
            iconBgUnread: 'bg-moss/15 text-moss dark:bg-moss/25',
            iconBgRead: 'bg-surface-container-high text-on-surface-variant/60',
        };
    }
    if (type.includes('PartnerStartedTheme')) {
        return {
            icon: <Sparkles className="size-5" />,
            title: 'New theme started',
            iconBgUnread: 'bg-moss/15 text-moss dark:bg-moss/25',
            iconBgRead: 'bg-surface-container-high text-on-surface-variant/60',
        };
    }
    return {
        icon: <Bell className="size-5" />,
        title: 'Notification',
        iconBgUnread: 'bg-moss/15 text-moss dark:bg-moss/25',
        iconBgRead: 'bg-surface-container-high text-on-surface-variant/60',
    };
}

function NotificationCard({ notification }: { notification: AppNotification }) {
    const isUnread = notification.read_at === null;
    const meta = getNotificationMeta(notification.type);

    return (
        <div
            className={cn(
                'group flex gap-5 rounded-xl p-6 transition-all duration-300',
                isUnread
                    ? 'border-l-4 border-moss bg-surface-container-low hover:bg-surface-container'
                    : 'bg-background hover:bg-surface-container-low',
            )}
        >
            <div className="shrink-0">
                <div
                    className={cn(
                        'flex size-10 items-center justify-center rounded-full transition-colors',
                        isUnread ? meta.iconBgUnread : meta.iconBgRead,
                    )}
                >
                    {meta.icon}
                </div>
            </div>
            <div className={cn('flex-1', !isUnread && 'opacity-70')}>
                <div className="mb-1 flex items-start justify-between gap-4">
                    <h3
                        className={cn(
                            'text-on-surface',
                            isUnread
                                ? 'text-base font-semibold'
                                : 'text-base font-medium',
                        )}
                    >
                        {meta.title}
                    </h3>
                    <span className="shrink-0 text-[10px] font-medium tracking-widest text-on-surface-variant uppercase">
                        {formatRelativeTime(notification.created_at)}
                    </span>
                </div>
                <p className="leading-relaxed text-on-surface-variant">
                    {notification.data.message}
                </p>
            </div>
        </div>
    );
}

function PreferenceToggle({
    label,
    description,
    enabled,
    onToggle,
}: {
    label: string;
    description: string;
    enabled: boolean;
    onToggle: () => void;
}) {
    return (
        <div className="flex items-center justify-between gap-6 py-4">
            <div className="flex-1">
                <p className="text-sm font-medium text-on-surface">{label}</p>
                <p className="mt-0.5 text-sm text-on-surface-variant">
                    {description}
                </p>
            </div>
            <button
                type="button"
                role="switch"
                aria-checked={enabled}
                onClick={onToggle}
                className={cn(
                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200',
                    enabled ? 'bg-moss' : 'bg-surface-container-highest',
                )}
            >
                <span
                    className={cn(
                        'pointer-events-none inline-block size-5 translate-y-0.5 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200',
                        enabled ? 'translate-x-[22px]' : 'translate-x-0.5',
                    )}
                />
            </button>
        </div>
    );
}

export default function NotificationsIndex({
    notifications,
    preferences,
}: Props) {
    const [prefs, setPrefs] = useState(preferences);
    const prefsRef = useRef<HTMLDivElement>(null);

    const unread = notifications.filter((n) => n.read_at === null);
    const read = notifications.filter((n) => n.read_at !== null);

    function togglePreference(
        key: keyof Pick<
            NotificationPreference,
            | 'completion_notifications'
            | 'observation_notifications'
            | 'new_theme_notifications'
            | 'reminder_notifications'
        >,
    ): void {
        const updated = { ...prefs, [key]: !prefs[key] };
        setPrefs(updated);
        router.put(
            update.url(),
            {
                completion_notifications: updated.completion_notifications,
                observation_notifications: updated.observation_notifications,
                new_theme_notifications: updated.new_theme_notifications,
                reminder_notifications: updated.reminder_notifications,
            },
            { preserveScroll: true },
        );
    }

    function scrollToPreferences(): void {
        prefsRef.current?.scrollIntoView({ behavior: 'smooth' });
    }

    return (
        <DevotionalLayout>
            <Head title="Notifications" />

            <div className="px-6 py-8 md:px-12 lg:px-16">
                <div className="mx-auto max-w-3xl">
                    {/* Editorial Header */}
                    <header className="mb-12">
                        <span className="text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                            Correspondence
                        </span>
                        <div className="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <h1 className="font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl lg:text-6xl">
                                Notifications
                            </h1>
                            <div className="flex items-center gap-6">
                                {unread.length > 0 && (
                                    <span className="flex items-center gap-1.5 text-sm text-on-surface-variant">
                                        <span className="size-2 rounded-full bg-moss" />
                                        {unread.length} new
                                    </span>
                                )}
                                <button
                                    type="button"
                                    onClick={scrollToPreferences}
                                    className="flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                                >
                                    <Settings className="size-4" />
                                    Settings
                                </button>
                            </div>
                        </div>
                    </header>

                    {/* Empty State */}
                    {notifications.length === 0 ? (
                        <div className="mt-12 flex flex-col items-center justify-center rounded-2xl bg-surface-container p-12 text-center">
                            <Bell className="mb-4 size-10 text-on-surface-variant/30" />
                            <p className="font-serif text-2xl text-on-surface-variant">
                                No notifications yet
                            </p>
                            <p className="mt-2 max-w-sm text-sm text-on-surface-variant/60">
                                When your partner completes a devotion or shares
                                a reflection, you&apos;ll see it here.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {/* Unread notifications */}
                            {unread.map((notification) => (
                                <NotificationCard
                                    key={notification.id}
                                    notification={notification}
                                />
                            ))}

                            {/* Divider between unread and read */}
                            {unread.length > 0 && read.length > 0 && (
                                <div className="flex items-center gap-4 py-6">
                                    <div className="h-px flex-1 bg-border/30" />
                                    <span className="text-[10px] font-medium tracking-[0.2em] text-on-surface-variant/50 uppercase">
                                        Earlier
                                    </span>
                                    <div className="h-px flex-1 bg-border/30" />
                                </div>
                            )}

                            {/* Read notifications */}
                            {read.map((notification) => (
                                <NotificationCard
                                    key={notification.id}
                                    notification={notification}
                                />
                            ))}
                        </div>
                    )}

                    {/* Notification Preferences */}
                    <div
                        ref={prefsRef}
                        className="mt-16 rounded-3xl border border-moss/10 bg-moss/5 p-8 md:p-10"
                    >
                        <h2 className="mb-2 font-serif text-2xl text-on-surface italic">
                            Notification Preferences
                        </h2>
                        <p className="mb-6 text-sm text-on-surface-variant">
                            Choose which notifications you&apos;d like to
                            receive.
                        </p>
                        <div className="divide-y divide-border/30">
                            <PreferenceToggle
                                label="Completion updates"
                                description="When your partner completes a devotional entry"
                                enabled={prefs.completion_notifications}
                                onToggle={() =>
                                    togglePreference('completion_notifications')
                                }
                            />
                            <PreferenceToggle
                                label="Shared reflections"
                                description="When your partner adds an observation or note"
                                enabled={prefs.observation_notifications}
                                onToggle={() =>
                                    togglePreference(
                                        'observation_notifications',
                                    )
                                }
                            />
                            <PreferenceToggle
                                label="New themes"
                                description="When new devotional themes are published"
                                enabled={prefs.new_theme_notifications}
                                onToggle={() =>
                                    togglePreference('new_theme_notifications')
                                }
                            />
                            <PreferenceToggle
                                label="Daily reminders"
                                description="Gentle reminders to continue your devotional journey"
                                enabled={prefs.reminder_notifications}
                                onToggle={() =>
                                    togglePreference('reminder_notifications')
                                }
                            />
                        </div>
                    </div>
                </div>
            </div>
        </DevotionalLayout>
    );
}
