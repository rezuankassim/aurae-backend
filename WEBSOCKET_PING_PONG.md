# WebSocket Ping-Pong Mechanism

## Overview
This document describes the ping-pong mechanism for maintaining WebSocket connections between frontend devices and the backend server. The mechanism helps keep connections alive and detect network issues.

## How It Works

1. Frontend device sends a POST request to `/api/ws/ping` with the device UUID
2. Backend receives the request and broadcasts a `device.pong` event via WebSocket
3. Device listens to its specific channel (`device.{uuid}`) and receives the pong response

## Backend Implementation

### Event: `DevicePing`
Location: `app/Events/DevicePing.php`

Broadcasts a pong message to the device-specific channel when triggered.

### Controller: `WebSocketController`
Location: `app/Http/Controllers/Api/WebSocketController.php`

Handles the `/api/ws/ping` endpoint and fires the `DevicePing` event.

### Route
Location: `routes/api.php`

```php
Route::post('/ws/ping', [WebSocketController::class, 'ping'])->name('api.ws.ping');
```

## Frontend Implementation

### Prerequisites
Make sure you have Laravel Echo and Pusher JS installed:
```bash
npm install laravel-echo pusher-js
```

### Setup Echo Connection
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Laravel Echo
window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: 'aurae-app-key', // Same as REVERB_APP_KEY in .env
    wsHost: 'localhost',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// Device UUID (from hardware or storage)
const deviceUuid = 'your-device-uuid-here';
```

### Listen for Pong Events
```typescript
// Subscribe to device-specific channel
const channel = echo.channel(`device.${deviceUuid}`);

// Listen for pong responses
channel.listen('.device.pong', (event: { message: string; timestamp: string }) => {
    console.log('Received pong:', event.message);
    console.log('Server timestamp:', event.timestamp);
    
    // Update connection status
    setConnectionStatus('connected');
    setLastPongTime(new Date(event.timestamp));
});
```

### Send Ping Requests
```typescript
async function sendPing() {
    try {
        const response = await fetch('http://localhost:8000/api/ws/ping', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Device-Udid': deviceUuid,
                'X-Device-OS': 'iOS', // or 'Android'
                'X-Device-OS-Version': '17.0',
                'X-Device-Manufacturer': 'Apple',
                'X-Device-Model': 'iPhone 15 Pro',
                'X-Device-App-Version': '1.0.0',
            },
            body: JSON.stringify({
                device_uuid: deviceUuid,
            }),
        });
        
        const data = await response.json();
        console.log('Ping sent:', data);
    } catch (error) {
        console.error('Ping failed:', error);
        setConnectionStatus('disconnected');
    }
}
```

### Implement Periodic Ping
```typescript
import { useEffect, useState, useRef } from 'react';

function useWebSocketPing(deviceUuid: string, intervalMs: number = 30000) {
    const [isConnected, setIsConnected] = useState(false);
    const [lastPongTime, setLastPongTime] = useState<Date | null>(null);
    const pingIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);
    
    useEffect(() => {
        // Listen for pong responses
        const channel = echo.channel(`device.${deviceUuid}`);
        
        channel.listen('.device.pong', (event: { message: string; timestamp: string }) => {
            console.log('Pong received:', event.timestamp);
            setIsConnected(true);
            setLastPongTime(new Date(event.timestamp));
            
            // Clear timeout since we received response
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        });
        
        // Send ping periodically
        const sendPingWithTimeout = async () => {
            try {
                await fetch('http://localhost:8000/api/ws/ping', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Device-Udid': deviceUuid,
                        'X-Device-OS': 'iOS',
                        'X-Device-OS-Version': '17.0',
                        'X-Device-Manufacturer': 'Apple',
                        'X-Device-Model': 'iPhone 15 Pro',
                        'X-Device-App-Version': '1.0.0',
                    },
                    body: JSON.stringify({ device_uuid: deviceUuid }),
                });
                
                // Set timeout for pong response (5 seconds)
                timeoutRef.current = setTimeout(() => {
                    console.warn('Pong response timeout');
                    setIsConnected(false);
                }, 5000);
            } catch (error) {
                console.error('Ping failed:', error);
                setIsConnected(false);
            }
        };
        
        // Send initial ping
        sendPingWithTimeout();
        
        // Set up periodic pings
        pingIntervalRef.current = setInterval(sendPingWithTimeout, intervalMs);
        
        // Cleanup
        return () => {
            if (pingIntervalRef.current) {
                clearInterval(pingIntervalRef.current);
            }
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
            channel.stopListening('.device.pong');
        };
    }, [deviceUuid, intervalMs]);
    
    return { isConnected, lastPongTime };
}

