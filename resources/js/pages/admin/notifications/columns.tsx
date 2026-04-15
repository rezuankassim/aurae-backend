import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { show } from '@/routes/admin/notifications';
import { AdminNotification } from '@/types';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import { MoreHorizontal } from 'lucide-react';

export const columns: ColumnDef<AdminNotification>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'type',
        header: 'Type',
        cell: ({ row }) => {
            const type = row.getValue('type') as string;
            return (
                <Badge variant={type === 'emergency' ? 'destructive' : 'secondary'} className="capitalize">
                    {type}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'title',
        header: 'Title',
        cell: ({ row }) => {
            const title = row.getValue('title') as string;
            return <span className="font-medium">{title}</span>;
        },
    },
    {
        id: 'user',
        header: 'User',
        cell: ({ row }) => {
            const data = row.original.data;
            if (!data) return 'N/A';
            return (
                <div>
                    <span>
                        {data.user_name} {data.is_guest ? '(Guest)' : ''}
                    </span>
                    <p className="text-xs text-muted-foreground">{data.user_phone}</p>
                </div>
            );
        },
    },
    {
        accessorKey: 'read_at',
        header: 'Status',
        cell: ({ row }) => {
            const readAt = row.getValue('read_at') as string | null;
            return <Badge variant={readAt ? 'outline' : 'default'}>{readAt ? 'Read' : 'Unread'}</Badge>;
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Date',
        cell: ({ row }) => {
            return dayjs(row.getValue('created_at') as string).format('DD MMM YYYY, HH:mm');
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                        <DropdownMenuItem asChild>
                            <Link className="hover:cursor-pointer" href={show(row.original.id).url}>
                                View
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
