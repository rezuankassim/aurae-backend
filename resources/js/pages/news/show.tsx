import AppLayout from '@/layouts/app-layout';
import { News, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { index } from '@/routes/news';

import dayjs from 'dayjs';
import { Clock } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'News',
        href: index().url,
    },
    {
        title: 'Show News',
        href: '#',
    },
];

export default function ShowNews({ news }: { news: News }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Show News" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Show News" description="Manage system's news, create new or publish" />
                </div>

                <span className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Clock className="size-5" />
                    <time className="mb-0.5">{dayjs(news.published_at).format('MMMM DD YYYY, HH:mm')}</time>
                </span>

                <h1 className="mt-4 text-lg font-extrabold">{news.title}</h1>

                {news.image_url ? <img src={news.image_url} alt={news.title} className="mt-2 max-h-8/12 w-full object-cover" /> : null}

                <article className="prose mt-6 max-w-none prose-invert">
                    <div dangerouslySetInnerHTML={{ __html: news.html_content }} />
                </article>
            </div>
        </AppLayout>
    );
}
