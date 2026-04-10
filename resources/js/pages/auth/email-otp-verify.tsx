import EmailOtpController from '@/actions/App/Http/Controllers/EmailOtpController';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

interface EmailOtpVerifyProps {
    email: string;
}

export default function EmailOtpVerify({ email }: EmailOtpVerifyProps) {
    const emailOtpCreate = EmailOtpController.create();

    return (
        <AuthLayout
            title="Verify your email"
            description="Enter the 6-digit code sent to your email"
        >
            <Head title="Verify email code" />

            <div className="space-y-6">
                <Form
                    {...EmailOtpController.verify.form()}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="email" value={email} />

                            <div className="grid gap-2">
                                <Label htmlFor="code">Verification code</Label>
                                <Input
                                    id="code"
                                    type="text"
                                    name="code"
                                    required
                                    autoFocus
                                    autoComplete="one-time-code"
                                    inputMode="numeric"
                                    maxLength={6}
                                    placeholder="000000"
                                    className="text-center text-lg tracking-widest"
                                />
                                <InputError message={errors.code} />
                                <InputError message={errors.email} />
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing && (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                )}
                                Verify and sign in
                            </Button>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Didn't receive a code?</span>
                    <TextLink href={emailOtpCreate.url}>Try again</TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
