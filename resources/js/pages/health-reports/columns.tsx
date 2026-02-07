import { Button } from '@/components/ui/button';
import { show } from '@/routes/health-reports';
import { ColumnDef } from '@tanstack/react-table';

import dayjs from 'dayjs';

export interface HealthReport {
    id: string;
    full_body_file: string | null;
    full_body_file_url: string | null;
    meridian_file: string | null;
    meridian_file_url: string | null;
    multidimensional_file: string | null;
    multidimensional_file_url: string | null;
    created_at: string;
    updated_at: string;
}

export const columns: ColumnDef<HealthReport>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        id: 'full_body',
        header: 'Full Body (全身健康评估)',
        cell: ({ row }) => {
            if (!row.original.full_body_file) return <span className="text-muted-foreground">-</span>;
            return (
                <Button variant="link" size="sm" asChild className="p-0">
                    <a href={show([row.original.id, 'full_body']).url} target="_blank" rel="noopener noreferrer">
                        View
                    </a>
                </Button>
            );
        },
    },
    {
        id: 'meridian',
        header: 'Meridian (经络健康评估)',
        cell: ({ row }) => {
            if (!row.original.meridian_file) return <span className="text-muted-foreground">-</span>;
            return (
                <Button variant="link" size="sm" asChild className="p-0">
                    <a href={show([row.original.id, 'meridian']).url} target="_blank" rel="noopener noreferrer">
                        View
                    </a>
                </Button>
            );
        },
    },
    {
        id: 'multidimensional',
        header: 'Multidimensional (多维健康评估)',
        cell: ({ row }) => {
            if (!row.original.multidimensional_file) return <span className="text-muted-foreground">-</span>;
            return (
                <Button variant="link" size="sm" asChild className="p-0">
                    <a href={show([row.original.id, 'multidimensional']).url} target="_blank" rel="noopener noreferrer">
                        View
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
