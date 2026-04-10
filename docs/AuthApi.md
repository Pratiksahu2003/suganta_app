# Auth API (v1)

**Base path:** `{BASE_URL}/api/v1` (see root [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) for `BASE_URL`).

All paths below are relative to `/api/v1` (e.g. full URL for register: `POST /api/v1/auth/register`).

---

## Response envelope

Successful responses use `App\Traits\ApiResponse`:

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | `true` for success responses |
| `message` | string | Human-readable message |
| `code` | integer | HTTP status echoed in body (e.g. `200`, `201`) |
| `data` | object | Present when the endpoint returns a payload (omitted if empty) |

Error responses set `success: false`. Validation errors (HTTP **422**) put Laravel validation messages in **`errors`** (may be a keyed object of arrays). Some flows intentionally put non-validation payloads in **`errors`** when `success` is `false` but HTTP status is **200** (see **Login – registration payment required**).

---

## Headers (authenticated routes)

| Header | Required | Description |
|--------|----------|-------------|
| `Authorization` | Token clients | `Bearer {access_token}` (omit when using SPA session cookies only) |
| `Accept` | Recommended | `application/json` |
| `Content-Type` | For JSON bodies | `application/json` |

**Web SPA (e.g. `app.suganta.com` → `api.suganta.com`):** Configure `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN` (e.g. `.suganta.com`), and `CORS_ALLOWED_ORIGINS` with credentials (see `.env.example`). After `register` / `login` / `login/verify`, responses use **`auth_mode`: `both`**: you get a **session cookie** (for cookie-based requests) **and** a **`token`** (same as mobile). Prefer sending **`Authorization: Bearer {token}`** on API calls from the browser to avoid CSRF setup; you can still use cookies + `X-XSRF-TOKEN` if you call `GET {API_URL}/sanctum/csrf-cookie` first.

**Mobile / Postman:** No matching `Origin` → **`auth_mode`: `token`**; send `Authorization: Bearer` on later requests.

### CSRF token mismatch (HTTP 419)

- **Public auth** (`register`, `login`, `login/send-otp`, `login/verify`, `forgot-password`, `reset-password`): excluded from CSRF in `App\Http\Middleware\ValidateCsrfToken` so the first request works without `sanctum/csrf-cookie`.
- **All other API routes:** Either send **`Authorization: Bearer {token}`** (skips CSRF when `csrf_skip_with_bearer` is true in `config/sanctum.php`) or use **`GET {API_URL}/sanctum/csrf-cookie`** + **`X-XSRF-TOKEN`** on mutating requests with `credentials: 'include'`.
- **Cookies:** Set `SESSION_DOMAIN` to the parent domain (e.g. `.suganta.com`) and list the SPA in `CORS_ALLOWED_ORIGINS` with credentials enabled.

**Login (optional):** `X-Device-Token` — if set and recognized for the user, the device is treated as trusted and password login can complete without the OTP step. Otherwise the API returns **`requires_otp`** and you must use `login/send-otp` and `login/verify`.

---

## 1. Register

**`POST /auth/register`** · Public

Creates a user, profile, session/activity where applicable, sends email OTP, and returns a Sanctum token.

### Request body (JSON)

| Field | Type | Required | Rules / notes |
|-------|------|----------|----------------|
| `first_name` | string | Yes | Max 255 |
| `last_name` | string | Yes | Max 255 |
| `email` | string | Yes | Valid email, unique in `users` |
| `password` | string | Yes | Must match `password_confirmation`; `Password::defaults()` |
| `password_confirmation` | string | Yes | Same as `password` |
| `role` | string | Yes | One of: `student`, `teacher`, `institute`, `ngo` |
| `phone` | string | No | Max 20 |
| `referral_code` | string | No | Max 20 |
| `device_name` | string | No | Max 255; token name (defaults from User-Agent) |

### Success — `201 Created`

```json
{
  "success": true,
  "message": "User registered successfully",
  "code": 201,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "teacher",
      "email_verified_at": null,
      "phone_verified_at": null,
      "registration_fee_status": "pending"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "next_step": "email_verification",
    "requires_registration_payment": true,
    "registration_charges": {
      "actual_price": 1000,
      "discounted_price": 299,
      "currency": "INR",
      "description": "Teacher Registration Fee"
    }
  }
}
```

`registration_charges` is the `config('registration.charges.{role}')` array (or `null` when payment is not required for that role).

### Errors

| HTTP | When |
|------|------|
| `422` | Validation failed (`errors` field with field messages) |
| `500` | Server error (`Registration failed. Please try again.`; debug details may appear in `errors` if `APP_DEBUG=true`) |

---

## 2. Login (email or phone + password)

**`POST /auth/login`** · Public

| Field | Type | Required | Rules / notes |
|-------|------|----------|----------------|
| `email` | string | Yes | **Identifier:** email **or** phone (see `InputDetectionService`); not limited to email format in validation |
| `password` | string | Yes | Account password |
| `device_name` | string | No | Max 255; Sanctum token name |

