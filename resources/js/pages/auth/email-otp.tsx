import EmailOtpController from '@/actions/App/Http/Controllers/EmailOtpController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight, LoaderCircle, Lock, Sparkles } from 'lucide-react';

export default function EmailOtp() {
    return (
        <AuthLayout
            title="Enter Your Email"
            description="A unique 6-digit verification code will be dispatched to your registered inbox"
        >
            <Head title="Sign in with email" />

            <div className="space-y-6">
                {/* Lock icon */}
                <div className="flex justify-center">
                    <div className="flex size-12 items-center justify-center rounded-full border border-border/60 bg-background text-muted-foreground">
                        <Lock className="size-5" />
                    </div>
                </div>

                {/* Label */}
                <p className="text-center text-xs tracking-[0.2em] text-muted-foreground uppercase">
                    Secure Access
                </p>

                <Form
                    {...EmailOtpController.store.form()}
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-2">
                                <label
                                    htmlFor="email"
                                    className="block text-xs tracking-[0.15em] text-muted-foreground uppercase"
                                >
                                    Email Address
                                </label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    placeholder="you@example.com"
                                    className="h-12 border-border/60 bg-background text-base"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <Button
                                type="submit"
                                className="h-12 w-full rounded-lg text-sm font-semibold tracking-[0.1em] uppercase"
                                disabled={processing}
                            >
                                {processing ? (
                                    <LoaderCircle className="size-4 animate-spin" />
                                ) : (
                                    <>
                                        Send Code
                                        <ArrowRight className="size-4" />
                                    </>
                                )}
                            </Button>
                        </>
                    )}
                </Form>

                {/* Back link */}
                <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                    <Sparkles className="size-3.5" />
                    <Link
                        href={login()}
                        className="tracking-[0.1em] uppercase transition-colors hover:text-foreground"
                    >
                        Back to Social Login
                    </Link>
                </div>
            </div>

            {/* Tagline */}
            <div className="mt-10 flex items-center justify-center gap-3">
                <div className="h-px w-8 bg-border" />
                <span className="font-serif text-sm text-muted-foreground italic">
                    The Digital Curator
                </span>
                <div className="h-px w-8 bg-border" />
            </div>
        </AuthLayout>
    );
}
