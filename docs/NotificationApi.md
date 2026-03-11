# Notification API

Endpoints for retrieving the authenticated user's notifications with pagination and filtering.

**Base path**: `/api/v1`  
**Auth**: All endpoints require Bearer token (Sanctum)

---

## Endpoints Summary

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/notifications` | List user's notifications with pagination | Any authenticated user |

---

## 1. List Notifications

| | |
|---|---|
| **Endpoint** | `GET /api/v1/notifications` |
| **Access** | Protected (auth:sanctum) |

Returns paginated notifications for the authenticated user. Supports filtering by read/unread status.

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 15 | Items per page (max 50) |
| page | integer | 1 | Page number |
| filter | string | all | `all`, `read`, or `unread` |

### Success (200)

```json
{
  "message": "Notifications retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "data": [
      {
        "id": "9d8f7e6d-5c4b-3a2b-1a0e-9d8c7b6a5f4e",
        "title": "Support Ticket Reply",
        "message": "You have received a reply on your support ticket.",
        "type": "support",
        "priority": "normal",
        "action_url": null,
        "resource_type": "support_ticket",
        "resource_id": 42,
        "action": "view",
        "read_at": null,
        "created_at": "2025-03-11T12:00:00.000000Z"
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
      "first": "https://api.example.com/api/v1/notifications?page=1",
      "last": "https://api.example.com/api/v1/notifications?page=5",
      "prev": null,
      "next": "https://api.example.com/api/v1/notifications?page=2"
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

## Data Field Reference

### Notification Object

| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Notification ID |
| title | string \| null | Notification title |
| message | string \| null | Notification body |
| type | string \| null | e.g. `support`, `payment`, `session` |
| priority | string | `normal`, `low`, `high` |
| action_url | string \| null | Optional action URL |
| resource_type | string \| null | Resource for navigation (e.g. `support_ticket`, `session`) |
| resource_id | integer \| null | ID of the linked resource |
| action | string \| null | Action hint (e.g. `view`, `edit`) |
| read_at | string \| null | ISO 8601 datetime when read |
| created_at | string | ISO 8601 datetime |

For client-side navigation using resource metadata, see [Notification Architecture](./NOTIFICATION_ARCHITECTURE.md).

---

## Example Requests

### List all notifications (default)

```bash
curl -X GET "https://api.example.com/api/v1/notifications" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### List unread only, 20 per page

```bash
curl -X GET "https://api.example.com/api/v1/notifications?filter=unread&per_page=20" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### JavaScript (Fetch)

```javascript
const response = await fetch('/api/v1/notifications?filter=unread&per_page=20', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});
const { data } = await response.json();
console.log('Notifications:', data.data);
console.log('Pagination:', data.meta);
```
