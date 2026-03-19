import * as React from 'react';
import { Bar, BarChart, CartesianGrid, LabelList, XAxis, YAxis } from 'recharts';
import { format } from 'date-fns';
import type { DateRange } from 'react-day-picker';
import { CalendarIcon } from 'lucide-react';
import { router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { index } from '@/routes/admin/dashboard';

const chartConfig = {
    count: {
        label: 'Subscriptions',
        color: 'var(--primary)',
    },
} satisfies ChartConfig;

type TopSubscriptionItem = { name: string; count: number };

type ChartFilter = {
    range: string;
    dateFrom?: string | null;
    dateTo?: string | null;
};

export function TopDevicesChart({ data, filter }: { data: TopSubscriptionItem[]; filter: ChartFilter }) {
    const [selectValue, setSelectValue] = React.useState(filter.range);
    const [dateRange, setDateRange] = React.useState<DateRange | undefined>(
        filter.dateFrom && filter.dateTo
            ? { from: new Date(filter.dateFrom), to: new Date(filter.dateTo) }
            : undefined,
    );
    const [popoverOpen, setPopoverOpen] = React.useState(false);

    const handleRangeChange = (value: string) => {
        setSelectValue(value);
        if (value !== 'custom') {
            router.get(index().url, { range: value }, { preserveState: true, preserveScroll: true });
        } else {
            setPopoverOpen(true);
        }
    };

    const handleApply = () => {
        if (!dateRange?.from || !dateRange?.to) return;
        router.get(
            index().url,
            {
                date_from: format(dateRange.from, 'yyyy-MM-dd'),
                date_to: format(dateRange.to, 'yyyy-MM-dd'),
            },
            { preserveState: true, preserveScroll: true },
        );
        setPopoverOpen(false);
    };

    const description =
        filter.range === 'custom' && filter.dateFrom && filter.dateTo
            ? `${format(new Date(filter.dateFrom), 'MMM d, yyyy')} – ${format(new Date(filter.dateTo), 'MMM d, yyyy')}`
            : { '7d': 'Last 7 days', '30d': 'Last 30 days', '90d': 'Last 3 months' }[filter.range] ?? 'Last 3 months';

    return (
        <>
            <CardHeader>
                <CardTitle>Top Devices</CardTitle>
                <CardDescription>Top 10 subscription plans — {description}</CardDescription>
                <CardAction>
                    <div className="flex items-center gap-2">
                        <Select value={selectValue} onValueChange={handleRangeChange}>
                            <SelectTrigger className="w-44" size="sm">
                                <SelectValue placeholder="Last 3 months" />
                            </SelectTrigger>
                            <SelectContent className="rounded-xl">
                                <SelectItem value="90d" className="rounded-lg">Last 3 months</SelectItem>
                                <SelectItem value="30d" className="rounded-lg">Last 30 days</SelectItem>
                                <SelectItem value="7d" className="rounded-lg">Last 7 days</SelectItem>
                                <SelectItem value="custom" className="rounded-lg">Custom date range</SelectItem>
                            </SelectContent>
                        </Select>

                        {selectValue === 'custom' && (
                            <Popover open={popoverOpen} onOpenChange={setPopoverOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className={cn('w-56 justify-start text-left font-normal', !dateRange && 'text-muted-foreground')}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {dateRange?.from ? (
                                            dateRange.to ? (
                                                <>{format(dateRange.from, 'MMM d, yyyy')} – {format(dateRange.to, 'MMM d, yyyy')}</>
                                            ) : (
                                                format(dateRange.from, 'MMM d, yyyy')
                                            )
                                        ) : (
                                            'Pick a date range'
                                        )}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0" align="end">
                                    <Calendar
                                        mode="range"
                                        selected={dateRange}
                                        onSelect={setDateRange}
                                        numberOfMonths={2}
                                        disabled={{ after: new Date() }}
                                    />
                                    <div className="flex justify-end gap-2 border-t px-4 py-3">
                                        <Button variant="ghost" size="sm" onClick={() => setPopoverOpen(false)}>
                                            Cancel
                                        </Button>
                                        <Button size="sm" onClick={handleApply} disabled={!dateRange?.from || !dateRange?.to}>
                                            Apply
                                        </Button>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        )}
                    </div>
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pb-4 sm:px-6">
                <ChartContainer config={chartConfig} className="aspect-auto h-[300px] w-full">
                    <BarChart data={data} layout="vertical" margin={{ left: 0, right: 32 }}>
                        <CartesianGrid horizontal={false} />
                        <YAxis
                            dataKey="name"
                            type="category"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            width={120}
                            tick={{ fontSize: 12 }}
                        />
                        <XAxis type="number" hide />
                        <ChartTooltip cursor={false} content={<ChartTooltipContent indicator="dot" />} />
                        <Bar dataKey="count" fill="var(--color-count)" radius={[0, 4, 4, 0]}>
                            <LabelList dataKey="count" position="right" className="fill-foreground" fontSize={12} />
                        </Bar>
                    </BarChart>
                </ChartContainer>
            </CardContent>
        </>
    );
}
