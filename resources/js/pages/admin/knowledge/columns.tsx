import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Knowledge } from '@/types';
import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { edit, show } from '@/routes/admin/knowledge';
import { Link } from '@inertiajs/react';
import dayjs from 'dayjs';

export const columns: ColumnDef<Knowledge>[] = [
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
        accessorKey: 'is_published',
        header: 'Published?',
        cell: ({ row }) => {
            return row.getValue('is_published') ? <Badge>Yes</Badge> : <Badge variant="outline">No</Badge>;
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
                        <DropdownMenuItem asChild>
                            <Link className="hover:cursor-pointer" href={edit(row.original.id).url}>
                                Edit
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
