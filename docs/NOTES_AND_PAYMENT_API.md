# Notes & Payment API — Complete Documentation

Complete API for purchasing individual notes and subscription plans (s_type=1) with Cashfree payment integration.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Error Handling](#error-handling)
5. [Notes API (V2)](#notes-api-v2)
6. [Subscription API (V1)](#subscription-api-v1)
7. [Payment API](#payment-api)
8. [Payment Flow — Step by Step](#payment-flow--step-by-step)
9. [Client Implementation Guide](#client-implementation-guide)
10. [Access Logic](#access-logic)

---

## Overview

| API | Base Path | Auth Required |
|-----|-----------|----------------|
| Notes | `/api/v2/notes` | Yes (all routes) |
| Subscriptions | `/api/v1/subscriptions` | Plans: No, Others: Yes |
| Payments | `/api/v1/payments` | Yes |
| Payment Checkout | `/api/v1/payment` | No (public) |

**Payment Gateway:** Cashfree (hosted checkout)

---

## Authentication

All Notes API (V2) and Payment API routes require Bearer token authentication.

```http
Authorization: Bearer {your_sanctum_token}
Content-Type: application/json
```

**Obtain token:** Use `/api/v1/auth/login` or `/api/v1/auth/register`.

---

## Response Format

### Success Response

```json
{
  "message": "Operation completed successfully.",
  "success": true,
  "code": 200,
  "data": { ... }
}
```

### Error Response

```json
{
  "message": "Error description.",
  "success": false,
  "code": 400,
  "errors": { "field": ["Validation message"] }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized (missing/invalid token) |
| 403 | Forbidden (no access) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## Error Handling

| Scenario | Response |
|----------|----------|
| Missing `order_id` | 400 — "Missing order_id parameter." |
| Payment not found | 404 — "Payment not found or access denied." |
| Note not found | 404 — "Note not found or inactive." |
| Already purchased | 200 — `status: "already_paid"` in data |
| No access to download | 403 — "You must purchase this note or have an active subscription to download it." |
| Payment system not configured | 400 — "Payment system is not configured. Please contact the administrator." |
| Email not verified | 400 — "Please verify your email address before purchasing notes." |

---

## Notes API (V2)

**Base URL:** `{APP_URL}/api/v2/notes`  
**Auth:** Required for all routes

---

### 1. List Notes

**GET** `/api/v2/notes`

| Parameter | Type | Required | Description |
|-----------|------|----------|--------------|
| `category_id` | integer | No | Filter by note category ID |
| `note_type_id` | integer | No | Filter by note type ID |
| `search` | string | No | Search in name and description |
| `is_paid` | boolean | No | `true` = paid only, `false` = free only |
| `per_page` | integer | No | Items per page (1–50, default: 15) |

**Example Request:**
```http
GET /api/v2/notes?category_id=2&search=physics&per_page=20
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "message": "Notes retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Physics Notes - Chapter 1",
        "description": "Complete notes for physics chapter 1",
        "price": 99.00,
        "is_paid": true,
        "is_active": true,
        "download_count": 45,
        "file_url": null,
        "file_size": "2.5 MB",
        "formatted_price": "₹99.00",
        "can_access": false,
        "is_purchased": false,
        "has_subscription_access": false,
        "created_at": "2025-03-15T10:00:00.000000Z",
        "updated_at": "2025-03-15T10:00:00.000000Z",
        "note_type": { "id": 1, "code": "pdf", "name": "PDF" },
        "note_category": { "id": 2, "name": "Physics", "slug": "physics" }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 72,
      "from": 1,
      "to": 15
    },
    "links": {
      "first": "https://api.example.com/api/v2/notes?page=1",
      "last": "https://api.example.com/api/v2/notes?page=5",
      "prev": null,
      "next": "https://api.example.com/api/v2/notes?page=2"
    }
  }
}
```

**Note:** `file_url` is `null` when `can_access` is `false` (user has not purchased and has no subscription).

---

### 2. Get Single Note

**GET** `/api/v2/notes/{id}`

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Note ID (path parameter) |

**Success Response (200):**
```json
{
  "message": "Note retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "note": { ... }
  }
}
```

---

### 3. Get Note Categories

**GET** `/api/v2/notes/categories`

**Success Response (200):**
```json
{
  "message": "Note categories retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "categories": [
      { "id": 1, "name": "Mathematics", "slug": "mathematics", "description": "Math notes" },
      { "id": 2, "name": "Physics", "slug": "physics", "description": "Physics notes" }
    ]
  }
}
```

---

### 4. Get Note Types

**GET** `/api/v2/notes/types`

**Success Response (200):**
```json
{
  "message": "Note types retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "types": [
      { "id": 1, "code": "pdf", "name": "PDF", "description": "PDF documents" }
    ]
  }
}
```

---

### 5. Check Access

**GET** `/api/v2/notes/{id}/check-access`

**Success Response (200):**
```json
{
  "message": "Access check result.",
  "success": true,
  "code": 200,
  "data": {
    "note_id": 1,
    "can_access": true,
    "is_purchased": false,
    "has_subscription_access": true,
    "message": "You have access to this note."
  }
}
```

---

### 6. Purchase Note

**POST** `/api/v2/notes/purchase`

| Parameter | Type | Required | Description |
|-----------|------|----------|--------------|
| `note_id` | integer | Yes | ID of the note to purchase |

**Request Body:**
```json
{
  "note_id": 1
}
```

**Success Response — Free Note (200):**
```json
{
  "message": "This note is free. No payment required.",
  "success": true,
  "code": 200,
  "data": {
    "note": { ... },
    "payment_required": false
  }
}
```

**Success Response — Already Purchased (200):**
```json
{
  "message": "You have already purchased this note.",
  "success": true,
  "code": 200,
  "data": {
    "order_id": null,
    "status": "already_paid",
    "note": { ... }
  }
}
```

**Success Response — Payment Created (200):**
```json
{
  "message": "Note purchase payment created successfully.",
  "success": true,
  "code": 200,
  "data": {
    "payment": {
      "order_id": "NOTE_ABC123XYZ",
      "amount": 99.00,
      "currency": "INR",
      "status": "pending"
    },
    "checkout_url": "https://yoursite.com/api/v1/payment/checkout?order_id=NOTE_ABC123XYZ",
    "payment_session_id": "session_xxx",
    "note": { ... }
  }
}
```

**Error Response — Validation (422):**
```json
{
  "message": "Validation failed",
  "success": false,
  "code": 422,
  "errors": {
    "note_id": ["The selected note is not available or inactive."]
  }
}
```

---

### 7. Download Note

**GET** `/api/v2/notes/{id}/download`

**Headers:** `Authorization: Bearer {token}`

**Success:** Returns file stream (binary download).

**Error (403):** User does not have access.

---

### 8. My Purchases

**GET** `/api/v2/notes/my-purchases`

| Parameter | Type | Required | Description |
|-----------|------|----------|--------------|
| `status` | string | No | Filter: `completed`, `pending`, `failed`, `refunded` |
| `per_page` | integer | No | Items per page (1–50, default: 15) |

**Success Response (200):**
```json
{
  "message": "Your note purchases retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 10,
        "note_id": 5,
        "amount": 99.00,
        "status": "completed",
        "download_count": 2,
        "purchased_at": "2025-03-17T10:30:00.000000Z",
        "can_download": true,
        "note": { ... },
        "payment": { ... }
      }
    ],
    "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 3, "from": 1, "to": 3 },
    "links": { "first": "...", "last": "...", "prev": null, "next": null }
  }
}
```

---

## Subscription API (V1)

**Base URL:** `{APP_URL}/api/v1/subscriptions`

---

### 1. List Plans (Public)

**GET** `/api/v1/subscriptions/plans`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `s_type` | integer | No | Plan type (default: 1 = Notes plans) |

**Success Response (200):**
```json
{
  "message": "Subscription plans retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "Monthly Plan",
        "slug": "monthly-plan",
        "description": "Unlimited notes access",
        "price": 299.00,
        "currency": "INR",
        "billing_period": "monthly",
        "max_images": 10,
        "max_files": 5,
        "features": ["Unlimited notes", "Priority support"],
        "is_popular": true,
        "is_active": true,
        "sort_order": 1,
        "s_type": 1,
        "formatted_price": "INR 299.00"
      }
    ]
  }
}
```

---

### 2. Purchase Subscription (Auth Required)

**POST** `/api/v1/subscriptions/purchase`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscription_plan_id` | integer | Yes | Plan ID |

