import { AdminNotification, SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

export type ConnectionState = 'initialized' | 'connecting' | 'connected' | 'unavailable' | 'failed' | 'disconnected' | 'disabled';

export function useAdminNotifications() {
    const { adminNotifications: initialNotifications, adminUnreadCount: initialUnreadCount } = usePage<SharedData>().props;

    const [notifications, setNotifications] = useState<AdminNotification[]>(initialNotifications ?? []);
    const [unreadCount, setUnreadCount] = useState<number>(initialUnreadCount ?? 0);
    const [connectionState, setConnectionState] = useState<ConnectionState>('initialized');

    const echoRef = useRef<Echo<'reverb'> | null>(null);
    const alertAudioRef = useRef<HTMLAudioElement | null>(null);
    // Capture the initial admin state once — subscriptions should only be set up once on mount
    const isAdminRef = useRef(initialNotifications !== null);

    useEffect(() => {
        if (!isAdminRef.current) {
            // Not an admin — do not subscribe
            setConnectionState('disabled');
            return;
        }

        if (!import.meta.env.VITE_REVERB_APP_KEY) {
            console.warn('[AdminNotifications] VITE_REVERB_APP_KEY is not set — skipping WebSocket connection.');
            setConnectionState('disabled');
            return;
        }

        // Preload the alert sound once and reuse the same Audio instance for every emergency event.
        // This avoids re-fetching/decoding the file on each notification and keeps memory usage flat.
        const audio = new Audio('/alert-notification.wav');
        audio.preload = 'auto';
        audio.volume = 0.7;
        alertAudioRef.current = audio;

        // Browsers block autoplay until the user has interacted with the page.
        // "Unlock" the audio element on the first user gesture by playing it muted, then resetting it.
        // After this one-time priming, subsequent .play() calls (triggered by WebSocket events) are allowed.
        const unlockAudio = () => {
            const a = alertAudioRef.current;
            if (!a) return;
            const wasMuted = a.muted;
            a.muted = true;
            a.play()
                .then(() => {
                    a.pause();
                    a.currentTime = 0;
                    a.muted = wasMuted;
                })
                .catch(() => {
                    a.muted = wasMuted;
                });
        };

        const unlockEvents: Array<keyof DocumentEventMap> = ['pointerdown', 'keydown', 'touchstart'];
        const handleUnlock = () => {
            unlockAudio();
            unlockEvents.forEach((evt) => document.removeEventListener(evt, handleUnlock));
        };
        unlockEvents.forEach((evt) => document.addEventListener(evt, handleUnlock, { once: true, passive: true }));

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

            // Track the underlying Pusher connection so the UI can show whether real-time alerts are active.
            // Reverb speaks the Pusher protocol, so the standard state machine applies:
            // initialized → connecting → connected, plus unavailable / failed / disconnected.
            const pusherConnection = echoRef.current.connector.pusher.connection;
            setConnectionState(pusherConnection.state as ConnectionState);
            const handleStateChange = ({ current }: { current: ConnectionState }) => {
                setConnectionState(current);
            };
            pusherConnection.bind('state_change', handleStateChange);

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

                    // Play the alert sound. Reset currentTime so rapid consecutive events still trigger playback.
                    const alertAudio = alertAudioRef.current;
                    if (alertAudio) {
                        alertAudio.currentTime = 0;
                        // play() returns a Promise that may reject due to browser autoplay policies — swallow it.
                        void alertAudio.play().catch((err) => {
                            console.warn('[AdminNotifications] Unable to play alert sound:', err);
                        });
                    }
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
            echoRef.current?.connector.pusher.connection.unbind('state_change');
            echoRef.current?.leave('admin');
            echoRef.current?.disconnect();
            echoRef.current = null;
            setConnectionState('disconnected');

            unlockEvents.forEach((evt) => document.removeEventListener(evt, handleUnlock));

            if (alertAudioRef.current) {
                alertAudioRef.current.pause();
                alertAudioRef.current.src = '';
                alertAudioRef.current = null;
            }
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

    return { notifications, unreadCount, markAllAsRead, connectionState };
}
