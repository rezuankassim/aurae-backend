import { RealtimeStatusIndicator } from '@/components/realtime-status-indicator';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useAdminNotifications } from '@/hooks/use-admin-notifications';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/admin/notifications';
import { AdminNotification } from '@/types';
import { Link } from '@inertiajs/react';
import { Bell, User } from 'lucide-react';

function timeAgo(dateStr: string): string {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

function NotificationItem({ notification }: { notification: AdminNotification }) {
    const isUnread = !notification.read_at;
    const data = notification.data;

    return (
        <div className={cn('flex gap-3 rounded-md px-3 py-2.5 transition-colors', 'border-l-2 border-transparent', isUnread && 'bg-muted/50')}>
            <div className="mt-0.5 shrink-0">
                <Bell className="h-4 w-4 text-muted-foreground" />
            </div>
            <div className="min-w-0 flex-1">
                <p className={cn('truncate text-sm leading-none font-medium')}>{notification.title}</p>
                {data && (
                    <div className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                        <User className="h-3 w-3" />
                        <span className="truncate">
                            {data.user_name} {data.is_guest ? '(Guest)' : ''} · {data.user_phone}
                        </span>
                    </div>
                )}
                <p className="mt-0.5 text-xs text-muted-foreground">{notification.body}</p>
                <p className="mt-0.5 text-xs text-muted-foreground/70">{timeAgo(notification.created_at)}</p>
            </div>
            {isUnread && <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary" />}
        </div>
    );
}

export function NotificationBell() {
    const { notifications, unreadCount, markAllAsRead, connectionState } = useAdminNotifications();

    if (!notifications) {
        return null;
    }

    return (
        <div className="flex items-center gap-1">
            <RealtimeStatusIndicator connectionState={connectionState} />
            <DropdownMenu onOpenChange={(open) => open && unreadCount > 0 && markAllAsRead()}>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="relative h-8 w-8">
                        <Bell className="h-4 w-4" />
                        {unreadCount > 0 && (
                            <Badge
                                variant="destructive"
                                className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full p-0 text-[10px]"
                            >
                                {unreadCount > 9 ? '9+' : unreadCount}
                            </Badge>
                        )}
                        <span className="sr-only">Notifications</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-96">
                    <DropdownMenuLabel className="flex items-center justify-between">
                        <span>Notifications</span>
                        {unreadCount > 0 && (
                            <Badge variant="secondary" className="text-xs font-normal">
                                {unreadCount} unread
                            </Badge>
                        )}
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {notifications.length === 0 ? (
                        <div className="py-6 text-center text-sm text-muted-foreground">No notifications yet</div>
                    ) : (
                        <ScrollArea className="max-h-[360px] overflow-x-hidden">
                            <div className="space-y-0.5 p-1">
                                {notifications.map((notification) => (
                                    <DropdownMenuItem key={notification.id} className="p-0 focus:bg-transparent" asChild>
                                        <Link href={show(notification.id).url} className="w-full min-w-0">
                                            <NotificationItem notification={notification} />
                                        </Link>
                                    </DropdownMenuItem>
                                ))}
                            </div>
                        </ScrollArea>
                    )}
                    <DropdownMenuSeparator />
                    <DropdownMenuItem className="justify-center p-0 focus:bg-transparent" asChild>
                        <Link href={index().url} className="flex w-full justify-center py-2 text-xs font-medium text-primary hover:text-primary/80">
                            View more
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
