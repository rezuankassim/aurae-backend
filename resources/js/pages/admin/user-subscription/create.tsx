import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { FieldDescription } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { bulk, create, index, store } from '@/routes/admin/user-subscriptions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { Check, ChevronsUpDown, Users, X } from 'lucide-react';
import { useState } from 'react';

interface Subscription {
    id: number;
    title: string;
    pricing_title: string;
    price: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
}

interface Props {
    subscriptions: Subscription[];
    users: User[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Subscriptions',
        href: index().url,
    },
    {
        title: 'Create B2B Subscription',
        href: create().url,
    },
];

const todayIso = format(new Date(), 'yyyy-MM-dd');

export default function CreateUserSubscription({ subscriptions, users }: Props) {
    // ── Single form ──────────────────────────────────────────────────────────
    const singleForm = useForm<{
        user_id: string;
        subscription_id: string;
        starts_at: string;
        months: string;
    }>({
        user_id: '',
        subscription_id: '',
        starts_at: todayIso,
        months: '',
    });

    // ── Bulk form ────────────────────────────────────────────────────────────
    const bulkForm = useForm<{
        user_ids: number[];
        subscription_id: string;
        starts_at: string;
        months: string;
    }>({
        user_ids: [],
        subscription_id: '',
        starts_at: todayIso,
        months: '',
    });

    // ── User combobox state (single mode) ────────────────────────────────────
    const [userPopoverOpen, setUserPopoverOpen] = useState(false);
    const selectedUser = users.find((u) => String(u.id) === singleForm.data.user_id);

    // ── Bulk user search/filter ──────────────────────────────────────────────
    const [bulkSearch, setBulkSearch] = useState('');
    const filteredUsers = users.filter(
        (u) => u.name.toLowerCase().includes(bulkSearch.toLowerCase()) || u.email.toLowerCase().includes(bulkSearch.toLowerCase()),
    );

    const toggleBulkUser = (userId: number) => {
        const current = bulkForm.data.user_ids;
        bulkForm.setData('user_ids', current.includes(userId) ? current.filter((id) => id !== userId) : [...current, userId]);
    };

    const selectAllFiltered = () => {
        const filteredIds = filteredUsers.map((u) => u.id);
        const existing = bulkForm.data.user_ids;
        const merged = Array.from(new Set([...existing, ...filteredIds]));
        bulkForm.setData('user_ids', merged);
    };

    const clearAllFiltered = () => {
        const filteredIds = new Set(filteredUsers.map((u) => u.id));
        bulkForm.setData(
            'user_ids',
            bulkForm.data.user_ids.filter((id) => !filteredIds.has(id)),
        );
    };

    // ── Submit handlers ──────────────────────────────────────────────────────
    const handleSingleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        singleForm.post(store().url);
    };

    const handleBulkSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        bulkForm.post(bulk().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create B2B Subscription" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Create B2B Subscription"
                        description="Assign subscriptions directly to customers without going through the payment gateway"
                    />
                </div>

                <Tabs defaultValue="single" className="w-full">
                    <TabsList className="mb-6">
                        <TabsTrigger value="single">Single User</TabsTrigger>
                        <TabsTrigger value="bulk">Bulk Users</TabsTrigger>
                    </TabsList>

                    {/* ── SINGLE MODE ─────────────────────────────────────────── */}
                    <TabsContent value="single">
                        <form onSubmit={handleSingleSubmit} className="max-w-2xl space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Subscription Details</CardTitle>
                                    <CardDescription>
                                        Select a customer and configure the subscription. The subscription will be immediately active with payment
                                        method set to <span className="font-semibold">B2B</span>.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    {/* User */}
                                    <div className="space-y-2">
                                        <Label>Customer *</Label>
                                        <Popover open={userPopoverOpen} onOpenChange={setUserPopoverOpen}>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    role="combobox"
                                                    aria-expanded={userPopoverOpen}
                                                    className="w-full justify-between"
                                                >
                                                    {selectedUser ? (
                                                        <span>
                                                            {selectedUser.name} <span className="text-muted-foreground">({selectedUser.email})</span>
                                                        </span>
                                                    ) : (
                                                        'Search customer...'
                                                    )}
                                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                                                <Command>
                                                    <CommandInput placeholder="Search by name or email..." />
                                                    <CommandList>
                                                        <CommandEmpty>No customers found.</CommandEmpty>
                                                        <CommandGroup>
                                                            {users.map((user) => (
                                                                <CommandItem
                                                                    key={user.id}
                                                                    value={`${user.name} ${user.email}`}
                                                                    onSelect={() => {
                                                                        singleForm.setData('user_id', String(user.id));
                                                                        setUserPopoverOpen(false);
                                                                    }}
                                                                >
                                                                    <Check
                                                                        className={cn(
                                                                            'mr-2 h-4 w-4',
                                                                            singleForm.data.user_id === String(user.id) ? 'opacity-100' : 'opacity-0',
                                                                        )}
                                                                    />
                                                                    <div>
                                                                        <p className="font-medium">{user.name}</p>
                                                                        <p className="text-xs text-muted-foreground">{user.email}</p>
                                                                    </div>
                                                                </CommandItem>
                                                            ))}
                                                        </CommandGroup>
                                                    </CommandList>
                                                </Command>
                                            </PopoverContent>
                                        </Popover>
                                        {singleForm.errors.user_id && <p className="text-sm text-red-600">{singleForm.errors.user_id}</p>}
                                    </div>

                                    {/* Subscription Plan */}
                                    <div className="space-y-2">
                                        <Label>Subscription Plan *</Label>
                                        <Select
                                            value={singleForm.data.subscription_id}
                                            onValueChange={(val) => singleForm.setData('subscription_id', val)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a plan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {subscriptions.map((sub) => (
                                                    <SelectItem key={sub.id} value={String(sub.id)}>
                                                        {sub.title} — {sub.pricing_title}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {singleForm.errors.subscription_id && (
                                            <p className="text-sm text-red-600">{singleForm.errors.subscription_id}</p>
                                        )}
                                    </div>

                                    {/* Start Date */}
                                    <div className="space-y-2">
                                        <Label htmlFor="single-starts-at">Start Date *</Label>
                                        <Input
                                            id="single-starts-at"
                                            type="date"
                                            value={singleForm.data.starts_at}
                                            onChange={(e) => singleForm.setData('starts_at', e.target.value)}
                                        />
                                        {singleForm.errors.starts_at && <p className="text-sm text-red-600">{singleForm.errors.starts_at}</p>}
                                    </div>

                                    {/* Duration */}
                                    <div className="space-y-2">
                                        <Label htmlFor="single-months">Duration (months)</Label>
                                        <Input
                                            id="single-months"
                                            type="number"
                                            min={0}
                                            max={120}
                                            value={singleForm.data.months}
                                            onChange={(e) => singleForm.setData('months', e.target.value)}
                                        />
                                        <FieldDescription>Leave blank for non-ending subscription</FieldDescription>
                                        {singleForm.errors.months && <p className="text-sm text-red-600">{singleForm.errors.months}</p>}
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={singleForm.processing}>
                                    {singleForm.processing ? 'Creating...' : 'Create Subscription'}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href={index().url}>Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    {/* ── BULK MODE ────────────────────────────────────────────── */}
                    <TabsContent value="bulk">
                        <form onSubmit={handleBulkSubmit} className="space-y-6">
                            <div className="grid gap-6 lg:grid-cols-2">
                                {/* Left: Plan + Dates */}
                                <div className="space-y-6">
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Subscription Settings</CardTitle>
                                            <CardDescription>These settings apply to all selected customers.</CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-5">
                                            {/* Subscription Plan */}
                                            <div className="space-y-2">
                                                <Label>Subscription Plan *</Label>
                                                <Select
                                                    value={bulkForm.data.subscription_id}
                                                    onValueChange={(val) => bulkForm.setData('subscription_id', val)}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select a plan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {subscriptions.map((sub) => (
                                                            <SelectItem key={sub.id} value={String(sub.id)}>
                                                                {sub.title} — {sub.pricing_title}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {bulkForm.errors.subscription_id && (
                                                    <p className="text-sm text-red-600">{bulkForm.errors.subscription_id}</p>
                                                )}
                                            </div>

                                            {/* Start Date */}
                                            <div className="space-y-2">
                                                <Label htmlFor="bulk-starts-at">Start Date *</Label>
                                                <Input
                                                    id="bulk-starts-at"
                                                    type="date"
                                                    value={bulkForm.data.starts_at}
                                                    onChange={(e) => bulkForm.setData('starts_at', e.target.value)}
                                                />
                                                {bulkForm.errors.starts_at && <p className="text-sm text-red-600">{bulkForm.errors.starts_at}</p>}
                                            </div>

                                            {/* Duration */}
                                            <div className="space-y-2">
                                                <Label htmlFor="bulk-months">Duration (months)</Label>
                                                <Input
                                                    id="bulk-months"
                                                    type="number"
                                                    min={0}
                                                    max={120}
                                                    value={bulkForm.data.months}
                                                    onChange={(e) => bulkForm.setData('months', e.target.value)}
                                                />
                                                <FieldDescription>Leave blank for non-ending subscription</FieldDescription>
                                                {bulkForm.errors.months && <p className="text-sm text-red-600">{bulkForm.errors.months}</p>}
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {/* Selected summary */}
                                    {bulkForm.data.user_ids.length > 0 && (
                                        <Card className="border-green-200 dark:border-green-900">
                                            <CardHeader className="pb-3">
                                                <CardTitle className="flex items-center gap-2 text-green-700 dark:text-green-400">
                                                    <Users className="h-4 w-4" />
                                                    {bulkForm.data.user_ids.length} customer
                                                    {bulkForm.data.user_ids.length !== 1 ? 's' : ''} selected
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {bulkForm.data.user_ids.map((uid) => {
                                                        const u = users.find((u) => u.id === uid);
                                                        if (!u) return null;
                                                        return (
                                                            <Badge key={uid} variant="secondary" className="flex items-center gap-1 pr-1">
                                                                {u.name}
                                                                <button
                                                                    type="button"
                                                                    onClick={() => toggleBulkUser(uid)}
                                                                    className="ml-0.5 rounded-sm opacity-70 hover:opacity-100"
                                                                >
                                                                    <X className="h-3 w-3" />
                                                                </button>
                                                            </Badge>
                                                        );
                                                    })}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}

                                    {bulkForm.errors.user_ids && <p className="text-sm text-red-600">{bulkForm.errors.user_ids}</p>}

                                    <div className="flex gap-2">
                                        <Button type="submit" disabled={bulkForm.processing || bulkForm.data.user_ids.length === 0}>
                                            {bulkForm.processing
                                                ? 'Creating...'
                                                : `Create ${bulkForm.data.user_ids.length > 0 ? bulkForm.data.user_ids.length : ''} Subscription${bulkForm.data.user_ids.length !== 1 ? 's' : ''}`}
                                        </Button>
                                        <Button type="button" variant="outline" asChild>
                                            <Link href={index().url}>Cancel</Link>
                                        </Button>
                                    </div>
                                </div>

                                {/* Right: User list */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Select Customers</CardTitle>
                                        <CardDescription>Search and select the customers to assign subscriptions to.</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <Input
                                            placeholder="Search by name or email..."
                                            value={bulkSearch}
                                            onChange={(e) => setBulkSearch(e.target.value)}
                                        />

                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-muted-foreground">{filteredUsers.length} customers</span>
                                            <div className="flex gap-2">
                                                <button
                                                    type="button"
                                                    onClick={selectAllFiltered}
                                                    className="text-primary underline-offset-2 hover:underline"
                                                >
                                                    Select all
                                                </button>
                                                <span className="text-muted-foreground">·</span>
                                                <button
                                                    type="button"
                                                    onClick={clearAllFiltered}
                                                    className="text-muted-foreground underline-offset-2 hover:underline"
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                        </div>

                                        <div className="max-h-[420px] space-y-1 overflow-y-auto rounded-md border p-2">
                                            {filteredUsers.length === 0 ? (
                                                <p className="py-6 text-center text-sm text-muted-foreground">No customers found.</p>
                                            ) : (
                                                filteredUsers.map((user) => {
                                                    const checked = bulkForm.data.user_ids.includes(user.id);
                                                    return (
                                                        <label
                                                            key={user.id}
                                                            className={cn(
                                                                'flex cursor-pointer items-start gap-3 rounded-md px-3 py-2 transition-colors hover:bg-muted',
                                                                checked && 'bg-primary/5',
                                                            )}
                                                        >
                                                            <Checkbox
                                                                checked={checked}
                                                                onCheckedChange={() => toggleBulkUser(user.id)}
                                                                className="mt-0.5"
                                                            />
                                                            <div className="min-w-0 flex-1">
                                                                <p className="truncate font-medium">{user.name}</p>
                                                                <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                                                            </div>
                                                        </label>
                                                    );
                                                })
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </form>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
