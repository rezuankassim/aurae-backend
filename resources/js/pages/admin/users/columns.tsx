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
import { destroy, edit, show } from '@/routes/admin/users';
import { User } from '@/types';
import { Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import { MoreHorizontal } from 'lucide-react';
export const columns = (showDeleted: boolean): ColumnDef<User>[] => [
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
            const { is_admin, guest } = row.original;

            if (is_admin) {
                return <Badge variant="destructive">Admin</Badge>;
            }

            if (guest) {
                return <Badge variant="secondary">Guest</Badge>;
            }

            return <Badge>Customer</Badge>;
        },
        filterFn: (row, _columnId, filterValue) => {
            if (filterValue === '') return true;
            if (filterValue === 'guest') return !row.original.is_admin && !!row.original.guest;
            if (filterValue === '0') return !row.original.is_admin && !row.original.guest;
            if (filterValue === '1') return row.original.is_admin;
            return true;
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.original.status;
            return <Badge variant={status ? 'default' : 'outline'}>{status ? 'Active' : 'Inactive'}</Badge>;
        },
    },
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'created_at',
        header: 'Created at',
        cell: ({ row }) => {
            const createdAt = row.getValue('created_at') as string;
            return dayjs(createdAt).format('DD MMM YYYY, HH:mm');
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const querySuffix = showDeleted ? '?show_deleted=1' : '';
            const isDeleted = Boolean(row.original.deleted_at);
            const handleDelete = () => {
                if (confirm('Are you sure you want to delete this user?')) {
                    router.delete(`${destroy(row.original.id).url}${querySuffix}`);
                }
            };

            const handleRecover = () => {
                if (confirm('Are you sure you want to recover this user?')) {
                    router.put(`/admin/users/${row.original.id}/restore${querySuffix}`);
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
                        {!isDeleted && (
                            <>
                                <DropdownMenuItem asChild>
                                    <Link className="hover:cursor-pointer" href={show(row.original.id).url}>
                                        View
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link className="hover:cursor-pointer" href={edit(row.original.id).url}>
                                        Edit
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                            </>
                        )}
                        {isDeleted ? (
                            <DropdownMenuItem className="hover:cursor-pointer" onClick={handleRecover}>
                                Recover
                            </DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem className="text-destructive hover:cursor-pointer focus:text-destructive" onClick={handleDelete}>
                                Delete
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
