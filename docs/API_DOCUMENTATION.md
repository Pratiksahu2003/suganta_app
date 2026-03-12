# SuGanta API Documentation

## Base URL
`http://localhost:8000/api/v1`

## API Modules

| Module | Documentation | Description |
|--------|---------------|-------------|
| **Profile** | [Profile API](./ProfileApi.md) | User profile, location, social, teaching, institute, student, avatar, password, preferences |
| **Auth** | (below) | Register, login, logout, password reset, verification |
| **Support Tickets** | [Support Ticket API](./SUPPORT_TICKET_API.md) | Create, manage support tickets |
| **Portfolio** | [Portfolio API](./PortfolioApi.md) | User portfolios |
| **Registration** | [Registration API](./RegistrationApi.md) | Registration flows |
| **Options** | [Options API](./OptionApi.md) | Dropdown options |
| **Study Requirements** | [Study Requirement API](./StudyRequirementApi.md) | Create, list, view study requirements; connect teachers to requirements |

---

## Authentication

### 1. Register User
Register a new user account.

- **Endpoint**: `POST /auth/register`
- **Access**: Public

#### Request Body
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "student", // Options: student, teacher, institute, ngo
    "phone": "9876543210", // Optional
    "referral_code": "REF123", // Optional
    "device_name": "iPhone 13" // Optional
}
```

#### Success Response (201 Created)
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john.doe@example.com",
            "role": "student"
        },
        "token": "1|abcdef123456...",
        "token_type": "Bearer"
    }
}
```

#### Error Response (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password confirmation does not match."]
    }
}
```

---

### 2. Login User
Authenticate a user and retrieve an access token.

- **Endpoint**: `POST /auth/login`
- **Access**: Public

#### Request Body
```json
{
    "email": "john.doe@example.com",
    "password": "password123",
    "device_name": "Web Browser" // Optional
}
```

#### Success Response (200 OK)
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john.doe@example.com",
            "role": "student"
        },
        "token": "2|ghijkl789012...",
        "token_type": "Bearer"
    }
}
```

#### Payment Required Response (200 OK - Specific Case)
If the user role requires a registration fee that hasn't been paid:
```json
{
    "success": false,
    "requires_registration_payment": true,
    "payment_link": "https://payments.cashfree.com/...",
    "order_id": "REG_ABC123",
    "actual_price": 500,
    "discounted_price": 450,
    "description": "Teacher Registration Fee",
    "role": "teacher",
    "message": "Registration fee payment is required to complete login."
}
```

---

### 3. Logout
Invalidate the current access token.

- **Endpoint**: `POST /auth/logout`
- **Access**: Private (Requires Bearer Token)

#### Headers
```
Authorization: Bearer <your_token_here>
```

#### Success Response (200 OK)
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

### 4. Logout All Devices
Invalidate all access tokens for the user.

- **Endpoint**: `POST /auth/logout-all`
- **Access**: Private (Requires Bearer Token)

#### Headers
```
Authorization: Bearer <your_token_here>
```

#### Success Response (200 OK)
```json
{
    "success": true,
    "message": "Logged out from all devices successfully"
}
```

---

### 5. Refresh Token
Generate a new access token and invalidate the current one.

- **Endpoint**: `POST /auth/refresh-token`
- **Access**: Private (Requires Bearer Token)

#### Headers
```
Authorization: Bearer <your_token_here>
```

#### Success Response (200 OK)
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "3|mnopqr345678...",
        "token_type": "Bearer"
    }
}
```

---

### 6. Forgot Password
Send a password reset link to the user's email.

- **Endpoint**: `POST /auth/forgot-password`
- **Access**: Public

#### Request Body
```json
{
    "email": "john.doe@example.com"
}
```

#### Success Response (200 OK)
```json
{
    "success": true,
    "message": "If an account with that email exists, a password reset link has been sent."
}
```

---

### 7. Reset Password
Reset the user's password using the token received via email.

- **Endpoint**: `POST /auth/reset-password`
- **Access**: Public

#### Request Body
```json
{
    "token": "reset_token_from_email",
    "email": "john.doe@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

#### Success Response (200 OK)
```json
{
    "success": true,
    "message": "Password has been reset successfully. Please login with your new password."
}
```

---

### 8. Resend Verification OTP
Resend the verification OTP to email or phone.

- **Endpoint**: `POST /auth/verification/resend`
- **Access**: Private (Requires Bearer Token)

#### Headers
```
Authorization: Bearer <your_token_here>
```

#### Request Body
```json
{
    "type": "email" // or "phone"
}
```

#### Success Response (200 OK)
```json
{
    "message": "Verification code sent."
}
```

---

### 9. Verify OTP
Verify the OTP received via email or phone.

- **Endpoint**: `POST /auth/verification/verify`
- **Access**: Private (Requires Bearer Token)

#### Headers
```
Authorization: Bearer <your_token_here>
```

#### Request Body
```json
{
    "type": "email", // or "phone"
    "otp": "123456"
}
```

#### Success Response (200 OK)
```json
{
    "message": "Email verified successfully."
}
```

---

## Configuration Notes

### Cashfree Payment Gateway
The application uses Cashfree for payment processing. Ensure the following environment variables are set in your `.env` file for payment features to work correctly:

```env
CASHFREE_APP_ID=your_app_id
CASHFREE_SECRET_KEY=your_secret_key
CASHFREE_IS_PRODUCTION=false # Set to true for production
CASHFREE_API_VERSION=2022-09-01
```

### Logging
Payment logs are stored separately in `storage/logs/payment.log` for easier debugging of transaction issues.
