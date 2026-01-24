# Password Reset Flow - Two-Step Process

## Overview
The password reset flow has been separated into two distinct steps for better security and user experience:
1. **Verify OTP** - User enters the OTP code to verify their identity
2. **Reset Password** - User enters and confirms their new password

## Flow Diagram
```
User forgets password
    ↓
1. POST /api/forgot-password (send phone number)
    ↓
    OTP sent to device via Firebase notification
    ↓
2. POST /api/verify-reset-otp (send phone + OTP code)
    ↓
    OTP verified, user can proceed to reset
    ↓
3. POST /api/reset-password (send phone + new password)
    ↓
    Password updated, all tokens revoked
    ↓
User must login with new password
```

## API Endpoints

### Step 0: Request OTP
**Endpoint:** `POST /api/forgot-password`  
**Authentication:** None (Device header required)  
**Headers:**
- `X-Device-ID: {device_uuid}`

**Request Body:**
```json
{
    "phone": "+60123456789"
}
```

**Success Response (200 OK):**
```json
{
    "data": null,
    "status": 200,
    "message": "OTP sent to your device successfully."
}
```

**Error Response (422 Validation Error):**
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
- OTP is sent via Firebase push notification to the user's registered device
- OTP is 6-digit numeric code (e.g., 123456)
- OTP is stored in database for verification

---

### Step 1: Verify OTP
**Endpoint:** `POST /api/verify-reset-otp`  
**Authentication:** None (Device header required)  
**Headers:**
- `X-Device-ID: {device_uuid}`

**Request Body:**
```json
{
    "phone": "+60123456789",
    "code": "123456"
}
```

**Success Response (200 OK):**
```json
{
    "data": {
        "phone": "+60123456789"
    },
    "status": 200,
    "message": "OTP verified successfully. You can now reset your password."
}
```

**Error Responses:**

**Invalid OTP (422 Validation Error):**
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

**User Not Found (422 Validation Error):**
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
- After successful verification, the OTP record is marked with `verified_at` timestamp
- The verification remains valid for **5 minutes** before expiring
- User receives the phone number back to use in the next step

---

### Step 2: Reset Password
**Endpoint:** `POST /api/reset-password`  
**Authentication:** None (Device header required)  
**Headers:**
- `X-Device-ID: {device_uuid}`

**Request Body:**
```json
{
    "phone": "+60123456789",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
}
```

**Validation Rules:**
- `phone`: Required, string, max 20 characters
- `password`: Required, string, minimum 8 characters, must match confirmation
- `password_confirmation`: Required, must match password

**Success Response (200 OK):**
```json
{
    "data": null,
    "status": 200,
    "message": "Password reset successfully. Please login with your new password."
}
```

**Error Responses:**

**OTP Not Verified or Expired (422 Validation Error):**
```json
{
    "message": "OTP verification expired or not completed. Please request a new OTP.",
    "errors": {
        "phone": [
            "OTP verification expired or not completed. Please request a new OTP."
        ]
    }
}
```

**Password Validation Failed (422 Validation Error):**
```json
{
    "message": "The password field must be at least 8 characters.",
    "errors": {
        "password": [
            "The password field must be at least 8 characters."
        ]
    }
}
```

**Password Mismatch (422 Validation Error):**
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
- Verification must be completed within the last 5 minutes
- All existing user tokens are revoked for security
- User must login again with new password
- Verification record is deleted after successful password reset

---

## Security Features

### 1. Time-Limited Verification
- OTP verification is valid for only **5 minutes** after verification
- After 5 minutes, user must request a new OTP

### 2. Token Revocation
- All existing authentication tokens are revoked after password reset
- Forces user to login again on all devices

### 3. Verification Cleanup
- Verification record is deleted after successful password reset
- Prevents reuse of the same OTP

### 4. Two-Step Process
- Separates identity verification from password entry
- Allows UI to show proper feedback at each step
- Better user experience with clear progress

---

## Database Schema

