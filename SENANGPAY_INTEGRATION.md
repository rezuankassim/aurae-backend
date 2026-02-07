# SenangPay Payment Gateway Integration

This document describes the SenangPay payment gateway integration for the Aurae Backend application.

## Implementation Status ✅

The SenangPay payment gateway has been successfully integrated with the following features:

- ✅ Payment driver extending Lunar PHP's AbstractPayment
- ✅ HMAC SHA256 signature generation and verification
- ✅ Mobile API endpoints for checkout and payment
- ✅ Return URL handling after payment completion
- ✅ API status query for payment verification
- ✅ WebSocket notifications for real-time payment status updates
- ✅ Transaction logging and tracking
- ✅ Duplicate payment prevention
- ✅ Comprehensive test coverage

## Setup Instructions

### 1. Configure Environment Variables

Add your SenangPay credentials to `.env`:

```env
SENANGPAY_MERCHANT_ID=your_merchant_id
SENANGPAY_SECRET_KEY=your_secret_key
SENANGPAY_BASE_URL=https://app.senangpay.my
SENANGPAY_CURRENCY=MYR
```

**For Testing/Sandbox:**
```env
SENANGPAY_BASE_URL=https://sandbox.senangpay.my  # If available
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

### 3. Set Default Payment Method

Update your `.env` to use SenangPay as the default payment method:

```env
PAYMENTS_TYPE=senangpay
```

Or leave it as `cash-in-hand` and let users select SenangPay during checkout.

### 4. Run Database Migrations

Lunar PHP transactions table should already exist. Verify:
```bash
php artisan migrate:status
```

## Architecture Overview

### Payment Flow

1. **Mobile App** → `/api/checkout/set-addresses` - Set shipping/billing addresses
2. **Mobile App** → `/api/checkout/initiate-payment` - Start payment with `payment_method=senangpay`
3. **Backend** → Creates draft order and intent transaction
4. **Backend** → Generates payment redirect URL and returns it to mobile app
5. **Mobile App** → Subscribes to WebSocket channel `payment.{user_id}`
6. **Mobile App** → Opens payment URL in WebView
7. **User** → Completes payment on SenangPay payment form
8. **SenangPay** → Redirects to `/payment/senangpay/return`
9. **Backend** → Queries payment status from SenangPay API
10. **Backend** → Verifies payment success and captures transaction
11. **Backend** → Broadcasts WebSocket event to mobile app
12. **Mobile App** → Receives WebSocket event, closes WebView, shows success/failure

### WebSocket Channel Strategy

- **Channel**: `payment.{user_id}`
- **Event**: `payment.completed`
- **Payload**:
  ```json
  {
    "reference_number": "ORD-2026-00123",
    "status": "success",
    "order_id": 123,
    "transaction_id": "SENANGPAY_TXN_456",
    "amount": "199.90",
    "currency": "MYR",
    "timestamp": "2026-01-28T10:00:00Z"
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
  "payment_method": "senangpay"
}
```

**Response:**
```json
{
  "status": 200,
  "message": "Payment initiated successfully.",
  "data": {
    "payment_url": "https://app.senangpay.my/payment/form?merchant_id=...",
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
  "message": "Payment status retrieved.",
  "data": {
    "reference_number": "ORD-2026-00123",
    "payment_status": "success",
    "order_id": 123,
    "order_status": "payment-received",
    "transaction_id": "SENANGPAY_TXN_456",
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
        apiService.initiatePayment(PaymentRequest("senangpay"))
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
            "success" -> {
                showSuccessScreen()
                completeOrder(event.order_id)
            }
            "failed" -> showFailureScreen()
        }
    }
}
```

### iOS (Swift) Example

```swift
class PaymentViewController: UIViewController {
    @IBOutlet weak var webView: WKWebView!
    
