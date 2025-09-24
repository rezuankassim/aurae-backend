import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { approve, edit, show } from '@/routes/device-maintenance';
import { DeviceMaintenance } from '@/types';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

import dayjs from 'dayjs';
import { Factory, MoreHorizontal, User } from 'lucide-react';

export const columns: ColumnDef<DeviceMaintenance>[] = [
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
            if (row.original.status === 0) {
                return <Badge>Pending</Badge>;
            } else if (row.original.status === 1) {
                return <Badge className="bg-cyan-200 text-cyan-900">Pending Factory Approval</Badge>;
            } else if (row.original.status === 2) {
                return <Badge className="bg-amber-200 text-amber-900">In Progress</Badge>;
            } else if (row.original.status === 3) {
                return <Badge className="bg-green-200 text-green-900">Completed</Badge>;
            }

            return '-';
        },
    },
    {
        id: 'requested_at',
        header: 'Requested At',
        cell: ({ row }) => {
            const maintenanceRequestedAt = row.original.maintenance_requested_at;
            const factoryMaintenanceRequestedAt = row.original.factory_maintenance_requested_at;

            return (
                <div className="grid gap-2">
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <span className="inline-flex max-w-fit items-center gap-2">
                                <User className="size-4" />
                                <time dateTime={maintenanceRequestedAt}>{dayjs(maintenanceRequestedAt).format('DD MMM YYYY, HH:mm')}</time>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>Time chosen by client</TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <span className="inline-flex max-w-fit items-center gap-2">
                                <Factory className="size-4" />
                                {factoryMaintenanceRequestedAt ? (
                                    <time dateTime={factoryMaintenanceRequestedAt}>
                                        {dayjs(factoryMaintenanceRequestedAt).format('DD MMM YYYY, HH:mm')}
                                    </time>
                                ) : (
                                    '-'
                                )}
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>Time chosen by factory</TooltipContent>
                    </Tooltip>
                </div>
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Created at',
        cell: ({ row }) => {
            const createdAt = row.getValue('created_at') as string;
            return dayjs(createdAt).format('DD MMM YYYY, HH:mm');
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
                        <DropdownMenuItem asChild>
                            <Link className="hover:cursor-pointer" href={show(row.original.id).url}>
                                View
                            </Link>
                        </DropdownMenuItem>
                        {row.original.status === 0 ? (
                            <DropdownMenuItem asChild>
                                <Link className="w-full hover:cursor-pointer" href={approve(row.original.id).url} method="post">
                                    Approve
                                </Link>
                            </DropdownMenuItem>
                        ) : null}

                        {row.original.status === 0 ? (
                            <DropdownMenuItem asChild>
                                <Link className="w-full hover:cursor-pointer" href={edit(row.original.id).url}>
                                    Edit
                                </Link>
                            </DropdownMenuItem>
                        ) : null}
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
