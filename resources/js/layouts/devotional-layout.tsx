import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useIsMobile } from '@/hooks/use-mobile';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { index as bibleStudyIndex } from '@/routes/bible-study';
import { index as bookmarksIndex } from '@/routes/bookmarks';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as themesIndex } from '@/routes/themes';
import { edit as settingsEdit } from '@/routes/user-profile';
import { logout } from '@/routes';
import { type SharedData } from '@/types';
import { Link, usePage, router } from '@inertiajs/react';
import {
    Bell,
    Bookmark,
    BookOpen,
    ChevronUp,
    GraduationCap,
    Link2,
    LogOut,
    Palette,
    Search,
    Settings,
    User,
} from 'lucide-react';
import { type ReactNode, useEffect, useRef, useState } from 'react';

interface DevotionalLayoutProps {
    children: ReactNode;
}

interface SidebarNavItem {
    title: string;
    href: string;
    icon: ReactNode;
}

interface TopTab {
    title: string;
    href: string;
}

const sidebarNavItems: SidebarNavItem[] = [
    { title: 'Themes', href: '/themes', icon: <Palette className="size-4" /> },
    { title: 'Bible Study', href: '/bible-study', icon: <GraduationCap className="size-4" /> },
    { title: 'Bookmarks', href: '/bookmarks', icon: <Bookmark className="size-4" /> },
    { title: 'Settings', href: '/settings', icon: <Settings className="size-4" /> },
];

const topTabs: TopTab[] = [
    { title: 'DEVOTIONS', href: themesIndex.url() },
    { title: 'BIBLE STUDY', href: bibleStudyIndex.url() },
    { title: 'BOOKMARKS', href: bookmarksIndex.url() },
];

const mobileNavItems = [
    { title: 'Themes', href: '/themes', icon: BookOpen },
    { title: 'Study', href: '/bible-study', icon: GraduationCap },
    { title: 'Bookmarks', href: '/bookmarks', icon: Bookmark },
    { title: 'Settings', href: '/settings', icon: Settings },
];

export default function DevotionalLayout({ children }: DevotionalLayoutProps) {
    const isMobile = useIsMobile();
    const page = usePage<SharedData>();
    const { auth, unreadNotificationsCount } = page.props;

    return (
        <div className="flex min-h-screen bg-background">
            {/* Desktop sidebar */}
            {!isMobile && (
                <DesktopSidebar
                    currentUrl={page.url}
                    user={auth.user}
                    unreadCount={unreadNotificationsCount}
                />
            )}

            {/* Main content */}
            <div className="flex min-w-0 flex-1 flex-col">
                {/* Desktop top tab bar */}
                {!isMobile && (
                    <DesktopTopBar
                        currentUrl={page.url}
                        unreadCount={unreadNotificationsCount}
                    />
                )}

                {/* Mobile top bar */}
                {isMobile && (
                    <MobileTopBar user={auth.user} />
                )}

                {/* Page content */}
                <main className={cn('flex-1', isMobile && 'pb-20')}>
                    {children}
                </main>

                {/* Mobile bottom nav */}
                {isMobile && (
                    <MobileBottomNav currentUrl={page.url} />
                )}
            </div>
        </div>
    );
}

/* ─────────────────────────────────────────────
   Desktop Sidebar
   ───────────────────────────────────────────── */
