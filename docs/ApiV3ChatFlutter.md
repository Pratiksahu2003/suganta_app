# API v3 Chat — Flutter implementation reference

All routes are registered under **`/api/v3/chat`**. The Laravel app also applies the global **`/api`** prefix (see `bootstrap/app.php` or route service provider), so the full base path is:

```text
{APP_URL}/api/v3/chat
```

**Authentication:** every endpoint requires a valid **Sanctum** token.

| Header | Value |
|--------|--------|
| `Authorization` | `Bearer {access_token}` |
| `Accept` | `application/json` |
| `Content-Type` | `application/json` (for JSON bodies) |

---

## 1. Standard JSON envelope

### 1.1 Success (200 / 201)

```json
{
  "message": "Human-readable message",
  "success": true,
  "code": 200,
  "data": { }
}
```

- **`201 Created`** is used for some create endpoints; `code` will be `201`.
- When **`data` is omitted**, the API may return only `message`, `success`, and `code`.

### 1.2 Validation error (422)

```json
{
  "message": "Validation failed",
  "success": false,
  "code": 422,
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 1.3 Other errors

| Situation | Typical `code` |
|-----------|----------------|
| Unauthenticated | `401` |
| Forbidden (not a participant, etc.) | `403` |
| Not found | `404` |
| Business rule / conflict | `422` or `400` |

```json
{
  "message": "Error description",
  "success": false,
  "code": 403
}
```

*(No `errors` object unless validation failed.)*

---

## 2. Shared data shapes

### 2.1 `UserSearchItem` (search results)

| Field | Type | Notes |
|-------|------|--------|
| `id` | `int` | User id |
| `name` | `String` | Display name |
| `phone` | `String?` | **Masked** for privacy |
| `profile_image` | `String` | Full URL (or placeholder URL if none) |
| `has_private_conversation` | `bool` | Whether a private chat already exists |
| `private_conversation_id` | `int?` | Conversation id if `has_private_conversation` is true |

### 2.2 `PeerUser` (private chat header / participant `user`)

| Field | Type |
|-------|------|
| `id` | `int` |
| `name` | `String` |
| `phone` | `String?` (masked) |
| `profile_image` | `String` |

### 2.3 `LastMessagePreview` (inbox row)

| Field | Type |
|-------|------|
| `id` | `int` |
| `sender_id` | `int` |
| `sender_name` | `String` | Display name of sender (your own `name` when `is_mine`) |
| `is_mine` | `bool` |
| `text` | `String` (truncated) |
| `created_at` | `String` (ISO 8601) |

`null` if there is no last message.

### 2.4 `ConversationListItem` (`data.data[]` on list conversations)

Built from the server; includes Laravel conversation fields plus:

| Field | Type | Notes |
|-------|------|--------|
| `id` | `int` | |
| `type` | `String` | `"private"` \| `"group"` |
| `title` | `String` | For private: other user’s name; for group: title or `"Group chat"` |
| `display_title` | `String` | Same intent as `title` for UI |
| `created_by` | `int` | User id |
| `last_message_id` | `int?` | |
| `last_message_at` | `String?` | ISO 8601 |
| `created_at` | `String?` | |
| `updated_at` | `String?` | |
| `total_messages` | `int` | From `withCount` |
| `peer` | `PeerUser?` | **Private only**; `null` for groups |
| `user_name` | `String` | Chat label: private = other user’s name; group = group title |
| `last_message` | `LastMessagePreview?` | Includes `sender_name` for subtitle lines like “Name: text” |
| `unread_count` | `int` | Incoming messages after your read cursor |
| `muted` | `bool` | Your mute flag |
| `archived` | `bool` | Your archive flag |
| `last_read_message_id` | `int?` | Your read cursor |

### 2.5 `MyMembership`

| Field | Type |
|-------|------|
| `last_read_message_id` | `int?` |
| `muted` | `bool` |
| `archived` | `bool` |

### 2.6 `ParticipantRow` (conversation detail)

| Field | Type |
|-------|------|
| `id` | `int` | Participant row id |
| `conversation_id` | `int` |
| `user_id` | `int` |
| `role` | `String` | `"admin"` \| `"member"` |
| `joined_at` | `String?` | ISO 8601 |
| `user` | `PeerUser?` | Nested profile + masked phone |

### 2.7 `ChatMessage` (REST-serialized message)

| Field | Type | Notes |
|-------|------|--------|
| `id` | `int` | |
| `conversation_id` | `int` | |
| `sender_id` | `int` | |
| `message` | `String` | Plain text + emoji (sanitized server-side) |
| `reply_to` | `int?` | Parent message id |
| `meta` | `dynamic` | Always `null` for new messages (text-only channel) |
| `is_edited` | `bool` | |
| `edited_at` | `String?` | ISO 8601 |
| `deleted_at` | `String?` | Soft delete |
| `created_at` | `String?` | ISO 8601 |
| `updated_at` | `String?` | ISO 8601 |
| `reply_to_message` | `object?` | See below |
| `reaction_summary` | `List<ReactionSummaryItem>` | |
| `my_reaction` | `String?` | Emoji you sent, or `null` |
| `read_by_user_ids` | `List<int>` | Users who marked read |

**`reply_to_message`** (when `reply_to` is set):

| Field | Type |
|-------|------|
| `id` | `int` |
| `sender_id` | `int?` |
| `message` | `String?` | Truncated parent text |
| `created_at` | `String?` |
| `is_unavailable` | `bool` | `true` if parent missing or deleted |

**`ReactionSummaryItem`:**

| Field | Type |
|-------|------|
| `emoji` | `String` |
| `count` | `int` |
| `user_ids` | `List<int>` |

---

## 3. Endpoints (every route in `routes/api/v3.php`)

### 3.1 Search users (start DM)

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v3/chat/users/search` |