**Request Body:**
```json
{
  "subscription_plan_id": 1
}
```

**Success Response (200):**
```json
{
  "message": "Subscription payment created successfully.",
  "success": true,
  "code": 200,
  "data": {
    "payment": {
      "order_id": "SUB_XYZ789ABC",
      "amount": 299.00,
      "currency": "INR",
      "status": "pending"
    },
    "checkout_url": "https://yoursite.com/api/v1/payment/checkout?order_id=SUB_XYZ789ABC",
    "payment_session_id": "session_xxx",
    "subscription_plan": { ... }
  }
}
```

---

### 3. My Subscriptions (Auth Required)

**GET** `/api/v1/subscriptions/my-subscriptions`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | No | Filter: `active`, `cancelled`, `expired` |
| `s_type` | integer | No | Filter by plan type |
| `per_page` | integer | No | Items per page (1–50) |

---

### 4. Current Subscription (Auth Required)

**GET** `/api/v1/subscriptions/current?s_type=1`

---

## Payment API

**Base URL:** `{APP_URL}/api/v1/payments`  
**Auth:** Required (except checkout/callback/webhook)

---

### 1. Payment Status (Polling)

**GET** `/api/v1/payments/status`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_id` | string | Yes | Order ID from purchase response |

**Success Response (200):**
```json
{
  "message": "Payment status retrieved.",
  "success": true,
  "code": 200,
  "data": {
    "order_id": "NOTE_ABC123XYZ",
    "status": "success",
    "type": "note",
    "amount": 99.00,
    "currency": "INR",
    "processed_at": "2025-03-17T10:35:00.000000Z"
  }
}
```

**Status values:** `created`, `pending`, `success`, `failed`, `cancelled`, `refunded`

---

### 2. Payment History

**GET** `/api/v1/payments`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | No | Filter by status |
| `per_page` | integer | No | Items per page (1–50) |

---

### 3. Checkout (Public — No Auth)

**GET** `/api/v1/payment/checkout?order_id={order_id}`

Opens Cashfree hosted payment page. Used as `checkout_url` from purchase responses.

**Flow:** Client opens this URL in browser/webview → User pays on Cashfree → Cashfree redirects to callback.

---

### 4. Callback (Public — No Auth)

**GET** `/api/v1/payment/callback?order_id={order_id}`

Cashfree redirects here after payment. Returns JSON:

```json
{
  "message": "Payment successful.",
  "success": true,
  "code": 200,
  "data": {
    "order_id": "NOTE_ABC123XYZ",
    "status": "success"
  }
}
```

---

## Payment Flow — Step by Step

### Individual Note Purchase

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐     ┌──────────────┐
│   Client    │     │  Your API    │     │  Cashfree   │     │  Webhook     │
└──────┬──────┘     └──────┬───────┘     └──────┬──────┘     └──────┬───────┘
       │                   │                    │                   │
       │ 1. POST /notes/   │                    │                   │
       │    purchase       │                    │                   │
       │──────────────────>│                    │                   │
       │                   │                    │                   │
       │ 2. Response with  │                    │                   │
       │    checkout_url   │                    │                   │
       │<──────────────────│                    │                   │
       │                   │                    │                   │
       │ 3. Open checkout_url in browser/webview                    │
       │───────────────────────────────────────>│                   │
       │                   │                    │                   │
       │                   │  4. User pays      │                   │
       │                   │<──────────────────>│                   │
       │                   │                    │                   │
       │                   │  5. Webhook (async) │                   │
       │                   │<──────────────────────────────────────│
       │                   │                    │                   │
       │  6. Redirect to callback?order_id=XXX │                   │
       │<──────────────────────────────────────│                   │
       │                   │                    │                   │
       │ 7. Poll GET /payments/status?order_id=XXX (optional)        │
       │──────────────────>│                    │                   │
       │                   │                    │                   │
       │ 8. GET /notes/{id}/download           │                   │
       │──────────────────>│                    │                   │
       │<──────────────────│                    │                   │
```

