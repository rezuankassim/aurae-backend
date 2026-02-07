import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { destroy, show } from '@/routes/admin/health-reports';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import { ExternalLink, MoreHorizontal, Trash2 } from 'lucide-react';

interface HealthReport {
    id: string;
    full_body_file: string | null;
    full_body_file_name: string | null;
    full_body_file_url: string | null;
    meridian_file: string | null;
    meridian_file_name: string | null;
    meridian_file_url: string | null;
    multidimensional_file: string | null;
    multidimensional_file_name: string | null;
    multidimensional_file_url: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
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
        accessorKey: 'user.name',
        header: 'User',
        cell: ({ row }) => {
            return (
                <div className="flex flex-col">
                    <span className="font-medium">{row.original.user.name}</span>
                    <span className="text-sm text-muted-foreground">{row.original.user.email}</span>
                </div>
            );
        },
    },
    {
        id: 'full_body',
        header: 'Full Body (全身健康评估)',
        cell: ({ row }) => {
            if (!row.original.full_body_file) return <span className="text-muted-foreground">-</span>;
            return (
                <a
                    href={show([row.original.id, 'full_body']).url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-1 text-primary hover:underline"
                >
                    <ExternalLink className="h-3 w-3" />
                    View
                </a>
            );
        },
    },
    {
        id: 'meridian',
        header: 'Meridian (经络健康评估)',
        cell: ({ row }) => {
            if (!row.original.meridian_file) return <span className="text-muted-foreground">-</span>;
            return (
                <a
                    href={show([row.original.id, 'meridian']).url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-1 text-primary hover:underline"
                >
                    <ExternalLink className="h-3 w-3" />
                    View
                </a>
            );
        },
    },
    {
        id: 'multidimensional',
        header: 'Multidimensional (多维健康评估)',
        cell: ({ row }) => {
            if (!row.original.multidimensional_file) return <span className="text-muted-foreground">-</span>;
            return (
                <a
                    href={show([row.original.id, 'multidimensional']).url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-1 text-primary hover:underline"
                >
                    <ExternalLink className="h-3 w-3" />
                    View
                </a>
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Upload Date',
        cell: ({ row }) => {
            const createdAt = row.getValue('created_at') as string;
            return dayjs(createdAt).format('DD MMM YYYY, HH:mm');
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const handleDelete = () => {
                if (confirm('Are you sure you want to delete this health report record? All associated files will be deleted.')) {
                    router.delete(destroy(row.original.id).url, {
                        preserveScroll: true,
                    });
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
                        <DropdownMenuItem onClick={handleDelete} className="text-red-600 hover:cursor-pointer focus:text-red-600">
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
