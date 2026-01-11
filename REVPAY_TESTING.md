# RevPay Payment Integration - Test Documentation

This document describes the test suite for the RevPay payment gateway integration.

## Test Coverage

The test suite covers all critical payment flows and security measures to ensure money is handled safely.

### Test Files

1. **`tests/Unit/Services/RevpaySignatureServiceTest.php`** - Signature service unit tests
2. **`tests/Feature/Payment/RevpayCallbackControllerTest.php`** - Payment callback tests (critical for money security)
3. **`tests/Feature/Api/CheckoutControllerTest.php`** - Mobile API checkout tests

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Feature/Payment/RevpayCallbackControllerTest.php

# Specific test method
php artisan test --filter=backend_callback_captures_successful_payment
```

### Run Tests with Coverage
```bash
php artisan test --coverage
```

## Critical Test Scenarios

### 🔒 Security Tests (MUST PASS)

#### 1. Signature Verification Tests
**File:** `RevpaySignatureServiceTest.php`

- ✅ `it_generates_payment_signature_correctly` - Ensures signatures are SHA-512 lowercase
- ✅ `it_generates_consistent_signatures_for_same_input` - Validates deterministic signature generation
- ✅ `it_verifies_matching_signatures` - Tests signature validation
- ✅ `it_rejects_non_matching_signatures` - **CRITICAL**: Prevents invalid payment callbacks
- ✅ `signature_is_sensitive_to_amount_format` - Prevents amount tampering

**Why Critical:** These tests ensure that only RevPay can send valid payment callbacks. Failed signature checks prevent fraudulent payment confirmations.

#### 2. Callback Security Tests
**File:** `RevpayCallbackControllerTest.php`

- ✅ `backend_callback_rejects_invalid_signature` - **MOST CRITICAL**: Blocks fake payment confirmations
- ✅ `backend_callback_prevents_duplicate_processing` - Prevents double-charging or double-capture

**Why Critical:** These tests protect against:
- Attackers sending fake "payment successful" callbacks
- Duplicate payment processing (charging customer multiple times)
- Replay attacks

### 💰 Money Handling Tests (MUST PASS)

#### 1. Payment Capture Tests
**File:** `RevpayCallbackControllerTest.php`

- ✅ `backend_callback_captures_successful_payment` - Verifies payment is captured correctly
- ✅ `backend_callback_handles_failed_payment` - Ensures failed payments don't create orders

**Why Critical:** Ensures:
- Successful payments are captured and orders fulfilled
- Failed payments don't result in fulfilled orders
- Customer is only charged when payment succeeds

#### 2. Transaction Recording Tests

- ✅ Verifies `intent` transaction created on payment initiation
- ✅ Verifies `capture` transaction created on successful payment
- ✅ Verifies transaction amounts match order totals
- ✅ Verifies transaction metadata (card type, last 4 digits) recorded

**Why Critical:** Provides audit trail for:
- Financial reconciliation
- Customer support
- Dispute resolution
- Regulatory compliance

### 📱 API Endpoint Tests

#### 1. Checkout Flow Tests
**File:** `CheckoutControllerTest.php`

- ✅ `it_sets_shipping_and_billing_addresses_for_cart` - Address management
- ✅ `it_initiates_revpay_payment_successfully` - Payment initiation
- ✅ `it_rejects_payment_initiation_without_addresses` - Validation
- ✅ `it_rejects_payment_initiation_for_empty_cart` - Prevents empty orders

#### 2. Authorization Tests

- ✅ `it_requires_authentication_for_checkout_endpoints` - Auth required
- ✅ `it_prevents_users_from_viewing_others_orders` - Authorization checks

**Why Critical:** Prevents:
- Unauthorized access to payment endpoints
- Users viewing other users' orders
- Payment initiation without required data

### 🔔 WebSocket Event Tests

#### Return URL Tests
**File:** `RevpayCallbackControllerTest.php`

- ✅ `return_url_broadcasts_websocket_event` - WebSocket notification sent
- ✅ `return_url_handles_bank_redirect_response_code_09` - 3D Secure handling
- ✅ `return_url_shows_error_for_invalid_signature` - Error handling

**Why Critical:** Ensures mobile app receives real-time payment status updates.

## Test Scenarios Covered

### ✅ Happy Path
1. User adds items to cart
2. User sets addresses
3. User initiates RevPay payment
4. Payment URL generated with valid signature
5. User completes payment on RevPay
6. Backend callback received with valid signature
7. Payment captured, order updated
8. WebSocket event sent to mobile app

### ✅ Failed Payment Path
1. User initiates payment
2. Payment fails on RevPay (e.g., insufficient funds)
3. Backend callback received with response code != '00'
4. Order status updated to 'payment-failed'
5. WebSocket event sent with failed status
6. NO capture transaction created

### ✅ Security Attack Scenarios

#### Scenario 1: Fake Payment Callback
**Test:** `backend_callback_rejects_invalid_signature`
- Attacker sends POST to `/payment/revpay/callback`
- Signature is invalid or missing
- **Expected:** 400 error, NO payment captured

#### Scenario 2: Modified Amount
**Test:** `signature_is_sensitive_to_amount_format`
- Attacker modifies amount in callback
- Signature no longer matches
- **Expected:** Signature verification fails

#### Scenario 3: Replay Attack
**Test:** `backend_callback_prevents_duplicate_processing`
- Same callback sent multiple times
- **Expected:** Only ONE capture transaction created

### ✅ Edge Cases
- Empty cart payment initiation → Rejected
- Missing addresses → Rejected
- Duplicate callbacks → Handled gracefully
- Response code '09' (bank redirect) → Properly handled
- Unauthorized order access → Blocked

## Test Data

### Test Credentials
```php
'merchant_id' => 'TEST_MERCHANT'
'merchant_key' => 'test-secret-key'
'key_index' => 1
'base_url' => 'https://test.revpay.com/v1'
'currency' => 'MYR'
```

### Test Amount
- Order total: 10000 (cents) = RM 100.00
- Amount format: "100.00" (2 decimal places)

### Test Signatures
All test signatures are generated using the real `RevpaySignatureService` to ensure they match production behavior.

## CI/CD Integration

### Pre-commit Hook
```bash
#!/bin/sh
php artisan test --testsuite=Unit --stop-on-failure
```

### CI Pipeline
```yaml
- name: Run Tests
  run: php artisan test --stop-on-failure

