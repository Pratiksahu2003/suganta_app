# Study Requirement API

Endpoints for managing study requirements (tutoring/learning requests) and connecting teachers to requirements. All endpoints require authentication.

**Base path**: `/api/v1`  
**Auth**: All endpoints require Bearer token (Sanctum)

---

## Overview

Study requirements represent tutoring or learning requests posted by students or parents. Teachers can browse requirements and **connect** to express interest. Each connection creates a `RequirementConnected` record linking the teacher to the requirement.

---

## Endpoints Summary

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/study-requirements` | List study requirements (paginated, includes `is_connected`) | Authenticated user |
| POST | `/study-requirements` | Create a new study requirement | Authenticated user |
| GET | `/study-requirements/my-connections` | List requirements the auth user has connected to (paginated) | Authenticated user |
| GET | `/study-requirements/{studyRequirement}` | Get a single study requirement (includes `is_connected`, `connected_users`) | Authenticated user |
| POST | `/study-requirements/{studyRequirement}/connect` | Connect (express interest) on a requirement | Authenticated user |

---

## 1. List Study Requirements

| | |
|---|---|
| **Endpoint** | `GET /api/v1/study-requirements` |
| **Content-Type** | — |
| **Access** | Protected (auth:sanctum) |

Paginated list of all study requirements. Ordered by `created_at` descending. Supports filtering by status, learning mode, and search. Each item includes `is_connected` (boolean): whether the authenticated user has connected to this requirement.

### Query Parameters

| Parameter | Type | Required | Default | Validation | Description |
|-----------|------|----------|---------|------------|-------------|
| status | string | No | — | See status options | Filter by requirement status |
| learning_mode | string | No | — | online, offline, both | Filter by learning mode |
| search | string | No | — | — | Search in reference_id, contact_name, student_name, location_city |
| per_page | integer | No | 15 | max: 50 | Items per page |
| page | integer | No | 1 | — | Page number |

### Example Request

```
GET /api/v1/study-requirements
GET /api/v1/study-requirements?status=new&per_page=20
GET /api/v1/study-requirements?search=Mumbai&learning_mode=online
GET /api/v1/study-requirements?status=in_review&per_page=10&page=2
```

### Success (200)

```json
{
  "message": "Study requirements retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "data": [
      {
        "id": 1,
        "reference_id": "REQ-202503120000001",
        "user_id": 5,
        "contact_role": "parent",
        "contact_name": "Rahul Sharma",
        "contact_email": "rahul@example.com",
        "contact_phone": "+919876543210",
        "is_contact_verified": false,
        "verified_at": null,
        "student_name": "Priya Sharma",
        "student_grade": "Class 10",
        "subjects": ["Mathematics", "Physics"],
        "learning_mode": "online",
        "preferred_days": "Mon, Wed, Fri",
        "preferred_time": "4:00 PM - 6:00 PM",
        "location_city": "Mumbai",
        "location_state": "Maharashtra",
        "location_area": "Andheri West",
        "location_pincode": "400058",
        "budget_min": "500.00",
        "budget_max": "1500.00",
        "requirements": "Need help with Algebra and Trigonometry",
        "status": "new",
        "meta": null,
        "created_at": "2025-03-12T10:00:00.000000Z",
        "updated_at": "2025-03-12T10:00:00.000000Z",
        "is_connected": false,
        "user": {
          "id": 5,
          "name": "Rahul Sharma",
          "email": "rahul@example.com"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 67,
      "from": 1,
      "to": 15
    },
    "links": {
      "first": "http://localhost/api/v1/study-requirements?page=1",
      "last": "http://localhost/api/v1/study-requirements?page=5",
      "prev": null,
      "next": "http://localhost/api/v1/study-requirements?page=2"
    }
  }
}
```

---

## 2. Create Study Requirement

| | |
|---|---|
| **Endpoint** | `POST /api/v1/study-requirements` |
| **Content-Type** | `application/json` |
| **Access** | Protected (auth:sanctum) |

Creates a new study requirement. The authenticated user's ID is stored as `user_id`. A unique `reference_id` (e.g. `REQ-20250312-000001`) is auto-generated.

### Request Parameters

| Parameter | Type | Required | Validation | Description |
|-----------|------|----------|------------|-------------|
| contact_role | string | **Yes** | in:student,parent | Role of the contact person |
| contact_name | string | **Yes** | max:255 | Contact person name |
| contact_email | string | **Yes** | email, max:255 | Contact email |
| contact_phone | string | **Yes** | max:30 | Contact phone number |
| student_name | string | No | max:255 | Student name (if different from contact) |
| student_grade | string | No | max:100 | Grade or class level |
| subjects | array | No | array of strings, max:255 each | Subjects needed (e.g. ["Mathematics", "Physics"]) |
| learning_mode | string | No | in:online,offline,both | Default: `both` |
| preferred_days | string | No | max:255 | Preferred days (e.g. "Mon, Wed, Fri") |
| preferred_time | string | No | max:255 | Preferred time slot |
| location_city | string | No | max:255 | City |
| location_state | string | No | max:255 | State |
| location_area | string | No | max:255 | Area/locality |
| location_pincode | string | No | max:12 | PIN code |
| budget_min | number | No | min:0 | Minimum budget (per session) |
| budget_max | number | No | min:0, gte:budget_min | Maximum budget (per session) |
| requirements | string | No | max:5000 | Additional requirements or notes |

### Example Request Body

```json
{
  "contact_role": "parent",
  "contact_name": "Rahul Sharma",
  "contact_email": "rahul@example.com",
  "contact_phone": "+919876543210",
  "student_name": "Priya Sharma",
  "student_grade": "Class 10",
  "subjects": ["Mathematics", "Physics"],
  "learning_mode": "online",
  "preferred_days": "Mon, Wed, Fri",
  "preferred_time": "4:00 PM - 6:00 PM",
  "location_city": "Mumbai",
  "location_state": "Maharashtra",
  "location_area": "Andheri West",
  "location_pincode": "400058",
  "budget_min": 500,
  "budget_max": 1500,
  "requirements": "Need help with Algebra and Trigonometry for board exam preparation."
}
```

### Success (201)

```json
{
  "message": "Study requirement created successfully.",
  "success": true,
  "code": 201,
  "data": {
    "id": 1,
    "reference_id": "REQ-202503120000001",
    "user_id": 5,
    "contact_role": "parent",
    "contact_name": "Rahul Sharma",
    "contact_email": "rahul@example.com",
    "contact_phone": "+919876543210",
    "student_name": "Priya Sharma",
    "student_grade": "Class 10",
    "subjects": ["Mathematics", "Physics"],
    "learning_mode": "online",
    "status": "new",
    "created_at": "2025-03-12T10:00:00.000000Z",
    "updated_at": "2025-03-12T10:00:00.000000Z",
    "user": {
      "id": 5,
      "name": "Rahul Sharma",
      "email": "rahul@example.com"
    }
  }
}
```

### Error (422) – Validation

```json
{
  "message": "Validation failed.",
  "success": false,
  "code": 422,
  "errors": {
    "contact_name": ["The contact name field is required."],
    "contact_email": ["The contact email field is required."],
    "contact_role": ["The selected contact role is invalid."],
    "budget_max": ["The budget max must be greater than or equal to budget min."]
  }
}
```

---

## 3. Get Study Requirement

| | |
|---|---|
| **Endpoint** | `GET /api/v1/study-requirements/{studyRequirement}` |
| **Content-Type** | — |
| **Access** | Protected (auth:sanctum) |

Returns a single study requirement with creator (`user`) and connected users (`connected_users`). Includes `is_connected` (boolean): whether the auth user has connected to this requirement. Useful for viewing full details and who has expressed interest.

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| studyRequirement | integer | **Yes** | Study requirement ID |

### Success (200)

```json
{
  "message": "Study requirement retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "id": 1,
    "reference_id": "REQ-202503120000001",
    "user_id": 5,
    "contact_role": "parent",
    "contact_name": "Rahul Sharma",
    "contact_email": "rahul@example.com",
    "contact_phone": "+919876543210",
    "student_name": "Priya Sharma",
    "student_grade": "Class 10",
    "subjects": ["Mathematics", "Physics"],
    "learning_mode": "online",
    "preferred_days": "Mon, Wed, Fri",
    "preferred_time": "4:00 PM - 6:00 PM",
    "location_city": "Mumbai",
    "status": "new",
    "created_at": "2025-03-12T10:00:00.000000Z",
    "user": {
      "id": 5,
      "name": "Rahul Sharma",
      "email": "rahul@example.com"
    },
    "is_connected": false,
    "connected_users": [
      {
        "id": 1,
        "requirement_id": 1,
        "user_id": 8,
        "status": "pending",
        "message": "I have 5 years of experience teaching Mathematics.",
        "connected_at": "2025-03-12T11:00:00.000000Z",
        "user": {
          "id": 8,
          "name": "Teacher Kumar",
          "email": "kumar@example.com"
        }
      }
    ]
  }
}
```

### Error (404)

```json
{
  "message": "Resource not found.",
  "success": false,
  "code": 404
}
```

---

## 4. My Connected Requirements

| | |
|---|---|
| **Endpoint** | `GET /api/v1/study-requirements/my-connections` |
| **Content-Type** | — |
| **Access** | Protected (auth:sanctum) |

Paginated list of requirements the authenticated user has connected to. Each item includes the connection metadata (status, message, connected_at) and the full requirement. Ordered by `connected_at` descending.

### Query Parameters

| Parameter | Type | Required | Default | Validation | Description |
|-----------|------|----------|---------|------------|-------------|
| status | string | No | — | pending, accepted, rejected | Filter by connection status |
| per_page | integer | No | 15 | max: 50 | Items per page |
| page | integer | No | 1 | — | Page number |

### Example Request

```
GET /api/v1/study-requirements/my-connections
GET /api/v1/study-requirements/my-connections?status=pending&per_page=20
```

### Success (200)

```json
{
  "message": "Your connected requirements retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "data": [
      {
        "id": 1,
        "requirement_id": 5,
        "status": "pending",
        "message": "I have experience in Mathematics.",
        "connected_at": "2025-03-12T11:00:00.000000Z",
        "requirement": {
          "id": 5,
          "reference_id": "REQ-202503120000005",
          "user_id": 3,
          "contact_name": "Priya Singh",
          "student_name": "Aarav Singh",
          "subjects": ["Mathematics"],
          "learning_mode": "online",
          "status": "new",
          "user": {
            "id": 3,
            "name": "Priya Singh",
            "email": "priya@example.com"
          }
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 15,
      "total": 18,
      "from": 1,
      "to": 15
    },
    "links": {
      "first": "http://localhost/api/v1/study-requirements/my-connections?page=1",
      "last": "http://localhost/api/v1/study-requirements/my-connections?page=2",
      "prev": null,
      "next": "http://localhost/api/v1/study-requirements/my-connections?page=2"
    }
  }
}
```

---

## 5. Connect to Requirement

| | |
|---|---|
| **Endpoint** | `POST /api/v1/study-requirements/{studyRequirement}/connect` |
| **Content-Type** | `application/json` |
| **Access** | Protected (auth:sanctum) |

Allows the authenticated user (e.g. a teacher) to express interest in a study requirement. Creates a `RequirementConnected` record with status `pending`.

### Business Rules

- **Cannot connect to own requirement**: If the requirement was created by the authenticated user, the request fails.
- **Requirement must accept connections**: Only requirements with status `new` or `in_review` accept connections. `matched` or `closed` requirements reject connections.
- **One connection per user**: Each user can connect to a requirement only once. Duplicate attempts are rejected.

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| studyRequirement | integer | **Yes** | Study requirement ID |

### Request Parameters

| Parameter | Type | Required | Validation | Description |
|-----------|------|----------|------------|-------------|
| message | string | No | max:2000 | Optional message to the requirement owner (e.g. introduction, experience) |

### Example Request Body

```json
{
  "message": "I have 5+ years of experience teaching Mathematics at Class 10 level. I specialize in Algebra and Trigonometry. Available Mon, Wed, Fri."
}
```

### Success (201)

```json
{
  "message": "Successfully connected to the requirement.",
  "success": true,
  "code": 201,
  "data": {
    "id": 1,
    "requirement_id": 1,
    "user_id": 8,
    "status": "pending",
    "message": "I have 5+ years of experience teaching Mathematics at Class 10 level.",
    "connected_at": "2025-03-12T11:00:00.000000Z",
    "created_at": "2025-03-12T11:00:00.000000Z",
    "updated_at": "2025-03-12T11:00:00.000000Z",
    "requirement": {
      "id": 1,
      "reference_id": "REQ-202503120000001",
      "contact_name": "Rahul Sharma",
      "student_name": "Priya Sharma",
      "subjects": ["Mathematics", "Physics"],
      "status": "new"
    },
    "user": {
      "id": 8,
      "name": "Teacher Kumar",
      "email": "kumar@example.com"
    }
  }
}
```

### Error (422) – Business Logic

**Own requirement:**
```json
{
  "message": "You cannot connect to your own requirement.",
  "success": false,
  "code": 422
}
```

**Requirement closed/matched:**
```json
{
  "message": "This requirement is no longer accepting connections.",
  "success": false,
  "code": 422
}
```

**Already connected:**
```json
{
  "message": "You have already connected to this requirement.",
  "success": false,
  "code": 422
}
```

---

## Valid Values Reference

### Requirement Status

| Value | Description |
|-------|-------------|
| new | Newly created, accepting connections |
| in_review | Being reviewed, still accepting connections |
| matched | Matched with a teacher, no new connections |
| closed | Requirement closed |

### Contact Role

| Value | Description |
|-------|-------------|
| student | Contact is the student |
| parent | Contact is a parent/guardian |

### Learning Mode

| Value | Description |
|-------|-------------|
| online | Online tutoring only |
| offline | In-person only |
| both | Both online and offline acceptable |

### Connection Status (RequirementConnected)

| Value | Description |
|-------|-------------|
| pending | Connection pending review |
| accepted | Connection accepted by requirement owner |
| rejected | Connection rejected |

---

## Data Models

### StudyRequirement

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| reference_id | string | Unique reference (e.g. REQ-20250312-000001) |
| user_id | integer \| null | Creator user ID |
| contact_role | string | student \| parent |
| contact_name | string | |
| contact_email | string | |
| contact_phone | string | |
| student_name | string \| null | |
| student_grade | string \| null | |
| subjects | array | Array of subject strings |
| learning_mode | string | online \| offline \| both |
| preferred_days | string \| null | |
| preferred_time | string \| null | |
| location_city | string \| null | |
| location_state | string \| null | |
| location_area | string \| null | |
| location_pincode | string \| null | |
| budget_min | decimal \| null | |
| budget_max | decimal \| null | |
| requirements | text \| null | Additional notes |
| status | string | new \| in_review \| matched \| closed |

### RequirementConnected

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| requirement_id | integer | Study requirement ID |
| user_id | integer | User who connected (teacher) |
| status | string | pending \| accepted \| rejected |
| message | text \| null | Optional message from connector |
| connected_at | datetime | When the connection was made |

---

## Example Requests

### List Requirements (cURL)

```bash
curl -X GET "https://api.example.com/api/v1/study-requirements?status=new&per_page=20" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Create Requirement (cURL)

