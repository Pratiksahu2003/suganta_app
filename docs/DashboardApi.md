# Dashboard API

Dashboard summary endpoints for authenticated users. The User Dashboard returns counts and data for the authenticated user. The Admin Dashboard returns system-wide totals and recent notifications for admins only.

**Base path**: `/api/v1`  
**Auth**: All endpoints require Bearer token (Sanctum)

---

## Endpoints Summary

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/dashboard` | User dashboard (counts, recent payments, recent leads, notifications, user info) | Any authenticated user |

---

## 1. User Dashboard

| | |
|---|---|
| **Endpoint** | `GET /api/v1/dashboard` |
| **Access** | Protected (auth:sanctum) |

Returns counts of the authenticated user's support tickets, payments, leads, study requirements, and posts, plus last 5 payment records, **latest 5 leads**, 10 latest notifications, and user profile info.

### Counts (User-Scoped)

| Count | Description |
|-------|-------------|
| support_tickets | User's own support tickets |
| payments | User's own payment records |
| leads | Leads owned by, assigned to, or created by user |
| study_requirements | Study requirements submitted by user |
| posts | Blog posts authored by user |

### Success (200)

```json
{
  "message": "Dashboard retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "counts": {
      "support_tickets": 3,
      "payments": 12,
      "leads": 5,
      "study_requirements": 2,
      "posts": 0
    },
    "recent_payments": [
      {
        "id": 1,
        "order_id": "ORD_xxx123",
        "currency": "INR",
        "amount": 500.00,
        "status": "success",
        "type": "registration",
        "description": "Registration fee",
        "created_at": "2025-03-11T10:30:00.000000Z",
        "processed_at": "2025-03-11T10:31:00.000000Z"
      }
    ],
    "recent_leads": [
      {
        "id": 1,
        "lead_id": "SUG-20250313-123456",
        "name": "Jane Smith",
        "email": "jane@example.com",
        "phone": "+919876543210",
        "type": "student",
        "status": "new",
        "priority": "medium",
        "subject_interest": "Mathematics",
        "created_at": "2025-03-13T09:15:00.000000Z"
      }
    ],
    "latest_notifications": [
      {
        "id": "9d8f7e6d-5c4b-3a2b-1a0e-9d8c7b6a5f4e",
        "title": "Support Ticket Reply",
        "message": "You have received a reply on your support ticket 'Login issue' from Support Team",
        "type": "support",
        "priority": "normal",
        "read_at": null,
        "created_at": "2025-03-11T12:00:00.000000Z"
      }
    ],
    "user": {
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+919876543210",
      "profile_pic": "https://example.com/storage/avatars/user.jpg"
    }
  }
}
```

### Error (401)

```json
{
  "message": "Unauthenticated."
}
```

---

## 2. Admin Dashboard

| | |
|---|---|
| **Endpoint** | `GET /api/v1/admin/dashboard` |
| **Access** | Protected (auth:sanctum, admin only) |

Returns system-wide counts for support tickets, payments, leads, study requirements, and posts, plus 10 latest notifications across all users. Each notification includes the recipient user's info (first_name, last_name, email, phone, profile_pic).

### Counts (System-Wide)

| Count | Description |
|-------|-------------|
| support_tickets | Total support tickets |
| payments | Total payment records |
| leads | Total leads |
| study_requirements | Total study requirements |
| posts | Total blog posts |

### Success (200)

```json
{
  "message": "Admin dashboard retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "counts": {
      "support_tickets": 25,
      "payments": 150,
      "leads": 42,
      "study_requirements": 18,
      "posts": 12
    },
    "latest_notifications": [
      {
        "id": "9d8f7e6d-5c4b-3a2b-1a0e-9d8c7b6a5f4e",
        "title": "Support Ticket Reply",
        "message": "You have received a reply on your support ticket 'Login issue' from Support Team",
        "type": "support",
        "priority": "normal",
        "read_at": null,
        "created_at": "2025-03-11T12:00:00.000000Z",
        "user": {
          "first_name": "John",
          "last_name": "Doe",
          "email": "john@example.com",
          "phone": "+919876543210",
          "profile_pic": "https://example.com/storage/avatars/user.jpg"
        }
      }
    ]
  }
}
```

### Error (401)

```json
{
  "message": "Unauthenticated."
}
```

### Error (403)

```json
{
  "message": "Access denied. Admin only.",
  "success": false,
  "code": 403
}
```

---

## Data Field Reference

### User Object

| Field | Type | Description |
|-------|------|-------------|
| first_name | string | From profile or parsed from name |
| last_name | string \| null | From profile or parsed from name |
| email | string | User email |
| phone | string \| null | User or profile primary phone |
| profile_pic | string | Avatar URL (or default placeholder) |

### Lead Object (Recent Leads)

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Lead ID |
| lead_id | string | Unique lead identifier (e.g. `SUG-20250313-123456`) |
| name | string | Lead contact name |
| email | string \| null | Lead email |
| phone | string | Lead phone number |
| type | string \| null | `student`, `parent`, `institute`, `teacher` |
| status | string | e.g. `new`, `contacted`, `qualified`, `lost` |
| priority | string \| null | `low`, `medium`, `high`, `urgent` |
| subject_interest | string \| null | Subject of interest |
| created_at | string | ISO 8601 datetime |

### Payment Object (Recent Payments)

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Payment ID |
| order_id | string | Order/reference ID |
| currency | string | e.g. `INR` |
| amount | float | Payment amount |
| status | string | e.g. `success`, `pending`, `failed` |
| type | string \| null | e.g. `registration`, `subscription` (from meta) |
| description | string \| null | Payment description (from meta) |
| created_at | string | ISO 8601 datetime |
| processed_at | string \| null | ISO 8601 datetime when processed |

### Notification Object

| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Notification ID |
| title | string | Notification title |
| message | string | Notification body |
| type | string | e.g. `support`, `payment`, `session` |
| priority | string | `low`, `normal`, `high` |
| read_at | string \| null | ISO 8601 datetime when read |
| created_at | string | ISO 8601 datetime |
| user | object \| null | *(Admin only)* Recipient user info |

---

## Example Requests

### User Dashboard (cURL)

```bash
curl -X GET "https://api.example.com/api/v1/dashboard" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Admin Dashboard (cURL)

```bash
curl -X GET "https://api.example.com/api/v1/admin/dashboard" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### JavaScript (Fetch)

```javascript
const response = await fetch('/api/v1/dashboard', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});
const { data } = await response.json();
console.log('Counts:', data.counts);
console.log('Recent payments:', data.recent_payments);
console.log('Recent leads:', data.recent_leads);
console.log('User:', data.user);
```

---

## Comparison

| Feature | User Dashboard | Admin Dashboard |
|---------|---------------|-----------------|
| **Counts** | User's own data | System-wide totals |
| **Recent payments** | Last 5 payment records | — |
| **Recent leads** | Last 5 leads (owned, assigned, or created) | — |
| **Notifications** | User's 10 latest | 10 latest from all users |
| **User info** | Current user only | Recipient user per notification |
| **Access** | Any authenticated user | Admin / Super-admin |
