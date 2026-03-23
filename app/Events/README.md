# Broadcast events reference

All events under `App\Events\Chat` implement `ShouldBroadcastNow` and are sent on **private** conversation channels. They are intended for Laravel Echo / Pusher-compatible clients (e.g. Reverb).

## Subscribing

| Item | Value |
|------|--------|
| Backend channel name | `chat.conversation.{conversationId}` |
| Client / wire channel name | `private-chat.conversation.{conversationId}` |
| Authorization | Registered in `routes/channels.php`; user must be an active participant. |

## Wire envelope (Pusher protocol)

Clients receive messages roughly shaped like:

```json
{
  "event": "<broadcastAs name>",
  "channel": "private-chat.conversation.<id>",
  "data": "<stringified JSON object with the payload below>"
}
```

Some clients parse `data` automatically into an object. Laravel Echo maps `broadcastAs` names to listeners (e.g. `.listen('.chat.message.sent', ...)` — note the leading `.` when using custom event names).

## `toOthers()`

Most chat broadcasts are dispatched with `->toOthers()`, so the **HTTP client that triggered the action usually does not receive the same WebSocket event**; other subscribers on the channel do.

---

## `chat.message.sent`

**Class:** `App\Events\Chat\MessageSent`  
**When:** A new message is created or an existing message is updated (broadcast from `MessageController`).  
**Channel:** `private-chat.conversation.{conversation_id}`

### Payload (`data`)

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Message primary key. |
| `conversation_id` | integer | Conversation this message belongs to. |
| `sender_id` | integer | User who sent the message. |
| `message` | string | Message body text. |
| `reply_to` | integer \| null | ID of the message being replied to, if any. |
| `meta` | object \| array | Extra metadata (JSON); shape is application-defined. |
| `is_edited` | boolean | Whether the message was edited. |
| `created_at` | string \| null | ISO 8601 timestamp from `created_at`, or omitted if null. |

### Example

```json
{
  "id": 42,
  "conversation_id": 7,
  "sender_id": 3,
  "message": "Hello",
  "reply_to": null,
  "meta": {},
  "is_edited": false,
  "created_at": "2025-03-21T12:00:00+00:00"
}
```

---

## `chat.user.typing`

**Class:** `App\Events\Chat\UserTyping`  
**When:** A participant updates typing status via the typing API.  
**Channel:** `private-chat.conversation.{conversation_id}`

### Payload (`data`)

| Field | Type | Description |
|-------|------|-------------|
| `conversation_id` | integer | Conversation ID. |
| `user_id` | integer | User whose typing state changed. |
| `is_typing` | boolean | `true` if typing, `false` when stopped. |

### Example

```json
{
  "conversation_id": 7,
  "user_id": 3,
  "is_typing": true
}
```

---

## `chat.message.read`

**Class:** `App\Events\Chat\MessageRead`  
**When:** A message is marked read for the current user.  
**Channel:** `private-chat.conversation.{conversation_id}`

### Payload (`data`)

| Field | Type | Description |
|-------|------|-------------|
| `conversation_id` | integer | Conversation ID. |
| `message_id` | integer | Message that was marked read. |
| `user_id` | integer | User who read the message. |
| `read_at` | string | ISO 8601 read timestamp (from server `now()` at persist time). |

### Example

```json
{
  "conversation_id": 7,
  "message_id": 42,
  "user_id": 3,
  "read_at": "2025-03-21T12:05:00+00:00"
}
```

---

## `chat.conversation.read_state`

**Class:** `App\Events\Chat\ConversationReadStateUpdated`  
**When:** The conversation is marked read (bulk / tip message), updating the participant’s read cursor.  
**Channel:** `private-chat.conversation.{conversation_id}`

### Payload (`data`)

| Field | Type | Description |
|-------|------|-------------|
| `conversation_id` | integer | Conversation ID. |
| `user_id` | integer | Participant whose read state was updated. |
| `last_read_message_id` | integer \| null | Highest message ID considered read for that user, or null if none. |
| `read_at` | string | ISO 8601 timestamp when the read action was processed. |

### Example

```json
{
  "conversation_id": 7,
  "user_id": 3,
  "last_read_message_id": 100,
  "read_at": "2025-03-21T12:06:00+00:00"
}
```

---

## `chat.message.reaction.updated`

**Class:** `App\Events\Chat\ReactionUpdated`  
**When:** A user sets or removes their reaction on a message.  
**Channel:** `private-chat.conversation.{conversation_id}`

### Payload (`data`)

| Field | Type | Description |
|-------|------|-------------|
| `conversation_id` | integer | Conversation ID. |
| `message_id` | integer | Message that was reacted to. |
| `user_id` | integer | User who owns the reaction row. |
| `reaction` | string \| null | Single emoji string when set; `null` when the reaction was removed. |

### Example (set)

```json
{
  "conversation_id": 7,
  "message_id": 42,
  "user_id": 3,
  "reaction": "👍"
}
```

### Example (removed)

```json
{
  "conversation_id": 7,
  "message_id": 42,
  "user_id": 3,
  "reaction": null
}
```

---

## Summary

| Client event name (`broadcastAs`) | Payload fields |
|-----------------------------------|----------------|
| `chat.message.sent` | `id`, `conversation_id`, `sender_id`, `message`, `reply_to`, `meta`, `is_edited`, `created_at` |
| `chat.user.typing` | `conversation_id`, `user_id`, `is_typing` |
| `chat.message.read` | `conversation_id`, `message_id`, `user_id`, `read_at` |
| `chat.conversation.read_state` | `conversation_id`, `user_id`, `last_read_message_id`, `read_at` |
| `chat.message.reaction.updated` | `conversation_id`, `message_id`, `user_id`, `reaction` |

For HTTP request/response shapes, see the API docs under `docs/` (e.g. Chat v3 Flutter guides).
