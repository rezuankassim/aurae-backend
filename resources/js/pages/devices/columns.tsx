import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown } from 'lucide-react';

interface Device {
    id: string;
    name: string;
    uuid: string;
    status: number;
    thumbnail: string;
    device_plan: string;
    started_at: string | null;
    should_end_at: string | null;
    last_used_at: string | null;
    created_at: string;
}

export const columns: ColumnDef<Device>[] = [
    {
        accessorKey: 'thumbnail',
        header: 'Thumbnail',
        cell: ({ row }) => (
            <div className="h-12 w-12 overflow-hidden rounded-md border bg-muted">
                <img src={`/${row.getValue('thumbnail')}`} alt={row.original.name} className="h-full w-full object-cover" />
            </div>
        ),
    },
    {
        accessorKey: 'name',
        header: ({ column }) => (
            <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                Name
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
        cell: ({ row }) => <div className="font-medium">{row.getValue('name')}</div>,
    },
    {
        accessorKey: 'uuid',
        header: 'UUID',
        cell: ({ row }) => <div className="font-mono text-sm">{row.getValue('uuid')}</div>,
    },
    {
        accessorKey: 'device_plan',
        header: 'Plan',
        cell: ({ row }) => <div>{row.getValue('device_plan')}</div>,
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status') as number;
            return status === 1 ? <Badge className="bg-green-100 text-green-800">Active</Badge> : <Badge variant="secondary">Inactive</Badge>;
        },
    },
    {
        accessorKey: 'started_at',
        header: 'Started At',
        cell: ({ row }) => {
            const startedAt = row.getValue('started_at') as string | null;
            return <div>{startedAt ? new Date(startedAt).toLocaleDateString() : '-'}</div>;
        },
    },
];
