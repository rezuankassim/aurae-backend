import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/feedbacks';
import { BreadcrumbItem, Feedback } from '@/types';
import { Head } from '@inertiajs/react';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Feedbacks',
        href: index().url,
    },
    {
        title: 'Show feedback',
        href: '#',
    },
];

export default function FeedbacksShow({ feedback }: { feedback: Feedback }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Show Feedback" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Show Feedback" description="View the feedback details submitted by user" />

                <div className="space-y-6">
                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">User</h2>

                        <span>
                            {feedback.user?.name} ({feedback.user?.email})
                        </span>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Description</h2>

                        <p className="whitespace-pre-wrap">{feedback.description}</p>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Submitted at</h2>

                        <span>{dayjs(feedback.created_at).format('DD MMM YYYY, HH:mm')}</span>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
