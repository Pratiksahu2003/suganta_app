# Google API v4

This document describes advanced Google integration APIs under `v4`, including OAuth code exchange, token lifecycle, sync, and user actions (add/edit/delete/upload/share/move) for Calendar and Drive.

## Base

- Base URL: `/api/v4/google`
- Auth: `Authorization: Bearer {sanctum_token}`
- Content-Type: `application/json`

## Environment setup

Set these in `.env`:

- `CACHE_STORE=redis`
- `GOOGLE_CLIENT_ID=...`
- `GOOGLE_CLIENT_SECRET=...`
- `GOOGLE_OAUTH_TOKEN_URL=https://oauth2.googleapis.com/token`
- `GOOGLE_REDIRECT_URI=...`
- `GOOGLE_WEBHOOK_URL=https://api.yourdomain.com/api/v4/google/webhook`
- `GOOGLE_WEBHOOK_SECRET=long-random-secret`
- `GOOGLE_WEBHOOK_REPLAY_WINDOW_SECONDS=300`
- `GOOGLE_WATCH_TOKEN_TTL_SECONDS=86400`
- `GOOGLE_WATCH_RENEW_BEFORE_SECONDS=900`
- `GOOGLE_CALENDAR_BASE_URL=https://www.googleapis.com/calendar/v3`
- `GOOGLE_YOUTUBE_BASE_URL=https://www.googleapis.com/youtube/v3`
- `GOOGLE_DRIVE_BASE_URL=https://www.googleapis.com/drive/v3`

---

## 1) Connect Google account

### `POST /api/v4/google/connect`

Store refresh token and optional access metadata for the authenticated user.

Request body:

```json
{
  "refresh_token": "1//0g_refresh_token",
  "access_token": "ya29.a0_access_token_optional",
  "expires_in": 3600,
  "google_email": "user@gmail.com",
  "google_calendar_id": "primary"
}
```

Success response:

```json
{
  "message": "Google account connected successfully.",
  "success": true,
  "code": 200,
  "data": {
    "connected": true,
    "google_email": "user@gmail.com",
    "google_calendar_id": "primary",
    "token_expires_at": "2026-03-24T15:00:00Z",
    "token_valid": true
  }
}
```

---

## 1.1) OAuth authorization code exchange (recommended)

### `POST /api/v4/google/oauth/exchange-code`

Exchange Google OAuth `code` server-side and store tokens for current user.

Request body:

```json
{
  "code": "4/0AeaYSH...",
  "redirect_uri": "https://your-app.com/oauth/google/callback"
}
```

---

## 2) Check connection status

### `GET /api/v4/google/status`

Returns whether Google is connected and token health.

---

## 3) Refresh token manually

### `POST /api/v4/google/token/refresh`

Refreshes Google access token using stored refresh token.

---

## 4) Watch/Webhook for real-time sync

### Start watch channel

`POST /api/v4/google/watch`

```json
{
  "resource_type": "calendar",
  "ttl_seconds": 3600
}
```

`resource_type` supports:
- `calendar`
- `drive`

### Stop watch channel

`DELETE /api/v4/google/watch/{channelId}`

### Webhook callback (public)

`POST /api/v4/google/webhook`

This endpoint is called by Google push notifications. It verifies channel token and updates cache version so next API fetch returns latest data.

Security hardening included:
- Signed channel token validation (HMAC).
- Token expiry validation.
- Replay protection window using message-number dedupe cache.

Auto renewal:
- Scheduled command `google:watches-renew` runs every 10 minutes.
- Channels expiring in the next `GOOGLE_WATCH_RENEW_BEFORE_SECONDS` are renewed automatically.
- Manual test: `php artisan google:watches-renew --dry-run`

### Get URLs (webhook + return URL)

`GET /api/v4/google/urls`

Returns:
- `webhook_url`
- `return_url` (OAuth redirect URL)
- `oauth_exchange_endpoint`
- `webhook_endpoint`

---

