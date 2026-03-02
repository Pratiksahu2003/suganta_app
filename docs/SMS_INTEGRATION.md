# SMS Country API Integration Documentation

This document outlines the setup, configuration, and usage of the SMS Country API integration within the SuGanta API application.

## Overview

The application uses **SMS Country** as the primary SMS gateway provider. A custom service wrapper `App\Services\SmsCountryService` handles API communication, template parsing, and error logging.

## Configuration

### 1. Environment Variables (.env)

The following keys must be present in your `.env` file to authenticate with the SMS Country API:

```dotenv
SMS_COUNTRY_AUTH_KEY=your_auth_key
SMS_COUNTRY_AUTH_TOKEN=your_auth_token
SMS_COUNTRY_BASE_URL="https://restapi.smscountry.com/v0.1/Accounts/YOUR_AUTH_KEY/SMSes/"
SMS_COUNTRY_SENDER_ID=YOUR_SENDER_ID
```

### 2. Service Configuration

The credentials are mapped in `config/services.php`:

```php
'smscountry' => [
    'auth_key' => env('SMS_COUNTRY_AUTH_KEY'),
    'auth_token' => env('SMS_COUNTRY_AUTH_TOKEN'),
    'sender_id' => env('SMS_COUNTRY_SENDER_ID'),
    'url' => env('SMS_COUNTRY_BASE_URL'),
],
```

### 3. Template Configuration

All SMS templates are defined in `config/sms_templates.php`. This file manages template content, required variables, and DLT (Distributed Ledger Technology) IDs.

**Example Template Structure:**

```php
'dlt_otp_verification' => [
    'name' => 'DLT OTP Verification',
    'content' => '{{otp}} is your verification code for Suganta Tutors. It expires in 5 minutes.',
    'required_variables' => ['otp'],
    'category' => 'dlt_authentication',
    'dlt_template_id' => 'DLT_OTP_001'
],
```

## Usage

### Using the Facade (Recommended)

You can use the `Sms` facade to send messages easily from anywhere in your application (Controllers, Jobs, etc.).

#### 1. Send using a Template

This is the preferred method as it ensures DLT compliance and consistent messaging.

```php
use App\Facades\Sms;

try {
    $response = Sms::sendTemplate('919876543210', 'dlt_otp_verification', [
        'otp' => '123456'
    ]);

    if ($response['status']) {
        // SMS Sent Successfully
    } else {
        // Failed: $response['error']
    }
} catch (\Exception $e) {
    // Handle template errors (e.g., missing variables)
}
```

#### 2. Send Raw SMS

Use this for testing or non-template messages (Note: Non-DLT messages may be blocked in production).

```php
use App\Facades\Sms;

$response = Sms::sendRaw('919876543210', 'Your custom message here');
```

## Adding New Templates

To add a new SMS template:

1.  Open `config/sms_templates.php`.
2.  Add a new entry to the `'templates'` array:

    ```php
    'new_template_key' => [
        'name' => 'New Template Name',
        'content' => 'Hello {{user_name}}, welcome to SuGanta!',
        'required_variables' => ['user_name'],
        'category' => 'dlt_onboarding',
        'dlt_template_id' => 'DLT_XXX_000'
    ],
    ```

3.  Use it in your code: `Sms::sendTemplate($mobile, 'new_template_key', ['user_name' => 'John'])`.

## Logs & Debugging

*   **Success/Failure Logs**: All SMS attempts are logged to the default application log (e.g., `storage/logs/laravel.log`).
*   **Log Level**: Successful sends are logged as `info`, failures as `error` or `warning`.

## File Structure

*   **Service**: [`app/Services/SmsCountryService.php`](../app/Services/SmsCountryService.php)
*   **Facade**: [`app/Facades/Sms.php`](../app/Facades/Sms.php)
*   **Provider**: [`app/Providers/SmsServiceProvider.php`](../app/Providers/SmsServiceProvider.php)
*   **Config**: [`config/sms_templates.php`](../config/sms_templates.php)
