# RevPay Payment Gateway Integration

This document describes the RevPay payment gateway integration for mobile app users.

## Implementation Complete ✅

The RevPay payment gateway has been successfully integrated with the following features:

- ✅ Payment driver extending Lunar PHP's AbstractPayment
- ✅ WebSocket notifications for real-time payment status updates
- ✅ Mobile API endpoints for checkout and payment
- ✅ Secure signature generation and verification
- ✅ Backend callback handling (authoritative source)
- ✅ Frontend return URL handling with WebSocket broadcast
- ✅ Transaction logging and tracking
- ✅ Refund support via RevPay API

## Setup Instructions

### 1. Configure Environment Variables

Add your RevPay credentials to `.env`:

```env
REVPAY_MERCHANT_ID=your_merchant_id
REVPAY_MERCHANT_KEY=your_merchant_key
REVPAY_KEY_INDEX=1
REVPAY_BASE_URL=https://stg-mpg.revpay-sandbox.com.my/v1  # UAT
# REVPAY_BASE_URL=https://mpg.revpay.com.my/v1  # Production
REVPAY_CURRENCY=MYR
```

### 2. Ensure Broadcasting is Configured

Make sure Laravel Reverb is running and configured:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=aurae-app-id
REVERB_APP_KEY=aurae-app-key
REVERB_APP_SECRET=aurae-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

Start Reverb server:
```bash
php artisan reverb:start
```

### 3. Run Database Migrations

Lunar PHP transactions table should already exist. Verify:
```bash
php artisan migrate:status
```

## Architecture Overview

### Payment Flow

1. **Mobile App** → `/api/checkout/set-addresses` - Set shipping/billing addresses
2. **Mobile App** → `/api/checkout/initiate-payment` - Start payment
3. **Backend** → Creates draft order and intent transaction
4. **Backend** → Returns RevPay payment URL
5. **Mobile App** → Subscribes to WebSocket channel `payment.{user_id}`
6. **Mobile App** → Opens payment URL in WebView
7. **User** → Completes payment on RevPay page
8. **RevPay** → Redirects to `/payment/revpay/return`
9. **Backend** → Verifies signature, broadcasts WebSocket event
10. **RevPay** → Sends backend callback to `/payment/revpay/callback`
11. **Backend** → Captures payment, creates capture transaction, broadcasts WebSocket
12. **Mobile App** → Receives WebSocket event, closes WebView, shows success/failure

### WebSocket Channel Strategy

- **Channel**: `payment.{user_id}`
- **Event**: `payment.completed`
- **Payload**:
  ```json
  {
    "reference_number": "ORD-2026-00123",
    "status": "success",  // "success", "failed", or "redirect_required"
    "order_id": 123,
    "transaction_id": "REVPAY_TX_456",
    "amount": "199.90",
    "currency": "MYR",
    "timestamp": "2026-01-11T20:00:00Z"
  }
  ```

## Mobile API Endpoints

### 1. Set Addresses
`POST /api/checkout/set-addresses`

**Request:**
```json
{
  "shipping_address": {
    "first_name": "John",
    "last_name": "Doe",
    "line_one": "123 Main St",
    "city": "Kuala Lumpur",
    "postcode": "50000",
    "country_id": 1,
    "contact_email": "john@example.com",
    "contact_phone": "+60123456789"
  },
  "billing_same_as_shipping": true
}
```

### 2. Initiate Payment
`POST /api/checkout/initiate-payment`

**Request:**
```json
{
  "payment_method": "revpay"  // or "cash-in-hand"
}
```

**Response:**
```json
{
  "status": 200,
  "message": "Payment initiated successfully",
  "data": {
    "payment_url": "https://stg-mpg.revpay-sandbox.com.my/payment/xyz",
    "reference_number": "ORD-2026-00123",
    "order_id": 123,
    "amount": "RM 199.90",
    "currency": "MYR"
  }
}
```

### 3. Check Payment Status
`GET /api/checkout/payment-status/{reference}`

**Response:**
```json
{
  "status": 200,
  "message": "Payment status retrieved",
  "data": {
    "reference_number": "ORD-2026-00123",
    "payment_status": "success",  // "pending", "success", "failed"
    "order_id": 123,
    "order_status": "payment-received",
    "transaction_id": "REVPAY_TX_456",
    "amount": "RM 199.90",
    "currency": "MYR"
  }
}
```

### 4. Order History
`GET /api/orders`

Returns list of user's orders with details.

