import { DataTablePagination } from '@/components/datatable-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { router } from '@inertiajs/react';
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

export function DataTable<TData, TValue>({ columns, data, filters }: DataTableProps<TData, TValue>) {
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

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

    const handleFilter = () => {
        router.get(
            '/admin/device-maintenances',
            { status, search },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        router.get('/admin/device-maintenances', {}, { preserveState: true });
    };

    return (
        <div>
            <div className="flex items-center gap-4 pb-4">
                <Input
                    placeholder="Search by user name, email, or device..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                    className="max-w-sm"
                />
                <Select value={status || undefined} onValueChange={(value) => setStatus(value || '')}>
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
                <Button onClick={handleFilter}>Apply</Button>
                <Button variant="outline" onClick={handleReset}>
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
