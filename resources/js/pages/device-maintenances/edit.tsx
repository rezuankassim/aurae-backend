import AppLayout from '@/layouts/app-layout';
import { DeviceMaintenance, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/device-maintenance';

import DeviceMaintenanceController from '@/actions/App/Http/Controllers/DeviceMaintenanceController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import dayjs from 'dayjs';
import { ChevronDown } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Device Maintenances',
        href: index().url,
    },
    {
        title: 'Edit maintenance',
        href: '#',
    },
];

export default function DeviceMaintenanceUpdate({ deviceMaintenance }: { deviceMaintenance: DeviceMaintenance }) {
    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>(
        deviceMaintenance.maintenance_requested_at ? dayjs(deviceMaintenance.maintenance_requested_at, 'DD-MM-YYYY').toDate() : undefined,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit maintenance" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit maintenance" description="Edit current maintenance to fit your schedule" />

                <Form
                    {...DeviceMaintenanceController.update.form(deviceMaintenance.id)}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    transform={(data) => ({
                        ...data,
                        maintenance_date: date ? dayjs(date).format('DD-MM-YYYY') : null,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <h2 className="text-sm font-medium">Status</h2>

                                {deviceMaintenance.status === 0 ? <Badge>Pending</Badge> : null}
                                {deviceMaintenance.status === 1 ? (
                                    <Badge className="bg-cyan-200 text-cyan-900">Pending Factory Approval</Badge>
                                ) : null}
                                {deviceMaintenance.status === 2 ? <Badge className="bg-amber-200 text-amber-900">In Progress</Badge> : null}
                                {deviceMaintenance.status === 3 ? <Badge className="bg-green-200 text-green-900">Completed</Badge> : null}
                            </div>

                            <div className="grid grid-flow-col gap-2">
                                <div className="grid gap-2">
                                    <h2 className="text-sm font-medium">Factory schedule date</h2>

                                    <span>{dayjs(deviceMaintenance.factory_maintenance_requested_at).format('DD MMM YYYY')}</span>
                                </div>

                                <div className="grid gap-2">
                                    <h2 className="text-sm font-medium">Factory schedule time</h2>

                                    <span>{dayjs(deviceMaintenance.factory_maintenance_requested_at).format('hh:mm:ss a')}</span>
                                </div>
                            </div>

                            <div>
                                <pre className="w-full rounded-lg bg-muted p-4 text-sm">
                                    <code>/** Device will be added in later **/</code>
                                </pre>
                            </div>

                            <div className="grid grid-flow-col gap-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="maintenance_date">Maintenance date</Label>

                                    <Popover open={open} onOpenChange={setOpen}>
                                        <PopoverTrigger asChild>
                                            <Button variant="outline" id="maintenance_date" className="justify-between font-normal">
                                                {date ? date.toLocaleDateString() : 'Select date'}
                                                <ChevronDown />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto overflow-hidden p-0" align="start">
                                            <Calendar
                                                mode="single"
                                                selected={date}
                                                captionLayout="dropdown"
                                                onSelect={(date) => {
                                                    setDate(date);
                                                    setOpen(false);
                                                }}
                                            />
                                        </PopoverContent>
                                    </Popover>

                                    <InputError message={errors.maintenance_date} />
                                </div>

                                <div className="flex flex-col gap-3">
                                    <Label htmlFor="maintenance_time">Maintenance time</Label>

                                    <Input
                                        type="time"
                                        id="maintenance_time"
                                        name="maintenance_time"
                                        step="1"
                                        className="appearance-none bg-background [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                                        defaultValue={
                                            deviceMaintenance.maintenance_requested_at
                                                ? dayjs(deviceMaintenance.maintenance_requested_at, 'DD-MM-YYYY HH:mm:ss').format('HH:mm:ss')
                                                : ''
                                        }
                                    />

                                    <InputError message={errors.maintenance_time} />
                                </div>
                            </div>

                            <Button type="submit" disabled={processing}>
                                Submit
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
