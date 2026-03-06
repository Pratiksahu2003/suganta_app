# Profile API Documentation

Auth user's profile only. All endpoints require `Authorization: Bearer {token}`.

**Base path:** `/api/v1/profile`

---

## Table of Contents

1. [Overview](#overview)
2. [Endpoints Summary](#endpoints-summary)
3. [Get Profile](#1-get-profile)
4. [Update Basic Profile](#2-update-basic-profile)
5. [Update Location](#3-update-location)
6. [Update Social Links](#4-update-social-links)
7. [Update Teaching Info](#5-update-teaching-info)
8. [Update Institute Info](#6-update-institute-info)
9. [Update Student Info](#7-update-student-info)
10. [Update Avatar](#8-update-avatar)
11. [Update Password](#9-update-password)
12. [Update Preferences](#10-update-preferences)
13. [Get Completion Data](#11-get-completion-data)
14. [Refresh Profile](#12-refresh-profile)
15. [Clear Cache](#13-clear-cache)
16. [Delete Account](#14-delete-account)
17. [Response Format](#response-format)
18. [Error Codes](#error-codes)

---

## Overview

The Profile API manages the authenticated user's profile data. It uses REST-style nested sub-resources for different profile sections (location, social, teaching, institute, student). All updates return the updated data and profile completion percentage where applicable.

### Authentication

All endpoints require a valid Sanctum Bearer token in the header:

```
Authorization: Bearer {your_token_here}
```

---

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/profile` | Get full profile |
| PUT/PATCH | `/api/v1/profile` | Update basic profile |
| PUT/PATCH | `/api/v1/profile/location` | Update address & location |
| PUT/PATCH | `/api/v1/profile/social` | Update social media links |
| PUT/PATCH | `/api/v1/profile/teaching` | Update teaching information |
| PUT/PATCH | `/api/v1/profile/institute` | Update institute information |
| PUT/PATCH | `/api/v1/profile/student` | Update student information |
| PUT/POST | `/api/v1/profile/avatar` | Update profile picture |
| PUT/PATCH | `/api/v1/profile/password` | Change password |
| PUT/PATCH | `/api/v1/profile/preferences` | Update theme, language, notifications |
| GET | `/api/v1/profile/completion` | Get profile completion data |
| POST | `/api/v1/profile/refresh` | Force refresh profile & clear caches |
| POST | `/api/v1/profile/cache/clear` | Clear profile caches |
| DELETE | `/api/v1/profile` | Delete account (permanent) |

---

## 1. Get Profile

Retrieve the authenticated user's full profile including related data.

- **Endpoint**: `GET /api/v1/profile`
- **Access**: Private (Bearer token required)

### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

### Success Response (200 OK)

```json
{
  "message": "Profile retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "teacher",
      "email_verified_at": "2025-01-15T10:00:00.000000Z"
    },
    "profile": {
      "id": 1,
      "user_id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "display_name": "John D",
      "bio": "Experienced tutor...",
      "phone_primary": "9876543210",
      "city": "Mumbai",
      "profile_completion_percentage": 75
    },
    "profile_image_url": "http://localhost:8000/storage/profile-images/profile_1_1234567890.jpg",
    "completion_percentage": 75
  }
}
```

---

## 2. Update Basic Profile

Update basic profile information (name, email, bio, contact details).

- **Endpoint**: `PUT /api/v1/profile` or `PATCH /api/v1/profile`
- **Access**: Private

### Request Body

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "display_name": "John D",
  "bio": "Experienced mathematics tutor with 10 years experience.",
  "date_of_birth": "1990-05-15",
  "gender_id": 1,
  "nationality": "Indian",
  "phone_primary": "9876543210",
  "phone_secondary": "9123456789",
  "whatsapp": "9876543210",
  "website": "https://johndoe.com",
  "emergency_contact_name": "Jane Doe",
  "emergency_contact_phone": "9999999999",
  "email": "john.doe@example.com"
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| first_name | string | Yes | max:255 |
| last_name | string | No | max:255 |
| display_name | string | No | max:255 |
| bio | string | No | max:1000 |
| date_of_birth | string | No | valid date |
| gender_id | integer | No | in:1,2,3,4 |
| nationality | string | No | max:255 |
| phone_primary | string | No | max:20 |
| phone_secondary | string | No | max:20 |
| whatsapp | string | No | max:20 |
| website | string | No | url, max:255 |
| emergency_contact_name | string | No | max:255 |
| emergency_contact_phone | string | No | max:20 |
| email | string | Yes | email, unique (excluding current user) |

### Success Response (200 OK)

```json
{
  "message": "Profile information updated successfully.",
  "success": true,
  "code": 200,
  "data": {
    "profile": { },
    "completion_percentage": 78
  }
}
```

---

## 3. Update Location

Update address and location information.

- **Endpoint**: `PUT /api/v1/profile/location` or `PATCH /api/v1/profile/location`
- **Access**: Private

### Request Body

```json
{
  "address_line_1": "123 Main Street",
  "address_line_2": "Apartment 4",
  "area": "Andheri West",
  "city": "Mumbai",
  "state": "Maharashtra",
  "pincode": "400058",
  "country_id": 1,
  "latitude": 19.1136,
  "longitude": 72.8697
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| address_line_1 | string | No | max:255 |
| address_line_2 | string | No | max:255 |
| area | string | No | max:255 |
| city | string | No | max:255 |
| state | string | No | max:255 |
| pincode | string | No | max:20 |
| country_id | integer | No | in:1-10 |
| latitude | numeric | No | between:-90,90 |
| longitude | numeric | No | between:-180,180 |

---

## 4. Update Social Links

Update social media and professional links.

- **Endpoint**: `PUT /api/v1/profile/social` or `PATCH /api/v1/profile/social`
- **Access**: Private

### Request Body

```json
{
  "facebook_url": "https://facebook.com/johndoe",
  "twitter_url": "https://twitter.com/johndoe",
  "instagram_url": "https://instagram.com/johndoe",
  "linkedin_url": "https://linkedin.com/in/johndoe",
  "youtube_url": "https://youtube.com/@johndoe",
  "tiktok_url": "https://tiktok.com/@johndoe",
  "telegram_username": "johndoe",
  "discord_username": "johndoe#1234",
  "github_url": "https://github.com/johndoe",
  "portfolio_url": "https://johndoe.dev",
  "blog_url": "https://blog.johndoe.com",
  "website": "https://johndoe.com",
  "whatsapp": "9876543210"
}
```

All fields are optional. URL fields must be valid URLs (max 255 chars). Username fields max 255 chars.

---

## 5. Update Teaching Info

Update teaching-specific information (for teacher role).

- **Endpoint**: `PUT /api/v1/profile/teaching` or `PATCH /api/v1/profile/teaching`
- **Access**: Private

### Request Body

```json
{
  "highest_qualification": "M.Sc. Mathematics",
  "institution_name": "University of Mumbai",
  "field_of_study": "Mathematics",
  "graduation_year": 2015,
  "teaching_experience_years": 10,
  "hourly_rate_id": 5,
  "monthly_rate_id": 4,
  "travel_radius_km_id": 10,
  "teaching_mode_id": 1,
  "availability_status_id": 1,
  "teaching_philosophy": "Focus on conceptual understanding...",
  "subjects_taught": [1, 2, 5]
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| highest_qualification | string | No | max:255 |
| institution_name | string | No | max:255 |
| field_of_study | string | No | max:255 |
| graduation_year | integer | No | 1950 to current+5 |
| teaching_experience_years | integer | No | 0-50 |
| hourly_rate_id | integer | No | in:1-10 |
| monthly_rate_id | integer | No | in:1-10 |
| travel_radius_km_id | integer | No | in:0,1,2,3,4,5,6,7,8,9,10,15,20,25,30,40,50,75,100 |
| teaching_mode_id | integer | No | in:1,2,3 |
| availability_status_id | integer | No | in:1,2,3 |
| teaching_philosophy | string | No | max:2000 |
| subjects_taught | array | No | array of valid subject IDs |

---

## 6. Update Institute Info

Update institute-specific information (for institute role).

- **Endpoint**: `PUT /api/v1/profile/institute` or `PATCH /api/v1/profile/institute`
- **Access**: Private

### Request Body

```json
{
  "institute_name": "ABC Academy",
  "institute_type_id": 1,
  "institute_category_id": 1,
  "affiliation_number": "AFF123",
  "registration_number": "REG456",
  "establishment_year_id": 5,
  "principal_name": "Dr. Smith",
  "principal_phone": "9876543210",
  "principal_email": "principal@abc.edu",
  "total_students_id": 4,
  "total_teachers_id": 3,
  "total_branches": 2,
  "institute_description": "Premier educational institute..."
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| institute_name | string | Yes | max:255 |
| institute_type_id | integer | Yes | in:1-5 |
| institute_category_id | integer | No | in:1-3 |
| affiliation_number | string | No | max:255 |
| registration_number | string | No | max:255 |
| establishment_year_id | integer | No | in:1-9 |
| principal_name | string | No | max:255 |
| principal_phone | string | No | max:20 |
| principal_email | string | No | email, max:255 |
| total_students_id | integer | No | in:1-8 |
| total_teachers_id | integer | No | in:1-8 |
| total_branches | integer | No | min:1 |
| institute_description | string | No | max:2000 |

---

## 7. Update Student Info

Update student-specific information (for student role).

- **Endpoint**: `PUT /api/v1/profile/student` or `PATCH /api/v1/profile/student`
- **Access**: Private

### Request Body

```json
{
  "current_class_id": 10,
  "current_school": "XYZ School",
  "board_id": 1,
  "stream_id": 2,
  "parent_name": "Jane Doe",
  "parent_phone": "9876543210",
  "parent_email": "jane@example.com",
  "budget_min": 2000,
  "budget_max": 5000,
  "learning_challenges": "Requires visual learning aids"
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| current_class_id | integer | No | in:1-14 |
| current_school | string | No | max:255 |
| board_id | integer | No | in:1-5 |
| stream_id | integer | No | in:1-6 |
| parent_name | string | No | max:255 |
| parent_phone | string | No | max:20 |
| parent_email | string | No | email, max:255 |
| budget_min | numeric | No | min:0 |
| budget_max | numeric | No | min:0 |
| learning_challenges | string | No | max:1000 |

---

## 8. Update Avatar

Update profile picture. Uses multipart form-data.

- **Endpoint**: `PUT /api/v1/profile/avatar` or `POST /api/v1/profile/avatar`
- **Access**: Private
- **Content-Type**: `multipart/form-data`

### Request Body (Form Data)

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| avatar | file | Yes | image, mimes:jpeg,png,jpg,gif, max:2048 KB |

### cURL Example

```bash
curl -X POST "http://localhost:8000/api/v1/profile/avatar" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "avatar=@/path/to/photo.jpg"
```

### Success Response (200 OK)

```json
{
  "message": "Profile picture updated successfully.",
  "success": true,
  "code": 200,
  "data": {
    "profile_image": "profile-images/profile_1_1234567890.jpg",
    "profile_image_url": "http://localhost:8000/storage/profile-images/profile_1_1234567890.jpg"
  }
}
```

---

## 9. Update Password

Change the user's password. Requires current password and new password confirmation.

- **Endpoint**: `PUT /api/v1/profile/password` or `PATCH /api/v1/profile/password`
- **Access**: Private

### Request Body

```json
{
  "current_password": "oldpassword123",
  "password": "NewSecureP@ss123",
  "password_confirmation": "NewSecureP@ss123"
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| current_password | string | Yes | Must match user's current password |
| password | string | Yes | min:8, confirmed, strength ≥ 3 |
| password_confirmation | string | Yes | Must match password |

**Password strength** requires at least 3 of: length ≥8, length ≥12, lowercase, uppercase, digit, special character.

### Success Response (200 OK)

```json
{
  "message": "Password updated successfully.",
  "success": true,
  "code": 200
}
```

---

## 10. Update Preferences

Update user preferences (theme, language, notifications).

- **Endpoint**: `PUT /api/v1/profile/preferences` or `PATCH /api/v1/profile/preferences`
- **Access**: Private

### Request Body

```json
{
  "theme": "dark",
  "language": "en",
  "notifications": {
    "email": true,
    "push": false
  },
  "privacy_settings": {
    "profile_visibility": "public"
  }
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| theme | string | No | in:light,dark,auto |
| language | string | No | max:10 |
| notifications | object | No | - |
| privacy_settings | object | No | - |

---

## 11. Get Completion Data

Get profile completion percentage and summary (cached for 2 minutes).

- **Endpoint**: `GET /api/v1/profile/completion`
- **Access**: Private

### Success Response (200 OK)

```json
{
  "message": "Completion data retrieved.",
  "success": true,
  "code": 200,
  "data": {
    "percentage": 75,
    "status": "Detailed",
    "color": "info",
    "completion_summary": {
      "total_fields": 15,
      "completed_fields": 11,
      "completion_percentage": 75,
      "high_priority_completed": 4,
      "high_priority_total": 5,
      "status": "Detailed",
      "color": "info",
      "next_priority_fields": [
        { "field": "profile_image", "label": "Profile Picture", "weight": 8, "priority": "high" }
      ]
    },
    "cached": false
  }
}
```

---

## 12. Refresh Profile

Force refresh profile data and clear all related caches.

- **Endpoint**: `POST /api/v1/profile/refresh`
- **Access**: Private

### Success Response (200 OK)

```json
{
  "message": "Profile data refreshed successfully.",
  "success": true,
  "code": 200,
  "data": {
    "profile_image_url": "http://localhost:8000/storage/...",
    "profile": { }
  }
}
```

---

## 13. Clear Cache

Clear all profile-related caches for the authenticated user.

- **Endpoint**: `POST /api/v1/profile/cache/clear`
- **Access**: Private

### Success Response (200 OK)

```json
{
  "message": "Profile caches cleared successfully.",
  "success": true,
  "code": 200
}
```

---

## 14. Delete Account

Permanently delete the user account. **Irreversible.** Requires password and confirmation.

- **Endpoint**: `DELETE /api/v1/profile`
- **Access**: Private

### Request Body

```json
{
  "password": "current_password",
  "confirmation": "DELETE",
  "reason": "No longer need the service"
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| password | string | Yes | Must match current password |
| confirmation | string | Yes | Must be exactly "DELETE" |
| reason | string | No | max:500 |

### Success Response (200 OK)

```json
{
  "message": "Your account has been permanently deleted.",
  "success": true,
  "code": 200
}
```

**Note:** All tokens are revoked before deletion. The client should clear local token storage after receiving this response.

---

## Response Format

All endpoints follow the standard API response structure.

### Success Response

```json
{
  "message": "Success message",
  "success": true,
  "code": 200,
  "data": { }
}
```

### Error Response (Validation 422)

```json
{
  "message": "Validation failed.",
  "success": false,
  "code": 422,
  "errors": {
    "email": ["The email has already been taken."],
    "first_name": ["The first name field is required."]
  }
}
```

### Error Response (Server 500)

```json
{
  "message": "Unable to update profile.",
  "success": false,
  "code": 500
}
```

### Error Response (Unauthorized 401)

```json
{
  "message": "Unauthenticated.",
  "success": false,
  "code": 401
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 401 | Unauthenticated (missing or invalid token) |
| 404 | Resource not found |
| 422 | Validation failed |
| 500 | Internal server error |

---

## Quick Reference (cURL Examples)

```bash
# Get profile
curl -X GET "http://localhost:8000/api/v1/profile" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Update basic profile
curl -X PUT "http://localhost:8000/api/v1/profile" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"first_name":"John","last_name":"Doe","email":"john@example.com"}'

# Update avatar
curl -X POST "http://localhost:8000/api/v1/profile/avatar" \
  -H "Authorization: Bearer {token}" \
  -F "avatar=@photo.jpg"
```

---

*Last Updated: March 6, 2026*