**Query parameters**

| Name | Required | Type | Rules |
|------|----------|------|--------|
| `q` | Yes | `String` | Min length `2`, max `100` |
| `limit` | No | `int` | `1`–`50`, default `20` |

**Example**

`GET /api/v3/chat/users/search?q=rahul&limit=10`

**Success `data`**

```json
{
  "query": "rahul",
  "total": 1,
  "users": [
    {
      "id": 18,
      "name": "Rahul Sharma",
      "phone": "+91 ••••••3210",
      "profile_image": "https://example.com/storage/...",
      "has_private_conversation": true,
      "private_conversation_id": 7
    }
  ]
}
```

---

### 3.2 List my conversations

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v3/chat/conversations` |

**Query parameters**

| Name | Required | Type | Values |
|------|----------|------|--------|
| `folder` | No | `String` | `inbox` (default), `archived`, `all` |

- **`inbox`:** not archived (for you).
- **`archived`:** only archived (for you).
- **`all`:** both.

**Pagination:** Laravel default page query `?page=2` applies (`per_page` is fixed at **20** on this endpoint).

**Success `data`**

Paginator fields are **merged** with `folder`:

| Field | Type |
|-------|------|
| `folder` | `String` |
| `current_page` | `int` |
| `data` | `List<ConversationListItem>` |
| `first_page_url` | `String` |
| `from` | `int?` |
| `last_page` | `int` |
| `last_page_url` | `String` |
| `links` | `List` |
| `next_page_url` | `String?` |
| `path` | `String` |
| `per_page` | `int` |
| `prev_page_url` | `String?` |
| `to` | `int?` |
| `total` | `int` |

---

### 3.3 Create conversation

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/conversations` |

**JSON body**

| Field | Required | Type | Rules |
|-------|----------|------|--------|
| `type` | Yes | `String` | `private` or `group` |
| `title` | No | `String` | Max `255`; ignored for `private` (stored as `null`) |
| `participants` | Yes | `List<int>` | Min `1` distinct user ids; must exist in `users` |

**Rules**

- **`private`:** exactly **one** other participant (after removing yourself if present).
- **`group`:** at least **two** other participants.

**Success**

- **201** if a new conversation is created.
- **200** if **`private`** and a private chat with that user **already exists** (`message` will say so).

**`data`**

```json
{
  "conversation": { }
}
```

`conversation` matches **`ConversationListItem`** shape (same presenter as list/detail).

---

