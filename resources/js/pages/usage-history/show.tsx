import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import usageHistory from '@/routes/usage-history';
import { BreadcrumbItem, UsageHistory } from '@/types';
import { Head } from '@inertiajs/react';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usage History',
        href: usageHistory.index().url,
    },
    {
        title: 'Show Usage History',
        href: '#',
    },
];

export default function UsageHistoryShow({ usageHistory }: { usageHistory: UsageHistory }) {
    const content = usageHistory.content as {
        duration?: number;
        force_stopped?: boolean;
        started_at?: string;
        ended_at?: string;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Show Usage History" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Show Usage History" description="View the details of your therapy usage" />

                <div className="space-y-6">
                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Status</h2>

                        {content?.force_stopped ? (
                            <Badge variant="destructive">Force Stopped</Badge>
                        ) : (
                            <Badge className="bg-green-600 text-white hover:bg-green-700">Completed</Badge>
                        )}
                    </div>

                    {usageHistory.therapy && (
                        <div className="grid gap-2">
                            <h2 className="text-sm font-medium">Therapy</h2>

                            {usageHistory.therapy.image_url && (
                                <img
                                    src={usageHistory.therapy.image_url}
                                    alt={usageHistory.therapy.name}
                                    className="aspect-3/2 max-w-sm rounded-md object-cover"
                                />
                            )}

                            <span>{usageHistory.therapy.name}</span>
                        </div>
                    )}

                    {content?.duration !== undefined && (
                        <div className="grid gap-2">
                            <h2 className="text-sm font-medium">Duration</h2>

                            <span>{content.duration} minutes</span>
                        </div>
                    )}

                    <div className="grid grid-flow-col gap-2">
                        {content?.started_at && (
                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Started date</h2>

                                <span>{dayjs(content.started_at).format('DD MMM YYYY')}</span>
                            </div>
                        )}

                        {content?.started_at && (
                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Started time</h2>

                                <span>{dayjs(content.started_at).format('hh:mm:ss a')}</span>
                            </div>
                        )}
                    </div>

                    <div className="grid grid-flow-col gap-2">
                        {content?.ended_at && (
                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Ended date</h2>

                                <span>{dayjs(content.ended_at).format('DD MMM YYYY')}</span>
                            </div>
                        )}

                        {content?.ended_at && (
                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Ended time</h2>

                                <span>{dayjs(content.ended_at).format('hh:mm:ss a')}</span>
                            </div>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Created at</h2>

                        <span>{dayjs(usageHistory.created_at).format('DD MMM YYYY, HH:mm')}</span>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
