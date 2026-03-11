# Payment API

Endpoints for retrieving the authenticated user's payment history and invoice URLs.

**Base path**: `/api/v1`  
**Auth**: All endpoints require Bearer token (Sanctum)

---

## Endpoints Summary

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/payments` | List user's payment history (paginated) | Any authenticated user |
| GET | `/payments/invoice/{orderId}` | Get invoice URL for a successful payment | Any authenticated user |

---

## 1. List Payment History

| | |
|---|---|
| **Endpoint** | `GET /api/v1/payments` |
| **Access** | Protected (auth:sanctum) |

Returns paginated payment history for the authenticated user.

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 15 | Items per page (max 50) |
| page | integer | 1 | Page number |
| status | string | — | Filter by status: `success`, `pending`, `failed`, `cancelled`, `refunded` |

### Success (200)

```json
{
  "message": "Payment history retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "data": [
      {
        "id": 1,
        "order_id": "ORD_xxx123",
        "reference_id": "REF_xxx456",
        "currency": "INR",
        "amount": 500.00,
        "status": "success",
        "type": "registration",
        "description": "Registration fee",
        "created_at": "2025-03-11T10:30:00.000000Z",
        "processed_at": "2025-03-11T10:31:00.000000Z",
        "invoice_url": "https://example.com/invoice/ORD_xxx123?signature=..."
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42,
      "from": 1,
      "to": 15
    },
    "links": {
      "first": "https://api.example.com/api/v1/payments?page=1",
      "last": "https://api.example.com/api/v1/payments?page=3",
      "prev": null,
      "next": "https://api.example.com/api/v1/payments?page=2"
    }
  }
}
```

**Note:** `invoice_url` is only present for payments with `status: "success"`.

### Error (401)

```json
{
  "message": "Unauthenticated."
}
```

---

## 2. Get Invoice URL

| | |
|---|---|
| **Endpoint** | `GET /api/v1/payments/invoice/{orderId}` |
| **Access** | Protected (auth:sanctum) |

Returns a signed temporary URL to download the invoice for a successful payment. The URL expires after a configurable number of days (default 7).

### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| orderId | string | The order ID of the payment |

### Success (200)

```json
{
  "message": "Invoice URL generated successfully.",
  "success": true,
  "code": 200,
  "data": {
    "order_id": "ORD_xxx123",
    "invoice_url": "https://example.com/invoice/ORD_xxx123?signature=...",
    "expires_at": "2025-03-18T10:30:00.000000Z"
  }
}
```

### Error (401)

```json
{
  "message": "Unauthenticated."
}
```

### Error (404)

**Condition:** Payment not found or does not belong to the authenticated user.

```json
{
  "message": "Payment not found or access denied."
}
```

### Error (400)

**Condition:** Payment is not successful (invoice only available for `status: "success"`).

```json
{
  "message": "Invoice is only available for successful payments.",
  "success": false,
  "code": 400
}
```

---

## Data Field Reference

### Payment Object

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Payment ID |
| order_id | string | Order/reference ID |
| reference_id | string | Payment gateway reference |
| currency | string | e.g. `INR` |
| amount | float | Payment amount |
| status | string | `success`, `pending`, `failed`, `cancelled`, `refunded` |
| type | string \| null | e.g. `registration`, `subscription` (from meta) |
| description | string \| null | Payment description (from meta) |
| created_at | string | ISO 8601 datetime |
| processed_at | string \| null | ISO 8601 datetime when processed |
| invoice_url | string | *(Success only)* Signed temporary invoice URL |

---

## Example Requests

### List payment history

```bash
curl -X GET "https://api.example.com/api/v1/payments" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Filter by successful payments

```bash
curl -X GET "https://api.example.com/api/v1/payments?status=success" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Get invoice URL

```bash
curl -X GET "https://api.example.com/api/v1/payments/invoice/ORD_xxx123" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### JavaScript (Fetch)

```javascript
// List payments
const paymentsResponse = await fetch('/api/v1/payments?per_page=10', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});
const { data: paymentsData } = await paymentsResponse.json();
console.log('Payments:', paymentsData.data);

// Get invoice URL for an order
const invoiceResponse = await fetch(`/api/v1/payments/invoice/${orderId}`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});
const { data: invoiceData } = await invoiceResponse.json();
if (invoiceData.invoice_url) {
  window.open(invoiceData.invoice_url);
}
```
