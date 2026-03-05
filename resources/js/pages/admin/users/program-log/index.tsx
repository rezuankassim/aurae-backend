import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import UsersLayout from '@/layouts/users/layout';
import { index } from '@/routes/admin/users';
import { BreadcrumbItem, ProgramLog, User } from '@/types';
import { Head } from '@inertiajs/react';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
    {
        title: 'Program Logs',
        href: '',
    },
];

export default function UsersProgramLogIndex({ user, programLogs }: { user: User; programLogs: ProgramLog[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="View user program logs" />

            <UsersLayout id_record={user.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <HeadingSmall title="Program logs" description="View user program activity logs" />

                    <div className="overflow-hidden rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Therapy</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Started at</TableHead>
                                    <TableHead>Ended at</TableHead>
                                    <TableHead>Logged at</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {programLogs.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-muted-foreground text-center">
                                            No program logs found.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {programLogs.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell>{log.therapy?.name ?? '-'}</TableCell>
                                        <TableCell>
                                            {log.action === 'start' ? (
                                                <Badge>Start</Badge>
                                            ) : (
                                                <Badge variant="destructive">Stop</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>{log.program_duration}</TableCell>
                                        <TableCell>
                                            {log.program_started_at ? dayjs(log.program_started_at).format('DD MMM YYYY, HH:mm') : '-'}
                                        </TableCell>
                                        <TableCell>
                                            {log.program_ended_at ? dayjs(log.program_ended_at).format('DD MMM YYYY, HH:mm') : '-'}
                                        </TableCell>
                                        <TableCell>{dayjs(log.created_at).format('DD MMM YYYY, HH:mm')}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </UsersLayout>
        </AppLayout>
    );
}
