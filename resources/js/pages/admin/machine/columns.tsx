import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { activate, deactivate, destroy, edit, show, unbind } from '@/routes/admin/machines';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';
import { Machine } from './index';

export const columns: ColumnDef<Machine>[] = [
    {
        accessorKey: 'serial_number',
        header: 'Serial Number',
    },
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.original.status;
            return <Badge variant={status === 1 ? 'default' : 'secondary'}>{status === 1 ? 'Active' : 'Inactive'}</Badge>;
        },
    },
    {
        accessorKey: 'user',
        header: 'Bound To',
        cell: ({ row }) => {
            const user = row.original.user;
            return user ? (
                <div>
                    <div className="font-medium">{user.name}</div>
                    <div className="text-sm text-muted-foreground">{user.email}</div>
                </div>
            ) : (
                <Badge variant="outline">Unbound</Badge>
            );
        },
    },
    {
        accessorKey: 'device',
        header: 'Tablet',
        cell: ({ row }) => {
            const device = row.original.device;
            return device ? <div className="text-sm">{device.name}</div> : <span className="text-muted-foreground">-</span>;
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const machine = row.original;

            const handleUnbind = () => {
                if (confirm('Are you sure you want to unbind this machine?')) {
                    router.post(unbind(machine).url);
                }
            };

            const handleActivate = () => {
                router.post(activate(machine).url);
            };

            const handleDeactivate = () => {
                router.post(deactivate(machine).url);
            };

            const handleDelete = () => {
                if (confirm('Are you sure you want to delete this machine?')) {
                    router.delete(destroy(machine).url);
                }
            };

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
                        <DropdownMenuItem onClick={() => router.get(show(machine).url)}>View Details</DropdownMenuItem>
                        <DropdownMenuItem onClick={() => router.get(edit(machine).url)}>Edit</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        {machine.user && <DropdownMenuItem onClick={handleUnbind}>Unbind from User</DropdownMenuItem>}
                        {machine.status === 1 ? (
                            <DropdownMenuItem onClick={handleDeactivate}>Deactivate</DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem onClick={handleActivate}>Activate</DropdownMenuItem>
                        )}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={handleDelete} className="text-red-600">
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
