# Firebase Notifications Guide

This guide explains how to send Firebase Cloud Messaging (FCM) notifications to your mobile app users.

## Prerequisites

1. Firebase credentials file configured in `config/firebase.php`
2. Users must have registered their FCM tokens in the `user_devices` table

## Testing Page

Visit the Firebase notification test page at `/admin/firebase-test` to:
- Send test notifications to specific FCM tokens
- Send notifications to specific users (all their devices)
- Broadcast notifications to all users
- Validate FCM tokens
- View notification history

## Using FirebaseService

### Direct Service Usage

Inject the `FirebaseService` into your controller or use it via dependency injection:

```php
use App\Services\FirebaseService;

class YourController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    public function sendNotification()
    {
        // Send to a specific user
        $user = User::find(1);
        $this->firebaseService->sendToUser(
            $user,
            'Welcome!',
            'Thanks for joining our platform',
            ['action' => 'welcome', 'url' => '/home'],
            'welcome'
        );

        // Send to multiple users
        $this->firebaseService->sendToUsers(
            [1, 2, 3],
            'New Feature Available',
            'Check out our new feature!',
            ['feature_id' => 123],
            'feature'
        );

        // Send to all users
        $this->firebaseService->sendToAll(
            'Maintenance Notice',
            'System maintenance scheduled for tonight',
            ['type' => 'maintenance'],
            'system'
        );

        // Send to specific device token
        $result = $this->firebaseService->sendToDevice(
            'fcm-token-here',
            'Test Notification',
            'This is a test'
        );

        // Validate a token
        $isValid = $this->firebaseService->validateToken('fcm-token-here');
    }
}
```

## Using Helper Functions

Convenient helper functions are available throughout your application:

### Send to a Single User

```php
// Using User model
$user = User::find(1);
send_firebase_notification(
    $user,
    'Order Shipped',
    'Your order #12345 has been shipped',
    ['order_id' => 12345, 'tracking' => 'ABC123'],
    'order'
);

// Using user ID
send_firebase_notification(
    1,
    'New Message',
    'You have a new message from support'
);
```

### Send to Multiple Users

```php
send_firebase_notification_to_users(
    [1, 2, 3, 4, 5],
    'Flash Sale!',
    '50% off all products for the next 2 hours',
    ['sale_id' => 789],
    'promotion'
);
```

### Send to All Users

```php
send_firebase_notification_to_all(
    'App Update Available',
    'Version 2.0 is now available with new features',
    ['version' => '2.0.0', 'force_update' => false],
    'app_update'
);
```

### Send to Specific Token

```php
$result = send_firebase_notification_to_token(
    'fcm-device-token-here',
    'Test Notification',
    'This is a test message',
    ['test' => true]
);

if ($result['success']) {
    // Notification sent successfully
} else {
    // Handle error: $result['error']
}
```

## Notification Structure

### Parameters

- **title** (string): Notification title (max 255 characters)
- **body** (string): Notification message (max 1000 characters)
- **data** (array, optional): Custom data to send with notification
- **type** (string, optional): Notification type for categorization (default: 'general')

### Custom Data

The `data` parameter allows you to send additional information:

```php
send_firebase_notification(
    $user,
    'New Order',
    'You have a new order to process',
    [
        'order_id' => 12345,
        'customer_name' => 'John Doe',
        'total_amount' => 99.99,
        'action' => 'open_order',
        'deep_link' => 'myapp://orders/12345'
    ],
    'order'
);
```

Your mobile app can then access this data to perform specific actions.

## Notification Types

Common notification types to use:

- `general` - General announcements
- `order` - Order updates
- `promotion` - Promotional messages
- `feature` - New feature announcements
- `welcome` - Welcome messages
- `reminder` - Reminders
- `alert` - Important alerts
- `system` - System notifications
- `test` - Test notifications

## Database Storage

Notifications are automatically stored in the `notifications` table with:
- User ID
- Title and body
- Custom data (JSON)
- Type
- Sent status
- Timestamp
- Error messages (if any)

Query notifications:

