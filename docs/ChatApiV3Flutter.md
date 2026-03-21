# Chat API V3 (Flutter + Reverb Realtime)

This guide documents the complete V3 chat implementation for Flutter apps, with primary focus on Reverb realtime communication:

- REST API usage
- Sanctum authentication
- Reverb realtime integration (private channels, auth, reconnect, lifecycle)
- Event payload handling

---

## 1) Base Information

- **REST Base URL**: `/api/v3/chat`
- **Auth**: `Authorization: Bearer {token}` (required for all endpoints)
- **Broadcast auth endpoint**: `/broadcasting/auth` (protected by Sanctum)
- **Realtime transport**: WebSocket via Reverb (Pusher protocol compatible)
- **Database connection used by chat**: `ai_mysql`

All API responses follow the project response format:

```json
{
  "message": "Success message",
  "success": true,
  "code": 200,
  "data": {}
}
```

---

## 2) Reverb Configuration (Backend + Flutter)

### 2.1 Backend `.env` (Required)

Use these keys on backend for Reverb:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=app-id
REVERB_APP_KEY=app-key
REVERB_APP_SECRET=app-secret

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

For production:

- use `https` in `REVERB_SCHEME`
- use real domain in `REVERB_HOST` (for example `ws.api.yourdomain.com`)

### 2.2 Flutter Runtime Config

Provide the same Reverb values into Flutter build-time env (`--dart-define`) or secure remote config:

- `REVERB_APP_KEY`
- `REVERB_HOST`
- `REVERB_PORT`
- `REVERB_SCHEME`

Example:

```bash
flutter run \
  --dart-define=REVERB_APP_KEY=app-key \
  --dart-define=REVERB_HOST=10.0.2.2 \
  --dart-define=REVERB_PORT=8080 \
  --dart-define=REVERB_SCHEME=http
```

`10.0.2.2` is Android emulator host mapping for local backend.

### 2.3 WebSocket Endpoint Notes

Your Flutter app should connect through host/port/scheme values and Pusher-compatible protocol. Do not hardcode localhost for physical devices.

Recommended host mapping:

- Android emulator: backend host as `10.0.2.2`
- iOS simulator: backend host as `127.0.0.1` (or local LAN IP)
- physical device: backend host as same-LAN IP or public domain

---

## 3) Conversation APIs

### 3.1 Search Users (Start New Conversation)

- **Method**: `GET`
- **URL**: `/api/v3/chat/users/search`
- **Description**: Search users by name or phone number before creating a new chat.

#### Query Parameters

- `q` (required, min 2 chars): name or phone text
- `limit` (optional): `1-50`, default `20`

#### Example Request

```http
GET /api/v3/chat/users/search?q=rahul&limit=10
Authorization: Bearer {token}
Accept: application/json
```

#### Example Response

```json
{
  "message": "Users fetched successfully.",
  "success": true,
  "code": 200,
  "data": {
    "query": "rahul",
    "total": 1,
    "users": [
      {
        "id": 18,
        "name": "Rahul Sharma",
        "phone": "9876543210",
        "profile_image": "profiles/18/avatar.jpg",
        "has_private_conversation": true,
        "private_conversation_id": 7
      }
    ]
  }
}
```

### 3.2 List My Conversations

- **Method**: `GET`
- **URL**: `/api/v3/chat/conversations`
- **Description**: Returns paginated conversations where logged-in user is an active participant.

### 3.3 Create Conversation

- **Method**: `POST`
- **URL**: `/api/v3/chat/conversations`

#### Request Body

```json
{
  "type": "private",
  "title": null,
  "participants": [12]
}
```

#### Rules

- `type`: `private` or `group`
- For `private`, `participants` must contain exactly 1 other user.
- For `group`, `participants` must contain at least 2 other users.

### 3.4 Conversation Details

- **Method**: `GET`
- **URL**: `/api/v3/chat/conversations/{conversation}`
- **Description**: Returns conversation + active participants list.

### 3.5 Add Participant (Group Only)

- **Method**: `POST`
- **URL**: `/api/v3/chat/conversations/{conversation}/participants`
- **Body**:

```json
{
  "user_id": 18
}
```

### 3.6 Remove Participant

- **Method**: `DELETE`
- **URL**: `/api/v3/chat/conversations/{conversation}/participants/{user}`

### 3.7 Leave Conversation

- **Method**: `POST`
- **URL**: `/api/v3/chat/conversations/{conversation}/leave`

---

## 4) Message APIs

### 4.1 List Messages

- **Method**: `GET`
- **URL**: `/api/v3/chat/conversations/{conversation}/messages`
- **Description**: Returns latest-first paginated messages with reads and reactions.

### 4.2 Send Message

- **Method**: `POST`
- **URL**: `/api/v3/chat/conversations/{conversation}/messages`