**Steps:**

1. **Initiate:** `POST /api/v2/notes/purchase` with `{ "note_id": 1 }`
2. **Store:** Save `order_id` and `checkout_url` from response
3. **Redirect:** Open `checkout_url` in browser or WebView
4. **User pays** on Cashfree
5. **Callback:** Cashfree redirects to `/api/v1/payment/callback?order_id=XXX` — parse JSON for `status`
6. **Optional:** Poll `GET /api/v1/payments/status?order_id=XXX` until `status === "success"`
7. **Download:** Call `GET /api/v2/notes/{id}/download` when access is granted

---

### Subscription Purchase

Same flow as note purchase, but:

- **Initiate:** `POST /api/v1/subscriptions/purchase` with `{ "subscription_plan_id": 1 }`
- **Order ID prefix:** `SUB_` (notes use `NOTE_`)
- **On success:** User gets access to all paid notes (no per-note purchase needed)

---

## Client Implementation Guide

### Web (SPA / React / Vue)

```javascript
// 1. Initiate purchase
const res = await fetch('/api/v2/notes/purchase', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({ note_id: 1 }),
});
const { data } = await res.json();

if (data.payment_required === false) {
  // Free note — show/download immediately
  return;
}
if (data.status === 'already_paid') {
  // Already purchased — allow download
  return;
}

// 2. Open checkout in new window/tab
const checkoutWindow = window.open(data.checkout_url, '_blank', 'width=500,height=600');

// 3. Poll for completion (or use postMessage if you control callback page)
const orderId = data.payment.order_id;
const pollInterval = setInterval(async () => {
  const statusRes = await fetch(`/api/v1/payments/status?order_id=${orderId}`, {
    headers: { 'Authorization': `Bearer ${token}` },
  });
  const { data: statusData } = await statusRes.json();
  if (statusData.status === 'success') {
    clearInterval(pollInterval);
    checkoutWindow?.close();
    // Refresh UI, enable download
  }
  if (['failed', 'cancelled'].includes(statusData.status)) {
    clearInterval(pollInterval);
    checkoutWindow?.close();
    // Show error
  }
}, 3000);
```

