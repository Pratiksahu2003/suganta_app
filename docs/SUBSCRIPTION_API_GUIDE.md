# Subscription API Guide

This guide explains how to use the subscription API endpoints to show subscription plans and allow users to purchase subscriptions with complete payment gateway integration.

## API Endpoints

### 1. Get Subscription Plans

**GET** `/api/v1/subscriptions/plans`

Get active subscription plans. **Access-based filtering:**
- **User has active subscription** for the requested s_type → returns all plans for that type
- **User has NO active subscription** (or not authenticated) → returns only **free plans** (price = 0) for that type

**Query Parameters:**
- `s_type` (optional): Filter by subscription type (default: 1). `1` = Portfolio plans, `2` = Note download plans.

**Response:**
```json
{
  "success": true,
  "message": "Subscription plans retrieved successfully.",
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "Basic Plan",
        "slug": "basic-plan",
        "description": "Basic subscription with limited features",
        "price": 99.00,
        "currency": "INR",
        "billing_period": "monthly",
        "max_images": 10,
        "max_files": 5,
        "features": ["Feature 1", "Feature 2"],
        "is_popular": false,
        "is_active": true,
        "sort_order": 1,
        "s_type": 1,
        "formatted_price": "INR 99.00",
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-01T00:00:00Z"
      }
    ]
  }
}
```

### 2. Get Specific Subscription Plan

**GET** `/api/v1/subscriptions/plans/{plan_id}`

Get details of a specific subscription plan. **Access restriction:**
- **Free plans** (price = 0): Always accessible
- **Paid plans** (price > 0): Returns 404 if user has NO active subscription for that plan's s_type

**Response:**
```json
{
  "success": true,
  "message": "Subscription plan retrieved successfully.",
  "data": {
    "plan": {
      "id": 1,
      "name": "Basic Plan",
      // ... other plan details
    }
  }
}
```

### 3. Get User's Subscriptions (Protected)

**GET** `/api/v1/subscriptions/my-subscriptions`

**Headers:**
- `Authorization: Bearer {access_token}`

**Query Parameters:**
- `status` (optional): Filter by status (active, cancelled, expired, etc.)
- `s_type` (optional): Filter by subscription type
- `per_page` (optional): Number of items per page (max 50, default 15)

**Response:**
```json
{
  "success": true,
  "message": "User subscriptions retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "subscription_plan_id": 1,
        "payment_id": 1,
        "status": "active",
        "starts_at": "2024-01-01T00:00:00Z",
        "expires_at": "2024-02-01T00:00:00Z",
        "payment_method": "cashfree",
        "transaction_id": "TXN123456",
        "amount_paid": 99.00,
        "is_active": true,
        "days_remaining": 15,
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-01T00:00:00Z",
        "plan": {
          // Plan details
        },
        "payment": {
          // Payment details
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1,
      "from": 1,
      "to": 1
    },
    "links": {
      "first": "...",
      "last": "...",
      "prev": null,
      "next": null
    }
  }
}
```

### 4. Get Current Active Subscription (Protected)

**GET** `/api/v1/subscriptions/current`

**Headers:**
- `Authorization: Bearer {access_token}`

**Query Parameters:**
- `s_type` (optional): Subscription type (default: 1)

**Response:**
```json
{
  "success": true,
  "message": "Current subscription retrieved successfully.",
  "data": {
    "subscription": {
      "id": 1,
      // ... subscription details with plan and payment info
    }
  }
}
```

### 5. Purchase Subscription (Protected)

**POST** `/api/v1/subscriptions/purchase`

**Headers:**
- `Authorization: Bearer {access_token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "subscription_plan_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription payment created successfully.",
  "data": {
    "payment": {
      "order_id": "SUB_ABC123XYZ",
      "amount": 99.00,
      "currency": "INR",
      "status": "pending"
    },
    "checkout_url": "https://www.suganta.in/api/v1/payment/checkout?order_id=SUB_ABC123XYZ",
    "payment_session_id": "session_abc123",
    "subscription_plan": {
      // Plan details
    }
  }
}
```

### 6. Cancel Subscription (Protected)

**PATCH** `/api/v1/subscriptions/{subscription_id}/cancel`

