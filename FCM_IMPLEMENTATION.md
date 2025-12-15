# Firebase Cloud Messaging Implementation Guide

## Setup Complete ✅

The FCM notification system has been implemented with the following components:

### 1. Dependencies
- ✅ Installed `kreait/firebase-php` package

### 2. Configuration
- ✅ Created `config/firebase.php`
- ✅ Added Firebase credentials to `.env.example`:
  - `FIREBASE_CREDENTIALS` - Path to service account JSON file
  - `FIREBASE_PROJECT_ID` - Your Firebase project ID
  - `FIREBASE_DATABASE_URL` - Firebase database URL (optional)

### 3. Database
- ✅ `user_devices` table has `fcm_token` column
- ✅ Created `notifications` table to track sent notifications

### 4. Services & Jobs
- ✅ `FirebaseService` - Handles all FCM operations
- ✅ `SendPushNotification` job - Queued notification sending

### 5. API Endpoints
- ✅ `POST /api/device/fcm-token` - Update device FCM token

## Setup Instructions

### 1. Firebase Console Setup
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Go to Project Settings → Service Accounts
4. Click "Generate New Private Key"
5. Save the JSON file to your server (e.g., `storage/firebase/credentials.json`)

### 2. Environment Configuration
Add to your `.env`:
```env
FIREBASE_CREDENTIALS=/absolute/path/to/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
```

### 3. Mobile App Integration

#### Register FCM Token
```http
POST /api/device/fcm-token
Authorization: Bearer {token}

{
  "fcm_token": "device_fcm_token_here"
}
```

## Usage Examples

### Send Notification to Single User
```php
use App\Services\FirebaseService;
use App\Models\User;

$firebaseService = new FirebaseService();
$user = User::find(1);

$firebaseService->sendToUser(
    $user,
    'Notification Title',
    'Notification Body',
    ['key' => 'value'], // Optional data
    'general' // Optional type
);
```

### Send Notification to Multiple Users
```php
$firebaseService->sendToUsers(
    [1, 2, 3], // User IDs
    'Notification Title',
    'Notification Body',
    ['key' => 'value'],
    'general'
);
```

### Send Notification to All Users
```php
$firebaseService->sendToAll(
    'Notification Title',
    'Notification Body',
    ['key' => 'value'],
    'maintenance'
);
```

### Using Queue (Recommended for Large Batches)
```php
use App\Jobs\SendPushNotification;

// Send to specific users
SendPushNotification::dispatch(
    [1, 2, 3], // User IDs
    'Title',
    'Body',
    ['key' => 'value'],
    'general',
    false // sendToAll
);

// Send to all users
SendPushNotification::dispatch(
    [],
    'Title',
    'Body',
    ['key' => 'value'],
    'general',
    true // sendToAll
);
```

## Notification Types
- `general` - General notifications
- `maintenance` - Maintenance reminders
- `promotion` - Promotional messages
- `therapy` - Therapy-related notifications
- `custom` - Custom notifications

## Admin Interface (To Be Implemented)

Create these admin pages for managing notifications:

### 1. Send Notification Page
- `/admin/notifications/create`
- Form fields:
  - Title (required)
  - Body/Message (required)
  - Type (dropdown)
  - Recipients (all users / specific users)
  - Additional data (JSON, optional)
- Submit button dispatches `SendPushNotification` job

### 2. Notification History Page
- `/admin/notifications`
- DataTable showing:
  - Title
  - Recipient count
  - Type
  - Status (sent/failed)
  - Sent at
  - Actions (view details)

### 3. Notification Details Page
- `/admin/notifications/{id}`
- Show:
  - Title, body, type
  - Recipient info
  - Sent status
  - Error messages (if any)
  - Timestamp

## Testing

### Test FCM Token
```php
$firebaseService = new FirebaseService();
$isValid = $firebaseService->validateToken('fcm_token_here');
```

### Test Send
```php
$result = $firebaseService->sendToDevice(
    'fcm_token',
    'Test Title',
    'Test Body',
    []
);

// Returns:
// ['success' => true/false, 'token' => '...', 'error' => '...']
```

## Troubleshooting

### Common Issues

1. **"Firebase messaging not initialized"**
   - Check `FIREBASE_CREDENTIALS` path is correct
   - Verify JSON file exists and is readable
   - Ensure JSON file format is valid

2. **Notifications not received**
   - Verify FCM token is up-to-date in database
   - Check Firebase project has Cloud Messaging enabled
   - Verify app has correct Firebase configuration

3. **Invalid credentials error**
   - Regenerate service account key from Firebase Console
   - Update FIREBASE_CREDENTIALS path

## Security Notes

- ⚠️ **NEVER** commit Firebase credentials to version control
- Add `storage/firebase/` to `.gitignore`
- Use environment variables for all sensitive configuration
- Restrict service account permissions to Cloud Messaging only

## Database Schema

### notifications table
```sql
- id (bigint)
- user_id (nullable, foreign key)
- title (string)
- body (text)
- data (json, nullable)
- type (string, default: 'general')
- sent_at (timestamp, nullable)
- is_sent (boolean, default: false)
- error_message (text, nullable)
- created_at, updated_at
```

### user_devices table (existing)
```sql
- fcm_token (string, nullable) ✅ Already exists
```
