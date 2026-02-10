import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { create, index } from '@/routes/admin/machines';
import { useState } from 'react';
import { columns } from './columns';
import { DataTable } from './data-table';

export interface Machine {
    id: string;
    serial_number: string;
    name: string;
    status: number;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
    device?: {
        id: string;
        name: string;
        uuid: string;
    } | null;
    last_used_at: string | null;
    last_logged_in_at: string | null;
    created_at: string;
}

export interface PaginatedMachines {
    data: Machine[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    search?: string;
    status?: string;
    bound?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Machines',
        href: index().url,
    },
];

export default function Machines({ machines, filters }: { machines: PaginatedMachines; filters: Filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [bound, setBound] = useState(filters.bound || 'all');

    const handleFilter = () => {
        router.get(
            index().url,
            {
                search,
                status: status === 'all' ? '' : status,
                bound: bound === 'all' ? '' : bound,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleReset = () => {
        setSearch('');
        setStatus('all');
        setBound('all');
        router.get(index().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Machines" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Machines" description="Manage physical machines with serial numbers" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Create Machine</Link>
                    </Button>
                </div>

                <div className="mb-4 flex gap-4">
                    <Input
                        placeholder="Search by serial or name..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="max-w-sm"
                    />

                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Status</SelectItem>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select value={bound} onValueChange={setBound}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Binding" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="1">Bound</SelectItem>
                            <SelectItem value="0">Unbound</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button onClick={handleFilter}>Filter</Button>
                    <Button variant="outline" onClick={handleReset}>
                        Reset
                    </Button>
                </div>

                <DataTable columns={columns} data={machines.data} />

                {machines.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-muted-foreground">
                            Showing {machines.data.length} of {machines.total} machines
                        </div>
                        <div className="flex gap-2">
                            {Array.from({ length: machines.last_page }, (_, i) => i + 1).map((page) => (
                                <Button
                                    key={page}
                                    variant={page === machines.current_page ? 'default' : 'outline'}
                                    onClick={() => router.get(index().url, { ...filters, page })}
                                >
                                    {page}
                                </Button>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
