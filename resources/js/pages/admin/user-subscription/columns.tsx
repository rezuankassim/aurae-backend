'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/admin/user-subscriptions';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { Eye, RefreshCcw } from 'lucide-react';
import type { UserSubscription } from './index';

export const columns: ColumnDef<UserSubscription>[] = [
    {
        accessorKey: 'user.name',
        header: 'User',
        cell: ({ row }) => {
            const user = row.original.user;
            return (
                <div>
                    <p className="font-medium">{user.name}</p>
                    <p className="text-xs text-muted-foreground">{user.email}</p>
                </div>
            );
        },
    },
    {
        accessorKey: 'subscription.title',
        header: 'Subscription',
        cell: ({ row }) => {
            const subscription = row.original.subscription;
            return (
                <div>
                    <p className="font-medium">{subscription.title}</p>
                    <p className="text-xs text-muted-foreground">RM {subscription.price}</p>
                </div>
            );
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.original.status;
            const statusMap: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
                pending: { label: 'Pending', variant: 'secondary' },
                active: { label: 'Active', variant: 'default' },
                expired: { label: 'Expired', variant: 'outline' },
                cancelled: { label: 'Cancelled', variant: 'destructive' },
            };

            const config = statusMap[status] || { label: status, variant: 'secondary' };
            return <Badge variant={config.variant}>{config.label}</Badge>;
        },
    },
    {
        accessorKey: 'is_recurring',
        header: 'Type',
        cell: ({ row }) => {
            const isRecurring = row.original.is_recurring;
            return (
                <Badge variant={isRecurring ? 'default' : 'outline'}>
                    {isRecurring ? (
                        <span className="flex items-center gap-1">
                            <RefreshCcw className="h-3 w-3" />
                            Recurring
                        </span>
                    ) : (
                        'One-time'
                    )}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'payment_status',
        header: 'Payment',
        cell: ({ row }) => {
            const status = row.original.payment_status;
            const statusMap: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
                pending: { label: 'Pending', variant: 'secondary' },
                completed: { label: 'Completed', variant: 'default' },
                failed: { label: 'Failed', variant: 'destructive' },
            };

            const config = statusMap[status] || { label: status, variant: 'secondary' };
            return <Badge variant={config.variant}>{config.label}</Badge>;
        },
    },
    {
        accessorKey: 'ends_at',
        header: 'Expires',
        cell: ({ row }) => {
            const endsAt = row.original.ends_at;
            if (!endsAt) return <span className="text-muted-foreground">-</span>;
            return format(new Date(endsAt), 'MMM d, yyyy');
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Created',
        cell: ({ row }) => {
            return format(new Date(row.original.created_at), 'MMM d, yyyy');
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            return (
                <Button variant="ghost" size="sm" asChild>
                    <Link href={show(row.original.id).url}>
                        <Eye className="mr-2 h-4 w-4" />
                        View
                    </Link>
                </Button>
            );
        },
    },
];
