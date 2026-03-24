# Google API v4 (Flutter Guide)

This is the full Flutter implementation guide for all routes in `routes/api/v4.php`.

## Base setup

- Base URL: `https://your-domain.com/api/v4/google`
- Auth header for protected routes: `Authorization: Bearer {sanctum_token}`
- Default content type: `application/json`
- Upload endpoint uses `multipart/form-data`

## Standard response envelope

Success:

```json
{
  "message": "Some success message",
  "success": true,
  "code": 200,
  "data": {}
}
```

Error:

```json
{
  "message": "Validation failed",
  "success": false,
  "code": 422,
  "errors": {
    "field": ["The field is required."]
  }
}
```

## Flutter (Dio) base client

```dart
final dio = Dio(BaseOptions(
  baseUrl: 'https://your-domain.com/api/v4/google',
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer $token',
  },
));
```

---

## Public route (no sanctum)

### 1) `POST /api/v4/google/webhook`
- **Use:** Google server sends push notifications.
- **Called by:** Google only (not your mobile app).
- **Request body:** none from app side.
- **Response example:**

```json
{ "message": "Webhook received." }
```

---

## Protected routes (sanctum required)

### 2) `POST /api/v4/google/connect`
- **Use:** Save Google refresh token + optional access token metadata.
- **Body params:**
  - `refresh_token` (string, required)
  - `access_token` (string, optional)
  - `expires_in` (int, optional)
  - `google_email` (string/email, optional)
  - `google_calendar_id` (string, optional)
- **Flutter body:**

```json
{
  "refresh_token": "1//refresh...",
  "access_token": "ya29.access...",
  "expires_in": 3600,
  "google_email": "user@gmail.com",
  "google_calendar_id": "primary"
}
```

### 3) `POST /api/v4/google/oauth/exchange-code`
- **Use:** Exchange OAuth authorization code on backend.
- **Body params:**
  - `code` (string, required)
  - `redirect_uri` (string/url, optional)

### 3.1) `GET /api/v4/google/oauth/callback` (public)
- **Use:** This should be set as `GOOGLE_REDIRECT_URI` in Google OAuth Console.
- **Auth:** Not required.
- **Query params from Google:** `code`, `scope`, `state`, `error`, `error_description`
- **Flow:** App receives `code` here, then calls `POST /api/v4/google/oauth/exchange-code` with sanctum auth.

### 4) `DELETE /api/v4/google/disconnect`
- **Use:** Remove linked Google account tokens.
- **Body:** none.

### 5) `GET /api/v4/google/status`
- **Use:** Token status + watch channel list.
- **Body:** none.

### 6) `GET /api/v4/google/urls`
- **Use:** Return configured webhook and return URL values.
- **Body:** none.
- **Response `data`:**
  - `webhook_url`
  - `return_url`
  - `oauth_exchange_endpoint`
  - `webhook_endpoint`

### 7) `POST /api/v4/google/watch`
- **Use:** Start Google push watch channel.
- **Body params:**
  - `resource_type` (`calendar|drive`, required)
  - `ttl_seconds` (int, optional)

### 8) `DELETE /api/v4/google/watch/{channelId}`
- **Use:** Stop a watch channel.
- **Path params:**
  - `channelId` (string, required)

### 9) `POST /api/v4/google/token/refresh`
- **Use:** Force refresh of Google access token.
- **Body:** none.

### 10) `POST /api/v4/google/sync`
- **Use:** Sync multiple resources in one call.
- **Body params:**
  - `access_token` (string, optional override)
  - `sync` (array optional: `calendar`, `youtube`, `drive`)
  - `calendar.max_results` (int optional)
  - `youtube.max_results` (int optional)
  - `drive.page_size` (int optional)
  - `drive.order_by` (string optional)
  - `drive.page_token` (string optional)
  - `drive.query` (string optional)

---

## Calendar routes

### 11) `POST /api/v4/google/calendar/events`
- **Use:** List calendar events.
- **Body params:**
  - `access_token` (string, optional)
  - `calendar.max_results` (int, optional)

### 12) `POST /api/v4/google/calendar/events/create`
- **Use:** Create event.
- **Body params:**
  - `summary` (string, required)
  - `description` (string, optional)
  - `location` (string, optional)
  - `start` (datetime string, required)
  - `end` (datetime string, required)
  - `timezone` (string, optional)
  - `attendees` (array optional)
  - `with_google_meet` (bool optional)
  - `reminders.use_default` (bool optional)
  - `reminders.overrides` (array optional)

