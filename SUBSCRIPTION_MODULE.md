# Subscription Module

## Overview
The subscription module allows admins to create and manage subscription plans that control device usage limits for users.

## Features
- **Admin Management**: Create, edit, and delete subscription plans
- **Device Limits**: Control how many devices each user can register based on their subscription
- **Flexible Pricing**: Set custom pricing titles (e.g., "RM 59.90 / month")
- **Plan Activation**: Enable/disable subscription plans
- **Icons**: Upload custom icons for each subscription plan

## Database Schema

### `subscriptions` Table
- `id`: Primary key
- `icon`: Icon image path (nullable)
- `title`: Plan title (e.g., "Basic Plan", "Premium Plan")
- `pricing_title`: Pricing display (e.g., "RM 59.90 / month")
- `description`: Plan description (nullable)
- `max_devices`: Maximum number of devices allowed
- `price`: Monthly price (decimal)
- `is_active`: Plan active status (boolean)
- `created_at`, `updated_at`: Timestamps

### `user_subscriptions` Table
- `id`: Primary key
- `user_id`: Foreign key to users table
- `subscription_id`: Foreign key to subscriptions table
- `starts_at`: Subscription start date
- `ends_at`: Subscription end date (nullable for active subscriptions)
- `status`: Enum ('active', 'expired', 'cancelled')
- `created_at`, `updated_at`: Timestamps

## Admin Panel

### Accessing Subscription Management
Navigate to **Admin Panel → Subscriptions** in the sidebar.

### Creating a Subscription Plan
1. Click "Create subscription"
2. Fill in the form:
   - **Icon**: Upload an icon image (optional)
   - **Title**: Plan name (e.g., "Basic Plan")
   - **Pricing Title**: Display pricing (e.g., "RM 59.90 / month")
   - **Description**: Plan description (optional)
   - **Maximum Devices**: Number of devices allowed (minimum 1)
   - **Price**: Monthly price in RM
   - **Is Active**: Enable/disable the plan
3. Click "Submit"

### Editing a Subscription Plan
1. Go to Subscriptions list
2. Click the menu (three dots) next to a plan
3. Select "Edit"
4. Update the fields as needed
5. Click "Update"

### Deleting a Subscription Plan
1. Go to Subscriptions list
2. Click the menu (three dots) next to a plan
3. Select "Delete"
4. Confirm deletion

## User Subscription Management

### Assigning a Subscription to a User
To assign a subscription to a user, you need to create a `UserSubscription` record:

```php
use App\Models\UserSubscription;

UserSubscription::create([
    'user_id' => $user->id,
    'subscription_id' => $subscription->id,
    'starts_at' => now(),
    'ends_at' => now()->addMonth(), // or null for unlimited
    'status' => 'active',
]);
```

### Checking User's Device Limit
```php
$user = User::find($userId);
$maxDevices = $user->getMaxDevices(); // Returns max_devices from active subscription, default 1
```

### Checking Active Subscription
```php
$user = User::find($userId);
$activeSubscription = $user->activeSubscription; // Returns active UserSubscription or null
```

## Device Limit Enforcement

When a user attempts to bind a device, the system automatically checks:
1. If the user has an active subscription
2. How many devices the user currently has registered
3. If adding the device would exceed the subscription limit

If the limit is reached, the API returns:
```json
{
    "status": 403,
    "message": "Device limit reached. Your subscription allows up to X device(s). Please upgrade your subscription to add more devices."
}
```

## Payment Integration Recommendations

Based on research, here are payment gateway options for handling subscriptions in Malaysia:

### 1. **RevPay Debit** (Recommended for Direct Debit)
- **Type**: Direct debit/recurring payment
- **How it works**: 
  - Send auto-debit request to customers
  - Customer approves once
  - Automatic collection based on your schedule
- **Setup**: Contact RevPay at revpay.com.my
- **Best for**: Automated monthly billing

### 2. **Razorpay Curlec** (Recommended for Full Subscription Management)
- **Type**: Complete subscription billing platform
- **Features**:
  - Subscription APIs and dashboard management
  - Multiple payment methods (cards, direct debit)
  - Plan upgrades/downgrades with proration
  - Automated retries and notifications
- **Setup**: Sign up at curlec.com
- **Best for**: Full-featured subscription system

### 3. **Manual Integration**
For now, you can:
1. Handle subscription logic in Laravel (already implemented)
2. Use RevPay/other gateway for one-time payment collection
3. Manually create `UserSubscription` records after payment confirmation
4. Use Laravel's task scheduler to handle subscription renewals/expirations

## Implementation Flow

### For Manual Payment Flow:
1. User selects a subscription plan
2. Redirect to payment gateway (RevPay API/Express)
3. Handle payment callback
4. On success, create `UserSubscription` record
5. Update subscription status based on renewal dates

