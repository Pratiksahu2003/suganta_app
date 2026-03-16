## AI Adviser V2 API – Gemini 2.5 Flash‑Lite

This document explains how the AI Adviser feature works, how it uses Gemini 2.5 Flash‑Lite, how token limits and subscriptions are enforced, and how to call the v2 API endpoints.

---

## 1. Architecture Overview

- **Framework**: Laravel 12
- **Versioned APIs**:
  - `routes/api.php` → includes:
    - `routes/api/v1.php` – all existing v1 routes
    - `routes/api/v2.php` – AI Adviser v2 routes
- **AI Adviser sessions (conversation history + meta)**:
  - Stored in the **separate AI database** (`ai_mysql` connection) using dedicated AI tables:
    - `ai_conversations` – per‑conversation meta and linkage
    - `ai_messages` – per‑message meta, roles, and usage
    - `ai_user_usages` – per‑user token usage counters
- **(Optional) main messaging system**:
  - The existing `conversations` and `messages` tables in the main DB can still be used for non‑AI chat features.
- **Gemini integration**:
  - Service: `App\Services\GeminiAiService`
  - Model: `gemini-2.5-flash-lite` (configurable)
  - Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent`

---

## 2. Environment & Configuration

### 2.1 Main DB (existing)

The main DB is already configured via standard Laravel `DB_*` variables. It continues to store:

- `conversations`
- `messages`
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
```

`config/gemini.php` reads these values:

- `api_key`
- `model_id`
- `system_user_id`
- `free_token_limit`
- `subscription_type`

---

## 3. Database Schema (AI DB)

All AI meta and usage live in the **AI database** (`ai_mysql`).

### 3.1 `ai_conversations`

- Migration: `2026_03_16_000000_create_ai_conversations_table.php`
- Model: `App\Models\Ai\AiConversation`
- Columns (key ones):
  - `conversation_id` – foreign key reference to main DB `conversations.id`
  - `model` – e.g. `gemini-2.5-flash-lite`
  - `purpose` – `ai_adviser`
  - `settings` (JSON) – per-conversation settings (optional)
  - `total_prompt_tokens`, `total_completion_tokens`, `total_tokens`
  - `last_used_at`, `last_error_code`, `last_error_message`

### 3.2 `ai_messages`

- Migration: `2026_03_16_000001_create_ai_messages_table.php`
- Model: `App\Models\Ai\AiMessage`
- Columns:
  - `message_id` – foreign key reference to main DB `messages.id`
  - `role` – `user` / `assistant` / `system`
  - `prompt_tokens`, `completion_tokens`, `total_tokens`
  - `raw_request` (JSON, optional)
  - `raw_response` (JSON, optional)

### 3.3 `ai_user_usages`

- Migration: `2026_03_16_000002_create_ai_user_usages_table.php`
- Model: `App\Models\Ai\AiUserUsage`
- Columns:
  - `user_id` – references main DB `users.id`
  - `total_tokens` – cumulative tokens consumed by this user (free + paid)

---

## 4. Subscription Plans & Token Limits

### 4.1 AI Plans

AI‑specific plans are stored in the existing `subscription_plans` table:

- Model: `App\Models\SubscriptionPlan`
- Seeder: `Database\Seeders\AiSubscriptionPlanSeeder`

The seeder defines two example AI plans (`s_type = 2`):

- **AI Basic** (`slug: ai-basic`)
  - `features['ai_tokens'] = 200000`
- **AI Pro** (`slug: ai-pro`)
  - `features['ai_tokens'] = 500000`
  - Marked as `is_popular = true`

You can adjust `price`, `currency`, `ai_tokens` and add more plans (e.g. “AI Advance”) as needed by inserting/updating `subscription_plans` records with `s_type = 2`.

### 4.2 Token Usage Rules

1. **Free tier**:
   - Each user gets **`AI_FREE_TOKENS`** tokens (default **100k**) even without a paid AI plan.
   - Usage is tracked in `ai_user_usages.total_tokens`.
2. **Paid AI plans**:
   - If a user has an active `user_subscriptions` record whose `plan.s_type = AI_SUBSCRIPTION_TYPE` (default `2`):
     - The token limit is taken from `plan.features['ai_tokens']`.
   - Example:
     - Basic: `ai_tokens = 200000`
     - Pro: `ai_tokens = 500000`
3. **Enforcement**:
   - Implemented in `App\Http\Controllers\Api\V2\AiAdviserController`:
     - `getUserTokenLimit($user)`:
       - If user has active AI subscription ⇒ use `plan.features['ai_tokens']`.
       - Else ⇒ use configured free limit.
     - `ensureWithinTokenLimit($user, int $newTokens)`:
       - Uses `AiUserUsage` to check if `current + newTokens > limit`.
       - If exceeded ⇒ returns `402` JSON error:
         - `message`: “AI token limit exceeded. Please upgrade your AI subscription plan.”
       - If allowed ⇒ increments `total_tokens` and continues.

---

## 5. Gemini Service

### 5.1 Service Class

- File: `app/Services/GeminiAiService.php`
- Method:

```php
public function generateReply(string $prompt, array $history = []): array
```

#### Parameters

- `$prompt` – current user message.
- `$history` – optional conversation history:
  - Each item: `['role' => 'user'|'assistant', 'content' => '...']`

#### Behaviour

- Builds the `contents` payload for Gemini:
  - Includes prior history (user/assistant messages).
  - Appends current user prompt as the last content.
- Calls Gemini `generateContent` endpoint with:
  - Model from `config('gemini.model_id')`
  - API key from `config('gemini.api_key')`
- Returns:

