# Subscription Machine Flow Documentation

This document describes the subscription-based machine management system for the Aurae Backend application.

## Table of Contents
- [Overview](#overview)
- [Terminology](#terminology)
- [System Architecture](#system-architecture)
- [Database Structure](#database-structure)
- [API Endpoints](#api-endpoints)
- [Mobile App Integration](#mobile-app-integration)
- [Admin Panel Features](#admin-panel-features)
- [Machine Serial Number Configuration](#machine-serial-number-configuration)
- [Error Handling](#error-handling)
- [Testing](#testing)

## Overview

The subscription machine flow allows users to:
1. Subscribe to plans via mobile app using SenangPay payment gateway
2. Each subscription allows binding **exactly 1 machine** (max_machines is always 1)
3. Users can have **multiple active subscriptions** to bind multiple machines
4. Bind physical machines to their account using QR code (from tablet) and serial number validation
5. Access tablet features only after successful machine binding
6. Each active subscription = 1 machine slot (e.g., 3 active subscriptions = 3 machines)

## Terminology

To avoid confusion, the system uses distinct terms for different components:

| Term | Description | Model |
|------|-------------|-------|
| **UserDevice** | Mobile phone device (tracked via EnsureDevice middleware with UDID, OS info, etc.) | `UserDevice` |
| **Device** | Tablet device that generates QR codes for machine binding | `Device` |
| **Machine** | Physical hardware unit with serial number that users bind via subscription | `Machine` |

## System Architecture

### Flow Diagram

```
1. User subscribes to plan → SenangPay payment
2. Payment verified → Subscription activated
3. Tablet generates QR code (Device)
4. Mobile app scans QR code (UserDevice)
5. User enters machine serial number
6. Backend validates:
   - Serial number format
   - Active subscription
   - Machine limit
   - Machine availability
7. Machine bound to user and tablet
8. Tablet features unlocked
```

### Key Components

- **Subscription Plans**: Each plan allows exactly 1 machine (max_machines is locked to 1)
- **User Subscriptions**: Users can have multiple active subscriptions (one per machine)
- **Machine Serial Service**: Validates and generates serial numbers
- **Payment Integration**: SenangPay for subscription payments
- **Device Binding**: Links machines to users and tablets

## Database Structure

### Tables

#### `machines`
```sql
- id (ULID)
- serial_number (string, unique)
- name (string)
- user_id (foreignId, nullable) - User who owns this machine
- device_id (foreignId, nullable) - Tablet linked to this machine
- status (tinyInt) - 1: active, 0: inactive
- last_used_at (timestamp, nullable)
- last_logged_in_at (timestamp, nullable)
- created_at, updated_at
```

#### `subscriptions`
```sql
- id
- icon (string, nullable)
- title (string) - e.g., "Basic Plan", "Premium Plan"
- pricing_title (string) - e.g., "RM 59.90 / month"
- description (text, nullable)
- max_machines (integer, default: 1) - Always set to 1, not user-editable
- price (decimal)
- is_active (boolean)
- created_at, updated_at
```

**Note:** The `max_machines` field is automatically set to 1 and cannot be changed by admins. Each subscription plan allows exactly one machine binding.

#### `user_subscriptions`
```sql
- id
- user_id (foreignId)
- subscription_id (foreignId)
- starts_at (timestamp)
- ends_at (timestamp, nullable)
- status (enum: active, expired, cancelled)
- transaction_id (string, nullable) - SenangPay reference
- payment_method (string, nullable)
- payment_status (enum: pending, completed, failed)
- paid_at (timestamp, nullable)
- created_at, updated_at
```

#### `general_settings`
```sql
... existing fields ...
- machine_serial_format (string, default: 'AUR-{NNNN}')
- machine_serial_prefix (string, nullable)
- machine_serial_length (integer, default: 8)
```

## API Endpoints

### Subscription Payment Endpoints

#### `POST /api/subscription/subscribe`
Initiate subscription payment.

**Request:**
```json
{
  "subscription_id": 1,
  "payment_method": "senangpay"
}
```

**Response:**
```json
{
  "status": 200,
  "message": "Subscription payment initiated successfully.",
  "data": {
    "payment_url": "https://app.senangpay.my/payment/...",
    "reference_number": "SUB-2026-00001",
    "subscription": {
      "id": 1,
      "title": "Basic Plan",
      "max_machines": 1,
      "price": "59.90"
    },
    "amount": "RM 59.90",
    "currency": "MYR"
  }
}
```

**Errors:**
- `400`: Subscription plan not available

**Note:** Users can subscribe to multiple plans simultaneously (one per machine).

#### `GET /api/subscription/payment-status/{reference}`
Check subscription payment status.

**Response:**
```json
{
  "status": 200,
  "message": "Subscription payment status retrieved.",
  "data": {
    "reference_number": "SUB-2026-00001",
    "payment_status": "success",
    "subscription": {...},
    "user_subscription_id": 1,
    "status": "active",
    "starts_at": "2026-02-09T03:00:00Z",
    "ends_at": "2026-03-09T03:00:00Z"
  }
}
```

### Machine Management Endpoints

#### `POST /api/machine/bind`
Bind machine to user.

**Request:**
```json
{
  "device_id": "01HQWE...",
  "device_uuid": "abc-123-def-456",
  "serial_number": "AUR-0001"
}
```

**Validation Flow:**
1. Verify tablet (Device) exists with matching id and uuid
2. Validate serial_number format against configured pattern
3. Check user has at least one active subscription
4. Check machine limit (current bound machines vs total active subscriptions)
5. Find Machine by serial_number
6. Check machine not already bound to another user
7. Check machine status is active

**Machine Limit Logic:**
- Total allowed machines = count of active subscriptions (each subscription = 1 machine)
- Example: User with 3 active subscriptions can bind 3 machines

**Response:**
```json
{
  "status": 200,
  "message": "Machine bound successfully.",
  "data": {
    "machine": {
      "id": "01HQWE...",
      "serial_number_masked": "XXXXX0001",
      "name": "Machine #1",
      "status": 1,
      "user": {...},
      "device": {...}
    },
    "subscription": {...},
    "machines_count": 1,
    "max_machines": 3
  }
}
```

**Errors:**
- `404`: Invalid tablet device
- `422`: Invalid serial number format
- `403`: No active subscription
- `403`: Machine limit reached
- `404`: Machine not found
- `403`: Machine already bound to another user
- `403`: Machine is inactive

#### `GET /api/machines`
List user's bound machines.

**Response:**
```json
{
  "status": 200,
  "message": "Machines retrieved successfully.",
  "data": [
    {
      "id": "01HQWE...",
      "serial_number_masked": "XXXXX0001",
      "name": "Machine #1",
      "status": 1,
      "is_bound": true,
      "last_used_at": "2026-02-09T03:00:00Z",
      "device": {...}
    }
  ]
}
```

#### `POST /api/machine/{machine}/unbind`
Unbind machine from user.

**Response:**
```json
{
  "status": 200,
  "message": "Machine unbound successfully.",
  "data": {}
}
```

**Errors:**
- `403`: You do not own this machine

### Existing Subscription Endpoints

#### `GET /api/subscriptions`
List available subscription plans.

**Response:**
```json
{
  "status": 200,
  "message": "Subscriptions retrieved successfully.",
  "data": [
    {
      "id": 1,
      "title": "Basic Plan",
      "pricing_title": "RM 59.90 / month",
      "description": "Perfect for single machine",
      "max_machines": 1,
      "price": "59.90",
      "is_active": true
    }
  ]
}
```

#### `GET /api/user/subscription`
Get user's active subscriptions (returns array of all active subscriptions).

**Response:**
```json
{
  "status": 200,
  "message": "User subscriptions retrieved successfully.",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "subscription": {
        "id": 1,
        "title": "Basic Plan",
        "price": "59.90"
      },
      "starts_at": "2026-02-09T00:00:00Z",
      "ends_at": "2026-03-09T00:00:00Z",
      "status": "active",
      "payment_status": "completed",
      "machines_bound": 1,
      "machines_available": 0,
      "days_remaining": 28
    },
    {
      "id": 2,
      "user_id": 1,
      "subscription": {
        "id": 1,
        "title": "Basic Plan",
        "price": "59.90"
      },
      "starts_at": "2026-02-10T00:00:00Z",
      "ends_at": "2026-03-10T00:00:00Z",
      "status": "active",
      "payment_status": "completed",
      "machines_bound": 1,
      "machines_available": 0,
      "days_remaining": 29
    }
  ]
}
```

**Note:** Users with 3 machines will have 3 separate subscription entries in this response.

## Mobile App Integration

### Subscription Purchase Flow

1. **View Plans**
   ```kotlin
   val plans = apiService.getSubscriptions()
   ```

2. **Initiate Payment**
   ```kotlin
   val response = apiService.subscribeToplan(
       subscriptionId = selectedPlan.id,
       paymentMethod = "senangpay"
   )
   ```

3. **Open Payment in WebView**
   ```kotlin
   webView.loadUrl(response.data.payment_url)
   ```

4. **Handle Payment Completion**
   - SenangPay redirects to backend callback
   - Backend processes payment and activates subscription
   - Mobile app polls `/api/subscription/payment-status/{reference}` or redirects to success page

### Machine Binding Flow

#### Step 1: Tablet Generates QR Code

**Tablet calls:**
```kotlin
POST /api/device-retrieve
{
  "uuid": "tablet-uuid-123",
  "name": "Aurae Tablet #1"
}
```

**Response:**
```json
{
  "status": 200,
  "data": {
    "qr": "data:image/png;base64,...",
    "device_id": "01HQWE..."
  }
}
```

Tablet displays QR code on screen.

#### Step 2: Mobile App Scans and Binds

1. **Scan QR Code**
   ```kotlin
   val qrData = scanQRCode() // Contains URL with device_id and uuid
   // e.g., "https://api.example.com/api/device-login?id=01HQWE...&uuid=abc-123"
   ```

2. **Extract Device Info**
   ```kotlin
   val deviceId = parseDeviceId(qrData)
   val deviceUuid = parseDeviceUuid(qrData)
   ```

3. **Prompt for Serial Number**
   ```kotlin
   val serialNumber = showSerialNumberInput()
   // User enters: "AUR-0001"
   ```

4. **Bind Machine**
   ```kotlin
   val response = apiService.bindMachine(
       deviceId = deviceId,
       deviceUuid = deviceUuid,
       serialNumber = serialNumber
   )
   
   if (response.status == 200) {
       showSuccessMessage("Machine bound successfully!")
   } else {
       showError(response.message)
   }
   ```

### Error Handling Example

```kotlin
fun handleBindingError(error: ApiError) {
    when (error.status) {
        422 -> {
            // Invalid serial format
            showError(error.message) // "Expected format: AUR-0001"
        }
        403 -> {
            when {
                error.message.contains("subscription") -> {
                    // No subscription
                    navigateToSubscriptionScreen()
                }
                error.message.contains("limit reached") -> {
                    // Machine limit reached
                    showUpgradeDialog()
                }
                else -> showError(error.message)
            }
        }
        404 -> {
            // Machine not found
            showError("Please check the serial number")
        }
    }
}
```

## Admin Panel Features

### Machine Management

#### List Machines (`GET /admin/machines`)
- View all machines with filters
- Search by serial number or name
- Filter by status (active/inactive)
- Filter by binding status (bound/unbound)

#### Create Machine (`POST /admin/machines`)
**Single Machine:**
```
- name: "Aurae Machine"
- serial_number: (optional, auto-generated if empty)
- status: 1 (active)
```

**Bulk Generation:**
```
- name: "Aurae Machine"
- quantity: 100
- status: 1
```
Creates 100 machines with auto-generated serial numbers.

#### Machine Actions
- **Unbind**: Remove user and tablet associations
- **Activate**: Set status to active
- **Deactivate**: Set status to inactive
- **Edit**: Update name, serial number, status
- **Delete**: Only if not bound to any user

### User Subscription Management

#### List Subscriptions (`GET /admin/user-subscriptions`)
- View all user subscriptions
- Search by user name/email
- Filter by status (active/expired/cancelled)
- Filter by payment status

#### Subscription Actions
- **View Details**: See subscription info, payment details, bound machines
- **Cancel**: Immediately cancel subscription
- **Extend**: Add months to subscription
  ```
  months: 3 // Adds 3 months to current end date
  ```

### General Settings - Machine Serial Format

Navigate to `/admin/general-settings` to configure:

**Machine Serial Format:**
- **Format Pattern**: `AUR-{NNNN}`, `{PREFIX}-{YYYY}-{NNNNN}`, `{NNNNN}`
- **Prefix**: Custom prefix (e.g., "AUR")
- **Length**: Total serial number length

**Format Examples:**
- `AUR-{NNNN}` → AUR-0001, AUR-0002, AUR-9999
- `{PREFIX}-{YYYY}-{NNNN}` → AUR-2026-0001
- `{NNNNN}` → 00001, 00002

**Placeholders:**
- `{PREFIX}` - Configurable prefix
- `{YYYY}` - Current year
- `{MM}` - Current month
- `{NNNN}` - Running number with leading zeros (4 digits)
- `{NNNNN}` - Running number with leading zeros (5 digits)

**Preview**: Shows next serial number to be generated

## Machine Serial Number Configuration

### Service: `MachineSerialService`

#### Methods

**`validateFormat(string $serialNumber): bool`**
- Validates serial number against configured pattern
- Returns `true` if matches, `false` otherwise

**`generateNextSerialNumber(): string`**
- Generates next serial number based on format
- Automatically increments running number
- Example: "AUR-0001" → "AUR-0002"

**`getFormatExample(): string`**
- Returns example serial number for user feedback
- Used in error messages

**`bulkGenerate(int $quantity, string $baseName): array`**
- Creates multiple machines with auto-generated serials
- Example: Creates 100 machines with serials AUR-0001 to AUR-0100

**`parseFormat(string $format): array`**
- Parses format pattern into components
- Returns validation regex and metadata

### Usage Example

```php
$serialService = app(MachineSerialService::class);

// Validate format
$isValid = $serialService->validateFormat('AUR-0001'); // true

// Generate next serial
$nextSerial = $serialService->generateNextSerialNumber(); // "AUR-0042"

// Get example
$example = $serialService->getFormatExample(); // "AUR-0001"

// Bulk generate
$machines = $serialService->bulkGenerate(50, 'Aurae Device');
// Creates 50 machines with sequential serial numbers
```

## Error Handling

### Subscription Errors

| Status | Message | Action |
|--------|---------|--------|
| 400 | Subscription plan not available | Select different plan |
| 500 | Payment initiation error | Contact support |

**Note:** Users can have multiple active subscriptions (one per machine).

### Machine Binding Errors

| Status | Error | Message | User Action |
|--------|-------|---------|-------------|
| 404 | Invalid tablet | Invalid tablet device | Generate new QR code from tablet |
| 422 | Invalid format | Expected format: AUR-0001 | Check serial number format |
| 403 | No subscription | Please subscribe to a plan | Navigate to subscription screen |
| 403 | Limit reached | Machine limit reached. Your plan allows up to X machine(s) | Subscribe to additional plan |
| 404 | Not found | Machine not found with serial number 'XYZ' | Verify serial number on machine |
| 403 | Already bound | Machine already bound to another user | Contact support |
| 403 | Inactive | Machine is inactive | Contact support |

## Testing

### Manual Testing Flow

1. **Create Subscription Plans** (Admin)
   - Navigate to `/admin/subscriptions`
   - Create plans (max_machines is auto-set to 1)

2. **Generate Machines** (Admin)
   - Navigate to `/admin/machines/create`
   - Bulk generate 10 machines

3. **Configure Serial Format** (Admin)
   - Navigate to `/admin/general-settings`
   - Set format: `AUR-{NNNN}`

4. **Subscribe** (Mobile)
   - Call `POST /api/subscription/subscribe`
   - Complete SenangPay payment
   - Verify subscription active

5. **Bind Machine** (Mobile + Tablet)
   - Tablet: Call `POST /api/device-retrieve`
   - Mobile: Scan QR code
   - Mobile: Enter serial number
   - Mobile: Call `POST /api/machine/bind`
   - Verify machine bound

6. **Test Limits** (Mobile)
   - Try binding more machines than subscription allows
   - Verify error: "Machine limit reached"

7. **Unbind** (Mobile/Admin)
   - Call `POST /api/machine/{id}/unbind`
   - Verify machine unbound

### Unit Tests

```php
// Test serial validation
test('validates serial number format', function () {
    $service = app(MachineSerialService::class);
    
    expect($service->validateFormat('AUR-0001'))->toBeTrue();
    expect($service->validateFormat('INVALID'))->toBeFalse();
});

// Test machine limit (1 subscription = 1 machine)
test('enforces machine limit', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(); // max_machines = 1 automatically
    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => 'active'
    ]);
    Machine::factory()->create(['user_id' => $user->id]);
    
    $response = $this->actingAs($user)
        ->post('/api/machine/bind', [
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
            'serial_number' => 'AUR-0002'
        ]);
    
    $response->assertStatus(403);
    $response->assertJson(['message' => 'Machine limit reached']);
});
```

## Support

For issues or questions:
1. Check error message for specific guidance
2. Verify subscription is active
3. Confirm serial number format matches configured pattern
4. Contact system administrator

## Changelog

- **2026-02-09**: Initial implementation
  - Subscription payment integration
  - Machine binding with serial validation
  - Configurable serial number formats
  - Admin management features
