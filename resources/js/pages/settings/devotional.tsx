import UserController from '@/actions/App/Http/Controllers/UserController';
import UserProfileController from '@/actions/App/Http/Controllers/UserProfileController';
import {
    AppleIcon,
    GitHubIcon,
    GoogleIcon,
} from '@/components/icons/social-icons';
import InputError from '@/components/input-error';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useInitials } from '@/hooks/use-initials';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import DevotionalLayout from '@/layouts/devotional-layout';
import { edit as editAppearance } from '@/routes/appearance';
import { update as updatePreferences } from '@/routes/notifications/preferences';
import {
    destroy as partnerDestroy,
    store as partnerStore,
} from '@/routes/partner';
import { disconnectSocial } from '@/routes/settings';
import { redirect as socialRedirect } from '@/routes/social';
import { disable, enable } from '@/routes/two-factor';
import { send } from '@/routes/verification';
import { type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    ExternalLink,
    Link2Off,
    LinkIcon,
    Palette,
    Shield,
    ShieldBan,
    ShieldCheck,
    Trash2,
    Unlink,
} from 'lucide-react';
import { useRef, useState } from 'react';

interface NotificationPreferences {
    completion_notifications: boolean;
    observation_notifications: boolean;
    new_theme_notifications: boolean;
    reminder_notifications: boolean;
}

interface Partner {
    id: number;
    name: string;
    email: string;
}

interface SocialAccount {
    id: number;
    provider: string;
}

interface Props {
    partner: Partner | null;
    preferences: NotificationPreferences;
    socialAccounts: SocialAccount[];
    availableProviders: string[];
    twoFactorEnabled: boolean;
    status?: string;
}

const providerMeta: Record<
    string,
    { label: string; icon: React.FC<{ className?: string }> }
> = {
    google: {
        label: 'Google',
        icon: GoogleIcon,
    },
    apple: {
        label: 'Apple',
        icon: AppleIcon,
    },
    github: {
        label: 'GitHub',
        icon: GitHubIcon,
    },
};

function SectionDivider() {
    return <div className="h-px bg-border/60" />;
}

