## AI Adviser V2 API – Gemini 2.5 Flash-Lite

This document explains how the AI Adviser feature works, how it uses Gemini 2.5 Flash-Lite, how token limits and subscriptions are enforced, and how to call the v2 API endpoints.

---

## 1. Architecture Overview

- **Framework**: Laravel 12
- **Versioned APIs**:
  - `routes/api.php` includes:
    - `routes/api/v1.php` – all existing v1 routes
    - `routes/api/v2.php` – AI Adviser v2 routes
- **AI Adviser sessions (conversation history + meta)**:
  - Stored in the **separate AI database** (`ai_mysql` connection) using dedicated AI tables:
    - `ai_conversations` – per-conversation meta and linkage
    - `ai_messages` – per-message meta, roles, and usage
    - `ai_user_usages` – per-user token usage counters
- **Gemini integration**:
  - Service: `App\Services\GeminiAiService`
  - Model: `gemini-2.5-flash-lite` (configurable)
  - Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent`
- **Response formatting**:
  - All AI responses are returned as both **clean plain text** (`content`) and **structured sections** (`content_sections`) for easy UI rendering.
  - Markdown symbols (`*`, `**`, `#`, backticks, etc.) are stripped from `content`.

---

## 2. Environment & Configuration

### 2.1 Main DB (existing)

The main DB is already configured via standard Laravel `DB_*` variables. It continues to store:

- `subscription_plans`
- `user_subscriptions`
- `users`, etc.

### 2.2 AI Database (separate)

In `.env` configure the AI DB connection:

```env
DB_AI_CONNECTION=mysql
DB_AI_HOST=127.0.0.1
DB_AI_PORT=3306
DB_AI_DATABASE=suganta_ai
DB_AI_USERNAME=your_ai_db_user
DB_AI_PASSWORD=your_ai_db_password
```

`config/database.php` defines the `ai_mysql` connection which uses these env variables.

### 2.3 Gemini API

In `.env`:

```env
GEMINI_API_KEY=your_google_gemini_api_key
GEMINI_MODEL_ID=gemini-2.5-flash-lite

# Optional: User id in main users table that represents the AI system
AI_ADVISER_SYSTEM_USER_ID=123

# Free tier token allowance (per user, total across lifetime)
AI_FREE_TOKENS=100000

# Subscription type id for AI plans
AI_SUBSCRIPTION_TYPE=2

# Prompt / response optimization (defaults shown)
GEMINI_HISTORY_LIMIT=10
GEMINI_HISTORY_MESSAGE_MAX_CHARS=800
GEMINI_MAX_OUTPUT_TOKENS=500
```

`config/gemini.php` reads these values:

- `api_key`
- `model_id`
- `system_user_id`
- `free_token_limit`
- `subscription_type`
- `history_limit`
- `history_message_max_chars`
- `max_output_tokens`

---

## 3. Database Schema (AI DB)

All AI meta and usage live in the **AI database** (`ai_mysql`).

### 3.1 `ai_conversations`

- Model: `App\Models\Ai\AiConversation`
- Key columns:
  - `user_id` – owner of the conversation
  - `subject` – optional label
  - `status` – `active`
  - `model` – e.g. `gemini-2.5-flash-lite`
  - `purpose` – `ai_adviser`
  - `settings` (JSON) – per-conversation settings (optional)
  - `last_used_at` – updated on every reply

### 3.2 `ai_messages`

- Model: `App\Models\Ai\AiMessage`
- Key columns:
  - `ai_conversation_id` – foreign key to `ai_conversations.id`
  - `user_id` – the sender (user or AI system user)
  - `content` – the message text
  - `role` – `user` / `assistant`
  - `prompt_tokens`, `completion_tokens`, `total_tokens`
  - `raw_request` (JSON, optional)
  - `raw_response` (JSON, optional)

### 3.3 `ai_user_usages`

- Model: `App\Models\Ai\AiUserUsage`
- Columns:
  - `user_id` – references main DB `users.id`
  - `total_tokens` – cumulative tokens consumed by this user

---

## 4. Subscription Plans & Token Limits

### 4.1 AI Plans

AI-specific plans are stored in the existing `subscription_plans` table:

- Model: `App\Models\SubscriptionPlan`
- Seeder: `Database\Seeders\AiSubscriptionPlanSeeder`

Example AI plans (`s_type = 2`):

