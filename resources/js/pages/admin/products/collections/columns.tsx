import { Button } from '@/components/ui/button';
import { destroy } from '@/routes/admin/products/collections';
import { Collection } from '@/types';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

export const columns: ColumnDef<Collection>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        id: 'name',
        header: 'Name',
        cell: ({ row }) => {
            return row.original.attribute_data.name.en;
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            return (
                <Button variant="destructive" size="sm" asChild>
                    <Link className="hover:cursor-pointer" href={destroy([row.original.pivot.product_id, row.original.id])}>
                        Detach
                    </Link>
                </Button>
            );
        },
    },
];
