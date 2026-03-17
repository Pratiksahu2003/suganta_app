# Review API V2 – Documentation

This document describes the V2 Review API for submitting (POST), listing, and managing user reviews. All endpoints require Sanctum authentication.

---

## 1. Overview

- **Base URL**: `/api/v2/reviews`
- **Auth**: `Authorization: Bearer {token}` (Sanctum)
- **Review target**: You are always reviewing a **user**:
  - **`reviewable_type`**: always `"user"`
  - **`reviewable_id`**: the target user's **`id`** (user_id)

All responses follow this structure:

```json
{
  "success": true,
  "code": 200,
  "message": "Message here",
  "data": { ... }
}
```

---

## 2. Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v2/reviews` | List all reviews for a user (latest 10/page, paginated) |
| `POST` | `/api/v2/reviews` | Submit a review |
| `GET` | `/api/v2/reviews/{id}` | Get a single review |
| `PUT/PATCH` | `/api/v2/reviews/{id}` | Update your review |
| `DELETE` | `/api/v2/reviews/{id}` | Delete your review |
| `GET` | `/api/v2/reviews/my` | Get your own reviews |
| `GET` | `/api/v2/reviews/stats` | Get rating stats for a user |
| `GET` | `/api/v2/reviews/check` | Check if you can review a user |
| `POST` | `/api/v2/reviews/{id}/helpful` | Mark a review as helpful |
| `POST` | `/api/v2/reviews/{id}/reply` | Reply to a review (reviewed user only) |
| `POST` | `/api/v2/reviews/{id}/report` | Report a review |

---

## 3. Review Resource Structure

Each review object in responses includes:

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Review ID |
| `rating` | integer | 1–5 stars |
| `title` | string | Review title |
| `comment` | string | Review text |
| `tags` | array | Array of tag strings |
| `is_verified` | boolean | Admin-verified |
| `helpful_count` | integer | Helpful votes count |
| `status` | string | `published`, `pending`, `rejected`, `hidden` |
| `reply` | string | Owner's reply |
| `replied_at` | string | ISO 8601 timestamp |
| `reviewed_at` | string | ISO 8601 timestamp |
| `created_at` | string | ISO 8601 timestamp |
| `updated_at` | string | ISO 8601 timestamp |
| `time_ago` | string | Human-readable (e.g. "2 hours ago") |
| `reviewer` | object | `{ id, name, avatar }` |
| `reviewable` | object | `{ type: "user", id, name }` |
| `permissions` | object | `{ can_edit, can_delete }` |

---

## 4. Endpoints Reference

### 4.1 List All Reviews for a User (Latest First, Paginated)

**Method**: `GET`  
**URL**: `/api/v2/reviews`

Returns all reviews for a user, latest first, 10 per page by default.

**Query Parameters**

| Parameter | Required | Type | Description |
|-----------|----------|------|-------------|
| `reviewable_type` | Yes | string | Must be `"user"` |
| `reviewable_id` | Yes | integer | Target user_id |
| `rating` | No | integer | Filter by rating (1–5) |
| `verified` | No | boolean | Filter verified reviews |
| `has_comment` | No | boolean | Filter reviews with comments |
| `search` | No | string | Search in title/comment |
| `sort` | No | string | `latest`, `oldest`, `highest`, `lowest`, `helpful` |
| `per_page` | No | integer | 1–50 (default 10) |

**Example Request**

```http
GET /api/v2/reviews?reviewable_type=user&reviewable_id=123&sort=latest&per_page=10
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "User reviews fetched successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "rating": 5,
        "title": "Great teacher",
        "comment": "Very clear explanation.",
        "tags": ["helpful"],
        "is_verified": false,
        "helpful_count": 3,
        "status": "published",
        "reply": null,
        "replied_at": null,
        "reviewed_at": "2026-03-17T10:00:00.000000Z",
        "created_at": "2026-03-17T10:00:00.000000Z",
        "updated_at": "2026-03-17T10:00:00.000000Z",
        "time_ago": "2 hours ago",
        "reviewer": {
          "id": 55,
          "name": "Student Name",
          "avatar": null
        },
        "reviewable": {
          "type": "user",
          "id": 123,
          "name": "Teacher Name"
        },
        "permissions": {
          "can_edit": false,
          "can_delete": false
        }
      }
    ],
    "current_page": 1,
    "per_page": 10,
    "total": 42,
    "last_page": 5
  }
}
```

---

### 4.2 POST – Submit a Review

