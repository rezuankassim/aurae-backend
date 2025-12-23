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

interface Device {
    id: number;
    label: string;
}

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    devices: Device[];
    filters: {
        device_id: string;
        from: string;
        to: string;
    };
}

export function DataTable<TData, TValue>({ columns, data, devices, filters }: DataTableProps<TData, TValue>) {
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    const [deviceId, setDeviceId] = useState(filters.device_id || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');

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
            '/admin/device-locations',
            { device_id: deviceId, from, to },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleReset = () => {
        setDeviceId('');
        setFrom('');
        setTo('');
        router.get('/admin/device-locations', {}, { preserveState: true });
    };

    return (
        <div>
            <div className="flex flex-wrap items-center gap-4 pb-4">
                <Select value={deviceId || undefined} onValueChange={(value) => setDeviceId(value || '')}>
                    <SelectTrigger className="w-[280px]">
                        <SelectValue placeholder="All Devices" />
                    </SelectTrigger>
                    <SelectContent>
                        {devices.map((device) => (
                            <SelectItem key={device.id} value={device.id.toString()}>
                                {device.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Input type="date" placeholder="From date" value={from} onChange={(e) => setFrom(e.target.value)} className="w-[180px]" />
                <Input type="date" placeholder="To date" value={to} onChange={(e) => setTo(e.target.value)} className="w-[180px]" />
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
