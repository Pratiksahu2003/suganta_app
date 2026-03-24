# Google API v4 (Current)

This document reflects the latest routes from `routes/api/v4.php` and current controller behavior.

## Base

- Base URL: `/api/v4/google`
- Auth: `Authorization: Bearer {sanctum_token}` for protected routes
- Content types:
  - JSON for most endpoints
  - `multipart/form-data` for drive upload

## Standard response format

### Success

```json
{
  "message": "Success message",
  "success": true,
  "code": 200,
  "data": {}
}
```

### Error

```json
{
  "message": "Readable error message",
  "success": false,
  "code": 422,
  "errors": {
    "field": [
      "Validation message"
    ]
  }
}
```

---

## Public endpoints

### `POST /api/v4/google/webhook`
- Auth: no
- Query fields: none
- Body fields: none (called by Google servers)
- Response:

```json
{
  "message": "Webhook received."
}
```

### `GET /api/v4/google/oauth/callback`
- Auth: no
- Query fields from Google:
  - `code` (string, optional when success)
  - `state` (string, optional when success)
  - `scope` (string, optional)
  - `error` (string, optional on failure)
  - `error_description` (string, optional on failure)
- Body fields: none
- Success response data:
  - `code`
  - `state`
  - `scope`
  - `hint`

---

## Protected endpoints

## OAuth and connection

### `POST /api/v4/google/connect`
- Query fields: none
- Body fields:
  - `refresh_token` (string, required)
  - `access_token` (string, optional)
  - `expires_in` (integer, optional)
  - `google_email` (string/email, optional)
  - `google_calendar_id` (string, optional)
- Response data:
  - `connected` (bool)
  - `google_email` (string|null)
  - `google_calendar_id` (string|null)
  - `token_expires_at` (string|null, ISO8601)
  - `token_valid` (bool)

### `GET /api/v4/google/oauth/url`
- Query fields:
  - `redirect_uri` (string/url, optional override)
- Body fields: none
- Response data:
  - `oauth_url` (string)
  - `state` (string)
  - `redirect_uri` (string)
  - `scopes` (array of string)

### `POST /api/v4/google/oauth/exchange-code`
- Query fields: none
- Body fields:
  - `code` (string, required)
  - `state` (string, required in current flow)
  - `redirect_uri` (string/url, optional override)
- Response data:
  - `access_token` (string)
  - `refresh_token_received` (bool)
  - `expires_in` (integer)
  - `status` (object, same shape as `connect` status)

### `DELETE /api/v4/google/disconnect`
- Query fields: none
- Body fields: none
- Response data: same shape as status object

### `GET /api/v4/google/status`
- Query fields: none
- Body fields: none
- Response data:
  - status object (`connected`, `google_email`, `google_calendar_id`, `token_expires_at`, `token_valid`)
  - `urls` object:
    - `webhook_url`
    - `return_url`
    - `oauth_exchange_endpoint`
    - `oauth_url_endpoint`
    - `oauth_callback_endpoint`
    - `webhook_endpoint`
  - `watch_channels` array with items:
    - `id`
    - `resource_type`
    - `channel_id`
    - `status`
    - `expires_at`
    - `last_notification_at`
    - `last_message_number`

### `GET /api/v4/google/urls`
- Query fields: none
- Body fields: none
- Response data:
  - `webhook_url`
  - `return_url`
  - `oauth_exchange_endpoint`
  - `oauth_url_endpoint`
  - `oauth_callback_endpoint`
  - `webhook_endpoint`

### `POST /api/v4/google/token/refresh`
- Query fields: none
- Body fields: none
- Response data:
  - `access_token`
  - `status` (status object)

---

## Watch endpoints

### `POST /api/v4/google/watch`
- Query fields: none
- Body fields:
  - `resource_type` (`calendar` | `drive`, required)
  - `ttl_seconds` (integer, optional)
  - `access_token` (string, optional override)
- Response data:
  - `channel` object (DB record fields)

### `DELETE /api/v4/google/watch/{channelId}`
- Path fields:
  - `channelId` (string, required)
- Query fields: none
- Body fields: none
- Response data: none

---

## Combined sync

### `POST /api/v4/google/sync`
- Query fields: none
- Body fields:
  - `access_token` (string, optional override)
  - `sync` (array, optional: `calendar`, `youtube`, `drive`)
  - `calendar.max_results` (integer, optional)
  - `youtube.max_results` (integer, optional)
  - `drive.page_size` (integer, optional)
  - `drive.order_by` (string, optional)
  - `drive.page_token` (string, optional)
  - `drive.query` (string, optional)
- Response behavior:
  - Returns partial success when one service fails and others succeed.
  - Returns full error when all requested services fail.
- Success / partial response data:
  - `resources` (object)
    - `calendar` (object, when requested + success)
    - `youtube` (object, when requested + success)
    - `drive` (object, when requested + success)
  - `errors` (object)
    - `calendar` (string, when failed)
    - `youtube` (string, when failed)
    - `drive` (string, when failed)

Example partial success:

```json
{
  "message": "Google sync partially completed.",
  "success": true,
  "code": 200,
  "data": {
    "resources": {
      "calendar": {
        "items": []
      }
    },
    "errors": {
      "youtube": "Google API error (403): Request had insufficient authentication scopes.",
      "drive": "Google API error (400): ... "
    }
  }
}
```

Example full failure:

```json
{
  "message": "Google sync failed for all requested services.",
  "success": false,
  "code": 400,
  "errors": {
    "calendar": "Google API error (400): ...",
    "youtube": "Google API error (400): ...",
    "drive": "Google API error (400): ..."
  }
}
```

---

## Calendar endpoints

