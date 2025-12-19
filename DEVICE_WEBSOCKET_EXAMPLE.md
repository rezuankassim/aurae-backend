# Device WebSocket Authentication Flow

## Overview
After a device successfully logs in, the backend broadcasts an access token via WebSocket to a device-specific channel. The frontend device listens to this channel using its UUID and receives the access token securely.

## Setup Instructions

### 1. Update Your Environment File
Copy the Reverb configuration from `.env.example` to your `.env` file:

```bash
cp .env.example .env
```

Make sure these values are set:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=aurae-app-id
REVERB_APP_KEY=aurae-app-key
REVERB_APP_SECRET=aurae-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 2. Start the Reverb WebSocket Server
```bash
php artisan reverb:start
```

Or add it to your development workflow by updating `composer.json`:
```json
"dev": [
    "Composer\\Config::disableProcessTimeout",
    "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74,#a78bfa\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"php artisan reverb:start\" \"npm run dev\" --names=server,queue,logs,reverb,vite --kill-others"
]
```

### 3. Start Queue Worker
Since the broadcasting is queued, make sure your queue worker is running:
```bash
php artisan queue:listen
```

## Frontend Device Implementation

### JavaScript/TypeScript Example (React Native / Mobile)

Install the Laravel Echo and Pusher JS libraries:
```bash
npm install laravel-echo pusher-js
```

**Example Code:**

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Laravel Echo
window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: 'aurae-app-key', // Same as REVERB_APP_KEY
    wsHost: 'localhost',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// Device UUID (obtained from device hardware or stored locally)
const deviceUuid = 'your-device-udid-here';

// Listen to the device-specific channel BEFORE making the login request
const channel = echo.channel(`device.${deviceUuid}`);

channel.listen('.device.authenticated', (event: { access_token: string }) => {
    console.log('Received access token:', event.access_token);
    
    // Store the access token securely
    // Use secure storage like Keychain (iOS) or KeyStore (Android)
    await SecureStore.setItemAsync('access_token', event.access_token);
    
    // Navigate to authenticated screen or update app state
    navigation.navigate('Home');
});

// Now make the login request
async function login(email: string, password: string) {
    try {
        const response = await fetch('http://localhost:8000/api/login', {
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
            body: JSON.stringify({ email, password }),
        });
        
        const data = await response.json();
        console.log('Login response:', data);
        
        // The access token will arrive via WebSocket
        // No need to extract it from the response
    } catch (error) {
        console.error('Login failed:', error);
    }
}
```

### Flutter Example

Install the packages:
```yaml
dependencies:
  laravel_echo:
  pusher_client:
```

**Example Code:**

```dart
import 'package:laravel_echo/laravel_echo.dart';
import 'package:pusher_client/pusher_client.dart';

class WebSocketService {
  late Echo echo;
  final String deviceUuid;

  WebSocketService(this.deviceUuid) {
    // Initialize Pusher
    PusherOptions options = PusherOptions(
      host: 'localhost',
      wsPort: 8080,
      encrypted: false,
      cluster: 'mt1',
    );

    PusherClient pusher = PusherClient(
      'aurae-app-key', // Same as REVERB_APP_KEY
      options,
      autoConnect: true,
    );

    // Initialize Echo
    echo = Echo({
      'broadcaster': 'pusher',
      'client': pusher,
    });
  }

  void listenForAuthentication(Function(String) onTokenReceived) {
    echo.channel('device.$deviceUuid')
        .listen('.device.authenticated', (e) {
      String accessToken = e['access_token'];
      print('Received access token: $accessToken');
      onTokenReceived(accessToken);
    });
  }

  Future<void> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('http://localhost:8000/api/login'),
      headers: {
        'Content-Type': 'application/json',
        'X-Device-Udid': deviceUuid,
        'X-Device-OS': 'Android',
        'X-Device-OS-Version': '13',
        'X-Device-Manufacturer': 'Samsung',
        'X-Device-Model': 'Galaxy S23',
        'X-Device-App-Version': '1.0.0',
      },
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );

    // Token will arrive via WebSocket
  }
}

// Usage
final wsService = WebSocketService('device-uuid-here');

wsService.listenForAuthentication((token) async {
  // Store token securely
  await storage.write(key: 'access_token', value: token);
  // Navigate to home screen
  Navigator.pushReplacementNamed(context, '/home');
});

await wsService.login('user@example.com', 'password');
```

## Testing

### 1. Start all services:
```bash
composer dev
# This will start: server, queue, logs, and vite
```

### 2. In a separate terminal, start Reverb:
```bash
php artisan reverb:start
```

### 3. Test the flow:
- Device connects to WebSocket channel: `device.{uuid}`
- Device sends login request with `X-Device-Udid` header
- Backend authenticates user and fires `DeviceAuthenticated` event
- Queue worker processes the event and broadcasts to Reverb
- Device receives the access token via WebSocket

## Security Considerations

1. **Channel Naming**: Device UUID is used as the channel name, making it unique per device
2. **Public Channel**: The channel is public (not private) since the device needs to listen before authentication
3. **Token Transmission**: Access token is only broadcast once after successful authentication
4. **Secure Storage**: Always store received tokens in secure storage (Keychain/KeyStore/SecureStore)
5. **HTTPS/WSS**: In production, use WSS (secure WebSocket) with proper SSL certificates

## Production Configuration

Update your `.env` for production:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-production-app-id
REVERB_APP_KEY=your-production-app-key
REVERB_APP_SECRET=your-production-app-secret
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

## Troubleshooting

### WebSocket not connecting
- Ensure Reverb server is running: `php artisan reverb:start`
- Check firewall settings for port 8080
- Verify `REVERB_APP_KEY` matches on both backend and frontend

### Token not received
- Ensure queue worker is running: `php artisan queue:listen`
- Check Laravel logs: `php artisan pail`
- Verify device UUID matches between connection and login request

### Broadcasting not working
- Check `BROADCAST_CONNECTION=reverb` in `.env`
- Verify Reverb credentials are correct
- Test with: `php artisan tinker` then `broadcast(new App\Events\DeviceAuthenticated('test-uuid', 'test-token'));`
