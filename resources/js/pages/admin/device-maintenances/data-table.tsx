import { DataTablePagination } from '@/components/datatable-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
    filters: {
        status: string;
        search: string;
    };
}

export function DataTable<TData, TValue>({ columns, data }: DataTableProps<TData, TValue>) {
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const [globalFilter, setGlobalFilter] = useState<any>('');
    const globalFilterFn = (row: any, columnId: string, filterValue: string[]) => {
        const userInfoString = [row.original.user.name, row.original.user.email, row.original.device?.name || '', row.original.device?.uuid || '']
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        const searchTerms = Array.isArray(filterValue) ? filterValue : [filterValue];

        // Check if any of the search terms are included in the userInfoString
        return searchTerms.some((term) => userInfoString.includes(term.toLowerCase()));
    };

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        onColumnFiltersChange: setColumnFilters,
        getFilteredRowModel: getFilteredRowModel(),
        onGlobalFilterChange: setGlobalFilter,
        state: {
            columnFilters,
            globalFilter,
        },
        globalFilterFn,
    });

    return (
        <div>
            <div className="flex items-center gap-4 pb-4">
                <Input
                    placeholder="Search by user name, email, device name or uuid..."
                    value={(globalFilter as string) ?? ''}
                    onChange={(e) => table.setGlobalFilter(e.target.value)}
                    className="max-w-sm"
                />
                <Select
                    key={table.getState().columnFilters.length}
                    value={table.getColumn('status')?.getFilterValue() as string}
                    onValueChange={(value) => table.getColumn('status')?.setFilterValue(value)}
                >
                    <SelectTrigger className="w-[180px]">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="0">Pending</SelectItem>
                        <SelectItem value="1">Pending Factory</SelectItem>
                        <SelectItem value="2">In Progress</SelectItem>
                        <SelectItem value="3">Completed</SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    variant="outline"
                    onClick={() => {
                        table.resetColumnFilters();
                    }}
                >
                    Reset
                </Button>
            </div>
            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    return (
                                        <TableHead key={header.id}>
                                            {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="h-24 text-center">
                                    No results.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="space-x-2 py-4">
                <DataTablePagination table={table} />
            </div>
        </div>
    );
}