## 5) Disconnect Google account

### `DELETE /api/v4/google/disconnect`

Removes stored access/refresh tokens and Google identity info.

---

## 6) Sync all resources

### `POST /api/v4/google/sync`

Sync selected Google resources in one API call.

Request body:

```json
{
  "sync": ["calendar", "youtube", "drive"],
  "calendar": { "max_results": 20 },
  "youtube": { "max_results": 10 },
  "drive": { "page_size": 50, "order_by": "modifiedTime desc" }
}
```

Notes:

- `access_token` can be passed as optional override.
- If not provided, server uses stored token and auto-refresh.

---

## 7) Calendar APIs

### A) List events

`POST /api/v4/google/calendar/events`

Request body:

```json
{
  "calendar": { "max_results": 20 }
}
```

### B) Create event

`POST /api/v4/google/calendar/events/create`

Request body:

```json
{
  "summary": "Math Class",
  "description": "Algebra revision",
  "location": "Online",
  "start": "2026-03-25T10:00:00+05:30",
  "end": "2026-03-25T11:00:00+05:30",
  "timezone": "Asia/Kolkata"
}
```

### C) Update event

`PUT /api/v4/google/calendar/events/{eventId}`

Request body is same as create.

### C.1) Get single event

`GET /api/v4/google/calendar/events/{eventId}`

### D) Delete event

`DELETE /api/v4/google/calendar/events/{eventId}`

---

## 8) YouTube API

### List channels

`POST /api/v4/google/youtube/channels`

Request body:

```json
{
  "youtube": { "max_results": 10 }
}
```

---

## 9) Drive APIs

### A) List files

`POST /api/v4/google/drive/files`

Request body:

```json
{
  "drive": { "page_size": 50, "order_by": "modifiedTime desc" }
}
```

### B) Create folder

`POST /api/v4/google/drive/folders/create`

Request body:

```json
{
  "name": "My App Folder",
  "parent_id": "optional_parent_folder_id"
}
```

### C) Rename file/folder

`PATCH /api/v4/google/drive/files/{fileId}/rename`

Request body:

```json
{
  "name": "New Name"
}
```

### D) Delete file/folder

`DELETE /api/v4/google/drive/files/{fileId}`

### E) Search files with query + pagination

`POST /api/v4/google/drive/files/search`

```json
{
  "drive": {
    "query": "name contains 'report' and trashed=false",
    "page_size": 50,
    "page_token": "next_page_token_optional",
    "order_by": "modifiedTime desc"
  }
}
```

### F) Upload file

`POST /api/v4/google/drive/files/upload`

`multipart/form-data` fields:

- `file` (required)
- `name` (optional)
- `parent_id` (optional)
- `mime_type` (optional)

### G) Move file/folder

`PATCH /api/v4/google/drive/files/{fileId}/move`

```json
{
  "new_parent_id": "folder_id",
  "remove_parent_id": "old_folder_id"
}
```

### H) Share file/folder

`POST /api/v4/google/drive/files/{fileId}/share`

```json
{
  "email": "student@example.com",
  "role": "reader",
  "type": "user",
  "send_notification_email": true
}
```

---

## Error handling

All APIs use standard API envelope:

- `success=false` on errors
- `code` = HTTP status
- `message` = readable error message

Typical cases:

- `401`: invalid/expired app auth token
- `422`: Google not connected (missing refresh token) or validation issue
- `400/403`: Google API permission/scope issue
- `500`: server config issue (missing `GOOGLE_CLIENT_ID/SECRET`)

---

## Recommended Google scopes

Use these OAuth scopes when obtaining refresh token from Google:

- Calendar: `https://www.googleapis.com/auth/calendar`
- Drive: `https://www.googleapis.com/auth/drive`
- YouTube read: `https://www.googleapis.com/auth/youtube.readonly`
- Drive file metadata/share: `https://www.googleapis.com/auth/drive.file`

If scopes are missing, Google endpoints may return permission errors.
