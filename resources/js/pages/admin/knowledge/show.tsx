import AppLayout from '@/layouts/app-layout';
import { Knowledge, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { index } from '@/routes/admin/knowledge';

import dayjs from 'dayjs';
import { Clock } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Knowledge Center Management',
        href: index().url,
    },
    {
        title: 'Show Knowledge',
        href: '#',
    },
];

export default function ShowKnowledge({ knowledge }: { knowledge: Knowledge }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Show Knowledge" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Show Knowledge" description="View system knowledge information" />
                </div>

                <span className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Clock className="size-5" />
                    {knowledge.published_at ? (
                        <time className="mb-0.5">{dayjs(knowledge.published_at).format('MMMM DD YYYY, HH:mm')}</time>
                    ) : (
                        <span className="italic">Unpublished</span>
                    )}
                </span>

                <h1 className="mt-4 text-lg font-extrabold">{knowledge.title}</h1>

                {/* Show uploaded video if exists, otherwise show YouTube embed */}
                {knowledge.video_path ? (
                    <video src={`/storage/${knowledge.video_path}`} controls className="w-full rounded-lg" />
                ) : knowledge.video_url ? (
                    <iframe
                        src={knowledge.video_url}
                        title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerPolicy="strict-origin-when-cross-origin"
                        allowFullScreen
                        className="h-96 w-full"
                    ></iframe>
                ) : null}

                <article className="prose mt-6 max-w-none prose-invert">
                    <div dangerouslySetInnerHTML={{ __html: knowledge.html_content }} />
                </article>
            </div>
        </AppLayout>
    );
}