function DesktopSidebar({
    currentUrl,
    user,
    unreadCount,
}: {
    currentUrl: string;
    user: SharedData['auth']['user'];
    unreadCount: number;
}) {
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const getInitials = useInitials();

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
                setUserMenuOpen(false);
            }
        }
        if (userMenuOpen) {
            document.addEventListener('mousedown', handleClickOutside);
        }
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [userMenuOpen]);

    const isAdmin = Boolean(user.is_admin);

    return (
        <aside className="flex w-60 shrink-0 flex-col bg-sidebar">
            {/* Branding */}
            <div className="px-5 pt-6 pb-4">
                <Link href={themesIndex.url()} className="block">
                    <h1 className="font-serif text-xl italic leading-tight tracking-tight text-sidebar-foreground">
                        Devotional
                    </h1>
                </Link>
                <p className="mt-2 text-sm text-sidebar-foreground/80">
                    The Digital Curator
                </p>
                <p className="text-[10px] font-medium uppercase tracking-[0.15em] text-sidebar-foreground/40">
                    Spiritual Reflection
                </p>
            </div>

            {/* Nav items */}
            <nav className="flex-1 px-3 py-2">
                <ul className="space-y-0.5">
                    {sidebarNavItems.map((item) => {
                        const isActive = currentUrl.startsWith(item.href);
                        return (
                            <li key={item.title}>
                                <Link
                                    href={item.href}
                                    prefetch
                                    className={cn(
                                        'flex h-9 items-center gap-3 rounded-md px-3 text-sm transition-colors',
                                        isActive
                                            ? 'bg-sidebar-accent font-semibold text-sidebar-foreground'
                                            : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground',
                                    )}
                                >
                                    {item.icon}
                                    <span>{item.title}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {/* User section */}
            <div className="relative px-3 pb-4" ref={menuRef}>
                {/* User menu popover */}
                {userMenuOpen && (
                    <div className="absolute bottom-full left-3 right-3 mb-1 rounded-lg bg-popover p-1.5 shadow-ambient-lg">
                        <p className="px-3 py-1.5 text-[10px] font-medium uppercase tracking-[0.15em] text-muted-foreground">
                            Account
                        </p>
                        <Link
                            href={settingsEdit.url()}
                            prefetch
                            className="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-popover-foreground transition-colors hover:bg-accent"
                            onClick={() => setUserMenuOpen(false)}
                        >
                            <User className="size-4" />
                            <span>Profile Settings</span>
                        </Link>
                        <Link
                            href={settingsEdit.url()}
                            prefetch
                            className="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-popover-foreground transition-colors hover:bg-accent"
                            onClick={() => setUserMenuOpen(false)}
                        >
                            <Link2 className="size-4" />
                            <span>Partner Linking</span>
                        </Link>
                        <Link
                            href={notificationsIndex.url()}
                            prefetch
                            className="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-popover-foreground transition-colors hover:bg-accent"
                            onClick={() => setUserMenuOpen(false)}
                        >
                            <Bell className="size-4" />
                            <span>Notifications</span>
                            {unreadCount > 0 && (
                                <span className="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-moss px-1.5 text-[10px] font-semibold text-moss-foreground">
                                    {unreadCount}
                                </span>
                            )}
                        </Link>
                        <div className="my-1 h-px bg-border" />
                        <button
                            type="button"
                            className="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm text-destructive transition-colors hover:bg-accent"
                            onClick={() => {
                                setUserMenuOpen(false);
                                router.post(logout.url());
                            }}
                        >
                            <LogOut className="size-4" />
                            <span>Sign Out</span>
                        </button>
                    </div>
                )}

                {/* User trigger */}
                <button
                    type="button"
                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                    className="flex w-full items-center gap-3 rounded-lg p-2 text-left transition-colors hover:bg-sidebar-accent"
                >
                    <Avatar className="size-8">
                        <AvatarImage src={user.avatar} alt={user.name} />
                        <AvatarFallback className="bg-moss text-[11px] font-medium text-moss-foreground">
                            {getInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium text-sidebar-foreground">
                            {user.name}
                        </p>
                        <p className="text-[10px] font-medium uppercase tracking-[0.1em] text-sidebar-foreground/50">
                            {isAdmin ? 'Administrator' : 'Member'}
                        </p>
                    </div>
                    <ChevronUp
                        className={cn(
                            'size-4 text-sidebar-foreground/40 transition-transform',
                            !userMenuOpen && 'rotate-180',
                        )}
                    />
                </button>
            </div>
        </aside>
    );
}

/* ─────────────────────────────────────────────
   Desktop Top Tab Bar
   ───────────────────────────────────────────── */
function DesktopTopBar({
    currentUrl,
    unreadCount,
}: {
    currentUrl: string;
    unreadCount: number;
}) {
    return (
        <div className="flex h-11 items-center justify-between bg-background px-6">
            {/* Tabs */}
            <nav className="flex h-full items-stretch gap-6">
                {topTabs.map((tab) => {
                    const isActive = currentUrl.startsWith(tab.href);
                    return (
                        <Link
                            key={tab.title}
                            href={tab.href}
                            prefetch
                            className={cn(
                                'relative flex items-center text-xs font-medium tracking-[0.12em] transition-colors',
                                isActive
                                    ? 'text-moss'
                                    : 'text-on-surface-variant hover:text-on-surface',
                            )}
                        >
                            {tab.title}
                            {isActive && (
                                <span className="absolute inset-x-0 bottom-0 h-0.5 bg-moss" />
                            )}
                        </Link>
                    );
                })}
            </nav>

            {/* Actions */}
            <div className="flex items-center gap-1">
                <Link
                    href={notificationsIndex.url()}
                    prefetch
                    className="relative flex h-9 w-9 items-center justify-center rounded-md text-on-surface-variant transition-colors hover:bg-accent hover:text-on-surface"
                >
                    <Bell className="size-4" />
                    {unreadCount > 0 && (
                        <span className="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-moss px-1 text-[9px] font-bold text-moss-foreground">
                            {unreadCount}
                        </span>
                    )}
                </Link>
                <button
                    type="button"
                    className="flex h-9 w-9 items-center justify-center rounded-md text-on-surface-variant transition-colors hover:bg-accent hover:text-on-surface"
                >
                    <Search className="size-4" />
                </button>
            </div>
        </div>
    );
}

/* ─────────────────────────────────────────────
   Mobile Top Bar
   ───────────────────────────────────────────── */
function MobileTopBar({ user }: { user: SharedData['auth']['user'] }) {
    const getInitials = useInitials();

    return (
        <div className="flex h-14 items-center justify-between bg-background px-4">
            <Link href={settingsEdit.url()} prefetch className="flex items-center gap-2">
                <Avatar className="size-8">
                    <AvatarImage src={user.avatar} alt={user.name} />
                    <AvatarFallback className="bg-moss text-[11px] font-medium text-moss-foreground">
                        {getInitials(user.name)}
                    </AvatarFallback>
                </Avatar>
                <span className="font-serif text-sm italic text-foreground">Curator</span>
            </Link>
            <button
                type="button"
                className="flex h-11 w-11 items-center justify-center rounded-md text-on-surface-variant"
            >
                <Search className="size-5" />
            </button>
        </div>
    );
}

/* ─────────────────────────────────────────────
   Mobile Bottom Nav
   ───────────────────────────────────────────── */
function MobileBottomNav({ currentUrl }: { currentUrl: string }) {
    return (
        <nav className="fixed inset-x-0 bottom-0 z-40 border-t border-border/40 bg-background/95 backdrop-blur-md">
            <div className="mx-auto flex h-16 max-w-lg items-center justify-around px-2">
                {mobileNavItems.map((item) => {
                    const isActive = currentUrl.startsWith(item.href);
                    const Icon = item.icon;
                    return (
                        <Link
                            key={item.title}
                            href={item.href}
                            prefetch
                            className={cn(
                                'flex h-11 w-16 flex-col items-center justify-center gap-0.5 rounded-lg transition-colors',
                                isActive
                                    ? 'text-moss'
                                    : 'text-on-surface-variant',
                            )}
                        >
                            <Icon className="size-5" />
                            <span className="text-[10px] font-medium">{item.title}</span>
                        </Link>
                    );
                })}
            </div>
            {/* Safe area for phones with home indicator */}
            <div className="h-safe-area-inset-bottom bg-background" />
        </nav>
    );
}
