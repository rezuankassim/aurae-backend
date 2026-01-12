# Forgot Password API Documentation

This document describes the forgot password functionality for the Aurae Backend API.

## Overview

The forgot password feature allows users to reset their password using a phone number and OTP (One-Time Password) sent via Firebase push notification.

## Endpoints

### 1. Request Password Reset OTP

**Endpoint:** `POST /api/forgot-password`

**Description:** Sends an OTP to the user's registered device via Firebase Cloud Messaging.

**Request Headers:**
```
Content-Type: application/json
X-Device-UDID: {device_udid}
```

**Request Body:**
```json
{
  "phone": "1234567890"
}
```

**Success Response (200):**
```json
{
  "data": null,
  "status": 200,
  "message": "OTP sent to your device successfully."
}
```

**Error Response (422):**
```json
{
  "message": "No user found with this phone number.",
  "errors": {
    "phone": [
      "No user found with this phone number."
    ]
  }
}
```

**Notes:**
- The user must have at least one device with a valid FCM token to receive the notification
- The OTP is a 6-digit code valid until a new one is generated or the password is reset
- The OTP is sent as a push notification with type `password_reset`

---

### 2. Reset Password with OTP

**Endpoint:** `POST /api/reset-password`

**Description:** Verifies the OTP and resets the user's password.

**Request Headers:**
```
Content-Type: application/json
X-Device-UDID: {device_udid}
```

**Request Body:**
```json
{
  "phone": "1234567890",
  "code": "123456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Success Response (200):**
```json
{
  "data": null,
  "status": 200,
  "message": "Password reset successfully."
}
```

**Error Responses:**

**Invalid OTP (422):**
```json
{
  "message": "The provided verification code is incorrect.",
  "errors": {
    "code": [
      "The provided verification code is incorrect."
    ]
  }
}
```

**User Not Found (422):**
```json
{
  "message": "No user found with this phone number.",
  "errors": {
    "phone": [
      "No user found with this phone number."
    ]
  }
}
```

**Validation Error (422):**
```json
{
  "message": "The password field confirmation does not match.",
  "errors": {
    "password": [
      "The password field confirmation does not match."
    ]
  }
}
```

**Notes:**
- The password must be at least 8 characters long
- The `password_confirmation` field must match the `password` field
- After successful password reset, all existing user tokens are revoked for security
- The OTP is deleted after successful verification

---

## Flow Diagram

```
┌──────────┐
│  Mobile  │
│   App    │
└─────┬────┘
      │
      │ 1. POST /api/forgot-password
      │    { phone: "1234567890" }
      ▼
┌─────────────┐
│   Backend   │
└─────┬───────┘
      │
      │ 2. Generate OTP
      │    Store in DB
      ▼
┌─────────────┐
│  Firebase   │
│     FCM     │
└─────┬───────┘
      │
      │ 3. Push notification
      │    with OTP
      ▼
┌──────────┐
│  Mobile  │
│   App    │
└─────┬────┘
      │
      │ 4. User enters OTP
      │    and new password
      │
      │ 5. POST /api/reset-password
      │    { phone, code, password, password_confirmation }
      ▼
┌─────────────┐
│   Backend   │
└─────┬───────┘
      │
      │ 6. Verify OTP
      │    Update password
      │    Revoke tokens
      │    Delete OTP
      ▼
┌──────────┐
│  Success │
└──────────┘
```

## Security Considerations

1. **Token Revocation**: All existing authentication tokens are revoked when a password is successfully reset to prevent unauthorized access.

2. **OTP Storage**: OTPs are stored in the database with the phone number as a unique key. Each new OTP request overwrites the previous one.

3. **Device Authentication**: All API requests require the `EnsureDevice` middleware, which validates the device UDID.

4. **Firebase Push Notification**: The OTP is sent via Firebase Cloud Messaging to all devices registered to the user. The notification includes:
   - Title: "Password Reset OTP"
   - Body: "Your OTP code is: {code}"
   - Data: `{ type: "password_reset", code: "{code}" }`

## Testing

To test these endpoints, you need:

1. A valid device UDID registered in the system
2. A user account with a phone number
3. At least one device with a valid FCM token associated with the user
4. Firebase credentials configured in `.env`

### Example Test Flow

```bash
# 1. Request OTP
curl -X POST http://localhost:8000/api/forgot-password \
  -H "Content-Type: application/json" \
  -H "X-Device-UDID: test-device-123" \
  -d '{"phone": "1234567890"}'

# 2. Check push notification on device and get OTP

# 3. Reset password
curl -X POST http://localhost:8000/api/reset-password \
  -H "Content-Type: application/json" \
  -H "X-Device-UDID: test-device-123" \
  -d '{
    "phone": "1234567890",
    "code": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }'
```

## Troubleshooting

### OTP Not Received

If users don't receive the OTP notification:
1. Check that Firebase credentials are configured correctly in `.env`
2. Verify the user has a device with a valid FCM token
3. Check the `notifications` table for failed notification records
4. Review Laravel logs for Firebase errors

### Invalid Credentials Error

If you get Firebase credential errors:
1. Ensure `FIREBASE_CREDENTIALS` is set in `.env`
2. Verify the Firebase service account JSON file exists at the specified path
3. Check that the Firebase project has Cloud Messaging enabled

## Related Files

- Controller: `app/Http/Controllers/Api/AuthenticationController.php`
- Routes: `routes/api.php`
- Service: `app/Services/FirebaseService.php`
- Model: `app/Models/Verification.php`
- Config: `config/firebase.php`
