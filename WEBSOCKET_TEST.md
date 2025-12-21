# WebSocket Test Page

An admin page to test the `DeviceAuthenticated` WebSocket event in real-time.

## Location

Navigate to: `/admin/websocket-test` (available in the admin sidebar under "WebSocket Test")

## Prerequisites

1. **Reverb Server Running**
   ```bash
   php artisan reverb:start
   ```

2. **Queue Worker Running** (events are queued)
   ```bash
   php artisan queue:listen
   ```

3. **Development Server Running**
   ```bash
   composer dev
   # Or: php artisan serve
   ```

## How to Use

### 1. Connect to WebSocket
- Click **"Connect"** button in the "Connection" card
- Status will change to "Connected"
- Connection log will show connection details

### 2. Listen to a Channel
- Enter a Device UUID (default: `test-device-123`)
- Click **"Listen"** button in the "Channel Listener" card
- The page will subscribe to `device.{uuid}` channel
- Status will change to "Listening"

### 3. Trigger an Event
- Enter the same Device UUID in the "Trigger Event" card
- Optionally, provide a custom Access Token (auto-generates if empty)
- Click **"Trigger Event"** button
- This will broadcast a `DeviceAuthenticated` event from the backend

### 4. View Results
- The event will be received in real-time
- Connection log will show the event details
- "Received Events" section will display:
  - Event number and timestamp
  - Full access token

## Features

### Connection Card
- **Connect/Disconnect**: Manage WebSocket connection
- **Status Badge**: Shows connection state (Connected/Disconnected)
- **Server Info**: Displays Reverb server configuration

### Channel Listener Card
- **Device UUID Input**: Specify which channel to listen to
- **Listen/Stop**: Control channel subscription
- **Status Badge**: Shows listening state (Listening/Idle)
- **Channel Preview**: Shows the full channel name being monitored

### Trigger Event Card
- **Device UUID**: Target device for the event
- **Access Token**: Optional custom token (auto-generates 60 char token)
- **Trigger Button**: Broadcasts the event via backend

### Connection Log
- Real-time activity log
- Timestamps for all actions
- Connection/disconnection events
- Event received notifications
- Clear button to reset logs

### Received Events
- Displays all received events
- Numbered in reverse order (newest first)
- Full access token display
- Timestamp for each event

## Testing Workflow

1. **Start Services**:
   ```bash
   # Terminal 1: Reverb
   php artisan reverb:start
   
   # Terminal 2: Queue Worker
   php artisan queue:listen
   
   # Terminal 3: Development Server
   composer dev
   ```

2. **Open Admin Panel**: Navigate to `/admin/websocket-test`

3. **Test Flow**:
   - Click "Connect"
   - Enter UUID: `test-device-123`
   - Click "Listen"
   - Click "Trigger Event"
   - Watch the event appear in "Received Events"

4. **Test Different Channels**:
   - Stop listening to current channel
   - Enter a different UUID
   - Start listening again
   - Trigger events with the new UUID

## Event Details

### DeviceAuthenticated Event
- **Channel**: `device.{deviceUuid}`
- **Event Name**: `device.authenticated`
- **Payload**:
  ```json
  {
    "access_token": "60-character-random-string"
  }
  ```

### Backend Implementation
- **Event Class**: `App\Events\DeviceAuthenticated`
- **Controller**: `App\Http\Controllers\Admin\WebSocketTestController`
- **Routes**:
  - GET `/admin/websocket-test` - Test page
  - POST `/admin/websocket-test/trigger` - Trigger event

## Troubleshooting

### WebSocket won't connect
- Ensure Reverb server is running: `php artisan reverb:start`
- Check `.env` has correct `REVERB_*` variables
- Verify port 8080 is not blocked

### Event not received
- Ensure queue worker is running: `php artisan queue:listen`
- Check Laravel logs: `php artisan pail`
- Verify Device UUID matches between listener and trigger

### "Already connected" message
- Click "Disconnect" first, then "Connect" again
- Refresh the page to reset connection state

## Configuration

Reverb configuration is loaded from `.env`:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=aurae-app-id
REVERB_APP_KEY=aurae-app-key
REVERB_APP_SECRET=aurae-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Notes

- This tool is for **admin testing only**
- Events are broadcast in real-time but go through the queue
- Multiple tabs can connect and listen simultaneously
- Page automatically disconnects on unmount/navigation