### Mobile (React Native / Flutter)

1. Use **WebView** to open `checkout_url`
2. Configure WebView to intercept redirect to callback URL
3. Parse `order_id` from URL, call `GET /payments/status?order_id=XXX`
4. On `success`, close WebView and refresh purchase state

### Deep Link / Custom URL Scheme

If callback uses a custom scheme (e.g. `myapp://payment/callback?order_id=XXX`):

1. Configure Cashfree return URL to your deep link
2. App opens on redirect
3. Extract `order_id`, poll status, then allow download

---

## Access Logic

| Condition | Can Access Note |
|-----------|-----------------|
| Note is free (`is_paid` = false) | Yes |
| User purchased the note | Yes |
| User has active subscription (s_type=1) | Yes |
| None of the above | No |

**Subscription types:** `s_type = 1` = Notes/Study material plans.

---

## Quick Reference

| Action | Method | Endpoint |
|--------|--------|----------|
| List notes | GET | `/api/v2/notes` |
| Get note | GET | `/api/v2/notes/{id}` |
| Categories | GET | `/api/v2/notes/categories` |
| Types | GET | `/api/v2/notes/types` |
| Check access | GET | `/api/v2/notes/{id}/check-access` |
| Purchase note | POST | `/api/v2/notes/purchase` |
| Download note | GET | `/api/v2/notes/{id}/download` |
| My purchases | GET | `/api/v2/notes/my-purchases` |
| List plans | GET | `/api/v1/subscriptions/plans?s_type=1` |
| Purchase plan | POST | `/api/v1/subscriptions/purchase` |
| Payment status | GET | `/api/v1/payments/status?order_id=XXX` |
| Checkout | GET | `/api/v1/payment/checkout?order_id=XXX` |