**Method**: `POST` (required)  
**URL**: `/api/v2/reviews`

**Request Body**

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `reviewable_type` | Yes | string | Must be `"user"` |
| `reviewable_id` | Yes | integer | Target user_id |
| `rating` | Yes | integer | 1–5 stars |
| `title` | No | string | Max 255 chars |
| `comment` | No | string | Max 5000 chars |
| `tags` | No | array | Max 10 strings, each ≤ 50 chars |

**Example Request**

```http
POST /api/v2/reviews
Authorization: Bearer {token}
Content-Type: application/json

{
  "reviewable_type": "user",
  "reviewable_id": 123,
  "rating": 5,
  "title": "Great teacher",
  "comment": "Very helpful and clear.",
  "tags": ["helpful", "clear"]
}
```

**Success Response (201)**

```json
{
  "success": true,
  "code": 201,
  "message": "Thank you! Your review has been submitted successfully.",
  "data": {
    "id": 10,
    "rating": 5,
    "title": "Great teacher",
    "comment": "Very helpful and clear.",
    "tags": ["helpful", "clear"],
    "is_verified": false,
    "helpful_count": 0,
    "status": "published",
    "reply": null,
    "replied_at": null,
    "reviewed_at": "2026-03-17T10:00:00.000000Z",
    "created_at": "2026-03-17T10:00:00.000000Z",
    "updated_at": "2026-03-17T10:00:00.000000Z",
    "time_ago": "just now",
    "reviewer": "..." ,
    "reviewable": "...",
    "permissions": "..."
  }
}
```

**Error Responses**

| Code | Condition |
|------|-----------|
| 422 | Validation failed (missing/invalid fields) |
| 409 | You have already submitted a review for this user. You can edit your existing review instead. |
| 403 | You cannot review yourself. |

---

### 4.3 Get a Single Review

**Method**: `GET`  
**URL**: `/api/v2/reviews/{id}`

**Example Request**

```http
GET /api/v2/reviews/10
Authorization: Bearer {token}
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "Review retrieved successfully",
  "data": { /* ReviewResource */ }
}
```

---

### 4.4 Update Your Review

**Method**: `PUT` or `PATCH`  
**URL**: `/api/v2/reviews/{id}`

Only the **author** (same user_id) can update.

**Request Body** (any subset)

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `rating` | No | integer | 1–5 stars |
| `title` | No | string | Max 255 chars |
| `comment` | No | string | Max 5000 chars |
| `tags` | No | array | Max 10 strings |

**Example Request**

```http
PATCH /api/v2/reviews/10
Authorization: Bearer {token}
Content-Type: application/json

{
  "rating": 4,
  "comment": "After more time, still good."
}
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "Review updated successfully",
  "data": { /* ReviewResource */ }
}
```

**Error Responses**

| Code | Condition |
|------|-----------|
| 403 | Not your review |
| 422 | Validation error |

---

### 4.5 Delete Your Review

**Method**: `DELETE`  
**URL**: `/api/v2/reviews/{id}`

Only the **author** can delete.

**Example Request**

```http
DELETE /api/v2/reviews/10
Authorization: Bearer {token}
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "Review deleted successfully"
}
```

---

### 4.6 Get Your Own Reviews

**Method**: `GET`  
**URL**: `/api/v2/reviews/my`

**Query Parameters**

| Parameter | Required | Type | Description |
|-----------|----------|------|-------------|
| `status` | No | string | `published`, `pending`, `rejected`, `hidden` |
| `sort` | No | string | `latest`, `oldest`, `highest`, `lowest` |
| `per_page` | No | integer | 1–50 |

**Example Request**

```http
GET /api/v2/reviews/my?status=published&sort=latest
Authorization: Bearer {token}
```

**Success Response (200)** – Paginated list of reviews.

---

### 4.7 Get Rating Stats for a User

**Method**: `GET`  
**URL**: `/api/v2/reviews/stats`

**Query Parameters**

| Parameter | Required | Type | Description |
|-----------|----------|------|-------------|
| `reviewable_type` | Yes | string | Must be `"user"` |
| `reviewable_id` | Yes | integer | Target user_id |

**Example Request**

