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
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if ($this->isValidEmail($input)) {
            return 'email';
        }

        if ($this->isValidPhone($input)) {
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
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Basic phone number validation.
     *
     * Accepts numbers with optional +, spaces, dashes, and parentheses.
     * Ensures there are enough digits to be a realistic phone number.
     *
     * @param string $phone
     * @return bool
     */
    public  function isValidPhone(string $phone): bool
    {
        // Remove common formatting characters
        $digitsOnly = preg_replace('/\D+/', '', $phone);

        // Require between 7 and 15 digits (typical phone number range)
        if ($digitsOnly === null) {
            return false;
        }

        $length = strlen($digitsOnly);

        return $length >= 7 && $length <= 15;
    }
}
