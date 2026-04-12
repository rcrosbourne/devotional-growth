import EmailOtpController from '@/actions/App/Http/Controllers/EmailOtpController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight, LoaderCircle } from 'lucide-react';

interface EmailOtpVerifyProps {
    email: string;
}

export default function EmailOtpVerify({ email }: EmailOtpVerifyProps) {
    const emailOtpCreate = EmailOtpController.create();

    return (
        <AuthLayout
            title="Verify Your Email"
            description="We've sent a code to your email. Please enter it below to securely log in."
        >
            <Head title="Verify email code" />

            <div className="space-y-6">
                {/* Label */}
                <p className="text-center text-xs tracking-[0.2em] text-muted-foreground uppercase">
                    Security Protocol
                </p>

                <Form
                    {...EmailOtpController.verify.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="email" value={email} />

                            <div className="space-y-3">
                                <div className="flex justify-center">
                                    <InputOTP
                                        maxLength={6}
                                        name="code"
                                        autoFocus
                                    >
                                        <InputOTPGroup className="gap-2.5">
                                            {[0, 1, 2, 3, 4, 5].map((i) => (
                                                <InputOTPSlot
                                                    key={i}
                                                    index={i}
                                                    className="h-14 w-12 rounded-lg border-border/60 bg-background text-lg font-medium shadow-sm first:rounded-l-lg first:border-l last:rounded-r-lg"
                                                />
                                            ))}
                                        </InputOTPGroup>
                                    </InputOTP>
                                </div>
                                <InputError
                                    message={errors.code}
                                    className="text-center"
                                />
                                <InputError
                                    message={errors.email}
                                    className="text-center"
                                />
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
                                        Verify Code
                                        <ArrowRight className="size-4" />
                                    </>
                                )}
                            </Button>
                        </>
                    )}
                </Form>

                {/* Resend link */}
                <p className="text-center text-sm text-muted-foreground">
                    Didn&apos;t receive it?{' '}
                    <Link
                        href={emailOtpCreate.url}
                        className="font-bold tracking-[0.05em] text-foreground uppercase underline decoration-border underline-offset-4 transition-colors hover:decoration-foreground"
                    >
                        Resend Code
                    </Link>
                </p>
            </div>

            {/* Wisdom quote */}
            <p className="mt-10 text-center text-xs leading-relaxed text-muted-foreground/70">
                &ldquo;Patience is the calm acceptance that things can happen in
                a different order than the one you have in mind.&rdquo;
            </p>
        </AuthLayout>
    );
}
