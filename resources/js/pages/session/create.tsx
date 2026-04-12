import EmailOtpController from '@/actions/App/Http/Controllers/EmailOtpController';
import SocialLoginController from '@/actions/App/Http/Controllers/SocialLoginController';
import {
    AppleIcon,
    GitHubIcon,
    GoogleIcon,
} from '@/components/icons/social-icons';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link } from '@inertiajs/react';
import { Mail } from 'lucide-react';

export default function Login() {
    return (
        <AuthLayout
            variant="split"
            title="Continue Your Journey"
            description="Enter your sanctuary to continue your daily reflections."
        >
            <Head title="Sign In" />

            <div className="space-y-6">
                {/* Social Login Buttons */}
                <div className="space-y-3">
                    <a
                        href={SocialLoginController.redirect.url('google')}
                        className="flex h-12 w-full items-center justify-center gap-3 rounded-lg border border-border bg-background text-sm font-medium text-foreground transition-colors hover:bg-accent"
                    >
                        <GoogleIcon className="size-5" />
                        Continue with Google
                    </a>

                    <a
                        href={SocialLoginController.redirect.url('apple')}
                        className="flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-[#1a1a18] text-sm font-medium text-white transition-opacity hover:opacity-90 dark:bg-[#ede9e0] dark:text-[#141210]"
                    >
                        <AppleIcon className="size-5" />
                        Continue with Apple
                    </a>

                    <a
                        href={SocialLoginController.redirect.url('github')}
                        className="flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-[#2d2d2a] text-sm font-medium text-white transition-opacity hover:opacity-90 dark:bg-[#3d3a35] dark:text-[#ede9e0]"
                    >
                        <GitHubIcon className="size-5" />
                        Continue with GitHub
                    </a>
                </div>

                {/* Divider */}
                <div className="flex items-center gap-4">
                    <div className="h-px flex-1 bg-border" />
                    <span className="text-xs text-muted-foreground">or</span>
                    <div className="h-px flex-1 bg-border" />
                </div>

                {/* Email OTP Login */}
                <Link
                    href={EmailOtpController.create().url}
                    className="flex h-12 w-full items-center justify-center gap-2.5 rounded-lg bg-primary text-sm font-semibold tracking-[0.1em] text-primary-foreground uppercase transition-opacity hover:opacity-90"
                >
                    <Mail className="size-4" />
                    Login with Email
                </Link>

                {/* TODO: Link to a registration/request-access page when the route exists */}
                <p className="text-center text-sm text-muted-foreground">
                    New curator?{' '}
                    <Link
                        href="/register"
                        className="font-semibold text-foreground underline decoration-border underline-offset-4 transition-colors hover:decoration-foreground"
                    >
                        Request access
                    </Link>
                </p>
            </div>
        </AuthLayout>
    );
}
