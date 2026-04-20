import { AdminNotification, SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

export function useAdminNotifications() {
    const { adminNotifications: initialNotifications, adminUnreadCount: initialUnreadCount } = usePage<SharedData>().props;

    const [notifications, setNotifications] = useState<AdminNotification[]>(initialNotifications ?? []);
    const [unreadCount, setUnreadCount] = useState<number>(initialUnreadCount ?? 0);

    const echoRef = useRef<Echo<'reverb'> | null>(null);
    // Capture the initial admin state once — subscriptions should only be set up once on mount
    const isAdminRef = useRef(initialNotifications !== null);

    useEffect(() => {
        if (!isAdminRef.current) {
            // Not an admin — do not subscribe
            return;
        }

        if (!import.meta.env.VITE_REVERB_APP_KEY) {
            console.warn('[AdminNotifications] VITE_REVERB_APP_KEY is not set — skipping WebSocket connection.');
            return;
        }

        try {
            // @ts-expect-error — Pusher must be on window for Echo's reverb broadcaster
            window.Pusher = Pusher;

            echoRef.current = new Echo<'reverb'>({
                broadcaster: 'reverb',
                key: import.meta.env.VITE_REVERB_APP_KEY,
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-XSRF-TOKEN': decodeURIComponent(
                            document.cookie
                                .split('; ')
                                .find((row) => row.startsWith('XSRF-TOKEN='))
                                ?.split('=')[1] ?? '',
                        ),
                    },
                },
            });

            echoRef.current.private('admin').listen('.program.stopped', (event: AdminNotification) => {
                setNotifications((prev) => [event, ...prev].slice(0, 5));
                setUnreadCount((prev) => prev + 1);

                const userLabel = event.data
                    ? `${event.data.user_name}${event.data.is_guest ? ' (Guest)' : ''} · ${event.data.user_phone}`
                    : '';
                const description = userLabel ? `${userLabel}\n${event.body}` : event.body;

                if (event.type === 'emergency') {
                    toast.error(event.title, {
                        description,
                        duration: 8000,
                    });
                } else {
                    toast.error(event.title, {
                        description,
                        duration: 5000,
                    });
                }
            });
        } catch (error) {
            console.error('[AdminNotifications] Failed to connect to WebSocket:', error);
        }

        return () => {
            echoRef.current?.leave('admin');
            echoRef.current?.disconnect();
            echoRef.current = null;
        };
    }, []);

    const markAllAsRead = async () => {
        await fetch('/admin/notifications/read-all', {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
            },
        });

        setNotifications((prev) => prev.map((n) => ({ ...n, read_at: new Date().toISOString() })));
        setUnreadCount(0);
    };

    return { notifications, unreadCount, markAllAsRead };
}
