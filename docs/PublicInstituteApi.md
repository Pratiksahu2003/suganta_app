# Public Institute API Documentation

Complete API documentation for `App\Http\Controllers\Api\V1\PublicInstituteController`. Public institute listing and profile endpoints. **No authentication required.** Uses **User ID only** for show endpoint (no slug).

**Base path:** `/api/v1`

**Controller:** `App\Http\Controllers\Api\V1\PublicInstituteController`

---

## Table of Contents

1. [Overview](#overview)
2. [Endpoints Summary](#endpoints-summary)
3. [Get Filter Options](#1-get-filter-options)
4. [List Institutes](#2-list-institutes)
5. [Filter Parameters Reference](#filter-parameters-reference)
6. [Show Institute by ID](#3-show-institute-by-id)
7. [Response Format](#response-format)
8. [Error Codes](#error-codes)
9. [Internal Behavior & Notes](#internal-behavior--notes)
10. [cURL Examples](#curl-examples)

---

## Overview

The Public Institute API provides:

- **Filter options** for building search/filter UIs (institute type, category, establishment year, student/teacher ranges, subjects, cities)
- **Paginated institute listing** with location, type, category, search, and boolean filters — returns rich card data
- **Single institute profile** by numeric User ID, including related institutes recommendation

### Eligibility Criteria

Institutes are included only if:

- `role` ∈ `institute`, `ngo`
- `email_verified_at` is not null
- `registration_fee_status` ∈ `paid`, `not_required`

### Routes

| Method | Route | Action |
|--------|-------|--------|
| GET | `/api/v1/institutes/options` | `options` |
| GET | `/api/v1/institutes` | `index` |
| GET | `/api/v1/institutes/{id}` | `show` |

---

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/institutes/options` | Get filter options and dropdown data |
| GET | `/api/v1/institutes` | List institutes (paginated, filtered, sorted) |
| GET | `/api/v1/institutes/{id}` | Show single institute profile by User ID |

---

## 1. Get Filter Options

Retrieve all filter options needed for institute search/filter UIs. Options come from `config/options.php`, active subjects, and institute cities. All data is cached for 1 hour.

- **Endpoint**: `GET /api/v1/institutes/options`
- **Access**: Public (no auth)

### Success Response (200 OK)

```json
{
  "message": "Institute filter options retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "options": {
      "institute_type": [
        { "id": 1, "label": "School" },
        { "id": 2, "label": "College" },
        { "id": 3, "label": "University" },
        { "id": 4, "label": "Coaching Institute" }
      ],
      "institute_category": [
        { "id": 1, "label": "Government" },
        { "id": 2, "label": "Private" },
        { "id": 3, "label": "Semi-Government" }
      ],
      "establishment_year_range": [
        { "id": 1, "label": "Before 1990" },
        { "id": 2, "label": "1990-2000" },
        { "id": 3, "label": "2000-2010" },
        { "id": 4, "label": "2010-2020" },
        { "id": 5, "label": "2020 & Later" }
      ],
      "total_students_range": [
        { "id": 1, "label": "1-100" },
        { "id": 2, "label": "100-500" },
        { "id": 3, "label": "500-1000" },
        { "id": 4, "label": "1000+" }
      ],
      "total_teachers_range": [
        { "id": 1, "label": "1-10" },
        { "id": 2, "label": "10-50" },
        { "id": 3, "label": "50-100" },
        { "id": 4, "label": "100+" }
      ]
    },
    "subjects": [
      { "id": 1, "name": "Mathematics", "slug": "mathematics" },
      { "id": 2, "name": "Physics", "slug": "physics" }
    ],
    "cities": [
      { "value": "Mumbai", "count": 25 },
      { "value": "Delhi", "count": 18 }
    ]
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `options` | object | Keys from config; values are `[{id, label}]` arrays |
| `subjects` | array | Active subjects: `id`, `name`, `slug` |
| `cities` | array | Cities with institute count: `value`, `count` |

---

## 2. List Institutes

Paginated list of institutes with optional filters and sorting. Returns rich card data including type, category, facilities, courses, contact info, and more. When no institutes match filters, the API **falls back to recently registered institutes**.

- **Endpoint**: `GET /api/v1/institutes`
- **Access**: Public (no auth)

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | int | 12 | Items per page (min: 1, max: 50) |
| `order_by` | string | `recent` | Sort option (see Sort Options below) |
| `sort` | string | `recent` | Alias for `order_by` |
| `search` | string | — | Search by name, description, bio |
| `location` | string | — | Search city, area, or state (overrides `city`/`area`/`state`) |
| `city` | string | — | Filter by city (ignored if `location` set) |
| `area` | string | — | Filter by area (ignored if `city` or `location` set) |
| `state` | string | — | Filter by state (ignored if `location` set) |
| `pincode` | string | — | Filter by exact pincode |
| `institute_type` | int | — | Option ID from `institute_type` |
| `type` | int | — | Alias for `institute_type` |
| `institute_category` | int | — | Option ID from `institute_category` |
| `category` | int | — | Alias for `institute_category` |
| `establishment_year_range` | int | — | Option ID from `establishment_year_range` |
| `established` | int | — | Alias for `establishment_year_range` |
| `total_students_range` | int | — | Option ID from `total_students_range` |
| `total_teachers_range` | int | — | Option ID from `total_teachers_range` |
| `total_teachers` | int | — | Alias for `total_teachers_range` |
| `verified` | bool | — | `true`/`1` = verified only |
| `featured` | bool | — | `true`/`1` = featured only |

### Sort Options

| `order_by` / `sort` value | Description |
|---------------------------|-------------|
| `recent` | Most recently registered (default) |
| `name_asc` | Institute name A → Z |
| `name_desc` | Institute name Z → A |
| `established_asc` | Oldest established first |
| `established_desc` | Newest established first |
| `students_asc` | Fewest students first |
| `students_desc` | Most students first |

### Success Response (200 OK)

```json
{
  "message": "Institutes retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "institutes": [
      {
        "id": 5,
        "name": "Delhi Public School",
        "slug": "delhi-public-school-5",
        "description": "A premier CBSE-affiliated school offering holistic education with state-of-the-art...",
        "logo_url": "https://storage.example.com/profile-images/institute_5.jpg",
        "cover_url": "https://storage.example.com/cover-images/institute_5.jpg",
        "city": "New Delhi",
        "state": "Delhi",
        "area": "Vasant Kunj",
        "pincode": "110070",
        "institute_type": "School",
        "institute_category": "Private",
        "establishment_year": "2000-2010",
        "total_students": "500-1000",
        "total_teachers": "50-100",
        "total_branches": 3,
        "facilities": ["Library", "Computer Lab", "Sports Ground", "Auditorium"],
        "specializations": ["Science", "Commerce", "Arts"],
        "courses_offered": ["Class I-XII", "JEE Foundation"],
        "affiliations": ["CBSE"],
        "accreditations": ["NAAC A+"],
        "principal_name": "Dr. Sharma",
        "website": "https://dps-example.edu.in",
        "phone_primary": "9876543210",
        "whatsapp": "9876543210",
        "latitude": 28.5245,
        "longitude": 77.1555,
        "verified": true,
        "is_featured": true
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 12,
      "total": 42,
      "last_page": 4,
      "from": 1,
      "to": 12,
      "first_page_url": "http://localhost:8000/api/v1/institutes?page=1",
      "last_page_url": "http://localhost:8000/api/v1/institutes?page=4",
      "next_page_url": "http://localhost:8000/api/v1/institutes?page=2",
      "prev_page_url": null
    }
  }
}
```

### List Item Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | User ID |
| `name` | string | Institute name (from `profile_institute_info.institute_name`, falls back to `profile.display_name`, then `user.name`) |
| `slug` | string\|null | URL-safe slug from profile |
| `description` | string | Truncated to 200 chars (from `institute_description` or `bio`) |
| `logo_url` | string\|null | Full storage URL for profile image |
| `cover_url` | string\|null | Full storage URL for cover image |
| `city` | string\|null | City |
| `state` | string\|null | State |
| `area` | string\|null | Area/locality |
| `pincode` | string\|null | Postal code |
| `institute_type` | string\|null | Human-readable label (e.g. "School", "College") |
| `institute_category` | string\|null | Human-readable label (e.g. "Private", "Government") |
| `establishment_year` | string\|null | Human-readable range (e.g. "2000-2010") |
| `total_students` | string\|null | Human-readable range (e.g. "500-1000") |
| `total_teachers` | string\|null | Human-readable range (e.g. "50-100") |
| `total_branches` | int\|null | Number of branches |
| `facilities` | array | List of facility names (e.g. `["Library", "Lab"]`) |
| `specializations` | array | List of specializations |
| `courses_offered` | array | List of courses |
| `affiliations` | array | Board/university affiliations (e.g. `["CBSE", "ICSE"]`) |
| `accreditations` | array | Accreditation bodies (e.g. `["NAAC A+"]`) |
| `principal_name` | string\|null | Principal/head name |
| `website` | string\|null | Institute website URL |
| `phone_primary` | string\|null | Primary contact phone |
| `whatsapp` | string\|null | WhatsApp number |
| `latitude` | float\|null | GPS latitude |
| `longitude` | float\|null | GPS longitude |
| `verified` | bool | Profile verification status |
| `is_featured` | bool | Featured institute flag |

---

## Filter Parameters Reference

Complete reference for all query parameters used with `GET /api/v1/institutes`. Get valid option IDs from `GET /api/v1/institutes/options` unless noted otherwise.

### Search Filter

| Parameter | Type | Source | Description |
|-----------|------|--------|-------------|
| `search` | string | free text | Searches across `users.name`, `profile.display_name`, `profile.bio`, `profile_institute_info.institute_name`, and `profile_institute_info.institute_description` (LIKE). Trimmed. |

**Example:** `?search=Delhi Public School`

---

### Location Filters

| Parameter | Type | Source | Description |
|-----------|------|--------|-------------|
| `location` | string | free text | Search in `city`, `area`, and `state` (LIKE). **Overrides** `city`, `area`, `state` when set. |
| `city` | string | `data.cities[].value` or free text | Filter by city (LIKE). Ignored if `location` is set. |
| `area` | string | free text | Filter by area (LIKE). Ignored if `city` or `location` is set. |
| `state` | string | free text | Filter by state (LIKE). Ignored if `location` is set. |
| `pincode` | string | free text | Exact match on `profile.pincode`. |

**Example:** `?location=Mumbai` or `?city=Delhi&state=Delhi`

---

### Institute Info Filters

All these filter by the corresponding column in `profile_institute_info`. Get valid IDs from `GET /api/v1/institutes/options`.

| Parameter | Alias | Source | Description |
|-----------|-------|--------|-------------|
| `institute_type` | `type` | `data.options.institute_type[].id` | Filter by institute type (School, College, etc.) |
| `institute_category` | `category` | `data.options.institute_category[].id` | Filter by category (Government, Private, etc.) |
| `establishment_year_range` | `established` | `data.options.establishment_year_range[].id` | Filter by establishment year range |
| `total_students_range` | — | `data.options.total_students_range[].id` | Filter by student count range |
| `total_teachers_range` | `total_teachers` | `data.options.total_teachers_range[].id` | Filter by teacher count range |

**Example:** `?institute_type=1&category=2` or `?type=1&established=3`

---

### Boolean Filters

| Parameter | Type | Description |
|-----------|------|-------------|
| `verified` | bool | `true`/`1` = verified institutes only. Default: all. |
| `featured` | bool | `true`/`1` = featured institutes only. Default: all. |

**Example:** `?verified=true&featured=true`

---

### Pagination & Sort

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | int | 12 | Items per page. Clamped 1–50. |
| `order_by` | string | `recent` | Sort option: `recent`, `name_asc`, `name_desc`, `established_asc`, `established_desc`, `students_asc`, `students_desc`. |
| `sort` | string | `recent` | Alias for `order_by`. |

---

### Filter Combination Examples

```bash
# Delhi + CBSE schools + verified
?city=Delhi&institute_type=1&verified=true

# Search by name + sort by name
?search=Engineering&order_by=name_asc

# Location (city/area/state) + category + establishment year
?location=Mumbai&category=2&established=4

# Featured institutes with many students, paginated
?featured=true&total_students_range=4&per_page=20

# Pincode-based search
?pincode=110070&institute_type=1
```

---

## 3. Show Institute by ID

Retrieve a single institute's full profile by User ID. Includes related institutes (same city, type, or category) cached for 5 minutes.

- **Endpoint**: `GET /api/v1/institutes/{id}`
- **Access**: Public (no auth)
- **Parameter**: `id` — integer (required), User ID

### Success Response (200 OK)

```json
{
  "message": "Institute profile retrieved successfully.",
  "success": true,
  "code": 200,
  "data": {
    "id": 5,
    "slug": "delhi-public-school-5",
    "user": {
      "id": 5,
      "name": "Delhi Public School",
      "email": "admin@dps-example.edu.in",
      "role": "institute"
    },
    "profile": {
      "name": "Delhi Public School",
      "description": "A premier CBSE-affiliated school offering holistic education...",
      "specializations": ["Science", "Commerce", "Arts"],
      "courses_offered": ["Class I-XII", "JEE Foundation", "NEET Foundation"],
      "affiliation_number": "2730045",
      "registration_number": "DL/2005/12345",
      "udise_code": "07060100501",
      "aicte_code": null,
      "ugc_code": null,
      "website": "https://dps-example.edu.in",
      "principal_name": "Dr. Sharma",
      "principal_phone": "9876543211",
      "principal_email": "principal@dps-example.edu.in",
      "phone_primary": "9876543210",
      "whatsapp": "9876543210",
      "address": "Sector 24, Vasant Kunj",
      "city": "New Delhi",
      "state": "Delhi",
      "area": "Vasant Kunj",
      "pincode": "110070",
      "latitude": 28.5245,
      "longitude": 77.1555,
      "institute_type": { "id": 1, "label": "School" },
      "institute_category": { "id": 2, "label": "Private" },
      "establishment_year": { "id": 3, "label": "2000-2010" },
      "total_students_range": { "id": 3, "label": "500-1000" },
      "total_teachers_range": { "id": 3, "label": "50-100" },
      "facilities": ["Library", "Computer Lab", "Sports Ground", "Auditorium", "Swimming Pool"],
      "accreditations": ["NAAC A+"],
      "affiliations": ["CBSE"],
      "logo_url": "https://storage.example.com/profile-images/institute_5.jpg",
      "cover_url": "https://storage.example.com/cover-images/institute_5.jpg",
      "gallery_urls": [
        "https://storage.example.com/gallery/img1.jpg",
        "https://storage.example.com/gallery/img2.jpg"
      ]
    },
    "social": {
      "facebook_url": "https://facebook.com/dps-example",
      "instagram_url": "https://instagram.com/dps_example",
      "youtube_url": "https://youtube.com/@dps-example",
      "linkedin_url": "https://linkedin.com/company/dps-example"
    },
    "counts": {
      "total_students": "500-1000",
      "total_teachers": "50-100",
      "total_branches": 3
    },
    "verified": true,
    "is_featured": true,
    "related_institutes": [
      {
        "id": 8,
        "name": "Modern School",
        "slug": "modern-school-8",
        "description": "A leading school in New Delhi...",
        "logo_url": "https://storage.example.com/profile-images/institute_8.jpg",
        "cover_url": null,
        "city": "New Delhi",
        "state": "Delhi",
        "area": "Barakhamba Road",
        "pincode": "110001",
        "institute_type": "School",
        "institute_category": "Private",
        "establishment_year": "Before 1990",
        "total_students": "1000+",
        "total_teachers": "100+",
        "total_branches": 1,
        "facilities": ["Library", "Lab", "Sports"],
        "specializations": ["Science", "Commerce"],
        "courses_offered": ["Class I-XII"],
        "affiliations": ["CBSE"],
        "accreditations": [],
        "principal_name": "Mrs. Gupta",
        "website": "https://modernschool.net",
        "phone_primary": "9876543222",
        "whatsapp": null,
        "latitude": 28.6315,
        "longitude": 77.2167,
        "verified": true,
        "is_featured": false
      }
    ]
  }
}
```

### Show Response Sections

| Section | Fields |
|---------|--------|
| `user` | `id`, `name`, `email`, `role` |
| `profile` | Full institute details — name, description, specializations, courses, registration codes (affiliation, UDISE, AICTE, UGC), contact (principal, phone, whatsapp), location (address, city, state, area, pincode, lat/lng), options (type, category, establishment year, student/teacher ranges as `{id, label}`), media (logo, cover, gallery), facilities, accreditations, affiliations |
| `social` | Non-null social links only — `facebook_url`, `twitter_url`, `instagram_url`, `linkedin_url`, `youtube_url`, `tiktok_url`, `telegram_username`, `discord_username`, `github_url`, `portfolio_url`, `blog_url`, `website_url`. Returns `null` if no links. |
| `counts` | `total_students` (label), `total_teachers` (label), `total_branches` (int) |
| `related_institutes` | Up to 4 related institutes — same structure as list items |

### Key Differences: List vs Show

| Field | List | Show |
|-------|------|------|
| `institute_type` | string label | `{ id, label }` object |
| `institute_category` | string label | `{ id, label }` object |
| `establishment_year` | string label | `{ id, label }` object |
| `total_students` / `total_teachers` | string label | string label (in `counts`) |
| `social` | not included | full social links |
| `gallery_urls` | not included | array of URLs |
| `registration codes` | not included | affiliation_number, udise_code, aicte_code, ugc_code |
| `principal_phone` / `principal_email` | not included | included |
| `address` (formatted) | not included | formatted string |
| `related_institutes` | not included | up to 4 items |

### Error Response (404 Not Found)

```json
{
  "message": "Institute not found.",
  "success": false,
  "code": 404
}
```

---

## Response Format

All endpoints use the standard API envelope:

```json
{
  "message": "Success or error message",
  "success": true,
  "code": 200,
  "data": { ... }
}
```

For errors: `success` is `false` and `errors` may contain details.

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 404 | Institute not found (`show` only) |
| 422 | Validation failed (if used with validation middleware) |
| 500 | Internal server error |

---

## Internal Behavior & Notes

### Architecture

```
PublicInstituteController (thin — 88 lines)
  ├── PublicInstituteService (query logic — 265 lines)
  └── PublicInstituteFormatter (response shaping — 194 lines)
```

### Data Flow

Queries the **User → Profile → ProfileInstituteInfo** relationship chain (not the `institutes` table):

| Table | Data |
|-------|------|
| `users` | role, email_verified_at, registration_fee_status |
| `profiles` | city, state, bio, display_name, slug, profile_image, etc. |
| `profile_institute_info` | institute_name, institute_type_id, facilities, etc. |
| `profile_social_links` | Social media URLs (show endpoint only) |

### Dependencies

| Helper/Class | Purpose |
|--------------|---------|
| `FilterOptionsHelper` | `buildFromConfig`, `getActiveSubjects`, `paginationMeta`, `fallbackPaginationMeta` |
| `PublicProfileOptionsMapper` | Map option IDs to `{ id, label }` for show responses |
| `PublicInstituteService` | All query building, filtering, sorting, caching |
| `PublicInstituteFormatter` | Response formatting for list and show |

### Caching

| Key | TTL | Description |
|-----|-----|-------------|
| `institute_related:{id}` | 300 s (5 min) | Related institutes for show page |
| `institute_cities_profile` | 3600 s (1 h) | Cities with institute counts |
| `filter_options:*` | 3600 s | Config-based option lists |
| `filter_subjects` | 3600 s | Active subjects |

### Filter Logic

- **`location`** — If present, searches across `city`, `area`, and `state`; individual `city`/`area`/`state` params are ignored.
- **`city`** / **`area`** / **`state`** — Used only when `location` is not set.
- **`type`** is an alias for `institute_type`; **`category`** for `institute_category`; **`established`** for `establishment_year_range`; **`total_teachers`** for `total_teachers_range`.
- **`search`** — Searches across `users.name`, `profile.display_name`, `profile.bio`, `profile_institute_info.institute_name`, and `profile_institute_info.institute_description`.

### Empty Result Fallback

When no institutes match filters, the list endpoint returns up to **`per_page` recently registered institutes** (excluding any IDs from the original query). Pagination metadata switches to a fallback structure indicating a single page.

### Related Institutes

- Same city **or** same `institute_type_id` **or** same `institute_category_id`.
- Excludes the current institute.
- Limited to 4 institutes.
- Cached per User ID for 5 minutes.

### Option Structures

- **List endpoint**: Option fields (`institute_type`, `institute_category`, `establishment_year`, `total_students`, `total_teachers`) return **string labels** directly for easy card rendering.
- **Show endpoint**: Same fields return **`{ "id": <int>, "label": "<string>" }` objects** (or `null`) for richer detail pages.

---

## cURL Examples

```bash
# Get filter options
curl -X GET "http://localhost:8000/api/v1/institutes/options" \
  -H "Accept: application/json"

# List all institutes (default: recent first, 12 per page)
curl -X GET "http://localhost:8000/api/v1/institutes" \
  -H "Accept: application/json"

# List with filters: Delhi + School type + verified
curl -X GET "http://localhost:8000/api/v1/institutes?city=Delhi&type=1&verified=true" \
  -H "Accept: application/json"

# Search by name + sort by name A-Z
curl -X GET "http://localhost:8000/api/v1/institutes?search=Engineering&order_by=name_asc" \
  -H "Accept: application/json"

# Location search + category + paginated
curl -X GET "http://localhost:8000/api/v1/institutes?location=Mumbai&category=2&per_page=20" \
  -H "Accept: application/json"

# Featured institutes with many students
curl -X GET "http://localhost:8000/api/v1/institutes?featured=true&total_students_range=4" \
  -H "Accept: application/json"

# Show institute by User ID
curl -X GET "http://localhost:8000/api/v1/institutes/5" \
  -H "Accept: application/json"
```

---

*Last Updated: March 2026*
