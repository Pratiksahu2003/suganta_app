# Subject API Documentation

## Introduction

This documentation covers the Subject list API endpoint. Use it to fetch subjects (id and name only) for dropdowns, filters, and autocomplete. Search by name is supported.

## Endpoints

### 1. Get Subjects

Retrieve a list of subjects with only `id` and `name`. Supports optional search by name (partial match).

- **URL**: `/api/v1/subjects`
- **Method**: `GET`
- **Authentication**: Not required (public)

#### Query Parameters

| Parameter | Type   | Required | Description                                           |
| :-------- | :----- | :------- | :---------------------------------------------------- |
| `search`  | string | No       | Filter subjects by name (case-insensitive partial).   |

#### Success Response

**Code**: `200 OK`

**Content Example (All Subjects)**:

```json
{
    "success": true,
    "message": "Subjects retrieved successfully.",
    "code": 200,
    "data": [
        { "id": 1, "name": "Mathematics" },
        { "id": 2, "name": "Physics" },
        { "id": 3, "name": "Chemistry" }
    ]
}
```

**Content Example (With Search: `?search=math`)**:

```json
{
    "success": true,
    "message": "Subjects retrieved successfully.",
    "code": 200,
    "data": [
        { "id": 1, "name": "Mathematics" },
        { "id": 5, "name": "Applied Mathematics" }
    ]
}
```

#### Request Examples

```http
GET /api/v1/subjects
GET /api/v1/subjects?search=science
GET /api/v1/subjects?search=eng
```
