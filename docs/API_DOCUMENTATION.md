# SuGanta API Documentation

## Base URL
`http://localhost:8000/api/v1`

## API Modules

| Module | Documentation | Description |
|--------|---------------|-------------|
| **Profile** | [Profile API](./ProfileApi.md) | User profile, location, social, teaching, institute, student, avatar, password, preferences |
| **Profile Teaching** | [Profile Teaching API](./ProfileTeachingApi.md) | Update teaching info (qualification, experience, rates, subjects) |
| **Dashboard** | [Dashboard API](./DashboardApi.md) | User dashboard: counts, recent payments, recent leads, notifications |
| **Auth** | [Auth API](./AuthApi.md) | Register, login, OTP login, logout, token refresh, password reset, email/phone verification |
| **Support Tickets** | [Support Ticket API](./SUPPORT_TICKET_API.md) | Create, manage support tickets |
| **Portfolio** | [Portfolio API](./PortfolioApi.md) | User portfolios |
| **Registration** | [Registration API](./RegistrationApi.md) | Registration flows |
| **Options** | [Options API](./OptionApi.md) | Dropdown options |
| **Subjects** | [Subject API](./SubjectApi.md) | Subject list (id, name) with search |
| **Study Requirements** | [Study Requirement API](./StudyRequirementApi.md) | Create, list, view study requirements; connect teachers to requirements |
| **Chat V3 (Realtime)** | [Chat API V3 (Flutter + Realtime)](./ChatApiV3Flutter.md) | Conversation/message APIs with Reverb private-channel realtime events |

---

## Authentication

Full reference: **[Auth API](./AuthApi.md)** — request parameters, JSON response shapes (including OTP login, registration payment on `success: false`, `X-Device-Token`, and account verification with `email_otp` / `phone_otp`).

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
