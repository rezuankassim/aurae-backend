import { DataTablePagination } from '@/components/datatable-pagination';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import news from '@/routes/news';
import { Link } from '@inertiajs/react';
import {
    ColumnDef,
    ColumnFiltersState,
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { useState } from 'react';

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
}

export function DataTable<TData, TValue>({ columns, data }: DataTableProps<TData, TValue>) {
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        onColumnFiltersChange: setColumnFilters,
        getFilteredRowModel: getFilteredRowModel(),
        state: {
            columnFilters,
        },
    });

    return (
        <div>
            <div className="flex items-center justify-between pb-4">
                <Input
                    placeholder="Filter title..."
                    value={(table.getColumn('title')?.getFilterValue() as string) ?? ''}
                    onChange={(event) => table.getColumn('title')?.setFilterValue(event.target.value)}
                    className="max-w-sm"
                />

                <ToggleGroup
                    type="single"
                    defaultValue="all"
                    onValueChange={(value) =>
                        value === 'all' ? table.getColumn('type')?.setFilterValue('') : table.getColumn('type')?.setFilterValue(value)
                    }
                >
                    <ToggleGroupItem value="all">All</ToggleGroupItem>
                    <ToggleGroupItem value="0">News</ToggleGroupItem>
                    <ToggleGroupItem value="1">Promotions</ToggleGroupItem>
                </ToggleGroup>
            </div>
            <div className="overflow-hidden">
                {table.getRowModel().rows?.length ? (
                    table.getRowModel().rows.map((row) => (
                        // @ts-expect-error because row.original is of type unknown
                        <Link href={news.show(row.original.id).url} key={row.id}>
                            <Card key={row.id} className="mb-4">
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <div className="flex flex-col gap-1.5">
                                        <CardTitle className="text-lg font-semibold">{row.getValue('title')}</CardTitle>
                                        <CardDescription className="mt-2">
                                            {flexRender(
                                                row.getVisibleCells().find((cell) => cell.column.id === 'published_at')!.column.columnDef.cell,
                                                row
                                                    .getVisibleCells()
                                                    .find((cell) => cell.column.id === 'published_at')!
                                                    .getContext(),
                                            )}
                                        </CardDescription>
                                    </div>

                                    <div>
                                        {flexRender(
                                            row.getVisibleCells().find((cell) => cell.column.id === 'image_url')!.column.columnDef.cell,
                                            row
                                                .getVisibleCells()
                                                .find((cell) => cell.column.id === 'image_url')!
                                                .getContext(),
                                        )}
                                    </div>
                                </CardHeader>
                            </Card>
                        </Link>
                    ))
                ) : (
                    <span className="text-muted-foreground">No results.</span>
                )}
            </div>

            <div className="space-x-2 py-4">
                <DataTablePagination table={table} />
            </div>
        </div>
    );
}