```php
use App\Models\Notification;

// Get user's notifications
$notifications = Notification::where('user_id', $userId)
    ->orderBy('sent_at', 'desc')
    ->get();

// Get failed notifications
$failed = Notification::where('is_sent', false)
    ->whereNotNull('error_message')
    ->get();
```

## Examples

### Order Status Update

```php
$order = Order::find($orderId);
send_firebase_notification(
    $order->user,
    "Order #{$order->id} - {$order->status}",
    "Your order status has been updated to {$order->status}",
    [
        'order_id' => $order->id,
        'status' => $order->status,
        'action' => 'view_order'
    ],
    'order'
);
```

### New Product Alert

```php
$interestedUsers = User::whereHas('interests', function($q) {
    $q->where('category_id', $product->category_id);
})->pluck('id')->toArray();

send_firebase_notification_to_users(
    $interestedUsers,
    "New Product: {$product->name}",
    $product->description,
    [
        'product_id' => $product->id,
        'category' => $product->category->name,
        'action' => 'view_product'
    ],
    'product'
);
```

### Scheduled Maintenance

```php
send_firebase_notification_to_all(
    'Scheduled Maintenance',
    'The app will be unavailable from 2 AM to 4 AM for maintenance',
    [
        'start_time' => '2024-01-01 02:00:00',
        'end_time' => '2024-01-01 04:00:00',
        'type' => 'maintenance'
    ],
    'system'
);
```

## Error Handling

The service returns results with success status:

```php
$result = send_firebase_notification_to_token($token, $title, $body);

if ($result['success']) {
    // Success
    $sentToken = $result['token'];
} else {
    // Failed
    $error = $result['error'];
    Log::error('FCM notification failed', [
        'token' => $result['token'],
        'error' => $error
    ]);
}
```

For user notifications (which can have multiple devices):

```php
$results = send_firebase_notification($user, $title, $body);

$successCount = collect($results)->where('success', true)->count();
$totalDevices = count($results);

if ($successCount === 0) {
    // All devices failed
} elseif ($successCount < $totalDevices) {
    // Some devices failed
} else {
    // All devices succeeded
}
```

## Token Management

Mobile apps should register/update FCM tokens via the API:

```php
// In your mobile API controller
public function updateFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
        'device_uuid' => 'required|string',
    ]);

    $device = UserDevice::updateOrCreate(
        [
            'device_uuid' => $request->device_uuid,
            'deviceable_type' => User::class,
            'deviceable_id' => auth()->id(),
        ],
        [
            'fcm_token' => $request->fcm_token,
        ]
    );

    return response()->json(['success' => true]);
}
```

## Troubleshooting

### Notifications not being received

1. Check Firebase credentials are properly configured
2. Verify FCM token is valid using the test page
3. Check `notifications` table for error messages
4. Ensure the mobile app has proper Firebase configuration
5. Check device notification permissions

### Invalid token errors

- Tokens can expire or become invalid
- Mobile apps should refresh tokens periodically
- Remove invalid tokens from database after repeated failures

### Rate limits

- FCM has rate limits (typically 1,000,000 messages per project per hour)
- For large broadcasts, consider queuing notifications

## Best Practices

1. **Queue notifications** for large batches:
   ```php
   SendFirebaseNotification::dispatch($user, $title, $body);
   ```

2. **Validate tokens** before important notifications

3. **Clean up old tokens** that repeatedly fail

4. **Use meaningful types** for filtering and analytics

5. **Keep notifications concise** - titles under 50 chars, body under 200 chars

6. **Test thoroughly** using the test page before production use

7. **Handle errors gracefully** and log failures

8. **Respect user preferences** - allow opt-out options

## Mobile App Integration

Your mobile app should:

1. Initialize Firebase SDK
2. Request notification permissions
3. Register/update FCM token with backend
4. Handle notification display
5. Process notification data/actions
6. Refresh tokens on updates

Example flow:
```
Mobile App -> Get FCM Token -> Send to Backend API -> Store in user_devices table
Backend -> Send Notification -> Firebase -> Mobile App
```
