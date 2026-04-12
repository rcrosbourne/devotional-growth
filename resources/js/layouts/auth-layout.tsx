import AuthCenteredLayout from '@/layouts/auth/auth-simple-layout';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';

interface AuthLayoutProps {
    children: React.ReactNode;
    title: string;
    description: string;
    variant?: 'centered' | 'split';
}

export default function AuthLayout({
    children,
    title,
    description,
    variant = 'centered',
    ...props
}: AuthLayoutProps) {
    if (variant === 'split') {
        return (
            <AuthSplitLayout title={title} description={description} {...props}>
                {children}
            </AuthSplitLayout>
        );
    }

    return (
        <AuthCenteredLayout title={title} description={description} {...props}>
            {children}
        </AuthCenteredLayout>
    );
}