    func initiatePayment() {
        // 1. Call API to initiate payment
        PaymentService.initiatePayment(method: "senangpay") { response in
            let paymentUrl = response.data.paymentUrl
            let userId = self.getCurrentUserId()
            
            // 2. Subscribe to WebSocket channel
            WebSocketManager.subscribe(to: "payment.\(userId)") { event in
                self.handlePaymentCompleted(event)
            }
            
            // 3. Load payment URL in WebView
            let request = URLRequest(url: URL(string: paymentUrl)!)
            DispatchQueue.main.async {
                self.webView.load(request)
            }
        }
    }
    
    private func handlePaymentCompleted(_ event: PaymentEvent) {
        DispatchQueue.main.async {
            self.webView.isHidden = true
            switch event.status {
            case "success":
                self.showSuccessScreen()
                self.completeOrder(event.orderId)
            case "failed":
                self.showFailureScreen()
            default:
                break
            }
        }
    }
}
```

## SenangPay API Integration Details

### Authentication
- **Method**: Basic Auth
- **Username**: Merchant ID
- **Password**: Empty (leave blank)

### Amount Format
- **Format**: Integer in cents
- **Example**: RM 2.00 = 200

### Signature Format
- **Algorithm**: HMAC SHA256
- **Format**: `merchant_id + secret_key + {params}`
- **Example**: `hash_hmac('sha256', 'MERCHANT123' . 'secret' . 'ORDER1', 'secret')`

### Query Endpoints

#### Query Order Status
```
GET https://app.senangpay.my/apiv1/query_order_status
Parameters: merchant_id, order_id, hash
```

#### Query Transaction Status
```
GET https://app.senangpay.my/apiv1/query_transaction_status
Parameters: merchant_id, transaction_reference, hash
```

#### Get Transaction List
```
GET https://app.senangpay.my/apiv1/get_transaction_list
Parameters: merchant_id, timestamp_start, timestamp_end, hash
```

## Files Structure

```
app/
├── PaymentTypes/
│   └── SenangpayPayment.php          # Payment driver
├── Services/
│   └── SenangpaySignatureService.php # Signature generation
├── Http/Controllers/Payment/
│   └── SenangpayCallbackController.php # Return URL handler
routes/
└── web.php                            # Payment routes
config/
└── lunar/payments.php                 # Payment configuration
tests/
├── Unit/Services/
│   └── SenangpaySignatureServiceTest.php
└── Feature/Payment/
    └── SenangpayCallbackControllerTest.php
```

## Testing

Run unit tests for signature service:
```bash
php artisan test tests/Unit/Services/SenangpaySignatureServiceTest.php
```

Run feature tests for callback controller:
```bash
php artisan test tests/Feature/Payment/SenangpayCallbackControllerTest.php
```

Run all payment tests:
```bash
php artisan test --filter Payment
```

## Debugging

- **Laravel Logs**: Use `php artisan pail` for real-time log viewing
- **Payment Logs**: All SenangPay transactions are logged with `Log::info()` and `Log::error()`
- **WebSocket**: Enable WebSocket debugging in browser DevTools
- **Database**: Check `lunar_transactions` table for transaction records

## Troubleshooting

### Payment returns to error page
1. Check if merchant credentials are correct in `.env`
2. Verify SenangPay API is accessible
3. Check logs for signature verification errors
4. Ensure order reference is correctly stored in `meta`

### WebSocket event not received
1. Verify Reverb server is running (`php artisan reverb:start`)
2. Check user ID is correctly passed to broadcast
3. Verify client is subscribing to correct channel: `payment.{user_id}`
4. Check browser console for WebSocket connection errors

### Duplicate payment captures
1. System prevents duplicate captures by checking for existing capture transactions
2. If duplicate occurs, manually verify transaction status from SenangPay dashboard
3. Check database for malformed transactions and delete if necessary

## Additional Notes

- **Refunds**: SenangPay API doesn't provide direct refund functionality. Refunds must be processed manually via the SenangPay merchant dashboard.
- **Server-Side Rendering**: SSR not required for payment flow
- **File Uploads**: Payment flow does not involve file uploads
- **Deployment**: No special deployment requirements beyond Reverb WebSocket server

## Support

For issues or questions regarding this integration:
1. Check SenangPay API documentation: https://guide.senangpay.com/api-guide
2. Review test files for implementation examples
3. Check application logs for error details
