# Profile Teaching API Documentation

Update teaching-specific information for teachers. Part of the [Profile API](./ProfileApi.md).

**Base path:** `/api/v1/profile`  
**Authentication:** Required (`Authorization: Bearer {token}`)

---

## Update Teaching Info

Update teaching credentials, experience, rates, and preferences.

- **URL**: `/api/v1/profile/teaching`
- **Method**: `PUT` or `PATCH`
- **Authentication**: Required (Bearer token)
- **Content-Type**: `application/json`

### Headers

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Request Body

All fields are optional. Send only the fields you want to update.

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
  "teaching_philosophy": "Focus on conceptual understanding rather than rote learning.",
  "subjects_taught": [1, 2, 5]
}
```

### Field Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| highest_qualification | string | No | max:255 |
| institution_name | string | No | max:255 |
| field_of_study | string | No | max:255 |
| graduation_year | integer | No | 1950 to (current year + 5) |
| teaching_experience_years | integer | No | 0–50 |
| hourly_rate_id | integer | No | in:1,2,3,4,5,6,7,8,9,10 |
| monthly_rate_id | integer | No | in:1,2,3,4,5,6,7,8,9,10 |
| travel_radius_km_id | integer | No | in:0,1,2,3,4,5,6,7,8,9,10,15,20,25,30,40,50,75,100 |
| teaching_mode_id | integer | No | in:1,2,3 |
| availability_status_id | integer | No | in:1,2,3 |
| teaching_philosophy | string | No | max:2000 |
| subjects_taught | array | No | Array of integers; each must exist in `subjects` table |

**Note:** For valid subject IDs, use `GET /api/v1/subjects`.

### Success Response (200 OK)

```json
{
  "message": "Teaching information updated successfully.",
  "success": true,
  "code": 200,
  "data": {
    "profile": {
      "id": 1,
      "user_id": 1,
      "highest_qualification": "M.Sc. Mathematics",
      "institution_name": "University of Mumbai",
      "field_of_study": "Mathematics",
      "graduation_year": 2015
    },
    "teaching_info": {
      "id": 1,
      "profile_id": 1,
      "teaching_experience_years": 10,
      "hourly_rate_id": 5,
      "monthly_rate_id": 4,
      "travel_radius_km_id": 10,
      "teaching_mode_id": 1,
      "availability_status_id": 1,
      "teaching_philosophy": "Focus on conceptual understanding...",
      "subjects_taught": [1, 2, 5]
    },
    "completion_percentage": 78
  }
}
```

### Error Response (422 Validation Failed)

```json
{
  "message": "Validation failed.",
  "success": false,
  "code": 422,
  "errors": {
    "subjects_taught.0": ["The selected subjects_taught.0 is invalid."],
    "graduation_year": ["The graduation year must be between 1950 and 2031."]
  }
}
```

### Error Response (401 Unauthenticated)

```json
{
  "message": "Unauthenticated.",
  "success": false,
  "code": 401
}
```

### Error Response (500 Server Error)

```json
{
  "message": "Unable to update teaching information.",
  "success": false,
  "code": 500
}
```

### cURL Example

```bash
curl -X PUT "http://localhost:8000/api/v1/profile/teaching" \
  -H "Authorization: Bearer {your_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "highest_qualification": "M.Sc. Mathematics",
    "teaching_experience_years": 10,
    "subjects_taught": [1, 2, 5]
  }'
```

### Related APIs

- [Subject API](./SubjectApi.md) – Get valid subject IDs for `subjects_taught`
- [Options API](./OptionApi.md) – Get IDs for rates, teaching mode, availability (if applicable)
- [Profile API](./ProfileApi.md) – Full profile documentation

---

*Last Updated: March 12, 2025*
