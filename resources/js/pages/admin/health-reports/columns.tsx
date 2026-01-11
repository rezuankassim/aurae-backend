import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { destroy, show } from '@/routes/admin/health-reports';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import { Download, ExternalLink, MoreHorizontal } from 'lucide-react';

interface HealthReport {
    id: string;
    file: string;
    file_name: string;
    file_url: string;
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
        accessorKey: 'file_name',
        header: 'File Name',
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
                if (confirm('Are you sure you want to delete this health report?')) {
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
                        <DropdownMenuItem asChild>
                            <a
                                href={show(row.original.id).url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex cursor-pointer items-center gap-2"
                            >
                                <ExternalLink className="h-4 w-4" />
                                View
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <a href={row.original.file_url} download className="flex cursor-pointer items-center gap-2">
                                <Download className="h-4 w-4" />
                                Download
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={handleDelete} className="text-red-600 hover:cursor-pointer focus:text-red-600">
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