- **AI Basic** (`slug: ai-basic`) – `features['ai_tokens'] = 200000`
- **AI Pro** (`slug: ai-pro`) – `features['ai_tokens'] = 500000`

### 4.2 Token Usage Rules

1. **Free tier**: Each user gets `AI_FREE_TOKENS` tokens (default 100,000) without a paid plan.
2. **Paid AI plans**: If a user has an active subscription with `plan.s_type = AI_SUBSCRIPTION_TYPE`, the token limit is taken from `plan.features['ai_tokens']`.
3. **Enforcement**: `ensureWithinTokenLimit()` checks `current + newTokens > limit`. If exceeded, returns a `402` error with usage details.

---

## 5. Gemini Service

### 5.1 Service Class

- File: `app/Services/GeminiAiService.php`

```php
public function generateReply(string $prompt, array $history = []): array
```

#### Parameters

- `$prompt` – current user message.
- `$history` – optional conversation history. Each item: `['role' => 'user'|'assistant', 'content' => '...']`

#### Behaviour

- Maps `assistant` role to `model` (Gemini only accepts `user` / `model`).
- Ensures conversation starts with `user` role (drops leading `model` entries).
- Merges consecutive same-role messages (Gemini disallows them).
- Calls Gemini `generateContent` endpoint.
- Strips markdown from the response and parses it into structured sections.

#### Return Value

```php
[
    'text'     => string,       // clean plain text (markdown stripped)
    'sections' => array,        // structured content sections for UI
    'usage'    => array|null,   // usageMetadata from Gemini
    'raw'      => array,        // full Gemini API JSON response
]
```

### 5.2 Response Formatting

The service processes every AI response in two ways:

1. **`stripMarkdown()`** – removes `*`, `**`, `#`, backticks, horizontal rules, blockquote markers, markdown links/images. Returns clean plain text.
2. **`parseIntoSections()`** – parses the raw markdown into an array of structured section objects.

### 5.3 Content Section Types

Each section in the `content_sections` array has a `type` field and a corresponding data field:

| Type        | Data Field | Description                          |
|-------------|------------|--------------------------------------|
| `heading`   | `heading`  | Section title (from `#` headings)    |
| `paragraph` | `body`     | Normal text block                    |
| `list`      | `items`    | Array of list item strings           |
| `note`      | `body`     | Highlighted callout (from `>` quotes)|

---

## 6. V2 API – Endpoints & Usage

All v2 endpoints are defined in:

- `routes/api/v2.php`
- Controller: `App\Http\Controllers\Api\V2\AiAdviserController`

Base URL (assuming app URL `https://www.suganta.in`):

- `https://www.suganta.in/api/v2/...`

All endpoints require:

- `auth:sanctum` (Bearer token via Sanctum).

---

### 6.1 Start a New AI Conversation

- **Method**: `POST`
- **URL**: `/api/v2/ai-adviser/conversations`
- **Auth**: required (`Authorization: Bearer {token}`)

#### Request Body

```json
{
  "message": "I am a student preparing for JEE. How should I plan my daily study schedule?",
  "subject": "JEE Study Plan"
}
```

- `message` (string, required): first user query.
- `subject` (string, optional, max 255): label for the conversation.

#### Success Response (200)