### 3.4 Get one conversation

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v3/chat/conversations/{conversation}` |

**Path parameters**

| Name | Type |
|------|------|
| `conversation` | `int` |

**Success `data`**

```json
{
  "conversation": { },
  "participants": [ ],
  "my_membership": {
    "last_read_message_id": 120,
    "muted": false,
    "archived": false
  }
}
```

---

### 3.5 Mute / archive (my view)

| | |
|---|---|
| **Method** | `PATCH` |
| **URL** | `/api/v3/chat/conversations/{conversation}` |

**Path parameters**

| Name | Type |
|------|------|
| `conversation` | `int` |

**JSON body** (at least one field required)

| Field | Required | Type |
|-------|----------|------|
| `muted` | No | `bool` |
| `archived` | No | `bool` |

**Success `data`**

```json
{
  "my_membership": {
    "last_read_message_id": 120,
    "muted": true,
    "archived": false
  }
}
```

**422** if body is empty `{}`.

---

### 3.6 Mark conversation read (inbox cursor)

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/conversations/{conversation}/read` |

**Path parameters**

| Name | Type |
|------|------|
| `conversation` | `int` |

**JSON body** (optional)

| Field | Required | Type |
|-------|----------|------|
| `message_id` | No | `int` | Must belong to this conversation and not be deleted |

If **`message_id` is omitted**, the server uses the conversation’s **`last_message_id`** (if any).

**Success `data`**

```json
{
  "last_read_message_id": 150,
  "read_at": "2026-03-21T12:00:00+00:00"
}
```

**Realtime:** other clients on the channel receive **`chat.conversation.read_state`** (see §5).

---

### 3.7 Add participant (group, admin only)

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/conversations/{conversation}/participants` |

**JSON body**

| Field | Required | Type |
|-------|----------|------|
| `user_id` | Yes | `int` | Must exist in `users` |

**Success `data`**

Empty or minimal success payload (no nested resource in code).

---

### 3.8 Remove participant (group, admin only)

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v3/chat/conversations/{conversation}/participants/{user}` |

**Path parameters**

| Name | Type |
|------|------|
| `conversation` | `int` |
| `user` | `int` | User id to remove |

---

### 3.9 Leave conversation

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/conversations/{conversation}/leave` |

**Path parameters**

| Name | Type |
|------|------|
| `conversation` | `int` |

---

### 3.10 List messages

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v3/chat/conversations/{conversation}/messages` |

**Path parameters**

| Name | Type |
|------|------|
| `conversation` | `int` |

**Query parameters**

| Name | Required | Type | Rules |
|------|----------|------|--------|
| `before_id` | No | `int` | Load messages **older** than this id (`id < before_id`) |
| `per_page` | No | `int` | `1`–`100`, default `50` |
| `page` | No | `int` | Standard Laravel page |

Default order: **newest first** (`id` descending). Use **`before_id`** from the **smallest** id you already have to load history upward in the UI.

**Success `data`**

Standard Laravel paginator JSON; each item in **`data`** is a **`ChatMessage`** (§2.7).

---

### 3.11 Send message

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/conversations/{conversation}/messages` |

**JSON body**

| Field | Required | Type | Rules |
|-------|----------|------|--------|
| `message` | Yes | `String` | Max `10000`; sanitized (plain text + emoji) |
| `reply_to` | No | `int` | Parent message id in **this** conversation |

**422** if, after sanitization, the message is empty.

**Success:** **201** `data`:

```json
{
  "message": { }
}
```

`message` is a full **`ChatMessage`** (§2.7).

---

### 3.12 Edit message

| | |
|---|---|
| **Method** | `PATCH` |
| **URL** | `/api/v3/chat/messages/{message}` |

**JSON body**

| Field | Required | Type |
|-------|----------|------|
| `message` | Yes | `String` | Max `10000` |

**403** if not the sender. **422** if deleted or empty after sanitize.

**Success `data`**

```json
{
  "message": { }
}
```

---

### 3.13 Delete message (soft)

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v3/chat/messages/{message}` |

**403** if not the sender.

---

