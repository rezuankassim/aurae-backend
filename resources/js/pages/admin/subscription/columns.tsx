import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { edit } from '@/routes/admin/subscription';
import { Link, router } from '@inertiajs/react';
import dayjs from 'dayjs';
import { Subscription } from './index';

export const columns: ColumnDef<Subscription>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'title',
        header: 'Title',
    },
    {
        accessorKey: 'pricing_title',
        header: 'Pricing',
    },
    {
        accessorKey: 'max_devices',
        header: 'Max Devices',
    },
    {
        accessorKey: 'price',
        header: 'Price',
        cell: ({ row }) => {
            return `RM ${row.getValue('price')}`;
        },
    },
    {
        accessorKey: 'is_active',
        header: 'Active',
        cell: ({ row }) => {
            return row.getValue('is_active') ? <Badge>Yes</Badge> : <Badge variant="outline">No</Badge>;
        },
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
            const handleDelete = () => {
                if (confirm('Are you sure you want to delete this subscription?')) {
                    router.delete(edit(row.original.id).url.replace('/edit', ''));
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
                        <DropdownMenuItem asChild>
                            <Link className="hover:cursor-pointer" href={edit(row.original.id).url}>
                                Edit
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem className="text-red-600 hover:cursor-pointer" onClick={handleDelete}>
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