**Headers:**
- `Authorization: Bearer {access_token}`

**Response:**
```json
{
  "success": true,
  "message": "Subscription cancelled successfully.",
  "data": {
    "subscription": {
      "id": 1,
      "status": "cancelled",
      // ... other subscription details
    }
  }
}
```

### 7. Renew Subscription (Protected)

**POST** `/api/v1/subscriptions/{subscription_id}/renew`

**Headers:**
- `Authorization: Bearer {access_token}`

**Response:**
```json
{
  "success": true,
  "message": "Subscription renewal payment created successfully.",
  "data": {
    "payment": {
      "order_id": "SUB_DEF456GHI",
      "amount": 99.00,
      "currency": "INR",
      "status": "pending"
    },
    "checkout_url": "https://www.suganta.in/api/v1/payment/checkout?order_id=SUB_DEF456GHI",
    "payment_session_id": "session_def456",
    "subscription": {
      // Current subscription details
    }
  }
}
```

## Payment Flow

The subscription payment flow now works exactly like the registration payment system for consistency and reliability.

### 1. User Purchases Subscription
1. User calls `/api/v1/subscriptions/purchase` with `subscription_plan_id`
2. API checks for existing active subscriptions and validates user
3. API creates or reuses existing payment record and Cashfree order
4. API returns `checkout_url` for payment (same as registration payments)
5. User is redirected to Cashfree payment page via `/api/v1/payment/checkout?order_id=SUB_XXXXX`

### 2. Payment Processing
1. User completes payment on Cashfree hosted page
2. Cashfree sends webhook to `/api/v1/payment/webhook`
3. API processes the webhook and creates/activates subscription automatically
4. User is redirected back to the application with payment status

### 3. Payment Verification
- Users can check payment status via `/api/v1/payments/`
- Subscription status can be checked via `/api/v1/subscriptions/current`
- Payment checkout uses the same proxy system as registration payments

### 4. Key Improvements
- **Consistent Flow**: Now matches registration payment flow exactly
- **Reuse Logic**: Reuses existing pending payments instead of creating duplicates
- **Error Handling**: Same error structure and handling as registration payments
- **Proxy Support**: Uses the same checkout proxy system for better reliability

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## Common HTTP Status Codes

- `200` - Success
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (missing or invalid token)
- `403` - Forbidden (access denied)
- `404` - Not Found
- `409` - Conflict (e.g., user already has active subscription)
- `500` - Internal Server Error

## Authentication

All protected endpoints require a valid Bearer token in the Authorization header:

```
Authorization: Bearer {access_token}
```

Tokens can be obtained through the authentication endpoints (`/api/v1/auth/login`).

## Subscription Types

The `s_type` parameter allows for different types of subscriptions:
- **`1`** - Portfolio plans (controls portfolio upload limits: max_images, max_files)
- **`2`** - Note download plans

**Free plan requirement (s_type = 1):** Users without an active Portfolio subscription can only see and use plans where `price = 0`. The free plan (s_type=1, price=0) defines default portfolio upload limits. Ensure a free plan exists in `subscription_plans` for users who haven't subscribed.

## Webhook Security

The payment webhook endpoint (`/api/v1/payment/webhook`) is secured with HMAC-SHA256 signature verification to ensure requests are genuine Cashfree webhooks.

## Testing

For testing in sandbox mode:
1. Set `CASHFREE_IS_PRODUCTION=false` in your environment
2. Use Cashfree sandbox credentials
3. Use test payment methods provided by Cashfree

## Frontend Integration Example

```javascript
// Get subscription plans
const plans = await fetch('/api/v1/subscriptions/plans').then(r => r.json());

// Purchase subscription
const purchase = await fetch('/api/v1/subscriptions/purchase', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    subscription_plan_id: 1
  })
}).then(r => r.json());

// Redirect to payment page (uses proxy checkout system)
if (purchase.success) {
  window.location.href = purchase.data.checkout_url;
  // This will redirect to: /api/v1/payment/checkout?order_id=SUB_XXXXX
  // Which then loads the Cashfree payment page
}

// Check current subscription
const current = await fetch('/api/v1/subscriptions/current', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
}).then(r => r.json());
```