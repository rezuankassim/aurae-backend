import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/device-locations';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { format } from 'date-fns';
import { ExternalLink } from 'lucide-react';

interface UserDevice {
    id: number;
    udid: string;
    os: string | null;
    os_version: string | null;
    manufacturer: string | null;
    model: string | null;
    app_version: string | null;
}

interface DeviceLocation {
    id: number;
    latitude: string | null;
    longitude: string | null;
    accuracy: string | null;
    altitude: string | null;
    speed: string | null;
    heading: string | null;
    api_endpoint: string | null;
    ip_address: string | null;
    created_at: string;
}

interface PaginatedLocations {
    data: DeviceLocation[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    device: UserDevice;
    locations: PaginatedLocations;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Locations',
        href: index().url,
    },
    {
        title: 'Device Location History',
        href: '',
    },
];

export default function AdminDeviceLocationsShow({ device, locations }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Device Location History" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading
                    title="Device Location History"
                    description={`GPS location tracking for ${device.manufacturer || 'Unknown'} ${device.model || 'Device'}`}
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Device Information</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Device</Label>
                            <p>
                                {device.manufacturer || 'Unknown'} {device.model || 'Device'}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label>UDID</Label>
                            <p className="font-mono text-sm">{device.udid}</p>
                        </div>

                        <div className="grid gap-2">
                            <Label>Operating System</Label>
                            <p>
                                {device.os || 'Unknown'} {device.os_version || ''}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label>App Version</Label>
                            <p>{device.app_version || '-'}</p>
                        </div>

                        <div className="grid gap-2">
                            <Label>Total Locations Logged</Label>
                            <p>
                                <Badge variant="secondary">{locations.total}</Badge>
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Location History</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-hidden rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Coordinates</TableHead>
                                        <TableHead>Accuracy</TableHead>
                                        <TableHead>API Endpoint</TableHead>
                                        <TableHead>IP Address</TableHead>
                                        <TableHead>Logged At</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {locations.data.length > 0 ? (
                                        locations.data.map((location, index) => (
                                            <TableRow key={location.id}>
                                                <TableCell className="font-medium">
                                                    {(locations.current_page - 1) * locations.per_page + index + 1}
                                                </TableCell>
                                                <TableCell>
                                                    {location.latitude && location.longitude ? (
                                                        <div className="font-mono text-sm">
                                                            <div>
                                                                {parseFloat(location.latitude).toFixed(6)},{' '}
                                                                {parseFloat(location.longitude).toFixed(6)}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">N/A</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {location.accuracy ? (
                                                        <span className="text-sm">±{parseFloat(location.accuracy).toFixed(1)}m</span>
                                                    ) : (
                                                        '-'
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-mono text-xs">{location.api_endpoint || '-'}</span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-mono text-xs">{location.ip_address || '-'}</span>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {format(new Date(location.created_at), 'MMM d, yyyy HH:mm:ss')}
                                                </TableCell>
                                                <TableCell>
                                                    {location.latitude && location.longitude && (
                                                        <a
                                                            href={`https://www.google.com/maps?q=${location.latitude},${location.longitude}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex items-center gap-1 text-primary hover:text-primary/80"
                                                        >
                                                            <ExternalLink className="h-3 w-3" />
                                                            <span className="text-xs">View Map</span>
                                                        </a>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-24 text-center">
                                                No location data available for this device.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
