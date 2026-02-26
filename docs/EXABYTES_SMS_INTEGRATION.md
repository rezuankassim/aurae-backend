# Exabytes SMS Integration

This document describes the Exabytes Bulk SMS integration for sending OTP verification codes to users.

## Overview

The application has been migrated from Twilio SMS to Exabytes Bulk SMS Marketing Solutions for better local (Malaysian) delivery rates and cost-effectiveness.

## Features

- ✅ Send OTP verification codes
- ✅ Malaysian standard SMS format: `RM0.00 [BrandName]: Your verification code is 123456.`
- ✅ Automatic phone number normalization (handles Malaysian formats)
- ✅ Support for both ASCII and Unicode messages
- ✅ Comprehensive error handling and logging
- ✅ Configurable brand name

## Configuration

### Environment Variables

Add the following to your `.env` file:

```bash
# Exabytes Bulk SMS Configuration
EXABYTES_USERNAME=your_username_here
EXABYTES_PASSWORD=your_password_here
EXABYTES_BRAND_NAME=AURAE
```

### Getting Credentials

1. Register an account at [Exabytes Bulk SMS Marketing Solutions](https://www.exabytes.my/online-marketing/bulk-sms-marketing)
2. Get 50 free SMS credits when you sign up
3. Your username and password will be provided after registration
4. Configure your brand name (3-8 ASCII characters for Malaysia)

### Brand Name Requirements

According to Malaysian regulations:
- Must be 3-8 ASCII characters (English & Bahasa Malaysia)
- Unicode/symbols like `@`, `®` are not allowed
- Must represent your registered business/brand
- Exabytes will review and approve your brand name

## Usage

### Service Class

The `ExabytesService` class provides two main methods:

```php
use App\Services\ExabytesService;

$exabytesService = app(ExabytesService::class);

// Send OTP (automatically formats with Malaysian standard)
$result = $exabytesService->sendOtp($phoneNumber, $code);

// Send custom SMS
$result = $exabytesService->sendSms($phoneNumber, $message);
```

### Response Format

Both methods return an array with the following structure:

```php
// Success
[
    'success' => true,
    'message_id' => '123456789' // Exabytes message ID
]

// Failure
[
    'success' => false,
    'error' => 'Error message here'
]
```

### Phone Number Format

The service automatically normalizes phone numbers to Exabytes format:

**Input formats supported:**
- `0123456789` (Malaysian local) → `60123456789`
- `60123456789` (Malaysian with country code)
- `+60123456789` (International format) → `60123456789`

**Exabytes expected format:** `60123456789` (country code + number, no +)

## Message Format

### OTP Messages

OTP messages follow Malaysian standard format to avoid international charges:

```
RM0.00 [AURAE]: Your verification code is 123456. This code expires in 5 minutes.
```

**Important:** Including `RM0.00` and your brand name prevents messages from being charged as international SMS (RM0.30 per SMS in Malaysia).

### Message Types

- **Type 1 (ASCII):** English, Bahasa Melayu - Standard SMS pricing
- **Type 2 (Unicode):** Chinese, Japanese, Emojis - May have different pricing

The service automatically detects and sets the correct message type.

## Integration Points

### Authentication Flow

**Forgot Password** (`AuthenticationController@forgotPassword`)
```php
POST /api/forgot-password
```

**Verify Reset OTP** (`AuthenticationController@verifyResetOtp`)
```php
POST /api/verify-reset-otp
```

### Profile Management

**Update Profile with Phone Change** (`ProfileController@update`)
```php
PUT /api/profile
```

**Verify Phone Change** (`ProfileController@verifyPhoneChange`)
```php
POST /api/profile/verify-phone-change
```

**Resend OTP** (`ProfileController@resendPhoneVerificationOtp`)
```php
POST /api/profile/resend-otp
```

## Error Handling

The service includes comprehensive error handling:

1. **Configuration Errors:** Returns error if credentials are missing
2. **HTTP Errors:** Logs and returns error for failed API requests
3. **API Errors:** Detects error responses from Exabytes API
4. **Exceptions:** Catches and logs all exceptions

All errors are logged to Laravel logs for debugging.

## Logging

The service logs all SMS activities:

```php
// Success
Log::info('Exabytes SMS Sent', [
    'phone' => '60123456789',
    'response' => 'message_id_here',
]);

// Errors
Log::error('Exabytes SMS Error', [
    'phone' => '60123456789',
    'response' => 'error_response',
]);
```

## Testing

### Development Testing

During development, you can test with actual phone numbers using your Exabytes free credits:

```bash
# Make sure .env has valid credentials
EXABYTES_USERNAME=your_username
EXABYTES_PASSWORD=your_password
EXABYTES_BRAND_NAME=AURAE
```

### API Testing

Test the forgot password endpoint:

```bash
curl -X POST http://localhost:8000/api/forgot-password \
  -H "Content-Type: application/json" \
  -H "X-Device-Key: your_device_key" \
  -d '{"phone": "0123456789"}'
```

## Cost Considerations

- **Local SMS (with brand name):** Standard Exabytes pricing
- **Without brand name:** RM0.30 per SMS (Malaysia) - treated as international
- **SMS Credits:** Valid for 1 year from purchase date
- **Free Credits:** 50 free SMS when you sign up

## Compliance

By using Exabytes Bulk SMS service, you must:

1. Provide business registration documents to Exabytes
2. Follow Malaysian Personal Data Protection Act 2010 (Act 709)
3. Follow Communications and Multimedia Act 1998 (Act 588)
4. Comply with anti-spam laws
5. Obtain necessary consent before sending messages

## Migration from Twilio

The integration replaces Twilio SMS:

- `TwilioService` → `ExabytesService`
- Same method signatures for easy migration
- Enhanced error handling
- Malaysian standard message format

Old Twilio configuration can remain in `.env` for reference but is no longer used.

## API Documentation

Official Exabytes API documentation:
https://support.exabytes.com.my/en/support/solutions/articles/14000110847-bulk-sms-api-integration

## Troubleshooting

### SMS Not Received

1. Check Exabytes account has sufficient credits
2. Verify phone number format is correct
3. Check Laravel logs for error messages
4. Ensure brand name is approved by Exabytes

### Configuration Issues

1. Verify `.env` credentials are correct
2. Clear Laravel config cache: `php artisan config:clear`
3. Check Exabytes account status

### Message Format Issues

1. Ensure brand name is included in OTP messages
2. Verify brand name meets Malaysian requirements (3-8 ASCII chars)
3. Check if using approved brand name

## Support

For Exabytes-specific issues:
- Support Portal: https://support.exabytes.com.my/
- Account setup takes 1 working day after payment
- Contact Exabytes support for credit/account issues