```bash
curl -X POST "https://api.example.com/api/v1/study-requirements" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_role": "parent",
    "contact_name": "Rahul Sharma",
    "contact_email": "rahul@example.com",
    "contact_phone": "+919876543210",
    "student_name": "Priya Sharma",
    "student_grade": "Class 10",
    "subjects": ["Mathematics", "Physics"],
    "learning_mode": "online",
    "preferred_days": "Mon, Wed, Fri",
    "budget_min": 500,
    "budget_max": 1500,
    "requirements": "Need help with Algebra."
  }'
```

### Get Requirement (cURL)

```bash
curl -X GET "https://api.example.com/api/v1/study-requirements/1" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### My Connected Requirements (cURL)

```bash
curl -X GET "https://api.example.com/api/v1/study-requirements/my-connections" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Connect to Requirement (cURL)

```bash
curl -X POST "https://api.example.com/api/v1/study-requirements/1/connect" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"message": "I have experience in Mathematics and Physics."}'
```

### JavaScript (Fetch)

```javascript
// List
const listRes = await fetch('/api/v1/study-requirements?status=new', {
  headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
});

// Create
const createRes = await fetch('/api/v1/study-requirements', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    contact_role: 'parent',
    contact_name: 'Rahul Sharma',
    contact_email: 'rahul@example.com',
    contact_phone: '+919876543210',
    subjects: ['Mathematics'],
    learning_mode: 'online',
  }),
});

// Connect
const connectRes = await fetch('/api/v1/study-requirements/1/connect', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ message: 'I am interested in this requirement.' }),
});
```

---

## Error Codes Summary

| Code | Condition |
|------|-----------|
| 401 | Unauthenticated (missing or invalid token) |
| 404 | Study requirement not found (show, connect) |
| 422 | Validation failed or business rule violation (own requirement, already connected, requirement closed) |
| 500 | Server error |