```php
[
    'text'  => string,       // assistant reply text
    'usage' => array|null,   // usageMetadata from Gemini, if provided
    'raw'   => array,        // full API JSON response
]
```

`usageMetadata` is used to update `ai_messages` and `ai_user_usages`.

---

## 6. V2 API – Endpoints & Usage

All v2 endpoints are defined in:

- `routes/api/v2.php`
- Controller: `App\Http\Controllers\Api\V2\AiAdviserController`

Base URL (assuming app URL `https://www.suganta.in`):

- `https://www.suganta.in/api/v2/...`

All endpoints require:

- `auth:sanctum` (Bearer token via Sanctum).

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
- `subject` (string, optional): label for the conversation.

#### Behaviour

- Creates a `conversations` row (main DB) with:
  - `initiator_id = user.id`
  - `participant_id = user.id` (AI responses are distinguished by sender id)
  - `type = 'general'`
  - `status = 'active'`
- Creates `ai_conversations` row (AI DB) linked by `conversation_id`.
- Creates user `messages` row + `ai_messages` meta row (`role = user`).
- Calls Gemini with the prompt, enforces token limit.
- Creates assistant `messages` row (sender = `AI_ADVISER_SYSTEM_USER_ID` or user id fallback) + `ai_messages` meta row (`role = assistant`).

#### Success Response (200)

```json
{
  "message": "AI adviser response generated.",
  "success": true,
  "code": 200,
  "data": {
    "conversation_id": 123,
    "message": "Here is a suggested daily schedule for your JEE preparation..."
  }
}
```

#### Error – Token Limit Exceeded (402)

```json
{
  "message": "AI token limit exceeded. Please upgrade your AI subscription plan.",
  "success": false,
  "code": 402
}
```

---

### 6.2 Send a Message in an Existing Conversation

- **Method**: `POST`
- **URL**: `/api/v2/ai-adviser/conversations/{conversation}/message`
- **Auth**: required

`{conversation}` is the ID from `conversation_id` returned in the start call.

#### Request Body

```json
{
  "message": "Can you adjust the plan for someone who also has school from 8am to 2pm?"
}
```

#### Behaviour

- Validates that the authenticated user is part of the conversation (`involvesUser` check).
- Ensures an `ai_conversations` record exists (creates it if missing).
- Loads previous messages and builds `history` for Gemini.
- Creates new user `messages` + `ai_messages` (`role = user`).
- Calls Gemini with history + current message, enforces token limit.
- Creates assistant `messages` + `ai_messages` (`role = assistant`).
- Updates `conversations.last_message_at` and `ai_conversations.last_used_at`.

#### Success Response (200)

```json
{
  "message": "AI adviser response generated.",
  "success": true,
  "code": 200,
  "data": {
    "conversation_id": 123,
    "message": "Given your school schedule from 8am to 2pm, here's an adjusted study plan..."
  }
}
```

---

### 6.3 List User’s AI Conversations

- **Method**: `GET`
- **URL**: `/api/v2/ai-adviser/conversations`
- **Auth**: required

#### Behaviour

- Fetches `conversations` where:
  - `initiator_id = auth()->id()`
  - There is a related `ai_conversations` record with `purpose = 'ai_adviser'`.
- Paginates results (`15` per page).

#### Success Response (200)

Standard paginated response from `ApiResponse::paginated`, containing conversation list with meta and links.

---

### 6.4 Get Full Conversation Messages

- **Method**: `GET`
- **URL**: `/api/v2/ai-adviser/conversations/{conversation}`
- **Auth**: required

#### Behaviour

- Ensures authenticated user is part of conversation (`involvesUser`).
- Returns all `messages` ordered by `created_at`, mapped to:

```json
{
  "id": 456,
  "role": "user" | "assistant",
  "content": "message text here",
  "created_at": "2026-03-16T10:00:00.000000Z"
}
```

#### Success Response (200)

```json
{
  "message": "AI adviser conversation fetched.",
  "success": true,
  "code": 200,
  "data": {
    "conversation_id": 123,
    "messages": [
      {
        "id": 1,
        "role": "user",
        "content": "I am a student preparing for JEE...",
        "created_at": "2026-03-16T10:00:00.000000Z"
      },
      {
        "id": 2,
        "role": "assistant",
        "content": "Here is a suggested daily schedule...",
        "created_at": "2026-03-16T10:00:01.000000Z"
      }
    ]
  }
}
```

---

## 7. Setup & Deployment Checklist

1. **AI database**
   - Create AI DB (e.g. `suganta_ai`).
   - Set `DB_AI_*` variables in `.env`.
2. **Gemini credentials**
   - Get API key from Google AI Studio.
   - Set `GEMINI_API_KEY` and optionally `GEMINI_MODEL_ID`.
3. **AI system user**
   - Create a dedicated user row in main `users` table (e.g. `ai@suganta.in`).
   - Set `AI_ADVISER_SYSTEM_USER_ID` to that user’s id.
4. **Migrations**
   - Run: `php artisan migrate`
     - Creates `ai_conversations`, `ai_messages`, `ai_user_usages` in AI DB.
5. **Seed AI plans**
   - Run: `php artisan db:seed --class=AiSubscriptionPlanSeeder`
   - Verify `subscription_plans` has `s_type = 2` plans with `features['ai_tokens']`.
6. **Sanctum auth**
   - Ensure Sanctum is configured and issuing tokens, since v2 routes are protected by `auth:sanctum`.

Once this setup is complete, the AI Adviser v2 endpoints are ready for use by your frontend or external clients. They will automatically:

- Persist conversation history in the existing messaging system.
- Store AI meta and usage in the separate AI database.
- Enforce free and paid token limits via subscription plans.