### For Automated Recurring Payment:
1. User selects subscription and authorizes recurring payment
2. Set up mandate with RevPay Debit or Curlec
3. Create `UserSubscription` record
4. Let payment gateway handle recurring charges
5. Use webhooks to update subscription status

## API Endpoints

### Get Available Subscriptions
**Endpoint:** `GET /api/subscriptions`  
**Authentication:** Required (Sanctum + Device Header)  
**Headers:**
- `Authorization: Bearer {token}`
- `X-Device-ID: {device_uuid}`

**Description:** Returns all active subscription plans available for purchase

**Success Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Basic Plan",
            "pricing_title": "RM 49.90 / month",
            "description": "Perfect for individuals just starting out. Includes 1 device.",
            "max_devices": 1,
            "price": "49.90",
            "icon_url": null,
            "is_active": true,
            "created_at": "2026-01-12T03:45:00.000000Z",
            "updated_at": "2026-01-12T03:45:00.000000Z"
        },
        {
            "id": 2,
            "title": "Family Plan",
            "pricing_title": "RM 99.90 / month",
            "description": "Great for small families. Connect up to 3 devices and share the wellness experience.",
            "max_devices": 3,
            "price": "99.90",
            "icon_url": null,
            "is_active": true,
            "created_at": "2026-01-12T03:45:00.000000Z",
            "updated_at": "2026-01-12T03:45:00.000000Z"
        },
        {
            "id": 3,
            "title": "Premium Plan",
            "pricing_title": "RM 149.90 / month",
            "description": "For power users and large families. Enjoy unlimited access with up to 5 devices.",
            "max_devices": 5,
            "price": "149.90",
            "icon_url": null,
            "is_active": true,
            "created_at": "2026-01-12T03:45:00.000000Z",
            "updated_at": "2026-01-12T03:45:00.000000Z"
        },
        {
            "id": 4,
            "title": "Enterprise Plan",
            "pricing_title": "RM 299.90 / month",
            "description": "Perfect for wellness centers and businesses. Support up to 10 devices with priority support.",
            "max_devices": 10,
            "price": "299.90",
            "icon_url": null,
            "is_active": true,
            "created_at": "2026-01-12T03:45:00.000000Z",
            "updated_at": "2026-01-12T03:45:00.000000Z"
        }
    ],
    "status": 200,
    "message": "Subscriptions retrieved successfully."
}
```

### Get User's Active Subscription
**Endpoint:** `GET /api/user/subscription`  
**Authentication:** Required (Sanctum + Device Header)  
**Headers:**
- `Authorization: Bearer {token}`
- `X-Device-ID: {device_uuid}`

**Description:** Returns the authenticated user's current active subscription with full plan details

**Success Response - With Active Subscription (200 OK):**
```json
{
    "data": {
        "id": 5,
        "subscription": {
            "id": 3,
            "title": "Premium Plan",
            "pricing_title": "RM 149.90 / month",
            "description": "For power users and large families. Enjoy unlimited access with up to 5 devices.",
            "max_devices": 5,
            "price": "149.90",
            "icon_url": null,
            "is_active": true,
            "created_at": "2026-01-12T03:45:00.000000Z",
            "updated_at": "2026-01-12T03:45:00.000000Z"
        },
        "starts_at": "2026-01-01T00:00:00.000000Z",
        "ends_at": "2026-02-01T00:00:00.000000Z",
        "status": "active",
        "is_active": true,
        "created_at": "2026-01-01T00:00:00.000000Z",
        "updated_at": "2026-01-01T00:00:00.000000Z"
    },
    "status": 200,
    "message": "User subscription retrieved successfully."
}
```

**Success Response - No Active Subscription (200 OK):**
```json
{
    "data": null,
    "status": 200,
    "message": "No active subscription found."
}
```

**Notes:**
- `is_active` in the response indicates if the subscription is currently valid (considers both status and end date)
- If `ends_at` is `null`, the subscription has no expiration date
- Users without an active subscription default to 1 device limit
```

### Subscribe to Plan (TODO - after payment integration)
**Endpoint:** `POST /api/user/subscribe`  
**Authentication:** Required (Sanctum)  
**Description:** Subscribe user to a plan after successful payment

**Request Body:**
```json
{
    "subscription_id": 1,
    "payment_reference": "PAY123456"
}
```

## Future Enhancements
- [ ] Subscription payment integration (RevPay/Curlec)
- [ ] Automatic subscription renewal
- [ ] Email notifications for expiring subscriptions
- [ ] Subscription upgrade/downgrade flow
- [ ] Payment history tracking
- [ ] Proration for mid-cycle changes
- [ ] Trial periods
- [ ] Discount codes/coupons
- [ ] Multiple billing cycles (monthly, yearly)
- [ ] Subscription analytics dashboard

## Notes
- Default device limit for users without subscription is 1
- Subscriptions are checked only when binding new devices
- Existing devices remain functional even if subscription expires (consider adding enforcement)
- Admin can manually manage user subscriptions via database/seeder for now
