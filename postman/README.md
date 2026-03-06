# Portfolio API - Postman Collection

## Import

1. Open Postman
2. File → Import → Upload `Portfolio_API.postman_collection.json`
3. Set variables: `base_url`, `token`, `portfolio_id`

## Variables

| Variable | Description |
|----------|-------------|
| `base_url` | `http://localhost:8000/api/v1` |
| `token` | Bearer token from Login |
| `portfolio_id` | Portfolio ID for Update (use ID from Create/Show response) |

## Flow

1. **Login** – Get token, copy to collection variable `token`
2. **Get Options** – Fetch dropdown data
3. **Show Portfolios** – List your portfolios
4. **Create Portfolio** – Use form-data (4) for file uploads, raw JSON (4b) for text only
5. **Update Portfolio** – Use form-data (5) for files, raw JSON (5b) for text only

## Raw JSON Payloads

**Create (no files):**
```json
{
    "title": "My Project",
    "description": "Project description",
    "category": "Web Development, SaaS",
    "tags": "Laravel, Vue.js, MySQL",
    "url": "https://example.com/project",
    "status": "published",
    "order": 0,
    "is_featured": true
}
```

**Update:**
```json
{
    "title": "Updated Title",
    "description": "Updated description",
    "category": "Web Development, Mobile",
    "tags": "Laravel, React, PostgreSQL",
    "url": "https://example.com/updated",
    "status": "published",
    "order": 1,
    "is_featured": false
}
```

**Note:** Create/Update with file uploads must use `form-data`, not raw JSON.
