import AppLogo from '@/components/app-logo';
import { Head, Link } from '@inertiajs/react';

export default function TermsAndConditions({ content }: { content: string }) {
    return (
        <>
            <Head title="Terms and Conditions" />

            <div className="min-h-screen bg-background">
                <header className="border-b">
                    <div className="container mx-auto flex h-16 items-center px-4">
                        <Link href="/" className="flex items-center gap-2">
                            <AppLogo />
                        </Link>
                    </div>
                </header>

                <main className="container mx-auto px-4 py-8">
                    <h1 className="mb-8 text-3xl font-bold">Terms and Conditions</h1>

                    {content ? (
                        <article className="prose max-w-none dark:prose-invert">
                            <div dangerouslySetInnerHTML={{ __html: content }} />
                        </article>
                    ) : (
                        <p className="text-muted-foreground">No terms and conditions have been published yet.</p>
                    )}
                </main>
            </div>
        </>
    );
}