### 3.14 Mark one message read

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/messages/{message}/read` |

No body. Updates per-message read + may advance **`last_read_message_id`** for you.

**Realtime:** **`chat.message.read`**.

---

### 3.15 Add / change reaction

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/messages/{message}/reaction` |

**JSON body**

| Field | Required | Type | Rules |
|-------|----------|------|--------|
| `reaction` | Yes | `String` | Max `16`; must contain at least one Unicode **emoji** (`Extended_Pictographic`) |

**Realtime:** **`chat.message.reaction.updated`**.

---

### 3.16 Remove my reaction

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v3/chat/messages/{message}/reaction` |

No body.

**Realtime:** **`chat.message.reaction.updated`** with `reaction: null`.

---

### 3.17 Typing indicator

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v3/chat/conversations/{conversation}/typing` |

**JSON body**

| Field | Required | Type |
|-------|----------|------|
| `is_typing` | Yes | `bool` |

**Realtime:** **`chat.user.typing`**.

---

## 4. Flutter quick notes

### 4.1 Parsing

- Prefer **`jsonDecode`** + typed models (e.g. `freezed`, `json_serializable`).
- Treat **`data`** as nullable when `success == false`.
- For **422**, read **`errors`** as `Map<String, dynamic>` where values are usually `List<dynamic>`.

### 4.2 HTTP client

```dart
final headers = {
  'Authorization': 'Bearer $token',
  'Accept': 'application/json',
  'Content-Type': 'application/json',
};
```

Use `Uri.parse('$baseUrl/api/v3/chat/...')` and encode query strings for `GET`.

### 4.3 Pagination

- **Conversations:** read `data['data']` as list; use `next_page_url` or `current_page` + `last_page` for “load more”.
- **Messages:** same; pass `before_id` for older messages.

### 4.4 Opening a chat

1. `GET` messages (newest page).
2. `POST .../conversations/{id}/read` to clear **`unread_count`** for the other user’s realtime + your cursor.

---

## 5. Realtime (Reverb / Pusher protocol) — summary

Backend channel name (see `routes/channels.php`): **`chat.conversation.{conversationId}`**.

With **Laravel Echo / Pusher protocol**, private channels are subscribed as:

```text
private-chat.conversation.{conversationId}
```

(i.e. the `private-` prefix is added automatically by the client library.)

**Events (server `broadcastAs`)**

| Event name | Payload highlights |
|------------|-------------------|
| `chat.message.sent` | `id`, `conversation_id`, `sender_id`, `message`, `reply_to`, `is_edited`, `created_at` *(REST list has more fields; refresh or merge locally)* |
| `chat.message.read` | `conversation_id`, `message_id`, `user_id`, `read_at` |
| `chat.conversation.read_state` | `conversation_id`, `user_id`, `last_read_message_id`, `read_at` |
| `chat.message.reaction.updated` | `conversation_id`, `message_id`, `user_id`, `reaction` (`null` if removed) |
| `chat.user.typing` | `conversation_id`, `user_id`, `is_typing` |

Use **`/broadcasting/auth`** with the same Bearer token for private channel authorization.

---

## 6. Route checklist (maps to `routes/api/v3.php`)

| # | Method | Path |
|---|--------|------|
| 1 | GET | `users/search` |
| 2 | GET | `conversations` |
| 3 | POST | `conversations` |
| 4 | GET | `conversations/{conversation}` |
| 5 | PATCH | `conversations/{conversation}` |
| 6 | POST | `conversations/{conversation}/read` |
| 7 | POST | `conversations/{conversation}/participants` |
| 8 | DELETE | `conversations/{conversation}/participants/{user}` |
| 9 | POST | `conversations/{conversation}/leave` |
| 10 | GET | `conversations/{conversation}/messages` |
| 11 | POST | `conversations/{conversation}/messages` |
| 12 | PATCH | `messages/{message}` |
| 13 | DELETE | `messages/{message}` |
| 14 | POST | `messages/{message}/read` |
| 15 | POST | `messages/{message}/reaction` |
| 16 | DELETE | `messages/{message}/reaction` |
| 17 | POST | `conversations/{conversation}/typing` |

All of the above are under **`/api/v3/chat/`** with **`auth:sanctum`**.
