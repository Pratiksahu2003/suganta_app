<?php

namespace App\Services;

class InputDetectionService
{
    /**
     * Detect if the input is an email or phone number.
     *
     * @param string $input
     * @return string|null 'email', 'phone', or null if invalid
     */
    public function detectType(string $input): ?string
    {
        if ($this->isValidEmail($input)) {
            return 'email';
        } else {
            return 'phone';
        }
        return null;
    }

    /**
     * Validate email using PHP's built-in filter.
     *
     * @param string $email
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
