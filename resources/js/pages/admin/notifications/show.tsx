import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/notifications';
import { AdminNotification, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: index().url,
    },
    {
        title: 'Notification Details',
        href: '#',
    },
];

export default function AdminNotificationsShow({ notification }: { notification: AdminNotification }) {
    const data = notification.data;
    const isEmergency = notification.type === 'emergency';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification Details" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Notification Details" description="View the full notification information" />

                <div className="space-y-6">
                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Type</h2>
                        <div>
                            <Badge variant={isEmergency ? 'destructive' : 'secondary'} className="capitalize">
                                {notification.type}
                            </Badge>
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Title</h2>
                        <span>{notification.title}</span>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Message</h2>
                        <p className="whitespace-pre-wrap">{notification.body}</p>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Status</h2>
                        <div>
                            <Badge variant={notification.read_at ? 'outline' : 'default'}>{notification.read_at ? 'Read' : 'Unread'}</Badge>
                            {notification.read_at && (
                                <span className="ml-2 text-sm text-muted-foreground">
                                    {dayjs(notification.read_at).format('DD MMM YYYY, HH:mm')}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">Created at</h2>
                        <span>{dayjs(notification.created_at).format('DD MMM YYYY, HH:mm')}</span>
                    </div>

                    {data && (
                        <>
                            <hr className="border-sidebar-border/50" />
                            <h2 className="text-lg font-semibold">User / Guest Information</h2>

                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Name</h2>
                                <span>
                                    {data.user_name} {data.is_guest ? <Badge variant="outline">Guest</Badge> : null}
                                </span>
                            </div>

                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Phone</h2>
                                <span>{data.user_phone}</span>
                            </div>

                            <hr className="border-sidebar-border/50" />
                            <h2 className="text-lg font-semibold">Program Information</h2>

                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Therapy</h2>
                                <span>{data.therapy_name}</span>
                            </div>

                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Duration</h2>
                                <span>{data.program_duration}</span>
                            </div>

                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Emergency</h2>
                                <div>
                                    <Badge variant={data.emergency ? 'destructive' : 'secondary'}>{data.emergency ? 'Yes' : 'No'}</Badge>
                                </div>
                            </div>

                            {data.program_error_message && (
                                <div className="grid gap-2">
                                    <h2 className="text-sm font-medium">Error Message</h2>
                                    <p className="whitespace-pre-wrap text-destructive">{data.program_error_message}</p>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
