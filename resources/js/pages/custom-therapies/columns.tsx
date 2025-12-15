import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { destroy, edit } from '@/routes/custom-therapies';
import { Therapy } from '@/types';
import { Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import { MoreHorizontal } from 'lucide-react';

export const columns: ColumnDef<Therapy>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return isActive ? <Badge>Active</Badge> : <Badge variant="destructive">Inactive</Badge>;
        },
        filterFn: (row, id, value) => {
            if (value === 'all') return true;
            if (value === 'active') return row.getValue(id) === true;
            if (value === 'inactive') return row.getValue(id) === false;
            return true;
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
                if (confirm('Are you sure you want to delete this custom therapy?')) {
                    router.delete(destroy(row.original.id).url);
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
                        <DropdownMenuItem className="text-destructive hover:cursor-pointer" onClick={handleDelete}>
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