### `verifications` Table
```sql
CREATE TABLE verifications (
    id BIGINT PRIMARY KEY,
    phone VARCHAR(255) UNIQUE,
    code VARCHAR(255),
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Columns:**
- `phone`: User's phone number (unique)
- `code`: 6-digit OTP code
- `verified_at`: Timestamp when OTP was verified (NULL if not verified)
- `created_at`: When OTP was generated
- `updated_at`: Last update time

---

## Mobile App Implementation Guide

### Step-by-Step Flow

#### 1. Forgot Password Screen
```dart
// User enters phone number
onForgotPassword() async {
  final response = await api.post('/api/forgot-password', {
    'phone': phoneController.text,
  });
  
  if (response.status == 200) {
    // Navigate to OTP verification screen
    navigateToOtpScreen(phoneController.text);
    showMessage('OTP sent to your device');
  }
}
```

#### 2. OTP Verification Screen
```dart
// User enters 6-digit OTP
onVerifyOtp() async {
  final response = await api.post('/api/verify-reset-otp', {
    'phone': phoneNumber,
    'code': otpController.text,
  });
  
  if (response.status == 200) {
    // Navigate to reset password screen
    navigateToResetPasswordScreen(phoneNumber);
    showMessage('OTP verified successfully');
  } else if (response.status == 422) {
    // Show error
    showError(response.errors.code[0]);
  }
}
```

#### 3. Reset Password Screen
```dart
// User enters new password
onResetPassword() async {
  final response = await api.post('/api/reset-password', {
    'phone': phoneNumber,
    'password': passwordController.text,
    'password_confirmation': confirmPasswordController.text,
  });
  
  if (response.status == 200) {
    // Navigate to login screen
    navigateToLoginScreen();
    showMessage('Password reset successfully. Please login.');
  } else if (response.status == 422) {
    // OTP expired or other error
    if (response.errors.phone != null) {
      // OTP expired - go back to forgot password
      navigateToForgotPasswordScreen();
      showError(response.errors.phone[0]);
    } else {
      // Password validation error
      showError(response.errors.password[0]);
    }
  }
}
```

---

## Testing the Flow

### Using cURL

**1. Request OTP:**
```bash
curl -X POST http://localhost:8000/api/forgot-password \
  -H "X-Device-ID: test-device-uuid" \
  -H "Content-Type: application/json" \
  -d '{"phone": "+60123456789"}'
```

**2. Verify OTP:**
```bash
curl -X POST http://localhost:8000/api/verify-reset-otp \
  -H "X-Device-ID: test-device-uuid" \
  -H "Content-Type: application/json" \
  -d '{"phone": "+60123456789", "code": "123456"}'
```

**3. Reset Password:**
```bash
curl -X POST http://localhost:8000/api/reset-password \
  -H "X-Device-ID: test-device-uuid" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+60123456789",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
  }'
```

---

## Common Error Scenarios

### 1. User enters wrong OTP 3 times
- Frontend should track failed attempts
- After 3 failed attempts, suggest requesting new OTP
- Clear the OTP input field

### 2. User takes too long to reset password
- If more than 5 minutes pass between OTP verification and password reset
- API returns error about expired verification
- User must start over from Step 0 (request new OTP)

### 3. Network issues during reset
- If password reset fails, user should retry
- Verification is still valid within 5-minute window
- No need to verify OTP again

### 4. User closes app during flow
- When user returns, check if they have a verified OTP
- If verification is still valid (< 5 minutes), allow password reset
- Otherwise, start from beginning

---

## Migration Required

To implement this flow, you need to add the `verified_at` column to the `verifications` table:

```sql
ALTER TABLE verifications ADD COLUMN verified_at TIMESTAMP NULL;
```

Or run the updated migration file.

---

## Differences from Previous Flow

### Before (Single Step):
```
POST /api/reset-password
{
  "phone": "+60123456789",
  "code": "123456",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```
- Everything in one request
- Less control over UI flow
- Harder to provide specific feedback

### After (Two Steps):
```
1. POST /api/verify-reset-otp
   { "phone": "+60123456789", "code": "123456" }

2. POST /api/reset-password
   { "phone": "+60123456789", "password": "NewPassword123!", "password_confirmation": "NewPassword123!" }
```
- Separate verification from password entry
- Better UX with clear progress
- Can show different screens for each step
- More secure with time-limited verification
