import { DataTablePagination } from '@/components/datatable-pagination';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { index } from '@/routes/admin/users';
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
    showDeleted: boolean;
}

export function DataTable<TData, TValue>({ columns, data, showDeleted: initialShowDeleted }: DataTableProps<TData, TValue>) {
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    const [showDeleted, setShowDeleted] = useState(initialShowDeleted);

    const handleShowDeletedChange = (checked: boolean) => {
        setShowDeleted(checked);

        router.get(
            index().url,
            checked
                ? {
                      show_deleted: 1,
                  }
                : {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };
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
                    placeholder="Filter name..."
                    value={(table.getColumn('name')?.getFilterValue() as string) ?? ''}
                    onChange={(event) => table.getColumn('name')?.setFilterValue(event.target.value)}
                    className="max-w-sm"
                />
                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="show-deleted-users"
                            checked={showDeleted}
                            onCheckedChange={(checked) => handleShowDeletedChange(checked === true)}
                        />
                        <Label htmlFor="show-deleted-users">Show deleted users</Label>
                    </div>

                    <ToggleGroup
                        type="single"
                        defaultValue="all"
                        onValueChange={(value) =>
                            value === 'all' ? table.getColumn('type')?.setFilterValue('') : table.getColumn('type')?.setFilterValue(value)
                        }
                    >
                        <ToggleGroupItem value="all">All</ToggleGroupItem>
                        <ToggleGroupItem value="0">Customer</ToggleGroupItem>
                        <ToggleGroupItem value="guest">Guest</ToggleGroupItem>
                        <ToggleGroupItem value="1">Admin</ToggleGroupItem>
                    </ToggleGroup>
                </div>
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