```http
GET /api/v2/reviews/stats?reviewable_type=user&reviewable_id=123
Authorization: Bearer {token}
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "Review statistics retrieved successfully",
  "data": {
    "total_reviews": 42,
    "average_rating": 4.6,
    "verified_count": 10,
    "total_helpful": 87,
    "distribution": [
      { "rating": 5, "count": 30, "percentage": 71.4 },
      { "rating": 4, "count": 8, "percentage": 19.0 },
      { "rating": 3, "count": 3, "percentage": 7.1 },
      { "rating": 2, "count": 1, "percentage": 2.4 },
      { "rating": 1, "count": 0, "percentage": 0.0 }
    ]
  }
}
```

---

### 4.8 Check If You Can Review a User

**Method**: `GET`  
**URL**: `/api/v2/reviews/check`

**Query Parameters**

| Parameter | Required | Type | Description |
|-----------|----------|------|-------------|
| `reviewable_type` | Yes | string | Must be `"user"` |
| `reviewable_id` | Yes | integer | Target user_id |

**Example Request**

```http
GET /api/v2/reviews/check?reviewable_type=user&reviewable_id=123
Authorization: Bearer {token}
```

**Success Response (200) – Can review**

```json
{
  "success": true,
  "code": 200,
  "message": "Review eligibility checked",
  "data": {
    "can_review": true,
    "has_reviewed": false,
    "existing_review": null
  }
}
```

**Success Response (200) – Already reviewed**

```json
{
  "success": true,
  "code": 200,
  "message": "Review eligibility checked",
  "data": {
    "can_review": false,
    "has_reviewed": true,
    "existing_review": { /* ReviewResource */ }
  }
}
```

---

### 4.9 Mark Review as Helpful

**Method**: `POST`  
**URL**: `/api/v2/reviews/{id}/helpful`

You cannot mark your own review as helpful.

**Example Request**

```http
POST /api/v2/reviews/10/helpful
Authorization: Bearer {token}
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "Review marked as helpful",
  "data": {
    "helpful_count": 5
  }
}
```

---

### 4.10 Reply to a Review

**Method**: `POST`  
**URL**: `/api/v2/reviews/{id}/reply`

Only the **reviewed user** (reviewable_id = user_id) can reply.

**Request Body**

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `reply` | Yes | string | Max 3000 chars |

**Example Request**

```http
POST /api/v2/reviews/10/reply
Authorization: Bearer {token}
Content-Type: application/json

{
  "reply": "Thank you for your feedback!"
}
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "Reply added successfully",
  "data": { /* ReviewResource with reply */ }
}
```

**Error Responses**

| Code | Condition |
|------|-----------|
| 403 | Not the reviewed user |

---

### 4.11 Report a Review

**Method**: `POST`  
**URL**: `/api/v2/reviews/{id}/report`

You cannot report your own review.

**Request Body**

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `reason` | Yes | string | Max 1000 chars |

**Example Request**

```http
POST /api/v2/reviews/10/report
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Inappropriate language"
}
```

**Success Response (200)**

```json
{
  "success": true,
  "code": 200,
  "message": "Review reported successfully. Our team will review it.",
  "data": {
    "review_id": 10,
    "reported_by": 55,
    "reason": "Inappropriate language",
    "reported_at": "2026-03-17T10:30:00.000000Z"
  }
}
```

**Error Responses**

| Code | Condition |
|------|-----------|
| 422 | Cannot report your own review |

---

## 5. Error Responses

### 5.1 Validation Error (422)

```json
{
  "message": "Validation failed",
  "success": false,
  "code": 422,
  "errors": {
    "reviewable_type": ["Review type must be user and reviewable_id must be a valid user_id."],
    "rating": ["Rating must be at least 1 star."]
  }
}
```

### 5.2 Duplicate Review (409)

```json
{
  "message": "You have already submitted a review for this user. You can edit your existing review instead.",
  "success": false,
  "code": 409
}
```

### 5.3 Forbidden (403)

```json
{
  "message": "You cannot review yourself.",
  "success": false,
  "code": 403
}
```

### 5.4 Not Found (404)

```json
{
  "message": "Resource not found",
  "success": false,
  "code": 404
}
```

---

## 6. Implementation Notes

- **Routes**: `routes/api/v2.php`
- **Controller**: `App\Http\Controllers\Api\V2\ReviewController`
- **Service**: `App\Services\ReviewService`
- **Request**: `App\Http\Requests\Api\V2\StoreReviewRequest`, `UpdateReviewRequest`
- **Resource**: `App\Http\Resources\V2\ReviewResource`
- **Model**: `App\Models\Review` (polymorphic `reviewable` → `User`)

All user reviews are stored with `reviewable_type = App\Models\User` and `reviewable_id = user_id`.