### 13) `GET /api/v4/google/calendar/events/{eventId}`
- **Use:** Get single event details.
- **Path params:** `eventId` (string, required)

### 14) `PUT /api/v4/google/calendar/events/{eventId}`
- **Use:** Update event.
- **Path params:** `eventId` (string, required)
- **Body params:** same as create.

### 15) `DELETE /api/v4/google/calendar/events/{eventId}`
- **Use:** Delete event.
- **Path params:** `eventId` (string, required)

---

## YouTube route

### 16) `POST /api/v4/google/youtube/channels`
- **Use:** Fetch linked user channels.
- **Body params:**
  - `access_token` (string, optional)
  - `youtube.max_results` (int, optional)

---

## Drive routes

### 17) `POST /api/v4/google/drive/files`
- **Use:** List drive files.
- **Body params:**
  - `access_token` (string, optional)
  - `drive.page_size` (int, optional)
  - `drive.order_by` (string, optional)

### 18) `POST /api/v4/google/drive/files/search`
- **Use:** Search files with query and pagination.
- **Body params:**
  - `access_token` (string, optional)
  - `drive.query` (string, optional)
  - `drive.page_size` (int, optional)
  - `drive.page_token` (string, optional)
  - `drive.order_by` (string, optional)

### 19) `POST /api/v4/google/drive/files/upload`
- **Use:** Upload a file to drive.
- **Content-Type:** `multipart/form-data`
- **Form params:**
  - `file` (file, required)
  - `name` (string, optional)
  - `parent_id` (string, optional)
  - `mime_type` (string, optional)

### 20) `POST /api/v4/google/drive/folders/create`
- **Use:** Create folder.
- **Body params:**
  - `name` (string, required)
  - `parent_id` (string, optional)

### 21) `PATCH /api/v4/google/drive/files/{fileId}/move`
- **Use:** Move file/folder to another parent.
- **Path params:** `fileId` (string, required)
- **Body params:**
  - `new_parent_id` (string, required)
  - `remove_parent_id` (string, optional)
  - `access_token` (string, optional)

### 22) `POST /api/v4/google/drive/files/{fileId}/share`
- **Use:** Share file/folder.
- **Path params:** `fileId` (string, required)
- **Body params:**
  - `email` (string/email, required)
  - `role` (`reader|commenter|writer`, required)
  - `type` (`user|group|domain|anyone`, optional)
  - `send_notification_email` (bool, optional)

### 23) `PATCH /api/v4/google/drive/files/{fileId}/rename`
- **Use:** Rename file/folder.
- **Path params:** `fileId` (string, required)
- **Body params:**
  - `name` (string, required)
  - `access_token` (string, optional)

### 24) `DELETE /api/v4/google/drive/files/{fileId}`
- **Use:** Delete file/folder.
- **Path params:** `fileId` (string, required)

---

## Flutter call examples

JSON POST:

```dart
final res = await dio.post('/sync', data: {
  'sync': ['calendar', 'drive'],
  'calendar': {'max_results': 20},
  'drive': {'page_size': 50}
});
final data = res.data['data'];
```

Path param:

```dart
await dio.delete('/calendar/events/$eventId');
```

Multipart upload:

```dart
final fileName = file.path.split('/').last;
final form = FormData.fromMap({
  'file': await MultipartFile.fromFile(file.path, filename: fileName),
  'name': fileName,
});
final res = await dio.post('/drive/files/upload', data: form);
```

Common error handling:

```dart
try {
  final res = await dio.get('/status');
} on DioException catch (e) {
  final msg = e.response?.data?['message'] ?? 'Something went wrong';
  // show snackbar/toast
}
```

---

## Required env for this module

- `GOOGLE_OAUTH_CLIENT_JSON`
  - Example: `storage/keys/suganta-sync.json`
  - JSON values are used first for `client_id`, `client_secret`, and `redirect_uri`.
- `GOOGLE_CLIENT_ID` (optional fallback if JSON missing)
- `GOOGLE_CLIENT_SECRET` (optional fallback if JSON missing)
- `GOOGLE_OAUTH_TOKEN_URL`
- `GOOGLE_REDIRECT_URI`
  - Recommended: `https://your-domain.com/api/v4/google/oauth/callback`
- `GOOGLE_WEBHOOK_URL`
- `GOOGLE_WEBHOOK_SECRET`
- `GOOGLE_WEBHOOK_REPLAY_WINDOW_SECONDS`
- `GOOGLE_WATCH_TOKEN_TTL_SECONDS`
- `GOOGLE_WATCH_RENEW_BEFORE_SECONDS`