**Business rules (high level):**

- User must exist, password must match, account must be **active**.
- **Email must be verified** (`email_verified_at` set); otherwise **`403`** with message *Email not verified. Please verify your email before logging in.*
- If the role requires a **registration fee** and it is not satisfied, the API returns **200** with `success: false` and payment fields (see below).
- If the device is **not** trusted (no valid `X-Device-Token`), an OTP is sent and the response asks for OTP verification instead of returning a token.

### Success — normal login — `200 OK`

```json
{
  "success": true,
  "message": "Login successful",
  "code": 200,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "student",
      "phone_verified_at": null
    },
    "email_verified_at": "2025-01-15T10:00:00.000000Z",
    "registration_fee_status": true,
    "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

`registration_fee_status` in this payload is a **boolean** indicating whether fee is considered satisfied (paid or not required).

### OTP required (untrusted device) — `200 OK`

```json
{
  "success": true,
  "message": "OTP sent to your email. Please verify to complete login.",
  "code": 200,
  "data": {
    "requires_otp": true,
    "identifier": "john@example.com",
    "type": "email",
    "message": "OTP sent to your email. Please verify to complete login."
  }
}
```

`type` may be `email` or `phone` (if phone is missing, email may be used for OTP). Next: call **`POST /auth/login/send-otp`** (optional if OTP already sent) then **`POST /auth/login/verify`**.

### Registration payment required — `200 OK` (note `success: false`)

The controller uses `coreResponse` with failure; the payment payload is attached under **`errors`** in the JSON body:

```json
{
  "success": false,
  "message": "Registration fee payment is required to complete login.",
  "code": 200,
  "errors": {
    "requires_registration_payment": true,
    "payment_link": "https://example.com/pay/...",
    "payment_session_id": "session_xxx",
    "order_id": "order_xxx",
    "actual_price": 500,
    "discounted_price": 450,
    "description": "Teacher Registration Fee",
    "role": "teacher",
    "message": "Registration fee payment is required to complete login."
  }
}
```

### Errors

| HTTP | When |
|------|------|
| `422` | Invalid credentials or invalid email/phone format (`Invalid credentials` / validation messages) |
| `403` | Deactivated account; or email not verified |
| `500` | Other failures |

---

## 3. Send login OTP

**`POST /auth/login/send-otp`** · Public

Sends an OTP to the user’s **email** or **phone** depending on detected `identifier` type.

| Field | Type | Required |
|-------|------|----------|
| `identifier` | string | Yes | Email or phone |

### Success — `200 OK`

```json
{
  "success": true,
  "message": "OTP sent to your email.",
  "code": 200,
  "data": {
    "success": true,
    "message": "OTP sent to your email.",
    "identifier": "john@example.com",
    "type": "email"
  }
}
```

### Errors

| HTTP | When |
|------|------|
| `422` | Invalid identifier format (`identifier` in `errors`) |
| `404` | User not found |
| `403` | Account deactivated (or other forbidden cases from service) |
| `429` | Rate limited (message/status from exception) |
| `500` | Generic failure |

---

## 4. Verify login OTP

**`POST /auth/login/verify`** · Public

| Field | Type | Required | Rules |
|-------|------|----------|--------|
| `identifier` | string | Yes | Same value used for login / send-otp |
| `otp` | string | Yes | One-time code |
| `remember_device` | boolean | No | If true, returns `device_token` for future `X-Device-Token` |
| `device_name` | string | No | Sanctum token / device label (defaults may apply) |

### Success — `200 OK`

```json
{
  "success": true,
  "message": "Login successful",
  "code": 200,
  "data": {
    "success": true,
    "message": "Login successful",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "teacher"
    },
    "email_verified_at": "2025-01-15T10:00:00.000000Z",
    "registration_fee_status": true,
    "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "device_token": "optional_hex_string_if_remember_device_true"
  }
}
```

If registration payment is still required, same pattern as login: **`success: false`**, **`code`: 200**, payment object under **`errors`**.

### Errors

| HTTP | When |
|------|------|
| `422` | Validation or invalid/expired OTP |
| `403` | Forbidden (e.g. deactivated account) |
| `500` | Verification failed |

---

## 5. Logout (current token)

**`POST /auth/logout`** · **Bearer token required**

Revokes the **current** Sanctum token and related session handling.

### Success — `200 OK`

```json
{
  "success": true,
  "message": "Logged out successfully",
  "code": 200
}
```

(No `data` property when empty.)

### Errors

| HTTP | When |
|------|------|
| `401` | Missing/invalid token |
| `500` | Logout failed |

---

## 6. Logout all devices

**`POST /auth/logout-all`** · **Bearer token required**

Deletes **all** personal access tokens for the user and deactivates sessions as implemented in `AuthService`.

### Success — `200 OK`

```json
{
  "success": true,
  "message": "Logged out from all devices successfully",
  "code": 200
}
```

### Errors

| HTTP | When |
|------|------|
| `401` | Unauthenticated |
| `500` | Server error |

---

## 7. Refresh token

**`POST /auth/refresh-token`** · **Bearer token required**

Deletes the current token and issues a new one (`auth-token` as device name in code).

### Success — `200 OK`

```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "code": 200,
  "data": {
    "token": "4|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

### Errors

| HTTP | When |
|------|------|
| `401` | Unauthenticated |
| `500` | Token refresh failed |

---

## 8. Forgot password

**`POST /auth/forgot-password`** · Public

Sends a password reset notification if a matching **active** user exists; response text does not reveal whether the email exists.

| Field | Type | Required |
|-------|------|----------|
| `email` | string | Yes | Valid email format |

### Success — `200 OK`

```json
{
  "success": true,
  "message": "If an account with that email exists, a password reset link has been sent.",
  "code": 200
}
```

### Errors

| HTTP | When |
|------|------|
| `422` | Validation failed |
| `403` | Account deactivated |
| `500` | Password reset request failed |

---

## 9. Reset password

**`POST /auth/reset-password`** · Public

| Field | Type | Required |
|-------|------|----------|
| `email` | string | Yes | User email |
| `token` | string | Yes | Token from reset email |
| `password` | string | Yes | New password (`Password::defaults()`) |
| `password_confirmation` | string | Yes | Must match `password` |

### Success — `200 OK`

```json
{
  "success": true,
  "message": "Password has been reset successfully. Please login with your new password.",
  "code": 200
}
```

### Errors

| HTTP | When |
|------|------|
| `422` | Validation failed |
| `400` | Invalid or expired reset token (message: *Invalid or expired reset token*) |
| `403` | Account deactivated |
| `404` | Mapped to `400` with invalid token message in some paths |
| `500` | Password reset failed |

---

## 10. Resend verification OTP (email or phone)

**`POST /auth/verification/resend`** · **Bearer token required**

| Field | Type | Required | Rules |
|-------|------|----------|--------|
| `type` | string | Yes | `email` or `phone` |

If email is already verified and `type` is `email`, returns **`400`**:

```json
{
  "success": false,
  "message": "Email already verified.",
  "code": 400
}
```

### Success — `200 OK`

```json
{
  "success": true,
  "message": "Verification code sent.",
  "code": 200
}
```

---

## 11. Verify email/phone OTP (account verification)

**`POST /auth/verification/verify`** · **Bearer token required**

Provide **at least one** of:

| Field | Type | Required |
|-------|------|----------|
| `email_otp` | string | No* |
| `phone_otp` | string | No* |

\* One or both may be sent; if neither is sent → **422**.

### Success — `200 OK`

```json
{
  "success": true,
  "message": "Email verified successfully.",
  "code": 200,
  "data": {
    "user": {
      "id": 1,
      "role": "teacher",
      "email": "john@example.com",
      "phone": "+919876543210",
      "email_verified_at": "2025-01-15T12:00:00.000000Z",
      "registration_fee_status": "pending",
      "verification_status": "verified",
      "payment_required": true
    }
  }
}
```

`payment_required` is `true` for non-student roles in code (`student` → `false`).

**Important:** On successful verification of an OTP, the service **logs the user out** (current token revoked). The client should expect **`401`** on subsequent requests with the old token and must **log in again** to obtain a new token.

### Partial failure — `400 Bad Request`

If any provided OTP is wrong, message combines results; `user` may still be included under **`errors`**:

```json
{
  "success": false,
  "message": "Invalid or expired Email OTP.",
  "code": 400,
  "errors": {
    "user": {
      "id": 1,
      "role": "teacher",
      "email": "john@example.com",
      "phone": null,
      "email_verified_at": null,
      "registration_fee_status": "pending",
      "verification_status": "pending",
      "payment_required": true
    }
  }
}
```

### Errors

| HTTP | When |
|------|------|
| `422` | Missing both OTPs or validation failure |
| `400` | Invalid/expired OTP(s) |

---

## Quick reference

| Method | Path | Auth |
|--------|------|------|
| `POST` | `/auth/register` | Public |
| `POST` | `/auth/login` | Public |
| `POST` | `/auth/login/send-otp` | Public |
| `POST` | `/auth/login/verify` | Public |
| `POST` | `/auth/logout` | Bearer |
| `POST` | `/auth/logout-all` | Bearer |
| `POST` | `/auth/refresh-token` | Bearer |
| `POST` | `/auth/forgot-password` | Public |
| `POST` | `/auth/reset-password` | Public |
| `POST` | `/auth/verification/resend` | Bearer |
| `POST` | `/auth/verification/verify` | Bearer |

---

*Generated from `routes/api/v1.php` auth routes, `AuthController`, `VerificationController`, `AuthService`, and form requests.*
