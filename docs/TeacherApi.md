# Teacher API Documentation

Public endpoints for teacher listing and profile pages. **No authentication required.**

**Base path:** `/api/v1/teachers`

---

## Table of Contents

1. [Overview](#overview)
2. [Endpoints Summary](#endpoints-summary)
3. [Get Filter Options](#1-get-filter-options)
4. [List Teachers](#2-list-teachers)
5. [Get Teacher Profile](#3-get-teacher-profile)
6. [Option Object Format](#option-object-format)
7. [Response Format](#response-format)
8. [Error Codes](#error-codes)
9. [Quick Reference](#quick-reference)

---

## Overview

The Teacher API provides public access to teacher listings and individual teacher profiles. It is used for public-facing pages such as teacher search, browse-by-subject, and teacher detail pages. All responses include option IDs mapped to labels from `config/options.php` for filters and display.

### Authentication

**No authentication required.** All endpoints are public and do not require a Bearer token.

---

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/teachers/options` | Get filter options (from config/options.php) |
| GET | `/api/v1/teachers` | List teachers (paginated, with filters) |
| GET | `/api/v1/teachers/{idOrSlug}` | Get single teacher profile by ID or slug |

---

## 1. Get Filter Options

Returns all option lists used for filters and ID fields. Values come from `config/options.php`.

- **Endpoint**: `GET /api/v1/teachers/options`
- **Access**: Public

### Headers

```
Accept: application/json
```

### Options Response Fields

| Field | Type | Description |
|-------|------|-------------|
| data.options | object | Option keys from config/options.php |
| data.options.gender | array | `[{id, label}, ...]` |
| data.options.teaching_mode | array | `[{id, label}, ...]` |
| data.options.availability_status | array | `[{id, label}, ...]` |
| data.options.hourly_rate_range | array | `[{id, label}, ...]` |
| data.options.monthly_rate_range | array | `[{id, label}, ...]` |
| data.options.teaching_experience_years | array | `[{id, label}, ...]` |
| data.options.travel_radius_km | array | `[{id, label}, ...]` |
| data.options.highest_qualification | array | `[{id, label}, ...]` |
| data.subjects | array | `[{id, name, slug}, ...]` |
| data.cities | array | `[{value, count}, ...]` |

### Success Response (200 OK)

```json
{
  "message": "Teacher filter options retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "options": {
      "gender": [{"id": 1, "label": "Male"}, {"id": 2, "label": "Female"}, ...],
      "teaching_mode": [{"id": 1, "label": "Online Only"}, ...],
      "availability_status": [{"id": 1, "label": "Available"}, ...],
      "hourly_rate_range": [{"id": 1, "label": "₹100-200"}, ...],
      "monthly_rate_range": [{"id": 1, "label": "₹1000-2000"}, ...],
      "teaching_experience_years": [{"id": 1, "label": "1 Year"}, ...],
      "travel_radius_km": [{"id": 0, "label": "No Travel"}, ...],
      "highest_qualification": [{"id": 1, "label": "High School"}, ...]
    },
    "subjects": [{"id": 1, "name": "Mathematics", "slug": "mathematics"}, ...],
    "cities": [{"value": "Mumbai", "count": 45}, ...]
  }
}
```

---

## 2. List Teachers

Paginated list of verified teachers with filters and sorting.

- **Endpoint**: `GET /api/v1/teachers`
- **Access**: Public

### Headers

```
Accept: application/json
```

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 15 | Items per page (max 50) |
| location | string | — | Filter by city or area (partial match) |
| city | string | — | Filter by city |
| pincode | string | — | Filter by pincode |
| subject_id | integer | — | Filter by subject ID |
| hourly_rate_range | integer | — | Option ID from config |
| monthly_rate_range | integer | — | Option ID from config |
| experience | integer | — | teaching_experience_years option ID |
| teaching_mode | integer | — | teaching_mode option ID |
| availability | integer | — | availability_status option ID |
| verified | 0\|1 | 1 | 1 = verified only |
| featured | 0\|1 | 0 | 1 = featured only |
| search | string | — | Search by teacher name |
| sort | string | rating | created_at, rating, price_low, price_high, name |
| order | string | desc | asc or desc |

### Example Request

```
GET /api/v1/teachers
GET /api/v1/teachers?subject_id=1&city=Mumbai&sort=price_low
GET /api/v1/teachers?per_page=20&search=John
```

### List Item Response Fields

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Teacher profile ID |
| slug | string | URL-friendly identifier |
| name | string | Teacher display name |
| bio | string | Short bio (max 120 chars in list) |
| avatar_url | string\|null | Profile image URL |
| qualification | string\|null | Qualification text |
| experience_years | integer\|null | Years of teaching experience |
| rating | float | Average rating (0–5) |
| total_reviews | integer | Number of reviews |
| hourly_rate | float\|null | Hourly rate in INR |
| city | string\|null | City |
| state | string\|null | State |
| teaching_mode | object\|null | `{id, label}` from config |
| travel_radius_km | object\|null | `{id, label}` from config |
| gender | object\|null | `{id, label}` from config |
| highest_qualification | object\|null | `{id, label}` from config |
| subjects | array | `[{id, name, slug}, ...]` |
| institute | object\|null | `{id, name, slug}` or null |
| verified | boolean | Verification status |
| is_featured | boolean | Featured flag |

### Success Response (200 OK)

```json
{
  "message": "Teachers retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "teachers": [
      {
        "id": 1,
        "slug": "john-doe",
        "name": "John Doe",
        "bio": "Experienced mathematics tutor...",
        "avatar_url": "https://...",
        "qualification": "B.Tech",
        "experience_years": 5,
        "rating": 4.8,
        "total_reviews": 42,
        "hourly_rate": 500,
        "city": "Mumbai",
        "state": "Maharashtra",
        "teaching_mode": {"id": 3, "label": "Both Online & Offline"},
        "travel_radius_km": {"id": 10, "label": "10 km"},
        "gender": {"id": 1, "label": "Male"},
        "highest_qualification": {"id": 3, "label": "Bachelor's Degree"},
        "subjects": [{"id": 1, "name": "Mathematics", "slug": "mathematics"}],
        "institute": {"id": 1, "name": "XYZ Academy", "slug": "xyz-academy"},
        "verified": true,
        "is_featured": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 120,
      "last_page": 8
    }
  }
}
```

---

## 3. Get Teacher Profile

Retrieve full teacher profile by numeric ID or slug.

- **Endpoint**: `GET /api/v1/teachers/{idOrSlug}`  
  `{idOrSlug}` — numeric ID (e.g. `123`) or slug (e.g. `john-doe`)
- **Access**: Public

### Headers

```
Accept: application/json
```

### Profile Response Fields

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Teacher profile ID |
| slug | string | URL-friendly identifier |
| name | string | Teacher display name |
| email | string\|null | Contact email |
| bio | string\|null | Full bio |
| avatar_url | string\|null | Profile image URL |
| qualification | string\|null | Qualification text |
| experience_years | integer | Years of experience (raw value) |
| specialization | string\|null | Teaching specialization |
| languages | array | Languages spoken |
| rating | float | Average rating |
| total_reviews | integer | Number of reviews |
| total_students | integer | Total students taught |
| hourly_rate | float\|null | Hourly rate in INR |
| hourly_rate_range | object\|null | `{id, label}` from config |
| monthly_rate | float\|null | Monthly rate in INR |
| monthly_rate_range | object\|null | `{id, label}` from config |
| teaching_mode | object\|null | `{id, label}` from config |
| online_classes | boolean | Offers online classes |
| home_tuition | boolean | Offers home tuition |
| institute_classes | boolean | Teaches at institute |
| travel_radius_km | object\|null | `{id, label}` from config |
| city | string\|null | City |
| state | string\|null | State |
| availability_status | object\|null | `{id, label}` from config |
| experience_years | object\|null | `{id, label}` from config (teaching_experience_years) |
| gender | object\|null | `{id, label}` from config |
| highest_qualification | object\|null | `{id, label}` from config |
| subjects | array | `[{id, name, slug, category}, ...]` |
| institute | object\|null | `{id, name, slug, city, address, website}` or null |
| verified | boolean | Verification status |
| is_featured | boolean | Featured flag |
| reviews_sample | array | `[{id, rating, comment, created_at}, ...]` (up to 5) |

### Success Response (200 OK)

```json
{
  "message": "Teacher profile retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "id": 1,
    "slug": "john-doe",
    "name": "John Doe",
    "email": "john@example.com",
    "bio": "...",
    "avatar_url": "https://...",
    "qualification": "B.Tech",
    "experience_years": {"id": 5, "label": "5 Years"},
    "specialization": "JEE preparation",
    "languages": ["English", "Hindi"],
    "rating": 4.8,
    "total_reviews": 42,
    "total_students": 150,
    "hourly_rate": 500,
    "hourly_rate_range": {"id": 5, "label": "₹501-600"},
    "monthly_rate": 6000,
    "monthly_rate_range": {"id": 6, "label": "₹6001-7000"},
    "teaching_mode": {"id": 3, "label": "Both Online & Offline"},
    "online_classes": true,
    "home_tuition": true,
    "institute_classes": false,
    "travel_radius_km": {"id": 10, "label": "10 km"},
    "city": "Mumbai",
    "state": "Maharashtra",
    "availability_status": {"id": 1, "label": "Available"},
    "gender": {"id": 1, "label": "Male"},
    "highest_qualification": {"id": 3, "label": "Bachelor's Degree"},
    "subjects": [
      {"id": 1, "name": "Mathematics", "slug": "mathematics", "category": "academic"},
      {"id": 2, "name": "Physics", "slug": "physics", "category": "academic"}
    ],
    "institute": {
      "id": 1,
      "name": "XYZ Academy",
      "slug": "xyz-academy",
      "city": "Mumbai",
      "address": "123 Main St",
      "website": "https://xyz.academy"
    },
    "verified": true,
    "is_featured": false,
    "reviews_sample": [
      {"id": 1, "rating": 5, "comment": "Excellent teacher!", "created_at": "2025-03-01T10:00:00.000000Z"}
    ]
  }
}
```

---

## Option Object Format

All ID fields (gender, teaching_mode, availability_status, etc.) use this structure when mapped from `config/options.php`:

```json
{
  "id": 1,
  "label": "Male"
}
```

Use the `GET /api/v1/teachers/options` endpoint to get full option lists for filters.

---

## Response Format

All endpoints follow the standard API response structure (same as [Profile API](ProfileApi.md#response-format)).

### Success Response (200 OK)

```json
{
  "message": "Success message",
  "success": true,
  "code": 200,
  "data": { }
}
```

### Error Response (Not Found 404)

```json
{
  "message": "Teacher not found.",
  "success": false,
  "code": 404
}
```

### Error Response (Validation 422)

For endpoints that accept request body (if any). Teacher GET endpoints do not return 422.

```json
{
  "message": "Validation failed.",
  "success": false,
  "code": 422,
  "errors": {
    "field_name": ["The field name field is required."]
  }
}
```

### Error Response (Unauthorized 401)

Teacher API is public; 401 does not apply. Shown for consistency with API standards.

```json
{
  "message": "Unauthenticated.",
  "success": false,
  "code": 401
}
```

### Error Response (Server 500)

```json
{
  "message": "Unable to retrieve teachers.",
  "success": false,
  "code": 500
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 404 | Teacher not found (show by id/slug) |
| 500 | Internal server error |

---

## Quick Reference (cURL Examples)

```bash
# Get filter options
curl -X GET "http://localhost:8000/api/v1/teachers/options" \
  -H "Accept: application/json"

# List teachers with filters
curl -X GET "http://localhost:8000/api/v1/teachers?subject_id=1&city=Mumbai&sort=price_low&per_page=20" \
  -H "Accept: application/json"

# Get teacher profile by slug
curl -X GET "http://localhost:8000/api/v1/teachers/john-doe" \
  -H "Accept: application/json"

# Get teacher profile by ID
curl -X GET "http://localhost:8000/api/v1/teachers/123" \
  -H "Accept: application/json"
```

---

*Last Updated: March 13, 2026*
