import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/websocket-test';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'WebSocket Test',
        href: index().url,
    },
];

interface ReverbConfig {
    host: string;
    port: number;
    key: string;
    scheme: string;
}

interface ReceivedEvent {
    timestamp: string;
    access_token: string;
}

interface PongEvent {
    timestamp: string;
    message: string;
    receivedAt: string;
}

interface TriggerResult {
    success: boolean;
    message: string;
    device_uuid: string;
    access_token: string;
    channel: string;
    event: string;
}

export default function WebSocketTest({ reverbConfig }: { reverbConfig: ReverbConfig }) {
    const { websocket_trigger } = usePage().props as { websocket_trigger?: TriggerResult };
    const [deviceUuid, setDeviceUuid] = useState('test-device-123');
    const [accessToken, setAccessToken] = useState('');
    const [isConnected, setIsConnected] = useState(false);
    const [isListening, setIsListening] = useState(false);
    const [receivedEvents, setReceivedEvents] = useState<ReceivedEvent[]>([]);
    const [pongEvents, setPongEvents] = useState<PongEvent[]>([]);
    const [connectionLog, setConnectionLog] = useState<string[]>([]);
    const [isSending, setIsSending] = useState(false);
    const [isPinging, setIsPinging] = useState(false);

    const echoRef = useRef<Echo | null>(null);
    const channelRef = useRef<any>(null);

    const addLog = (message: string) => {
        const timestamp = new Date().toLocaleTimeString();
        setConnectionLog((prev) => [...prev, `[${timestamp}] ${message}`]);
    };

    const connectWebSocket = () => {
        if (echoRef.current) {
            addLog('Already connected');
            return;
        }

        try {
            addLog('Connecting to WebSocket...');

            // @ts-expect-error - Pusher is assigned to window
            window.Pusher = Pusher;

            echoRef.current = new Echo({
                broadcaster: 'reverb',
                key: reverbConfig.key,
                wsHost: reverbConfig.host,
                wsPort: reverbConfig.port,
                wssPort: reverbConfig.port,
                forceTLS: reverbConfig.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
            });

            setIsConnected(true);
            addLog(`Connected to WebSocket at ${reverbConfig.scheme}://${reverbConfig.host}:${reverbConfig.port}`);
        } catch (error) {
            addLog(`Connection error: ${error instanceof Error ? error.message : String(error)}`);
            setIsConnected(false);
        }
    };

    const disconnectWebSocket = () => {
        if (channelRef.current) {
            addLog('Leaving channel...');
            echoRef.current?.leave(`device.${deviceUuid}`);
            channelRef.current = null;
            setIsListening(false);
        }

        if (echoRef.current) {
            addLog('Disconnecting from WebSocket...');
            echoRef.current.disconnect();
            echoRef.current = null;
            setIsConnected(false);
            addLog('Disconnected');
        }
    };

    const listenToChannel = () => {
        if (!echoRef.current) {
            addLog('Not connected to WebSocket. Connect first.');
            return;
        }

        if (channelRef.current) {
            addLog('Already listening to channel');
            return;
        }

        const channelName = `device.${deviceUuid}`;
        addLog(`Subscribing to channel: ${channelName}`);

        channelRef.current = echoRef.current.channel(channelName);

        channelRef.current.listen('.device.authenticated', (event: { access_token: string }) => {
            const timestamp = new Date().toLocaleString();
            addLog(`Event received! Access token: ${event.access_token.substring(0, 20)}...`);
            setReceivedEvents((prev) => [
                {
                    timestamp,
                    access_token: event.access_token,
                },
                ...prev,
            ]);
        });

        channelRef.current.listen('.device.pong', (event: { message: string; timestamp: string }) => {
            const receivedAt = new Date().toLocaleString();
            addLog(`🏓 PONG received! Server time: ${event.timestamp}`);
            setPongEvents((prev) => [
                {
                    message: event.message,
                    timestamp: event.timestamp,
                    receivedAt,
                },
                ...prev,
            ]);
        });

        setIsListening(true);
        addLog(`Listening for 'device.authenticated' and 'device.pong' events on channel: ${channelName}`);
    };

    const stopListening = () => {
        if (!channelRef.current) {
            addLog('Not listening to any channel');
            return;
        }

        const channelName = `device.${deviceUuid}`;
        addLog(`Leaving channel: ${channelName}`);
        echoRef.current?.leave(channelName);
        channelRef.current = null;
        setIsListening(false);
    };

    const triggerEvent = () => {
        setIsSending(true);
        addLog(`Triggering event for device: ${deviceUuid}`);

        router.post(
            '/admin/websocket-test/trigger',
            {
                device_uuid: deviceUuid,
                access_token: accessToken || undefined,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsSending(false);
                },
                onError: (errors) => {
                    addLog(`Error: ${JSON.stringify(errors)}`);
                },
            },
        );
    };

    useEffect(() => {
        if (websocket_trigger) {
            addLog(
                `Event triggered: ${websocket_trigger.event} on ${websocket_trigger.channel} (token: ${websocket_trigger.access_token.substring(0, 20)}...)`,
            );
        }
    }, [websocket_trigger]);

    const sendPing = async () => {
        if (!isConnected || !isListening) {
            addLog('Connect and listen to channel first');
            return;
        }

        setIsPinging(true);
        addLog(`📤 Sending ping for device: ${deviceUuid}`);

        try {
            const response = await fetch('/api/ws/ping', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Device-Udid': deviceUuid,
                    'X-Device-OS': 'Web',
                    'X-Device-OS-Version': '1.0',
                    'X-Device-Manufacturer': 'Test',
                    'X-Device-Model': 'Browser',
                    'X-Device-App-Version': '1.0.0',
                },
                body: JSON.stringify({
                    device_uuid: deviceUuid,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                addLog(`✅ Ping sent successfully! Waiting for pong...`);
            } else {
                addLog(`❌ Ping failed: ${data.message}`);
            }
        } catch (error) {
            addLog(`❌ Error sending ping: ${error instanceof Error ? error.message : String(error)}`);
        } finally {
            setIsPinging(false);
        }
    };

    const clearLogs = () => {
        setConnectionLog([]);
        setReceivedEvents([]);
        setPongEvents([]);
    };

    useEffect(() => {
        return () => {
            if (channelRef.current) {
                echoRef.current?.leave(`device.${deviceUuid}`);
            }
            if (echoRef.current) {
                echoRef.current.disconnect();
            }
        };
    }, [deviceUuid]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WebSocket Test" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="WebSocket Test" description="Test WebSocket connection for DeviceAuthenticated event" />

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Connection Controls */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Connection</CardTitle>
                            <CardDescription>
                                Server: {reverbConfig.scheme}://{reverbConfig.host}:{reverbConfig.port}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium">Status</p>
                                    <p className="text-xs text-muted-foreground">WebSocket connection state</p>
                                </div>
                                <Badge variant={isConnected ? 'default' : 'outline'}>{isConnected ? 'Connected' : 'Disconnected'}</Badge>
                            </div>

                            <div className="flex gap-2">
                                <Button onClick={connectWebSocket} disabled={isConnected} className="flex-1">
                                    Connect
                                </Button>
                                <Button onClick={disconnectWebSocket} disabled={!isConnected} variant="outline" className="flex-1">
                                    Disconnect
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Channel Controls */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Channel Listener</CardTitle>
                            <CardDescription>Subscribe to device-specific channel</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="device-uuid">Device UUID</Label>
                                <Input
                                    id="device-uuid"
                                    value={deviceUuid}
                                    onChange={(e) => setDeviceUuid(e.target.value)}
                                    placeholder="Enter device UUID"
                                    disabled={isListening}
                                />
                                <p className="mt-1 text-xs text-muted-foreground">Channel: device.{deviceUuid}</p>
                            </div>

                            <div className="flex gap-2">
                                <Button onClick={listenToChannel} disabled={!isConnected || isListening} className="flex-1">
                                    Listen
                                </Button>
                                <Button onClick={stopListening} disabled={!isListening} variant="outline" className="flex-1">
                                    Stop
                                </Button>
                            </div>

                            <div className="flex items-center justify-between rounded-md border p-3">
                                <div>
                                    <p className="text-sm font-medium">Listening Status</p>
                                    <p className="text-xs text-muted-foreground">Event: device.authenticated</p>
                                </div>
                                <Badge variant={isListening ? 'default' : 'outline'}>{isListening ? 'Listening' : 'Idle'}</Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Event Trigger */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Trigger Event</CardTitle>
                            <CardDescription>Broadcast DeviceAuthenticated event from backend</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="trigger-uuid">Device UUID</Label>
                                <Input
                                    id="trigger-uuid"
                                    value={deviceUuid}
                                    onChange={(e) => setDeviceUuid(e.target.value)}
                                    placeholder="Enter device UUID"
                                />
                            </div>

                            <div>
                                <Label htmlFor="access-token">Access Token (optional)</Label>
                                <Input
                                    id="access-token"
                                    value={accessToken}
                                    onChange={(e) => setAccessToken(e.target.value)}
                                    placeholder="Leave empty for auto-generated"
                                />
                                <p className="mt-1 text-xs text-muted-foreground">Auto-generates 60 char token if empty</p>
                            </div>

                            <Button onClick={triggerEvent} disabled={!deviceUuid || isSending} className="w-full">
                                {isSending ? 'Sending...' : 'Trigger Event'}
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Ping Pong Test */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Ping Pong Test</CardTitle>
                            <CardDescription>Test WebSocket ping-pong mechanism</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="rounded-md border bg-accent/30 p-3">
                                <p className="text-sm font-medium">How it works:</p>
                                <ol className="mt-2 space-y-1 text-xs text-muted-foreground">
                                    <li>1. Send HTTP ping request to server</li>
                                    <li>2. Server broadcasts pong via WebSocket</li>
                                    <li>3. Receive pong event on your channel</li>
                                </ol>
                            </div>

                            <Button onClick={sendPing} disabled={!isConnected || !isListening || isPinging} className="w-full">
                                {isPinging ? '🏓 Pinging...' : '🏓 Send Ping'}
                            </Button>

                            {pongEvents.length > 0 && (
                                <div className="rounded-md border bg-green-50 p-3 dark:bg-green-950/20">
                                    <p className="text-sm font-medium text-green-900 dark:text-green-100">
                                        ✅ Last Pong: {pongEvents[0].receivedAt}
                                    </p>
                                    <p className="mt-1 text-xs text-green-700 dark:text-green-300">
                                        Server: {pongEvents[0].timestamp}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Connection Log */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Connection Log</CardTitle>
                                    <CardDescription>WebSocket connection activity</CardDescription>
                                </div>
                                <Button onClick={clearLogs} variant="outline" size="sm">
                                    Clear
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-1 rounded-md border bg-black/5 p-3 font-mono text-xs dark:bg-white/5">
                                {connectionLog.length === 0 ? (
                                    <p className="text-muted-foreground">No activity yet</p>
                                ) : (
                                    connectionLog.map((log, index) => (
                                        <div key={index} className="text-foreground/80">
                                            {log}
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Received Events */}
                {receivedEvents.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Received Events ({receivedEvents.length})</CardTitle>
                            <CardDescription>DeviceAuthenticated events received via WebSocket</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {receivedEvents.map((event, index) => (
                                    <div key={index} className="rounded-md border bg-accent/50 p-4">
                                        <div className="mb-2 flex items-center justify-between">
                                            <Badge variant="default">Event #{receivedEvents.length - index}</Badge>
                                            <span className="text-xs text-muted-foreground">{event.timestamp}</span>
                                        </div>
                                        <div className="space-y-1">
                                            <div>
                                                <span className="text-sm font-medium">Access Token:</span>
                                                <p className="mt-1 rounded border bg-background p-2 font-mono text-xs break-all">
                                                    {event.access_token}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Pong Events */}
                {pongEvents.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pong Events ({pongEvents.length})</CardTitle>
                            <CardDescription>Pong responses received via WebSocket</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {pongEvents.map((event, index) => (
                                    <div key={index} className="rounded-md border bg-green-50 p-4 dark:bg-green-950/20">
                                        <div className="mb-2 flex items-center justify-between">
                                            <Badge variant="default" className="bg-green-600">🏓 Pong #{pongEvents.length - index}</Badge>
                                            <span className="text-xs text-muted-foreground">{event.receivedAt}</span>
                                        </div>
                                        <div className="space-y-2">
                                            <div>
                                                <span className="text-sm font-medium">Message:</span>
                                                <p className="mt-1 rounded border bg-background p-2 font-mono text-xs">
                                                    {event.message}
                                                </p>
                                            </div>
                                            <div>
                                                <span className="text-sm font-medium">Server Timestamp:</span>
                                                <p className="mt-1 rounded border bg-background p-2 font-mono text-xs">
                                                    {event.timestamp}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