### 5. Order Details
`GET /api/orders/{order}`

Returns specific order details with transaction history.

## Mobile App Implementation Guide

### Android (Kotlin) Example

```kotlin
class PaymentActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    
    fun initiatePayment() {
        // 1. Call API to initiate payment
        apiService.initiatePayment(PaymentRequest("revpay"))
            .enqueue { response ->
                val paymentUrl = response.data.payment_url
                val userId = getCurrentUserId()
                
                // 2. Subscribe to WebSocket channel
                websocketClient.subscribe("payment.$userId")
                    .listen("payment.completed") { event ->
                        handlePaymentCompleted(event)
                    }
                
                // 3. Open WebView
                webView.loadUrl(paymentUrl)
            }
    }
    
    private fun handlePaymentCompleted(event: PaymentEvent) {
        webView.visibility = View.GONE
        when (event.status) {
            "success" -> navigateToSuccess(event.order_id)
            "failed" -> navigateToFailure()
            else -> showPendingDialog()
        }
    }
}
```

### iOS (Swift) Example

```swift
class PaymentViewController: UIViewController {
    var webView: WKWebView!
    
    func initiatePayment() {
        // 1. Call API to initiate payment
        apiService.initiatePayment(method: "revpay") { response in
            let paymentUrl = response.data.payment_url
            let userId = getCurrentUserId()
            
            // 2. Subscribe to WebSocket channel
            websocketClient.subscribe(channel: "payment.\(userId)")
                .bind(eventName: "payment.completed") { event in
                    self.handlePaymentCompleted(event: event)
                }
            
            // 3. Open WebView
            if let url = URL(string: paymentUrl) {
                self.webView.load(URLRequest(url: url))
            }
        }
    }
    
    func handlePaymentCompleted(event: PaymentEvent) {
        webView.isHidden = true
        switch event.status {
        case "success":
            navigateToSuccess(orderId: event.order_id)
        case "failed":
            navigateToFailure()
        default:
            showPendingDialog()
        }
    }
}
```

## Testing

### UAT Testing
1. Use RevPay sandbox credentials
2. Test payment initiation from mobile app
3. Complete payment on RevPay sandbox
4. Verify WebSocket event received
5. Confirm transaction created in database
6. Check order status updated to "payment-received"

### Test Cards (RevPay Sandbox)
Refer to RevPay documentation for test card numbers and scenarios.

## Security Considerations

- ✅ All signatures verified using SHA-512
- ✅ Backend callback is authoritative (not frontend redirect)
- ✅ WebSocket events broadcast only to order owner
- ✅ Merchant key stored securely in `.env`
- ✅ HTTPS required for all callback URLs
- ✅ Amount validation before capture
- ✅ Complete audit trail logged

## Files Created/Modified

### New Files
- `app/Events/PaymentCompleted.php` - WebSocket event
- `app/Services/RevpaySignatureService.php` - Signature helpers
- `app/PaymentTypes/RevpayPayment.php` - Payment driver
- `app/Http/Controllers/Payment/RevpayCallbackController.php` - Callback handler
- `app/Http/Controllers/Api/CheckoutController.php` - Mobile API
- `app/Http/Resources/OrderResource.php` - Order API resource
- `app/Http/Resources/TransactionResource.php` - Transaction API resource
- `resources/views/payment/processing.blade.php` - Processing page
- `resources/views/payment/error.blade.php` - Error page

### Modified Files
- `.env.example` - Added RevPay config
- `config/services.php` - Added RevPay service config
- `config/lunar/payments.php` - Registered revpay payment type
- `app/Providers/AppServiceProvider.php` - Registered payment driver
- `routes/web.php` - Added callback routes
- `routes/api.php` - Added mobile API routes

## Troubleshooting

### WebSocket Not Connecting
- Ensure Reverb server is running: `php artisan reverb:start`
- Check `BROADCAST_CONNECTION=reverb` in `.env`
- Verify mobile app WebSocket configuration

### Payment Not Capturing
- Check Laravel logs: `php artisan pail`
- Verify signature generation matches RevPay requirements
- Ensure backend callback URL is accessible from RevPay servers

### Orders Stuck in Pending
- Check if backend callback is being received
- Verify callback returns plain text "OK"
- Use payment inquiry endpoint if needed

## Support

For RevPay API documentation, refer to: `revpay_merchant.md`

For Lunar PHP payment documentation: https://docs.lunarphp.com/1.x/extending/payments