### `POST /api/v4/google/calendar/events`
- Query fields: none
- Body fields:
  - `access_token` (string, optional)
  - `calendar.max_results` (integer, optional)
- Response data: Google Calendar list response payload

### `POST /api/v4/google/calendar/events/create`
- Query fields: none
- Body fields:
  - `summary` (string, required)
  - `description` (string, optional)
  - `location` (string, optional)
  - `start` (datetime string, required)
  - `end` (datetime string, required)
  - `timezone` (string, optional)
  - `attendees` (array, optional)
    - `attendees[].email` (email, required when attendees provided)
    - `attendees[].display_name` (string, optional)
    - `attendees[].optional` (bool, optional)
  - `with_google_meet` (bool, optional)
  - `reminders.use_default` (bool, optional)
  - `reminders.overrides` (array, optional)
  - `access_token` (string, optional)
- Response data: created Google event payload

### `GET /api/v4/google/calendar/events/{eventId}`
- Path fields:
  - `eventId` (string, required)
- Query fields: none
- Body fields: none
- Response data: Google event payload

### `PUT /api/v4/google/calendar/events/{eventId}`
- Path fields:
  - `eventId` (string, required)
- Query fields: none
- Body fields: same as create event
- Response data: updated Google event payload

### `DELETE /api/v4/google/calendar/events/{eventId}`
- Path fields:
  - `eventId` (string, required)
- Query fields: none
- Body fields: none
- Response data: none

---

## YouTube endpoint

### `POST /api/v4/google/youtube/channels`
- Query fields: none
- Body fields:
  - `access_token` (string, optional)
  - `youtube.max_results` (integer, optional)
- Response data: Google YouTube channels payload

---

## Drive endpoints

### `POST /api/v4/google/drive/files`
- Query fields: none
- Body fields:
  - `access_token` (string, optional)
  - `drive.page_size` (integer, optional)
  - `drive.order_by` (string, optional)
- Response data: Google Drive files payload

### `POST /api/v4/google/drive/files/search`
- Query fields: none
- Body fields:
  - `access_token` (string, optional)
  - `drive.query` (string, optional)
  - `drive.page_size` (integer, optional)
  - `drive.page_token` (string, optional)
  - `drive.order_by` (string, optional)
- Response data: Google Drive search payload

### `POST /api/v4/google/drive/files/upload`
- Query fields: none
- Body fields (`multipart/form-data`):
  - `file` (file, required)
  - `name` (string, optional)
  - `parent_id` (string, optional)
  - `mime_type` (string, optional)
  - `access_token` (string, optional)
- Response data: uploaded file payload

### `POST /api/v4/google/drive/folders/create`
- Query fields: none
- Body fields:
  - `name` (string, required)
  - `parent_id` (string, optional)
  - `access_token` (string, optional)
- Response data: created folder payload

### `PATCH /api/v4/google/drive/files/{fileId}/move`
- Path fields:
  - `fileId` (string, required)
- Query fields: none
- Body fields:
  - `new_parent_id` (string, required)
  - `remove_parent_id` (string, optional)
  - `access_token` (string, optional)
- Response data: moved file payload

### `POST /api/v4/google/drive/files/{fileId}/share`
- Path fields:
  - `fileId` (string, required)
- Query fields: none
- Body fields:
  - `email` (email, required)
  - `role` (`reader|commenter|writer`, required)
  - `type` (`user|group|domain|anyone`, optional)
  - `send_notification_email` (bool, optional)
  - `access_token` (string, optional)
- Response data: permission/share payload

### `PATCH /api/v4/google/drive/files/{fileId}/rename`
- Path fields:
  - `fileId` (string, required)
- Query fields: none
- Body fields:
  - `name` (string, required)
  - `access_token` (string, optional)
- Response data: renamed file payload

### `DELETE /api/v4/google/drive/files/{fileId}`
- Path fields:
  - `fileId` (string, required)
- Query fields: none
- Body fields: none
- Response data: none

---

## Env configuration (current)

- `GOOGLE_OAUTH_CLIENT_JSON` (recommended, e.g. `storage/keys/suganta-sync.json`)
- `GOOGLE_CLIENT_ID` (optional fallback)
- `GOOGLE_CLIENT_SECRET` (optional fallback)
- `GOOGLE_OAUTH_AUTHORIZE_URL`
- `GOOGLE_OAUTH_TOKEN_URL`
- `GOOGLE_REDIRECT_URI`
- `GOOGLE_OAUTH_STATE_TTL_SECONDS`
- `GOOGLE_DEFAULT_SCOPES`
- `GOOGLE_WEBHOOK_URL`
- `GOOGLE_WEBHOOK_SECRET`
- `GOOGLE_WEBHOOK_REPLAY_WINDOW_SECONDS`
- `GOOGLE_WATCH_TOKEN_TTL_SECONDS`
- `GOOGLE_WATCH_RENEW_BEFORE_SECONDS`

---

## Current request/response handling notes

- Access token normalization:
  - If `access_token` starts with `Bearer `, backend strips prefix automatically.
  - Control characters / line breaks are removed before sending to Google.
  - Empty or too-short token is rejected with clear `422` style message.
- Calendar request format:
  - `timeMin` is sent as RFC3339 Z format.
  - `maxResults`, `singleEvents`, `orderBy=startTime` are always included.
- Drive request format:
  - Drive list/search internally includes all required query fields and all-drives flags.
  - Start page token call includes `supportsAllDrives=true`.
- YouTube request format:
  - `part=snippet,statistics,contentDetails` and `mine=true` are always included.
  - Authorization header is always attached from normalized token.
- Logging (server side):
  - Every Google API/OAuth call logs URL, method, query/request payload, status, and trimmed response body.
  - HTML responses from Google are detected and logged as malformed/upstream mismatch for easier production debugging.
