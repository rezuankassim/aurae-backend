import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/device-maintenance';

import DeviceMaintenanceController from '@/actions/App/Http/Controllers/DeviceMaintenanceController';
import InputError from '@/components/input-error';
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
        title: 'Create new maintenance',
        href: create().url,
    },
];

export default function News() {
    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>(undefined);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create new maintenance" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Create new maintenance" description="Schedule a new maintenance for your devices" />

                <Form
                    {...DeviceMaintenanceController.store.form()}
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
