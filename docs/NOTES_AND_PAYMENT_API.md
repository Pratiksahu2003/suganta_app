# Notes & Payment API Documentation

Complete API for purchasing individual notes and subscription plans (s_type=1) with Cashfree payment integration.

---

## Notes API (V2) — All routes require authentication

**Base URL:** `/api/v2/notes`

**Authentication:** All routes require `Authorization: Bearer {token}` header (Sanctum).

### 1. List Notes
**GET** `/api/v2/notes`

| Query Param | Type | Description |
|-------------|------|--------------|
| category_id | int | Filter by note category |
| note_type_id | int | Filter by note type |
| search | string | Search in name/description |
| is_paid | bool | Filter paid/free notes |
| per_page | int | Items per page (max 50) |

**Response:**
```json
{
  "message": "Notes retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Physics Notes",
        "description": "...",
        "price": 99.00,
        "is_paid": true,
        "can_access": false,
        "is_purchased": false,
        "has_subscription_access": false,
        "formatted_price": "₹99.00"
      }
    ],
    "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 72 },
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
  }
}
```

### 2. Get Single Note
**GET** `/api/v2/notes/{id}`

### 3. Get Note Categories
**GET** `/api/v2/notes/categories`

### 4. Get Note Types
**GET** `/api/v2/notes/types`

### 5. Check Access
**GET** `/api/v2/notes/{id}/check-access`

**Response:**
```json
{
  "message": "Access check result.",
  "data": {
    "note_id": 1,
    "can_access": true,
    "is_purchased": false,
    "has_subscription_access": true,
    "message": "You have access to this note."
  }
}
```

### 6. Purchase Note
**POST** `/api/v2/notes/purchase`

**Body:**
```json
{
  "note_id": 1
}
```

**Response (Payment Required):**
```json
{
  "message": "Note purchase payment created successfully.",
  "data": {
    "payment": {
      "order_id": "NOTE_ABC123XYZ",
      "amount": 99.00,
      "currency": "INR",
      "status": "pending"
    },
    "checkout_url": "https://yoursite.com/api/v1/payment/checkout?order_id=NOTE_ABC123XYZ",
    "payment_session_id": "...",
    "note": { "id": 1, "name": "Physics Notes", ... }
  }
}
```

**Flow:**
1. Client opens `checkout_url` in browser/webview
2. User completes payment on Cashfree
3. Cashfree redirects to callback with `?order_id=NOTE_ABC123XYZ`
4. Client can poll `GET /payments/status?order_id=NOTE_ABC123XYZ` for status

### 7. Download Note
**GET** `/api/v2/notes/{id}/download`

Returns file download. Requires:
- Purchased the note, OR
- Active subscription (s_type=1), OR
- Note is free

### 8. My Purchases
**GET** `/api/v2/notes/my-purchases`

---

## Subscription API (s_type=1)

### 1. List Plans
**GET** `/subscriptions/plans?s_type=1`

**Response:**
```json
{
  "message": "Subscription plans retrieved successfully.",
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "Monthly Plan",
        "price": 299.00,
        "billing_period": "monthly",
        "s_type": 1,
        "features": ["Unlimited notes", "..."],
        "formatted_price": "INR 299.00"
      }
    ]
  }
}
```

### 2. Purchase Subscription
**POST** `/subscriptions/purchase`

**Body:**
```json
{
  "subscription_plan_id": 1
}
```

**Response:**
```json
{
  "message": "Subscription payment created successfully.",
  "data": {
    "payment": {
      "order_id": "SUB_XYZ789ABC",
      "amount": 299.00,
      "currency": "INR",
      "status": "pending"
    },
    "checkout_url": "https://yoursite.com/api/v1/payment/checkout?order_id=SUB_XYZ789ABC",
    "payment_session_id": "...",
    "subscription_plan": { ... }
  }
}
```

### 3. My Subscriptions
**GET** `/subscriptions/my-subscriptions`

### 4. Current Subscription
**GET** `/subscriptions/current?s_type=1`

---

## Payment API

### 1. Payment Status (Auth Required)
**GET** `/payments/status?order_id=NOTE_ABC123XYZ`

**Response:**
```json
{
  "message": "Payment status retrieved.",
  "data": {
    "order_id": "NOTE_ABC123XYZ",
    "status": "success",
    "type": "note",
    "amount": 99.00,
    "currency": "INR",
    "processed_at": "2025-03-17T10:30:00.000000Z"
  }
}
```

### 2. Payment History
**GET** `/payments`

### 3. Checkout (Public)
**GET** `/payment/checkout?order_id=NOTE_ABC123XYZ`

Opens Cashfree hosted payment page. Used by `checkout_url` from purchase responses.

### 4. Callback (Public)
**GET** `/payment/callback?order_id=NOTE_ABC123XYZ`

Cashfree redirects here after payment. Returns JSON status.

---

## Access Logic

| Scenario | Can Access Note |
|----------|-----------------|
| Note is free (is_paid=false) | Yes |
| User purchased the note | Yes |
| User has active subscription (s_type=1) | Yes |
| None of the above | No |

---

## Payment Flow Summary

### Individual Note Purchase
1. `POST /notes/purchase` with `{ "note_id": 1 }`
2. Open `checkout_url` from response
3. Complete payment on Cashfree
4. Poll `GET /payments/status?order_id=XXX` or use callback redirect
5. On success: `GET /notes/1/download` to download

### Subscription Purchase (s_type=1)
1. `GET /subscriptions/plans?s_type=1` to list plans
2. `POST /subscriptions/purchase` with `{ "subscription_plan_id": 1 }`
3. Open `checkout_url` from response
4. Complete payment
5. On success: User gets access to all paid notes (s_type=1 subscription)
