# revPAY Merchant API 3.1.8 — Implementation Reference (AI Context)

This document is a complete technical reference for integrating revPAY as a payment gateway.
Follow these rules exactly. Do not infer or improvise behavior.

---

## 1. Environments

### Base URLs

- UAT: https://stg-mpg.revpay-sandbox.com.my/v1
- Production: https://mpg.revpay.com.my/v1

---

## 2. Core Endpoints

| Purpose         | Endpoint        | Method     |
| --------------- | --------------- | ---------- |
| Create Payment  | /payment        | POST       |
| Payment Inquiry | /inquiry        | GET / POST |
| Cancel Payment  | /payment/cancel | POST       |
| Refund          | /refund         | POST       |
| Authorise       | /authorise      | POST       |
| Capture         | /capture        | POST       |

---

## 3. Payment Flow (Redirect Mode)

1. Merchant creates order and unique Reference_Number
2. Merchant submits Payment Request to `/payment`
3. Customer is redirected to revPAY payment page
4. Customer completes payment
5. revPAY responds:
    - Browser redirect to `Return_URL`
    - Server-to-server POST to `Backend_URL`
6. Merchant verifies signature and updates order
7. Merchant responds `OK` to backend callback

Backend callback is the authoritative result.

---

## 4. Payment Request — `/payment`

### HTTP

- Method: POST
- Content-Type:
    - application/x-www-form-urlencoded (redirect flow)
    - application/json (API flow)

### Mandatory Fields

| Field              | Description                    |
| ------------------ | ------------------------------ |
| Revpay_Merchant_ID | Merchant ID assigned by revPAY |
| Reference_Number   | Unique order reference         |
| Amount             | Decimal with exactly 2 digits  |
| Currency           | ISO 4217 (e.g. MYR)            |
| Customer_IP        | Customer IP address            |
| Return_URL         | Browser redirect URL           |
| Key_Index          | Merchant key index             |
| Signature          | SHA-512 checksum               |

### Optional / Conditional Fields

| Field                   | Notes                                           |
| ----------------------- | ----------------------------------------------- |
| Payment_ID              | Empty value shows payment method selection page |
| Bank_Code               | Required for specific schemes                   |
| Customer_Name           | Optional                                        |
| Customer_Email          | Required for some payment types                 |
| Customer_Contact        | Required for some payment types                 |
| Backend_URL             | Strongly recommended                            |
| Transaction_Description | Displayed in reports                            |
| Source_Of_Funds         | CARD (default) or TOKEN                         |
| Customization           | Payment UI customization JSON                   |

---

## 5. Signature Generation

### Payment Request Signature

```
signature = SHA512(
MerchantKey +
Revpay_Merchant_ID +
Reference_Number +
Amount +
Currency
)
```

Rules:

- Output must be lowercase
- Amount must be formatted exactly as sent
- MerchantKey is selected using Key_Index

---

## 6. Frontend Payment Response (Return_URL)

revPAY redirects browser to Return_URL using POST or JSON.

### Important Response Fields

| Field             | Description                          |
| ----------------- | ------------------------------------ |
| Transaction_ID    | revPAY transaction ID                |
| Reference_Number  | Original merchant reference          |
| Response_Code     | Transaction result                   |
| Amount            | Payment amount                       |
| Currency          | Payment currency                     |
| Signature         | Response signature                   |
| Bank_Redirect_URL | Present only when Response_Code = 09 |

---

## 7. Payment Response Codes

| Code   | Meaning           | Action                             |
| ------ | ----------------- | ---------------------------------- |
| 00     | Success           | Mark order paid                    |
| 09     | Redirect required | Redirect user to Bank_Redirect_URL |
| Others | Failed            | Mark order failed                  |

---

## 8. Payment Response Signature Verification

### Response Signature Format

```
expected_signature = SHA512(
MerchantKey +
Revpay_Merchant_ID +
Transaction_ID +
Response_Code +
Reference_Number +
Amount +
Currency
)
```

Rules:

- Must match lowercase signature from revPAY
- Must be validated before trusting payment status

---

## 9. Backend Callback (Backend_URL)

### Behavior

- revPAY sends HTTPS POST server-to-server
- Same fields as frontend response
- Merchant must reply with plain text `OK`
- revPAY retries up to 2 times (5-minute interval) if no OK

Backend callback is the source of truth for payment state.

---

## 10. Payment Inquiry — `/inquiry`

Used if payment result is missing or uncertain.

### Required Fields

| Field              | Description      |
| ------------------ | ---------------- |
| Revpay_Merchant_ID | Merchant ID      |
| Reference_Number   | Order reference  |
| Amount             | 2 decimal format |
| Currency           | ISO 4217         |
| Key_Index          | Key index        |
| Signature          | SHA-512          |

### Inquiry Signature

```
signature = SHA512(
MerchantKey +
Revpay_Merchant_ID +
Reference_Number +
Amount +
Currency
)
```

---

## 11. Refund — `/refund`

Used to refund a completed transaction.

### Required

- Original Reference_Number
- Refund Amount
- Signature (per refund specification)

Refund signature uses:

```
MerchantKey + MerchantID + ReferenceNumber + RefundAmount + OriginalReferenceNumber
```

---

## 12. Cancel Payment — `/payment/cancel`

Used to void unsettled payments.

### Required

- Reference_Number
- Original_Reference_Number
- Signature

---

## 13. JSON Payment Mode (Advanced)

If using JSON:

- POST to `/payment`
- If Response_Code = 09:
    - Redirect browser to Bank_Redirect_URL
- Final result will return to Return_URL

Do not treat initial JSON response as final unless Response_Code = 00.

---

## 14. Security Rules (Mandatory)

- Always verify signatures
- Always use HTTPS
- Never trust frontend redirect alone
- Backend callback is authoritative
- Store request, frontend response, backend response

---

## 15. Common Integration Errors

| Issue                 | Cause                         |
| --------------------- | ----------------------------- |
| Invalid signature     | Wrong amount format or key    |
| Payment stuck pending | Backend_URL not responding OK |
| Duplicate callbacks   | No OK response                |
| Failed redirect       | Missing Return_URL            |

---

## 16. Implementation Checklist

- Generate unique Reference_Number
- Format Amount to 2 decimals
- Generate correct signature
- Redirect customer to revPAY
- Verify frontend response signature
- Process backend callback and reply OK
- Update order state accordingly

---

## End of revPAY Integration Context