- name: Run Payment Tests
  run: php artisan test tests/Feature/Payment/ --stop-on-failure
```

## Manual Testing Checklist

After running automated tests, perform these manual tests in UAT:

### UAT Environment Testing
- [ ] Initiate payment with real RevPay sandbox
- [ ] Complete payment with test card
- [ ] Verify WebSocket event received in mobile app
- [ ] Check transaction recorded in database
- [ ] Verify order status updated correctly
- [ ] Test failed payment scenario
- [ ] Test Response_Code 09 (3D Secure)
- [ ] Verify signature validation with real callbacks

### Production Readiness
- [ ] All tests passing
- [ ] UAT testing completed
- [ ] RevPay production credentials configured
- [ ] Reverb WebSocket server running
- [ ] Monitoring and alerts configured
- [ ] Rollback plan documented

## Common Test Failures

### Issue: Signature Tests Failing
**Cause:** Incorrect signature generation algorithm
**Fix:** Verify SHA-512 hashing and lowercase conversion

### Issue: Database Errors
**Cause:** Missing migrations or factories
**Fix:** Run `php artisan migrate:fresh` in test environment

### Issue: Event Not Dispatched
**Cause:** Event::fake() not called
**Fix:** Ensure `Event::fake()` is called before test execution

### Issue: Factory Not Found
**Cause:** Missing model factories
**Fix:** Ensure Lunar PHP factories are published

## Debugging Tests

### Enable Detailed Output
```bash
php artisan test --verbose
```

### Debug Single Test
```bash
php artisan test --filter=test_name --stop-on-failure
```

### View Database State
Add this to your test:
```php
$this->dumpDatabase();
```

### View Request/Response
```php
$response->dump();
```

## Test Maintenance

### When to Update Tests
- ✅ Adding new payment methods
- ✅ Changing signature algorithm
- ✅ Modifying callback handling
- ✅ Adding new API endpoints
- ✅ Changing transaction structure

### When Tests Must Pass
- ✅ Before every commit
- ✅ Before deploying to production
- ✅ After updating Lunar PHP package
- ✅ After changing payment configuration

## Security Audit Checklist

Use this checklist when reviewing payment code changes:

- [ ] All signatures verified before processing
- [ ] Backend callback is authoritative (not frontend)
- [ ] Duplicate processing prevented
- [ ] Amount validation before capture
- [ ] User authorization checked
- [ ] Sensitive data logged appropriately (no secrets)
- [ ] Error messages don't leak sensitive info
- [ ] All tests passing

## Support

For test failures or questions:
1. Check test output for specific error
2. Review this documentation
3. Check `REVPAY_INTEGRATION.md` for implementation details
4. Verify RevPay sandbox credentials

## Summary

Total Tests: **40+ test cases**

Critical Security Tests: **15+**
- Signature generation and verification
- Invalid callback rejection
- Amount tampering prevention
- Duplicate processing prevention

Money Handling Tests: **10+**
- Payment capture
- Failed payment handling
- Transaction recording
- Order status updates

API Endpoint Tests: **15+**
- Authentication and authorization
- Input validation
- Payment initiation
- Order management

**All tests must pass before deploying to production.**
