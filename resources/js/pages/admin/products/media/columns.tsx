import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { destroy } from '@/routes/admin/products/media';
import { Media } from '@/types';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { CheckCircle2, CircleX, MoreHorizontal } from 'lucide-react';

export const columns: ColumnDef<Media>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'file_name',
        header: 'File',
    },
    {
        id: 'name',
        header: 'name',
        cell: ({ row }) => {
            return row.original.custom_properties.name;
        },
    },
    {
        id: 'primary',
        header: 'Primary',
        cell: ({ row }) => {
            return row.original.custom_properties.primary ? (
                <CheckCircle2 className="size-4 text-green-400" />
            ) : (
                <CircleX className="size-4 text-red-400" />
            );
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            return (
                <Dialog>
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
                                <a className="hover:cursor-pointer" href={row.original.url} target="_blank" rel="noopener noreferrer">
                                    View
                                </a>
                            </DropdownMenuItem>

                            <DialogTrigger asChild>
                                <DropdownMenuItem>Delete</DropdownMenuItem>
                            </DialogTrigger>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Are you absolutely sure?</DialogTitle>
                            <DialogDescription>
                                This action cannot be undone. This will permanently delete this media from our servers.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            <DialogClose asChild>
                                <Button asChild>
                                    <Link href={destroy({ product: row.original.model_id, media: row.original.id })}>Delete</Link>
                                </Button>
                            </DialogClose>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            );
        },
    },
];
