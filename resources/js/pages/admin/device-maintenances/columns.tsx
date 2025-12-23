import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { show } from '@/routes/admin/device-maintenances';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { MoreHorizontal } from 'lucide-react';

interface Device {
    id: string;
    name: string;
    uuid: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface DeviceMaintenance {
    id: number;
    status: number;
    user_id: number;
    device_id: string;
    device: Device;
    user: User;
    maintenance_requested_at: string;
    factory_maintenance_requested_at: string | null;
    is_factory_approved: boolean;
    is_user_approved: boolean;
    created_at: string;
}

const getStatusBadge = (status: number) => {
    const statusMap: Record<number, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
        0: { label: 'Pending', variant: 'secondary' },
        1: { label: 'Pending Factory', variant: 'default' },
        2: { label: 'In Progress', variant: 'outline' },
        3: { label: 'Completed', variant: 'default' },
    };

    const config = statusMap[status] || { label: 'Unknown', variant: 'secondary' };
    return <Badge variant={config.variant}>{config.label}</Badge>;
};

export const columns: ColumnDef<DeviceMaintenance>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'user.name',
        header: 'User',
        cell: ({ row }) => {
            return (
                <div>
                    <p className="font-medium">{row.original.user.name}</p>
                    <p className="text-xs text-muted-foreground">{row.original.user.email}</p>
                </div>
            );
        },
    },
    {
        accessorKey: 'device.name',
        header: 'Device',
        cell: ({ row }) => {
            return (
                <div>
                    <p className="font-medium">{row.original.device?.name || '-'}</p>
                    <p className="text-xs text-muted-foreground">{row.original.device?.uuid || '-'}</p>
                </div>
            );
        },
    },
    {
        accessorKey: 'maintenance_requested_at',
        header: 'Requested Date',
        cell: ({ row }) => {
            const date = row.getValue('maintenance_requested_at') as string;
            return format(new Date(date), 'MMM d, yyyy HH:mm');
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status') as number;
            return getStatusBadge(status);
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Created',
        cell: ({ row }) => {
            const date = row.getValue('created_at') as string;
            return format(new Date(date), 'MMM d, yyyy');
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
                                View Details
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
