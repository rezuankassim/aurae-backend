'use client';

import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index } from '@/routes/admin/user-subscriptions';
import { router } from '@inertiajs/react';
import { ColumnDef, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { useCallback, useRef, useState } from 'react';

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    filters?: {
        search?: string;
        status?: string;
        payment_status?: string;
    };
}

export function DataTable<TData, TValue>({ columns, data, filters }: DataTableProps<TData, TValue>) {
    const [search, setSearch] = useState(filters?.search || '');
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
    });

    const handleSearch = useCallback((value: string) => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }
        debounceRef.current = setTimeout(() => {
            router.get(
                index().url,
                { search: value, status: filters?.status, payment_status: filters?.payment_status },
                { preserveState: true, replace: true },
            );
        }, 300);
    }, [filters?.status, filters?.payment_status]);

    const handleStatusChange = (value: string) => {
        router.get(index().url, { search: filters?.search, status: value === 'all' ? '' : value, payment_status: filters?.payment_status }, { preserveState: true, replace: true });
    };

    const handlePaymentStatusChange = (value: string) => {
        router.get(index().url, { search: filters?.search, status: filters?.status, payment_status: value === 'all' ? '' : value }, { preserveState: true, replace: true });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Input
                    placeholder="Search by user name or email..."
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        handleSearch(e.target.value);
                    }}
                    className="max-w-sm"
                />
                <Select value={filters?.status || 'all'} onValueChange={handleStatusChange}>
                    <SelectTrigger className="w-[180px]">
                        <SelectValue placeholder="Filter by status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="expired">Expired</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                    </SelectContent>
                </Select>
                <Select value={filters?.payment_status || 'all'} onValueChange={handlePaymentStatusChange}>
                    <SelectTrigger className="w-[180px]">
                        <SelectValue placeholder="Filter by payment" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Payment</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="failed">Failed</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="rounded-md border">
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
        </div>
    );
}