function SectionHeader({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return (
        <div className="max-w-xs shrink-0 md:w-64">
            <h2 className="font-serif text-xl font-medium tracking-tight text-on-surface md:text-2xl">
                {title}
            </h2>
            <p className="mt-1.5 text-sm leading-relaxed text-on-surface-variant">
                {description}
            </p>
        </div>
    );
}

function ProfileSection({ status }: { status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();

    return (
        <section className="flex flex-col gap-8 md:flex-row md:gap-16">
            <SectionHeader
                title="Profile"
                description="Update your name, email address, and profile photo."
            />

            <div className="flex-1">
                <div className="flex items-start gap-6">
                    <Avatar className="size-16 shrink-0 border-2 border-surface-container-highest">
                        <AvatarImage
                            src={auth.user.avatar}
                            alt={auth.user.name}
                        />
                        <AvatarFallback className="bg-moss text-lg font-medium text-moss-foreground">
                            {getInitials(auth.user.name)}
                        </AvatarFallback>
                    </Avatar>

                    <Form
                        {...UserProfileController.update.form()}
                        options={{ preserveScroll: true }}
                        className="flex-1 space-y-5"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-1.5">
                                    <Label
                                        htmlFor="name"
                                        className="text-[10px] font-medium tracking-[0.15em] text-on-surface-variant/50 uppercase"
                                    >
                                        Full Name
                                    </Label>
                                    <Input
                                        id="name"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label
                                        htmlFor="email"
                                        className="text-[10px] font-medium tracking-[0.15em] text-on-surface-variant/50 uppercase"
                                    >
                                        Email Address
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                {auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="text-sm text-on-surface-variant">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="font-medium text-moss underline-offset-4 hover:underline"
                                            >
                                                Resend verification email.
                                            </Link>
                                        </p>
                                        {status ===
                                            'verification-link-sent' && (
                                            <p className="mt-2 text-sm font-medium text-moss">
                                                A new verification link has been
                                                sent to your email address.
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        className="bg-moss text-moss-foreground hover:bg-moss/90"
                                    >
                                        Save Changes
                                    </Button>
                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-moss">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </section>
    );
}

function TwoFactorSection({ twoFactorEnabled }: { twoFactorEnabled: boolean }) {
    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState(false);

    return (
        <section className="flex flex-col gap-8 md:flex-row md:gap-16">
            <SectionHeader
                title="Two-Factor Auth"
                description="Add an extra layer of security to your account with TOTP authentication."
            />

            <div className="flex-1 space-y-4">
                {twoFactorEnabled ? (
                    <>
                        <div className="flex items-center gap-3">
                            <Badge className="bg-moss text-moss-foreground hover:bg-moss/90">
                                Enabled
                            </Badge>
                            <p className="text-sm text-on-surface-variant">
                                Your account is protected with two-factor
                                authentication.
                            </p>
                        </div>

                        <TwoFactorRecoveryCodes
                            recoveryCodesList={recoveryCodesList}
                            fetchRecoveryCodes={fetchRecoveryCodes}
                            errors={errors}
                        />

                        <Form {...disable.form()}>
                            {({ processing }) => (
                                <Button
                                    variant="destructive"
                                    type="submit"
                                    disabled={processing}
                                >
                                    <ShieldBan className="size-4" />
                                    Disable 2FA
                                </Button>
                            )}
                        </Form>
                    </>
                ) : (
                    <>
                        <div className="flex items-center gap-3">
                            <Badge variant="destructive">Disabled</Badge>
                            <p className="text-sm text-on-surface-variant">
                                Two-factor authentication is not enabled.
                            </p>
                        </div>
                        <p className="max-w-md text-sm leading-relaxed text-on-surface-variant">
                            When enabled, you will be prompted for a secure pin
                            during login. This pin can be retrieved from a
                            TOTP-supported application on your phone.
                        </p>

                        {hasSetupData ? (
                            <Button onClick={() => setShowSetupModal(true)}>
                                <ShieldCheck className="size-4" />
                                Continue Setup
                            </Button>
                        ) : (
                            <Form
                                {...enable.form()}
                                onSuccess={() => setShowSetupModal(true)}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-moss text-moss-foreground hover:bg-moss/90"
                                    >
                                        <ShieldCheck className="size-4" />
                                        Enable 2FA
                                    </Button>
                                )}
                            </Form>
                        )}
                    </>
                )}

                <TwoFactorSetupModal
                    isOpen={showSetupModal}
                    onClose={() => setShowSetupModal(false)}
                    twoFactorEnabled={twoFactorEnabled}
                    qrCodeSvg={qrCodeSvg}
                    manualSetupKey={manualSetupKey}
                    clearSetupData={clearSetupData}
                    fetchSetupData={fetchSetupData}
                    errors={errors}
                />
            </div>
        </section>
    );
}

function PartnerSection({ partner }: { partner: Partner | null }) {
    const [showUnlinkDialog, setShowUnlinkDialog] = useState(false);
    const partnerForm = useForm({ email: '' });

    function handleLink(e: React.FormEvent) {
        e.preventDefault();
        partnerForm.post(partnerStore.url(), {
            preserveScroll: true,
            onSuccess: () => partnerForm.reset(),
        });
    }

    function handleUnlink() {
        router.delete(partnerDestroy.url(), {
            preserveScroll: true,
            onFinish: () => setShowUnlinkDialog(false),
        });
    }

    return (
        <section className="flex flex-col gap-8 md:flex-row md:gap-16">
            <SectionHeader
                title="Partner Linking"
                description="Share your devotional journey with a companion or partner nearby."
            />

            <div className="flex-1">
                {partner ? (
                    <div className="rounded-2xl border border-border/50 bg-surface-container-low p-6">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex items-center gap-4">
                                <div className="flex size-11 items-center justify-center rounded-full bg-moss/10">
                                    <LinkIcon className="size-5 text-moss" />
                                </div>
                                <div>
                                    <p className="text-[10px] font-medium tracking-[0.15em] text-moss uppercase">
                                        Linked Partner
                                    </p>
                                    <p className="font-medium text-on-surface">
                                        {partner.name}
                                    </p>
                                    <p className="text-xs text-on-surface-variant">
                                        {partner.email}
                                    </p>
                                </div>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive-foreground hover:bg-destructive/10 hover:text-destructive-foreground"
                                onClick={() => setShowUnlinkDialog(true)}
                            >
                                <Link2Off className="size-4" />
                                <span className="hidden sm:inline">Unlink</span>
                            </Button>
                        </div>

                        <Dialog
                            open={showUnlinkDialog}
                            onOpenChange={setShowUnlinkDialog}
                        >
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Unlink Partner</DialogTitle>
                                    <DialogDescription>
                                        This will remove {partner.name} as your
                                        devotional partner. You can re-link at
                                        any time.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setShowUnlinkDialog(false)
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={handleUnlink}
                                    >
                                        <Unlink className="size-4" />
                                        Unlink Partner
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-2xl bg-primary text-primary-foreground">
                        <div className="relative p-8">
                            <div className="pointer-events-none absolute inset-0 opacity-[0.04]">
                                <div className="absolute top-4 right-8 size-32 rounded-full border border-current" />
                                <div className="absolute -bottom-4 -left-4 size-24 rounded-full border border-current" />
                            </div>

                            <div className="relative">
                                <h3 className="font-serif text-2xl font-medium tracking-tight">
                                    Connect Your Sanctuary
                                </h3>
                                <p className="mt-2 max-w-md text-sm leading-relaxed opacity-70">
                                    Link a partner to share devotional progress,
                                    observations, and encouragement with one
                                    another.
                                </p>

                                <form
                                    onSubmit={handleLink}
                                    className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start"
                                >
                                    <div className="flex-1">
                                        <Label
                                            htmlFor="partner-email"
                                            className="sr-only"
                                        >
                                            Partner email
                                        </Label>
                                        <Input
                                            id="partner-email"
                                            type="email"
                                            placeholder="partner@email.com"
                                            value={partnerForm.data.email}
                                            onChange={(e) =>
                                                partnerForm.setData(
                                                    'email',
                                                    e.target.value,
                                                )
                                            }
                                            className="border-primary-foreground/20 bg-primary-foreground/10 text-primary-foreground placeholder:text-primary-foreground/40 focus:border-primary-foreground/40"
                                        />
                                        {partnerForm.errors.email && (
                                            <p className="mt-1.5 text-xs text-red-300">
                                                {partnerForm.errors.email}
                                            </p>
                                        )}
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={
                                            partnerForm.processing ||
                                            !partnerForm.data.email
                                        }
                                        className="bg-moss text-moss-foreground hover:bg-moss/90"
                                    >
                                        <LinkIcon className="size-4" />
                                        Link Partner
                                    </Button>
                                </form>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

function NotificationsSection({
    preferences,
}: {
    preferences: NotificationPreferences;
}) {
    const [localPrefs, setLocalPrefs] = useState(preferences);

    function handleToggle(key: keyof NotificationPreferences) {
        const updated = { ...localPrefs, [key]: !localPrefs[key] };
        setLocalPrefs(updated);

        router.put(updatePreferences.url(), updated, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    const notificationItems: Array<{
        key: keyof NotificationPreferences;
        label: string;
        description: string;
    }> = [
        {
            key: 'reminder_notifications',
            label: 'Daily Reflection Reminders',
            description:
                'Receive a gentle nudge to continue your devotional journey each day.',
        },
        {
            key: 'completion_notifications',
            label: 'Partner Activity',
            description: 'Know when your partner completes a devotional entry.',
        },
        {
            key: 'observation_notifications',
            label: 'New Observations',
            description:
                'Get notified when your partner shares a reflection or observation.',
        },
        {
            key: 'new_theme_notifications',
            label: 'New Theme Publications',
            description:
                'Be the first to explore newly published devotional themes.',
        },
    ];

    return (
        <section className="flex flex-col gap-8 md:flex-row md:gap-16">
            <SectionHeader
                title="Notifications"
                description="Determine how and when The Sanctuary reaches out to you."
            />

            <div className="flex-1 space-y-1">
                {notificationItems.map((item) => (
                    <div
                        key={item.key}
                        className="flex items-center justify-between gap-6 rounded-xl px-1 py-4"
                    >
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium text-on-surface">
                                {item.label}
                            </p>
                            <p className="mt-0.5 text-xs leading-relaxed text-on-surface-variant">
                                {item.description}
                            </p>
                        </div>
                        <Switch
                            checked={localPrefs[item.key]}
                            onCheckedChange={() => handleToggle(item.key)}
                            className="data-[state=checked]:bg-moss"
                        />
                    </div>
                ))}
            </div>
        </section>
    );
}

function SocialAccountsSection({
    socialAccounts,
    availableProviders,
}: {
    socialAccounts: SocialAccount[];
    availableProviders: string[];
}) {
    const [disconnectingProvider, setDisconnectingProvider] = useState<
        string | null
    >(null);

    function handleDisconnect() {
        if (!disconnectingProvider) {
            return;
        }
        router.delete(disconnectSocial.url(disconnectingProvider), {
            preserveScroll: true,
            onFinish: () => setDisconnectingProvider(null),
        });
    }

    const connectedProviders = new Set(socialAccounts.map((a) => a.provider));

    return (
        <section className="flex flex-col gap-8 md:flex-row md:gap-16">
            <SectionHeader
                title="Connected Accounts"
                description="Manage social sign-in methods linked to your account."
            />

            <div className="flex-1 space-y-3">
                {availableProviders.map((provider) => {
                    const meta = providerMeta[provider];
                    if (!meta) {
                        return null;
                    }
                    const isConnected = connectedProviders.has(provider);
                    const Icon = meta.icon;

                    return (
                        <div
                            key={provider}
                            className="flex items-center justify-between gap-4 rounded-xl border border-border/50 bg-surface-container-low px-5 py-4"
                        >
                            <div className="flex items-center gap-4">
                                <div className="flex size-10 items-center justify-center rounded-lg bg-surface-container-highest">
                                    <Icon className="size-5 text-on-surface" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-on-surface">
                                        {meta.label}
                                    </p>
                                    {isConnected ? (
                                        <p className="flex items-center gap-1 text-[10px] font-medium tracking-[0.1em] text-moss uppercase">
                                            <Shield className="size-3" />
                                            Connected
                                        </p>
                                    ) : (
                                        <p className="text-[10px] font-medium tracking-[0.1em] text-on-surface-variant/50 uppercase">
                                            Not connected
                                        </p>
                                    )}
                                </div>
                            </div>

                            {isConnected ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive-foreground hover:bg-destructive/10"
                                    onClick={() =>
                                        setDisconnectingProvider(provider)
                                    }
                                >
                                    Disconnect
                                </Button>
                            ) : (
                                <a
                                    href={socialRedirect.url(provider)}
                                    className="inline-flex items-center gap-1.5 text-xs font-medium text-moss transition-colors hover:text-moss/80"
                                >
                                    Connect
                                    <ExternalLink className="size-3" />
                                </a>
                            )}
                        </div>
                    );
                })}

                <Dialog
                    open={disconnectingProvider !== null}
                    onOpenChange={(open) =>
                        !open && setDisconnectingProvider(null)
                    }
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                Disconnect{' '}
                                {disconnectingProvider
                                    ? providerMeta[disconnectingProvider]?.label
                                    : ''}
                            </DialogTitle>
                            <DialogDescription>
                                You will no longer be able to sign in with this
                                provider. Make sure you have another way to
                                access your account.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setDisconnectingProvider(null)}
                            >
                                Cancel
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={handleDisconnect}
                            >
                                Disconnect
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </section>
    );
}

function AppPreferencesSection() {
    return (
        <section className="flex flex-col gap-8 md:flex-row md:gap-16">
            <SectionHeader
                title="App Preferences"
                description="Tailor the visual and functional experience of the app."
            />

            <div className="flex-1">
                <Link
                    href={editAppearance.url()}
                    prefetch
                    className="group flex items-center justify-between rounded-xl border border-border/50 bg-surface-container-low px-5 py-4 transition-colors hover:bg-surface-container"
                >
                    <div className="flex items-center gap-4">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-surface-container-highest">
                            <Palette className="size-5 text-on-surface" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-on-surface">
                                Appearance
                            </p>
                            <p className="text-[10px] font-medium tracking-[0.1em] text-on-surface-variant/50 uppercase">
                                Theme &amp; Display
                            </p>
                        </div>
                    </div>
                    <ArrowRight className="size-4 text-on-surface-variant/30 transition-transform group-hover:translate-x-1 group-hover:text-on-surface-variant" />
                </Link>
            </div>
        </section>
    );
}

function DeleteAccountSection() {
    const passwordInputRef = useRef<HTMLInputElement>(null);

    return (
        <section className="flex flex-col gap-8 md:flex-row md:gap-16">
            <SectionHeader
                title="Delete Account"
                description="Permanently delete your account and all associated data."
            />

            <div className="flex-1">
                <div className="rounded-2xl border border-destructive/20 bg-destructive/5 p-6">
                    <p className="text-sm leading-relaxed text-on-surface-variant">
                        Once your account is deleted, all of its resources and
                        data will be permanently removed. This action cannot be
                        undone.
                    </p>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive" className="mt-4">
                                <Trash2 className="size-4" />
                                Delete Account
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Are you sure you want to delete your
                                    account?
                                </DialogTitle>
                                <DialogDescription>
                                    Once your account is deleted, all of its
                                    resources and data will also be permanently
                                    deleted. Please enter your password to
                                    confirm.
                                </DialogDescription>
                            </DialogHeader>

                            <Form
                                {...UserController.destroy.form()}
                                options={{ preserveScroll: true }}
                                onError={() =>
                                    passwordInputRef.current?.focus()
                                }
                                resetOnSuccess
                                className="space-y-6"
                            >
                                {({
                                    resetAndClearErrors,
                                    processing,
                                    errors,
                                }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="delete-password"
                                                className="sr-only"
                                            >
                                                Password
                                            </Label>
                                            <Input
                                                id="delete-password"
                                                type="password"
                                                name="password"
                                                ref={passwordInputRef}
                                                placeholder="Password"
                                                autoComplete="current-password"
                                            />
                                            <InputError
                                                message={errors.password}
                                            />
                                        </div>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    variant="secondary"
                                                    onClick={() =>
                                                        resetAndClearErrors()
                                                    }
                                                >
                                                    Cancel
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                variant="destructive"
                                                disabled={processing}
                                                asChild
                                            >
                                                <button type="submit">
                                                    Delete Account
                                                </button>
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </section>
    );
}

export default function DevotionalSettings({
    partner,
    preferences,
    socialAccounts,
    availableProviders,
    twoFactorEnabled,
    status,
}: Props) {
    return (
        <DevotionalLayout>
            <Head title="Settings" />

            <div className="px-6 py-8 md:px-12 lg:px-16">
                {/* Editorial Header */}
                <header className="mb-14 max-w-5xl md:mb-20">
                    <span className="text-[11px] font-semibold tracking-[0.3em] text-moss uppercase">
                        Account Configuration
                    </span>
                    <h1 className="mt-3 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl lg:text-7xl">
                        Settings
                    </h1>
                </header>

                {/* Sections */}
                <div className="max-w-5xl space-y-16">
                    <ProfileSection status={status} />
                    <SectionDivider />
                    <TwoFactorSection twoFactorEnabled={twoFactorEnabled} />
                    <SectionDivider />
                    <PartnerSection partner={partner} />
                    <SectionDivider />
                    <NotificationsSection preferences={preferences} />
                    <SectionDivider />
                    <SocialAccountsSection
                        socialAccounts={socialAccounts}
                        availableProviders={availableProviders}
                    />
                    <SectionDivider />
                    <AppPreferencesSection />
                    <SectionDivider />
                    <DeleteAccountSection />
                </div>

                {/* Footer spacing */}
                <div className="h-16" />
            </div>
        </DevotionalLayout>
    );
}
