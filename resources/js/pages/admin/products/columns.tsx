import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { edit } from '@/routes/admin/products';
import { Product } from '@/types';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

export const columns: ColumnDef<Product>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status');

            if (status === 'published') {
                return <Badge>Published</Badge>;
            }

            return <Badge variant="outline">Draft</Badge>;
        },
    },
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            return row.original.attribute_data.name.en || '-';
        },
        filterFn: (row, columnId, filterValue) => {
            return row.original.attribute_data.name.en.toLowerCase().includes(filterValue.toLowerCase());
        },
    },
    {
        accessorKey: 'brand',
        header: 'Brand',
        cell: ({ row }) => {
            return row.original.brand?.name || '-';
        },
    },
    {
        id: 'sku',
        header: 'SKU',
        cell: ({ row }) => {
            const firstsku = row.original.variants[0]?.sku;
            let andstring = null;
            let allstring = null;

            if (row.original.variants.length > 1) {
                andstring = ` and ${row.original.variants.length - 1} more`;
                allstring = row.original.variants.map((variant) => variant.sku).join(', ');
            }

            return andstring ? (
                <Tooltip>
                    <TooltipTrigger>
                        <div className="flex flex-col">
                            <p>{firstsku}</p>
                            <p className="text-muted-foreground">{andstring}</p>
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>{allstring}</p>
                    </TooltipContent>
                </Tooltip>
            ) : (
                firstsku
            );
        },
    },
    {
        id: 'stock',
        header: 'Stock',
        cell: ({ row }) => {
            const stock = row.original.variants.reduce((acc, variant) => acc + variant.stock, 0);
            return stock;
        },
    },
    {
        id: 'product_type',
        header: 'Product Type',
        cell: ({ row }) => {
            return row.original.product_type.name;
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
                        {/* <DropdownMenuItem asChild>
                            <Link className="hover:cursor-pointer" href={show(row.original.id).url}>
                                View
                            </Link>
                        </DropdownMenuItem> */}
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
