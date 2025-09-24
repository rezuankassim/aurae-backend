import { Button } from '@/components/ui/button';
import { show } from '@/routes/health-reports';
import { HealthReport } from '@/types';
import { ColumnDef } from '@tanstack/react-table';

import dayjs from 'dayjs';

export const columns: ColumnDef<HealthReport>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'file_url',
        header: 'File',
        cell: ({ row }) => {
            return (
                <Button variant="link" size="sm" asChild>
                    <a href={show(row.original.id.toString()).url} target="_blank" rel="noopener noreferrer">
                        View Report
                    </a>
                </Button>
            );
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
];