// Usage in component
function MyComponent() {
    const deviceUuid = 'your-device-uuid';
    const { isConnected, lastPongTime } = useWebSocketPing(deviceUuid, 30000); // Ping every 30 seconds
    
    return (
        <div>
            <p>Status: {isConnected ? 'Connected' : 'Disconnected'}</p>
            {lastPongTime && <p>Last ping: {lastPongTime.toLocaleString()}</p>}
        </div>
    );
}
```

### React Native Example
```typescript
import { useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Echo (do this once, preferably in a service file)
const echo = new Echo({
    broadcaster: 'reverb',
    key: 'aurae-app-key',
    wsHost: 'your-server.com',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

export function useDevicePing(deviceUuid: string) {
    const [isConnected, setIsConnected] = useState(false);
    const [lastPongTime, setLastPongTime] = useState<Date | null>(null);
    
    useEffect(() => {
        // Listen for pong
        const channel = echo.channel(`device.${deviceUuid}`);
        
        channel.listen('.device.pong', (event) => {
            console.log('Pong received');
            setIsConnected(true);
            setLastPongTime(new Date(event.timestamp));
        });
        
        // Send ping every 30 seconds
        const sendPing = async () => {
            try {
                await fetch('https://your-server.com/api/ws/ping', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Device-Udid': deviceUuid,
                        'X-Device-OS': Platform.OS,
                        'X-Device-OS-Version': Platform.Version.toString(),
                        'X-Device-Manufacturer': await Device.manufacturer,
                        'X-Device-Model': await Device.modelName,
                        'X-Device-App-Version': '1.0.0',
                    },
                    body: JSON.stringify({ device_uuid: deviceUuid }),
                });
            } catch (error) {
                console.error('Ping error:', error);
                setIsConnected(false);
            }
        };
        
        // Send initial ping
        sendPing();
        
        // Set interval
        const interval = setInterval(sendPing, 30000);
        
        return () => {
            clearInterval(interval);
            channel.stopListening('.device.pong');
        };
    }, [deviceUuid]);
    
    return { isConnected, lastPongTime };
}
```

## Configuration

### Environment Variables
Make sure these are set in your `.env` file:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=aurae-app-id
REVERB_APP_KEY=aurae-app-key
REVERB_APP_SECRET=aurae-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Start Reverb Server
```bash
php artisan reverb:start
```

### Queue Worker
Since broadcasting is queued, ensure the queue worker is running:
```bash
php artisan queue:listen
```

Or use the combined development command:
```bash
composer dev
```

## API Reference

### POST `/api/ws/ping`

**Headers:**
- `Content-Type`: application/json
- `X-Device-Udid`: Device UUID (required by EnsureDevice middleware)
- `X-Device-OS`: Device OS (iOS/Android)
- `X-Device-OS-Version`: OS version
- `X-Device-Manufacturer`: Device manufacturer
- `X-Device-Model`: Device model
- `X-Device-App-Version`: App version

**Request Body:**
```json
{
    "device_uuid": "your-device-uuid"
}
```

**Response:**
```json
{
    "data": {
        "message": "pong",
        "timestamp": "2026-01-04T02:41:11+00:00"
    },
    "status": 200,
    "message": "Pong sent successfully"
}
```

**WebSocket Event:**
Channel: `device.{uuid}`
Event: `.device.pong`
```json
{
    "message": "pong",
    "timestamp": "2026-01-04T02:41:11+00:00"
}
```

## Best Practices

1. **Ping Interval**: 30 seconds is recommended to keep connections alive without overwhelming the server
2. **Timeout**: Wait up to 5 seconds for pong response before marking connection as dead
3. **Reconnection**: Implement exponential backoff when reconnecting after failures
4. **Error Handling**: Always handle network errors gracefully
5. **Battery Optimization**: On mobile, adjust ping frequency based on app state (foreground/background)

## Connection Pruning

The backend automatically monitors WebSocket connections and prunes inactive/stale connections. When a connection is pruned, detailed information is logged.

### What Gets Logged

When a connection is pruned due to inactivity, the following information is logged:

- **Connection ID**: Unique identifier for the connection
- **Connection Identifier**: Raw socket connection identifier
- **App ID & Key**: Reverb application credentials
- **Origin**: Origin of the WebSocket connection
- **Last Seen At**: Timestamp when connection was last active
- **Last Seen Seconds Ago**: How long the connection was inactive
- **Was Inactive**: Whether the connection exceeded ping interval
- **Was Stale**: Whether the connection failed to respond to ping
- **Uses Control Frames**: Whether WebSocket control frames are used
- **Ping Interval**: Configured ping interval for the app
- **Channel Data**: Any additional channel subscription data

### Viewing Connection Logs

You can monitor connection pruning events in real-time:

```bash
php artisan pail
```

Or check the Laravel log file:

```bash
tail -f storage/logs/laravel.log | grep "connection_pruned"
```

### Example Log Entry

```json
{
    "event": "connection_pruned",
    "connection_id": "abc123",
    "connection_identifier": "resource_123",
    "app_id": "605567",
    "app_key": "bldn8aqyc7eyb1jwgkn7",
    "origin": "http://localhost:3000",
    "last_seen_at": "2026-01-04 14:30:00",
    "last_seen_seconds_ago": 65,
    "was_inactive": true,
    "was_stale": true,
    "uses_control_frames": false,
    "ping_interval": 30,
    "channel_data": []
}
```

## Troubleshooting

### Pong not received
- Verify Reverb server is running: `php artisan reverb:start`
- Check queue worker is running: `php artisan queue:listen`
- Ensure device UUID matches between ping request and channel subscription
- Check Laravel logs: `php artisan pail`

### Connection keeps dropping
- Check network stability
- Verify firewall allows WebSocket connections (port 8080)
- Ensure REVERB_APP_KEY matches between backend and frontend
- Monitor connection pruning logs to see if connections are timing out
- Adjust ping interval if connections are being pruned too aggressively

### High latency
- Check server resources
- Consider adjusting ping interval
- Verify no network congestion

### Connection Pruned Frequently
- Check if ping interval is too short in Reverb config:
  - **Config file**: `config/reverb.php` line 86
  - **Environment variable**: `REVERB_APP_PING_INTERVAL` (default: 60 seconds)
  - **Activity timeout**: `REVERB_APP_ACTIVITY_TIMEOUT` (default: 30 seconds)
  - Connections are pruned if inactive longer than `ping_interval`
- Verify frontend is sending pings regularly (recommended every 30 seconds)
- Ensure network isn't dropping packets
- Review pruning logs for patterns (check `last_seen_seconds_ago`)
