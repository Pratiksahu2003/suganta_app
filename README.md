# SuGanta API

Laravel-based REST API project. Structure and defaults are set up for building and maintaining a clean, versioned API.

## Stack

- **Laravel 12** (PHP 8.2+)
- **API-only** routing and JSON responses
- **Versioned routes**: `/api/v1/...`
- **Structured responses**: `success`, `message`, `data`, `errors`, `meta`

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## API Base URL

- Local: `http://localhost:8000/api/v1`
- All API responses are JSON.

## Project structure (API)

| Path | Purpose |
|------|--------|
| `routes/api.php` | API routes; versioned under `v1` |
| `app/Http/Controllers/Api/V1/` | Versioned API controllers |
| `app/Http/Controllers/Api/V1/BaseApiController.php` | Base controller with `ApiResponse` trait |
| `app/Traits/ApiResponse.php` | `success()`, `error()`, `created()`, `paginated()` helpers |
| `app/Http/Middleware/ForceJsonApi.php` | Forces `Accept: application/json` on API routes |
| `config/api.php` | API version, rate limit, response keys |
| `bootstrap/app.php` | Registers API routes, JSON exception handling, API middleware |

## Response format

**Success (e.g. 200):**
```json
{
  "success": true,
  "message": "Success",
  "data": { ... }
}
```

**Error (e.g. 400, 422, 500):**
```json
{
  "success": false,
  "message": "Error description",
  "errors": null
}
```

**Validation (422):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Validation message."]
  }
}
```

## Endpoints

- **GET /api/v1/health** – Health check (app + DB status). No auth.

Add new endpoints under `Route::prefix('v1')->group(...)` in `routes/api.php`, using controllers in `App\Http\Controllers\Api\V1\`.

## Adding new API resources

1. Create a controller in `App\Http\Controllers\Api\V1\` extending `BaseApiController`.
2. Use the `ApiResponse` trait methods: `$this->success()`, `$this->error()`, `$this->created()`, `$this->paginated()`.
3. Register routes in `routes/api.php` inside the `v1` prefix.
4. Optionally use Form Requests and API Resources (e.g. `php artisan make:resource UserResource`).

## Authentication (optional)

For token-based auth, install Laravel Sanctum:

```bash
php artisan install:api
```

Then use `auth:sanctum` middleware on protected routes and add `HasApiTokens` to the `User` model.

## Rate limiting

API routes use the default `api` throttle (e.g. 60 requests per minute). Configure in `config/api.php` (`API_RATE_LIMIT`) or in `bootstrap/app.php` if you need a custom limiter.

## API Documentation

- [Option API](docs/OptionApi.md)
- [Registration API](docs/RegistrationApi.md)
- [Portfolio API](docs/PortfolioApi.md)

## File Storage Documentation

- [Storage Structure](docs/StorageStructure.md)
- [HandlesFileStorage Trait](docs/HandlesFileStorageTrait.md)
- [Storage Switch Guide](docs/StorageSwitchGuide.md)
- [GCP Cloud Storage Setup](docs/GCP_Storage_Setup.md)

## License

MIT.