```json
{
  "message": "AI adviser conversation started.",
  "success": true,
  "code": 200,
  "data": {
    "conversation": {
      "id": 42,
      "subject": "JEE Study Plan",
      "status": "active",
      "started_at": "2026-03-16T10:30:00+00:00"
    },
    "messages": [
      {
        "id": 1,
        "role": "user",
        "content": "I am a student preparing for JEE. How should I plan my daily study schedule?",
        "sent_at": "2026-03-16T10:30:00+00:00"
      },
      {
        "id": 2,
        "role": "assistant",
        "content": "Here is a suggested daily schedule for your JEE preparation...",
        "sent_at": "2026-03-16T10:30:01+00:00",
        "content_sections": [
          {
            "type": "heading",
            "heading": "Daily Study Plan for JEE"
          },
          {
            "type": "paragraph",
            "body": "A well-structured daily plan is crucial for JEE success. Here is a recommended breakdown."
          },
          {
            "type": "list",
            "items": [
              "6:00 AM - 8:00 AM: Physics (problem solving and concepts)",
              "9:00 AM - 11:00 AM: Mathematics (practice previous year papers)",
              "2:00 PM - 4:00 PM: Chemistry (organic + inorganic revision)",
              "7:00 PM - 8:00 PM: Review mistakes and short notes"
            ]
          },
          {
            "type": "note",
            "body": "Consistency matters more than long hours. Take regular breaks to stay focused."
          }
        ]
      }
    ],
    "token_usage": {
      "tokens_used": 350,
      "tokens_limit": 100000,
      "tokens_remaining": 99650,
      "usage_percentage": 0.35
    }
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `data.conversation.id` | integer | Conversation ID (use for subsequent replies) |
| `data.conversation.subject` | string/null | Conversation label |
| `data.conversation.status` | string | Always `"active"` on creation |
| `data.conversation.started_at` | string | ISO 8601 timestamp |
| `data.messages` | array | Array of message objects (user + assistant) |
| `data.messages[].id` | integer | Message ID |
| `data.messages[].role` | string | `"user"` or `"assistant"` |
| `data.messages[].content` | string | Clean plain text (no markdown symbols) |
| `data.messages[].sent_at` | string | ISO 8601 timestamp |
| `data.messages[].content_sections` | array | Structured sections (assistant messages only) |
| `data.token_usage` | object | Current token usage snapshot |

---

### 6.2 Send a Reply in an Existing Conversation

- **Method**: `POST`
- **URL**: `/api/v2/ai-adviser/conversations/{conversation}/message`
- **Auth**: required

`{conversation}` is the `conversation.id` from the start response.

#### Request Body

```json
{
  "message": "Can you adjust the plan for someone who also has school from 8am to 2pm?"
}
```

- `message` (string, required): the follow-up query.

#### Success Response (200)

```json
{
  "message": "AI adviser reply received.",
  "success": true,
  "code": 200,
  "data": {
    "conversation": {
      "id": 42,
      "subject": "JEE Study Plan",
      "status": "active",
      "total_messages": 4
    },
    "user_message": {
      "id": 3,
      "role": "user",
      "content": "Can you adjust the plan for someone who also has school from 8am to 2pm?",
      "sent_at": "2026-03-16T10:35:00+00:00"
    },
    "assistant_message": {
      "id": 4,
      "role": "assistant",
      "content": "Given your school schedule from 8am to 2pm, here is an adjusted study plan...",
      "sent_at": "2026-03-16T10:35:01+00:00",
      "content_sections": [
        {
          "type": "heading",
          "heading": "Adjusted Study Plan (School 8 AM - 2 PM)"
        },
        {
          "type": "paragraph",
          "body": "Since you have school from 8 AM to 2 PM, your study sessions need to be concentrated in the early morning and evening."
        },
        {
          "type": "list",
          "items": [
            "5:30 AM - 7:30 AM: Physics (concepts and numerical practice)",
            "3:00 PM - 5:00 PM: Mathematics (focus on problem solving)",
            "6:00 PM - 7:30 PM: Chemistry (revision and formulas)",
            "8:30 PM - 9:30 PM: Review the day and solve mock questions"
          ]
        },
        {
          "type": "note",
          "body": "Use your school breaks for quick revision of formulas and short notes."
        }
      ]
    },
    "token_usage": {
      "tokens_used": 720,
      "tokens_limit": 100000,
      "tokens_remaining": 99280,
      "usage_percentage": 0.72
    }
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `data.conversation.id` | integer | Conversation ID |
| `data.conversation.subject` | string/null | Conversation label |
| `data.conversation.status` | string | Conversation status |
| `data.conversation.total_messages` | integer | Total messages in conversation |
| `data.user_message` | object | The user's message just sent |
| `data.assistant_message` | object | The AI's reply |
| `data.assistant_message.content_sections` | array | Structured sections for UI rendering |
| `data.token_usage` | object | Updated token usage snapshot |

---

### 6.3 List User's AI Conversations

- **Method**: `GET`
- **URL**: `/api/v2/ai-adviser/conversations`
- **Auth**: required

Returns a paginated list of the user's AI adviser conversations, ordered by most recently active.

#### Success Response (200)

```json
{
  "message": "AI adviser conversations fetched.",
  "success": true,
  "code": 200,
  "data": {
    "conversations": [
      {
        "id": 42,
        "subject": "JEE Study Plan",
        "status": "active",
        "total_messages": 4,
        "last_message_preview": "Given your school schedule from 8am to 2pm, here is an adjusted study plan that fits around yo...",
        "started_at": "2026-03-16T10:30:00+00:00",
        "last_active_at": "2026-03-16T10:35:01+00:00"
      },
      {
        "id": 38,
        "subject": null,
        "status": "active",
        "total_messages": 2,
        "last_message_preview": "To improve your English speaking skills, you should practice daily conversation with a partner o...",
        "started_at": "2026-03-15T14:00:00+00:00",
        "last_active_at": "2026-03-15T14:00:05+00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 2,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `data.conversations` | array | List of conversation summary objects |
| `data.conversations[].id` | integer | Conversation ID |
| `data.conversations[].subject` | string/null | Conversation label |
| `data.conversations[].status` | string | Conversation status |
| `data.conversations[].total_messages` | integer | Total messages in the conversation |
| `data.conversations[].last_message_preview` | string/null | Truncated preview of the last message (120 chars) |
| `data.conversations[].started_at` | string | ISO 8601 timestamp of creation |
| `data.conversations[].last_active_at` | string | ISO 8601 timestamp of last activity |
| `data.pagination` | object | Pagination metadata |
| `data.pagination.current_page` | integer | Current page number |
| `data.pagination.per_page` | integer | Items per page (15) |
| `data.pagination.total` | integer | Total number of conversations |
| `data.pagination.last_page` | integer | Last page number |
| `data.pagination.has_more` | boolean | Whether more pages exist |

---

### 6.4 Get Full Conversation Messages

- **Method**: `GET`
- **URL**: `/api/v2/ai-adviser/conversations/{conversation}`
- **Auth**: required

Returns all messages in a conversation with full conversation metadata.

#### Success Response (200)

```json
{
  "message": "AI adviser conversation details.",
  "success": true,
  "code": 200,
  "data": {
    "conversation": {
      "id": 42,
      "subject": "JEE Study Plan",
      "status": "active",
      "total_messages": 4,
      "started_at": "2026-03-16T10:30:00+00:00",
      "last_active_at": "2026-03-16T10:35:01+00:00"
    },
    "messages": [
      {
        "id": 1,
        "role": "user",
        "content": "I am a student preparing for JEE. How should I plan my daily study schedule?",
        "sent_at": "2026-03-16T10:30:00+00:00"
      },
      {
        "id": 2,
        "role": "assistant",
        "content": "Here is a suggested daily schedule for your JEE preparation...",
        "sent_at": "2026-03-16T10:30:01+00:00",
        "content_sections": [
          {
            "type": "heading",
            "heading": "Daily Study Plan for JEE"
          },
          {
            "type": "paragraph",
            "body": "A well-structured daily plan is crucial for JEE success."
          },
          {
            "type": "list",
            "items": [
              "6:00 AM - 8:00 AM: Physics",
              "9:00 AM - 11:00 AM: Mathematics",
              "2:00 PM - 4:00 PM: Chemistry"
            ]
          }
        ]
      },
      {
        "id": 3,
        "role": "user",
        "content": "Can you adjust the plan for someone who also has school from 8am to 2pm?",
        "sent_at": "2026-03-16T10:35:00+00:00"
      },
      {
        "id": 4,
        "role": "assistant",
        "content": "Given your school schedule, here is an adjusted plan...",
        "sent_at": "2026-03-16T10:35:01+00:00",
        "content_sections": [
          {
            "type": "heading",
            "heading": "Adjusted Study Plan"
          },
          {
            "type": "paragraph",
            "body": "Your study sessions need to be concentrated in early morning and evening."
          },
          {
            "type": "list",
            "items": [
              "5:30 AM - 7:30 AM: Physics",
              "3:00 PM - 5:00 PM: Mathematics",
              "6:00 PM - 7:30 PM: Chemistry"
            ]
          },
          {
            "type": "note",
            "body": "Use school breaks for quick formula revision."
          }
        ]
      }
    ]
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `data.conversation` | object | Conversation metadata |
| `data.conversation.id` | integer | Conversation ID |
| `data.conversation.subject` | string/null | Conversation label |
| `data.conversation.status` | string | Conversation status |
| `data.conversation.total_messages` | integer | Total message count |
| `data.conversation.started_at` | string | ISO 8601 creation timestamp |
| `data.conversation.last_active_at` | string | ISO 8601 last activity timestamp |
| `data.messages` | array | All messages in chronological order |
| `data.messages[].content_sections` | array | Structured sections (assistant messages only) |

---

### 6.5 Get Token Usage

- **Method**: `GET`
- **URL**: `/api/v2/ai-adviser/usage`
- **Auth**: required

Returns the authenticated user's current token usage and plan limits.

#### Success Response (200)

```json
{
  "message": "AI adviser token usage.",
  "success": true,
  "code": 200,
  "data": {
    "token_usage": {
      "tokens_used": 720,
      "tokens_limit": 100000,
      "tokens_remaining": 99280,
      "usage_percentage": 0.72
    },
    "is_limit_reached": false
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `data.token_usage.tokens_used` | integer | Total tokens consumed so far |
| `data.token_usage.tokens_limit` | integer | Maximum tokens allowed by plan |
| `data.token_usage.tokens_remaining` | integer | Tokens still available |
| `data.token_usage.usage_percentage` | float | Percentage of limit used (0-100) |
| `data.is_limit_reached` | boolean | `true` if no tokens remaining |

---

## 7. Error Responses

### 7.1 Token Limit Exceeded (402)

Returned when a reply would push the user over their token limit.

```json
{
  "message": "AI token limit exceeded. Please upgrade your AI subscription plan.",
  "success": false,
  "code": 402,
  "errors": {
    "tokens_used": 99800,
    "tokens_limit": 100000,
    "tokens_remaining": 200
  }
}
```

### 7.2 Forbidden (403)

Returned when a user tries to access a conversation that does not belong to them.

```json
{
  "message": "You are not part of this conversation.",
  "success": false,
  "code": 403
}
```

### 7.3 Validation Error (422)

Returned when required fields are missing or invalid.

```json
{
  "message": "Validation failed",
  "success": false,
  "code": 422,
  "errors": {
    "message": ["The message field is required."]
  }
}
```

---

## 8. Content Sections Reference

Every assistant message includes a `content_sections` array that breaks the AI response into structured blocks. The frontend can loop through this array and render each section with appropriate styling.

### Section Types

| Type | Fields | Frontend Rendering |
|------|--------|--------------------|
| `heading` | `heading` (string) | Bold / larger title text |
| `paragraph` | `body` (string) | Regular text block |
| `list` | `items` (string[]) | Numbered or bulleted list |
| `note` | `body` (string) | Highlighted tip / callout box |

### Example: Rendering in a Mobile App

```
FOR EACH section IN content_sections:

  IF section.type == "heading"
    --> Render as bold title (e.g. 18px, semi-bold)

  IF section.type == "paragraph"
    --> Render as normal body text (e.g. 14px, regular)

  IF section.type == "list"
    --> Render each item in section.items as a numbered/bulleted row

  IF section.type == "note"
    --> Render in a highlighted card/box with an info icon
```

### Fallback

If the AI response has no recognizable structure, `content_sections` will contain a single paragraph:

```json
{
  "content_sections": [
    {
      "type": "paragraph",
      "body": "The entire response as a single clean text block."
    }
  ]
}
```

The `content` field always contains the full plain-text version as a fallback for simple displays.

---

## 9. Setup & Deployment Checklist

1. **AI database**
   - Create AI DB (e.g. `suganta_ai`).
   - Set `DB_AI_*` variables in `.env`.
2. **Gemini credentials**
   - Get API key from Google AI Studio.
   - Set `GEMINI_API_KEY` and optionally `GEMINI_MODEL_ID`.
3. **AI system user**
   - Create a dedicated user row in main `users` table (e.g. `ai@suganta.in`).
   - Set `AI_ADVISER_SYSTEM_USER_ID` to that user's id.
4. **Migrations**
   - Run: `php artisan migrate`
   - Creates `ai_conversations`, `ai_messages`, `ai_user_usages` in AI DB.
5. **Seed AI plans**
   - Run: `php artisan db:seed --class=AiSubscriptionPlanSeeder`
   - Verify `subscription_plans` has `s_type = 2` plans with `features['ai_tokens']`.
6. **Sanctum auth**
   - Ensure Sanctum is configured and issuing tokens, since v2 routes are protected by `auth:sanctum`.

Once this setup is complete, the AI Adviser v2 endpoints are ready for use. They will automatically:

- Persist conversation history in the AI database.
- Return structured, user-friendly responses with `content_sections`.
- Enforce free and paid token limits via subscription plans.
- Provide real-time token usage snapshots with every AI response.
