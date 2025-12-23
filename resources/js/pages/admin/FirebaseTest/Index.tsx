import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/firebase-test';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Send } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Firebase Test',
        href: index().url,
    },
];

interface User {
    id: number;
    name: string;
    email: string;
    devices_count: number;
    fcm_tokens: string[];
}

interface FirebaseResult {
    type: string;
    success: boolean;
    message: string;
    details: any;
}

interface NotificationLog {
    timestamp: string;
    type: string;
    target: string;
    title: string;
    body: string;
    success: boolean;
    message: string;
}

export default function FirebaseTest({ users }: { users: User[] }) {
    const { firebase_result } = usePage().props as { firebase_result?: FirebaseResult };
    const [notificationType, setNotificationType] = useState<'token' | 'user' | 'all'>('token');
    const [fcmToken, setFcmToken] = useState('');
    const [selectedUserId, setSelectedUserId] = useState('');
    const [title, setTitle] = useState('Test Notification');
    const [body, setBody] = useState('This is a test notification from Firebase');
    const [customData, setCustomData] = useState('{}');
    const [isSending, setIsSending] = useState(false);
    const [notificationLogs, setNotificationLogs] = useState<NotificationLog[]>([]);
    const [tokenValidation, setTokenValidation] = useState<{ valid: boolean; message: string } | null>(null);
    const [isValidating, setIsValidating] = useState(false);

    const selectedUser = users.find((u) => u.id.toString() === selectedUserId);

    const addLog = (log: NotificationLog) => {
        setNotificationLogs((prev) => [log, ...prev]);
    };

    const sendNotification = () => {
        setIsSending(true);

        let data: Record<string, any> = {
            type: notificationType,
            title,
            body,
        };

        try {
            const parsedData = JSON.parse(customData);
            if (Object.keys(parsedData).length > 0) {
                data.data = parsedData;
            }
        } catch (e) {
            // Ignore invalid JSON
        }

        if (notificationType === 'token') {
            data.fcm_token = fcmToken;
        } else if (notificationType === 'user') {
            data.user_id = selectedUserId;
        }

        router.post('/admin/firebase-test/send', data, {
            preserveScroll: true,
            onFinish: () => {
                setIsSending(false);
            },
        });
    };

    const validateToken = async () => {
        if (!fcmToken) return;

        setIsValidating(true);
        setTokenValidation(null);

        try {
            const response = await fetch('/admin/firebase-test/test-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ fcm_token: fcmToken }),
            });

            const result = await response.json();
            setTokenValidation(result);
        } catch (error) {
            setTokenValidation({
                valid: false,
                message: 'Failed to validate token',
            });
        } finally {
            setIsValidating(false);
        }
    };

    useEffect(() => {
        if (firebase_result) {
            const timestamp = new Date().toLocaleString();
            let target = '';

            if (firebase_result.type === 'token') {
                target = `Token: ${fcmToken.substring(0, 20)}...`;
            } else if (firebase_result.type === 'user') {
                target = `User: ${firebase_result.details?.user?.email || 'Unknown'}`;
            } else if (firebase_result.type === 'all') {
                target = `All Users (${firebase_result.details?.users_count || 0})`;
            }

            addLog({
                timestamp,
                type: firebase_result.type,
                target,
                title,
                body,
                success: firebase_result.success,
                message: firebase_result.message,
            });
        }
    }, [firebase_result]);

    const clearLogs = () => {
        setNotificationLogs([]);
    };

    const isFormValid = () => {
        if (!title || !body) return false;
        if (notificationType === 'token' && !fcmToken) return false;
        if (notificationType === 'user' && !selectedUserId) return false;
        return true;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Firebase Test" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Firebase Notification Test" description="Test Firebase Cloud Messaging (FCM) notifications" />

                {users.length === 0 && (
                    <Alert>
                        <AlertCircle className="size-4" />
                        <AlertDescription>No users with FCM tokens found. Make sure devices have registered their tokens.</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Send Notification Form */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Send Notification</CardTitle>
                            <CardDescription>Configure and send Firebase notifications</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Notification Type */}
                            <div>
                                <Label htmlFor="notification-type">Send To</Label>
                                <Select value={notificationType} onValueChange={(value: any) => setNotificationType(value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="token">Specific FCM Token</SelectItem>
                                        <SelectItem value="user">Specific User (All Devices)</SelectItem>
                                        <SelectItem value="all">All Users</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* FCM Token Input */}
                            {notificationType === 'token' && (
                                <div className="space-y-2">
                                    <Label htmlFor="fcm-token">FCM Token</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="fcm-token"
                                            value={fcmToken}
                                            onChange={(e) => setFcmToken(e.target.value)}
                                            placeholder="Enter FCM token"
                                            className="flex-1"
                                        />
                                        <Button onClick={validateToken} disabled={!fcmToken || isValidating} variant="outline" size="sm">
                                            {isValidating ? 'Validating...' : 'Validate'}
                                        </Button>
                                    </div>
                                    {tokenValidation && (
                                        <Alert variant={tokenValidation.valid ? 'default' : 'destructive'}>
                                            {tokenValidation.valid ? <CheckCircle2 className="size-4" /> : <AlertCircle className="size-4" />}
                                            <AlertDescription>{tokenValidation.message}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>
                            )}

                            {/* User Selection */}
                            {notificationType === 'user' && (
                                <div className="space-y-2">
                                    <Label htmlFor="user-select">Select User</Label>
                                    <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Choose a user..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {users.map((user) => (
                                                <SelectItem key={user.id} value={user.id.toString()}>
                                                    {user.name} ({user.email}) - {user.devices_count} device(s)
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {selectedUser && (
                                        <div className="rounded-md border bg-muted/50 p-3">
                                            <p className="mb-2 text-sm font-medium">FCM Tokens ({selectedUser.fcm_tokens.length}):</p>
                                            <div className="space-y-1">
                                                {selectedUser.fcm_tokens.map((token, idx) => (
                                                    <div key={idx} className="font-mono text-xs break-all">
                                                        {token.substring(0, 60)}...
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* All Users Info */}
                            {notificationType === 'all' && (
                                <Alert>
                                    <AlertCircle className="size-4" />
                                    <AlertDescription>
                                        This will send the notification to all {users.length} users with registered devices.
                                    </AlertDescription>
                                </Alert>
                            )}

                            {/* Notification Content */}
                            <div>
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Notification title" />
                            </div>

                            <div>
                                <Label htmlFor="body">Body</Label>
                                <Textarea
                                    id="body"
                                    value={body}
                                    onChange={(e) => setBody(e.target.value)}
                                    placeholder="Notification message"
                                    rows={3}
                                />
                            </div>

                            {/* Custom Data */}
                            <div>
                                <Label htmlFor="custom-data">Custom Data (JSON)</Label>
                                <Textarea
                                    id="custom-data"
                                    value={customData}
                                    onChange={(e) => setCustomData(e.target.value)}
                                    placeholder='{"key": "value"}'
                                    rows={3}
                                    className="font-mono text-xs"
                                />
                                <p className="mt-1 text-xs text-muted-foreground">Optional additional data to send with notification</p>
                            </div>

                            <Button onClick={sendNotification} disabled={!isFormValid() || isSending} className="w-full">
                                <Send className="mr-2 size-4" />
                                {isSending ? 'Sending...' : 'Send Notification'}
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Notification Logs */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Notification Log</CardTitle>
                                    <CardDescription>History of sent notifications</CardDescription>
                                </div>
                                <Button onClick={clearLogs} variant="outline" size="sm">
                                    Clear
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {notificationLogs.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">No notifications sent yet</p>
                                ) : (
                                    notificationLogs.map((log, index) => (
                                        <div key={index} className="space-y-2 rounded-md border p-3">
                                            <div className="flex items-center justify-between">
                                                <Badge variant={log.success ? 'default' : 'destructive'}>
                                                    {log.success ? (
                                                        <>
                                                            <CheckCircle2 className="mr-1 size-3" />
                                                            Success
                                                        </>
                                                    ) : (
                                                        <>
                                                            <AlertCircle className="mr-1 size-3" />
                                                            Failed
                                                        </>
                                                    )}
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">{log.timestamp}</span>
                                            </div>
                                            <div className="space-y-1 text-sm">
                                                <div>
                                                    <span className="font-medium">Type:</span> {log.type}
                                                </div>
                                                <div>
                                                    <span className="font-medium">Target:</span> {log.target}
                                                </div>
                                                <div>
                                                    <span className="font-medium">Title:</span> {log.title}
                                                </div>
                                                <div>
                                                    <span className="font-medium">Body:</span> {log.body}
                                                </div>
                                                <div className="rounded bg-muted p-2 text-xs">
                                                    <span className="font-medium">Result:</span> {log.message}
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Users Overview */}
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Users with FCM Tokens ({users.length})</CardTitle>
                            <CardDescription>Active users with registered device tokens</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {users.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No users found</p>
                            ) : (
                                <div className="space-y-2">
                                    {users.map((user) => (
                                        <div key={user.id} className="flex items-center justify-between rounded-md border p-3">
                                            <div>
                                                <p className="font-medium">{user.name}</p>
                                                <p className="text-sm text-muted-foreground">{user.email}</p>
                                            </div>
                                            <Badge variant="outline">{user.devices_count} device(s)</Badge>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
