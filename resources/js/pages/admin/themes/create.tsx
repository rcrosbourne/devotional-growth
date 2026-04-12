import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import DevotionalLayout from '@/layouts/devotional-layout';
import { index, store } from '@/routes/admin/themes';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { FormEventHandler } from 'react';

export default function ThemesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store.url());
    };

    return (
        <DevotionalLayout>
            <Head title="Create Theme" />

            <div className="px-6 py-6 md:px-8">
                <Link
                    href={index.url()}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to Themes
                </Link>

                <div className="mt-4">
                    <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        New Theme
                    </p>
                    <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface">
                        Create New Theme
                    </h1>
                </div>

                <form onSubmit={handleSubmit} className="mt-8 max-w-2xl">
                    <div className="space-y-6">
                        <div className="space-y-2">
                            <Label
                                htmlFor="name"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Theme Title
                            </Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                placeholder="e.g. The Architecture of Silence"
                                className="font-serif text-lg"
                                autoFocus
                            />
                            {errors.name && (
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label
                                htmlFor="description"
                                className="text-xs font-medium tracking-[0.1em] text-on-surface-variant uppercase"
                            >
                                Description
                            </Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                placeholder="Begin the narrative journey..."
                                rows={4}
                            />
                            {errors.description && (
                                <p className="text-sm text-destructive">
                                    {errors.description}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-8 flex items-center gap-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-moss text-moss-foreground hover:bg-moss/90"
                        >
                            Create Theme
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={index.url()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </DevotionalLayout>
    );
}