#### Request Body

```json
{
  "message": "Hello from Flutter",
  "reply_to": null,
  "meta": {
    "client_message_id": "tmp-17384",
    "platform": "flutter"
  }
}
```

### 4.3 Edit Message

- **Method**: `PATCH`
- **URL**: `/api/v3/chat/messages/{message}`

```json
{
  "message": "Edited text"
}
```

### 4.4 Delete Message

- **Method**: `DELETE`
- **URL**: `/api/v3/chat/messages/{message}`
- **Behavior**: Soft delete (`deleted_at` set).

### 4.5 Mark Message Read

- **Method**: `POST`
- **URL**: `/api/v3/chat/messages/{message}/read`

### 4.6 Add / Update Reaction

- **Method**: `POST`
- **URL**: `/api/v3/chat/messages/{message}/reaction`

```json
{
  "reaction": "like"
}
```

### 4.7 Remove Reaction

- **Method**: `DELETE`
- **URL**: `/api/v3/chat/messages/{message}/reaction`

### 4.8 Typing Indicator

- **Method**: `POST`
- **URL**: `/api/v3/chat/conversations/{conversation}/typing`

```json
{
  "is_typing": true
}
```

---

## 5) Realtime Events (Reverb)

Realtime channel is private per conversation:

- **Channel name**: `private-chat.conversation.{conversationId}`

Events emitted by backend:

1. **`chat.message.sent`**
2. **`chat.message.read`**
3. **`chat.message.reaction.updated`**
4. **`chat.user.typing`**

### 5.1 Event Payloads

#### `chat.message.sent`

```json
{
  "id": 101,
  "conversation_id": 7,
  "sender_id": 4,
  "message": "Hello",
  "reply_to": null,
  "meta": null,
  "is_edited": false,
  "created_at": "2026-03-21T07:10:00+00:00"
}
```

#### `chat.message.read`

```json
{
  "conversation_id": 7,
  "message_id": 101,
  "user_id": 8,
  "read_at": "2026-03-21T07:11:00+00:00"
}
```

#### `chat.message.reaction.updated`

```json
{
  "conversation_id": 7,
  "message_id": 101,
  "user_id": 8,
  "reaction": "love"
}
```

If reaction is removed, `reaction` becomes `null`.

#### `chat.user.typing`

```json
{
  "conversation_id": 7,
  "user_id": 8,
  "is_typing": true
}
```

---

## 6) Flutter Reverb Integration (Production-Ready)

Use any Pusher-compatible Flutter client for Reverb. Recommended:

