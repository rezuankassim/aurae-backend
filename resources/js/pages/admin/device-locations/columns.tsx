import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { show } from '@/routes/admin/device-locations';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ExternalLink, MoreHorizontal } from 'lucide-react';

interface Device {
    id: string;
    name: string;
    uuid: string;
}

interface DeviceLocation {
    id: number;
    device_id: string | null;
    latitude: string | null;
    longitude: string | null;
    accuracy: string | null;
    api_endpoint: string | null;
    ip_address: string | null;
    created_at: string;
    device: Device | null;
}

export const columns: ColumnDef<DeviceLocation>[] = [
    {
        id: 'no',
        header: '#',
        cell: ({ row, table }) => {
            return (table.getSortedRowModel()?.flatRows?.findIndex((flatRow) => flatRow.id === row.id) || 0) + 1;
        },
    },
    {
        accessorKey: 'device',
        header: 'Device',
        cell: ({ row }) => {
            const device = row.original.device;
            if (!device) return <span className="text-muted-foreground">-</span>;
            return (
                <div>
                    <p className="font-medium">{device.name}</p>
                    <p className="text-xs text-muted-foreground">{device.uuid}</p>
                </div>
            );
        },
    },
    {
        accessorKey: 'latitude',
        header: 'Coordinates',
        cell: ({ row }) => {
            const lat = row.original.latitude;
            const lng = row.original.longitude;

            if (!lat || !lng) return <span className="text-muted-foreground">N/A</span>;

            const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

            return (
                <div className="flex items-center gap-2">
                    <div>
                        <p className="font-mono text-sm">
                            {parseFloat(lat).toFixed(6)}, {parseFloat(lng).toFixed(6)}
                        </p>
                        {row.original.accuracy && <p className="text-xs text-muted-foreground">±{parseFloat(row.original.accuracy).toFixed(1)}m</p>}
                    </div>
                    <a href={mapsUrl} target="_blank" rel="noopener noreferrer" className="text-primary hover:text-primary/80">
                        <ExternalLink className="h-3 w-3" />
                    </a>
                </div>
            );
        },
    },
    {
        accessorKey: 'api_endpoint',
        header: 'API Endpoint',
        cell: ({ row }) => {
            const endpoint = row.getValue('api_endpoint') as string | null;
            return <span className="font-mono text-xs">{endpoint || '-'}</span>;
        },
    },
    {
        accessorKey: 'ip_address',
        header: 'IP Address',
        cell: ({ row }) => {
            const ip = row.getValue('ip_address') as string | null;
            return <span className="font-mono text-xs">{ip || '-'}</span>;
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Logged At',
        cell: ({ row }) => {
            const date = row.getValue('created_at') as string;
            return format(new Date(date), 'MMM d, yyyy HH:mm:ss');
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
                        {row.original.device_id && (
                            <DropdownMenuItem asChild>
                                <Link className="hover:cursor-pointer" href={show(row.original.device_id).url}>
                                    View Device History
                                </Link>
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