- [`pusher_channels_flutter`](https://pub.dev/packages/pusher_channels_flutter)

### 6.1 Recommended Client Architecture

- `ChatApiService`: REST calls (conversation list, history, send, read, reaction, typing)
- `ChatRealtimeService`: Reverb connection, channel subscribe/unsubscribe, event stream
- `ChatRepository`: merges REST and realtime data
- `ChatController`/`Bloc`: drives UI state

This separation makes reconnect and token refresh handling easier.

### 6.2 Suggested Flutter Setup Flow

1. Login and store Sanctum bearer token.
2. Fetch conversations via REST.
3. Open conversation detail.
4. Subscribe to `private-chat.conversation.{id}`.
5. Listen to all 4 events and update local state.
6. Keep REST as source of truth (initial load, pagination, recovery).

### 6.3 Reverb Auth Handshake Flow

1. Client connects socket to Reverb.
2. Client requests private channel subscription (`private-chat.conversation.{id}`).
3. Flutter client calls backend `POST /broadcasting/auth`.
4. Backend validates Sanctum token and channel permission in `routes/channels.php`.
5. If user is active participant, backend returns auth signature.
6. Subscription succeeds and events start streaming.

### 6.4 Example Dart Service (Realtime)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

class ChatRealtimeService {
  final String apiBase; // e.g. https://api.example.com
  final String token;   // Sanctum bearer token

  late final PusherChannelsFlutter pusher;
  bool _connected = false;

  ChatRealtimeService({required this.apiBase, required this.token});

  Future<void> init() async {
    pusher = PusherChannelsFlutter.getInstance();
    await pusher.init(
      apiKey: const String.fromEnvironment('REVERB_APP_KEY'),
      cluster: 'mt1',
      wsHost: const String.fromEnvironment('REVERB_HOST'),
      wsPort: int.parse(const String.fromEnvironment('REVERB_PORT')),
      forceTLS: const String.fromEnvironment('REVERB_SCHEME') == 'https',
      authEndpoint: '$apiBase/broadcasting/auth',
      onAuthorizer: (channelName, socketId, options) async {
        final response = await http.post(
          Uri.parse('$apiBase/broadcasting/auth'),
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: {
            'socket_id': socketId,
            'channel_name': channelName,
          },
        );
        return jsonDecode(response.body);
      },
      onConnectionStateChange: (currentState, previousState) {
        _connected = currentState == 'CONNECTED';
      },
      onError: (message, code, error) {
        // log + retry strategy trigger
      },
    );
    await pusher.connect();
  }

  Future<void> subscribeConversation(int conversationId) async {
    if (!_connected) return;
    final channelName = 'private-chat.conversation.$conversationId';
    await pusher.subscribe(
      channelName: channelName,
      onEvent: (event) {
        // event.eventName -> chat.message.sent / chat.message.read / etc
        // event.data -> JSON payload string
      },
    );
  }

  Future<void> unsubscribeConversation(int conversationId) async {
    await pusher.unsubscribe(
      channelName: 'private-chat.conversation.$conversationId',
    );
  }
}
```

### 6.5 Event Handling Contract

Map event names exactly as backend emits:

- `chat.message.sent` -> upsert message
- `chat.message.read` -> update read receipts
- `chat.message.reaction.updated` -> update reaction state
- `chat.user.typing` -> update typing UI

Keep UI resilient:

- ignore duplicate event IDs
- ignore events from muted/closed conversations
- debounce typing indicator clear timeout (2-4 seconds)

### 6.6 Reconnect and Recovery

On socket reconnect:

1. Re-subscribe active conversation channels.
2. Pull latest messages from REST for each open conversation.
3. Merge by `message.id` to prevent duplicates.
4. Replay pending outbound queue (if any unsent local messages exist).

### 6.7 Token Refresh Handling

When token rotates:

1. disconnect socket
2. update bearer token used by authorizer
3. reconnect
4. re-subscribe channels

Avoid continuing with old token because private channel auth will fail (401/403).

---

## 7) REST + Realtime Sync Strategy

Recommended pattern for robust Flutter chat:

- On screen open:
  - Fetch conversation + latest messages from REST.
  - Subscribe realtime channel.
- On `chat.message.sent`:
  - Insert/update message locally.
  - De-duplicate using message `id`.
- On send API success:
  - Replace local temporary message with server message.
- On reconnect:
  - Re-fetch last page from REST to heal missed events.

---

## 8) Common Error Cases

### 401 Unauthorized

- Missing/expired token.
- Fix: refresh login token and retry.
- If realtime fails: reconnect socket after token update.

### 403 Forbidden

- User is not participant/admin for that action.
- Fix: verify participant state before action.
- For realtime: ensure user still has active `left_at = null` participant state.

### 422 Validation Error

- Invalid payload (e.g., wrong participants count for private/group).
- Fix: enforce client-side validation.

### Realtime auth fails on device but works in Postman

- Usually wrong websocket host or unreachable backend from emulator/device.
- Fix:
  - Android emulator host => `10.0.2.2`
  - physical device => same-LAN/public URL
  - verify port/firewall

### No events received after successful REST send

- Reverb server not running or app connected to wrong host/port.
- Fix:
  - run `php artisan reverb:start`
  - verify `.env` Reverb keys/host/port
  - verify Flutter runtime `dart-define` matches backend

### Private subscribe succeeds but no updates for a conversation

- User may have left conversation (`left_at` set) or wrong conversation ID subscribed.
- Fix:
  - call `GET /api/v3/chat/conversations/{id}` and verify user is active participant
  - re-subscribe with correct `private-chat.conversation.{id}`

---

## 9) Backend File Map

- Routes: `routes/api/v3.php`
- Channels auth: `routes/channels.php`
- Controllers:
  - `app/Http/Controllers/Api/V3/Chat/ConversationController.php`
  - `app/Http/Controllers/Api/V3/Chat/MessageController.php`
- Events:
  - `app/Events/Chat/MessageSent.php`
  - `app/Events/Chat/MessageRead.php`
  - `app/Events/Chat/ReactionUpdated.php`
  - `app/Events/Chat/UserTyping.php`
- Models:
  - `app/Models/Chat/ChatConversation.php`
  - `app/Models/Chat/ChatConversationParticipant.php`
  - `app/Models/Chat/ChatMessage.php`
  - `app/Models/Chat/ChatMessageRead.php`
  - `app/Models/Chat/ChatMessageReaction.php`
- Migration:
  - `database/migrations/2026_03_21_000001_create_v3_chat_tables_on_ai_mysql.php`

---

## 10) Quick Testing Checklist

1. `php artisan migrate --database=ai_mysql`
2. `php artisan reverb:start`
3. Login two users in two Flutter app sessions.
4. Create/open same conversation.
5. Verify:
   - Send/receive messages realtime
   - Typing indicator realtime
   - Read receipt realtime
   - Reaction add/remove realtime

