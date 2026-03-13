<?php

namespace App\Helpers;

class ProfileOptionsHelper
{
    /**
     * Get the display label for an option ID from config/options.php.
     *
     * @param string $optionKey Key in config options (e.g. 'gender', 'country')
     * @param int|string|null $id The ID stored in database
     * @return string|null
     */
    public static function getLabel(string $optionKey, $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        $options = config("options.{$optionKey}");
        return $options[$id] ?? null;
    }

    /**
     * Get the ID for a label (reverse lookup).
     * Used for migrations when converting string values to IDs.
     *
     * @param string $optionKey Key in config options
     * @param string|null $label The label to find
     * @return int|string|null
     */
    public static function getValue(string $optionKey, ?string $label)
    {
        if ($label === null || $label === '') {
            return null;
        }

        $options = config("options.{$optionKey}");
        if (!$options) {
            return null;
        }

        $flipped = array_flip(array_map('strval', $options));
        return $flipped[(string) $label] ?? null;
    }

    /**
     * Get option as structured array { id, label } for API responses.
     *
     * @param string $optionKey Key in config options
     * @param int|string|null $id The ID stored in database
     * @return array{id: int|string, label: string}|null
     */
    public static function getOptionStructure(string $optionKey, $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $label = self::getLabel($optionKey, $id);
        if ($label === null && is_string($id)) {
            $resolvedId = self::getValue($optionKey, $id);
            if ($resolvedId !== null) {
                $id = $resolvedId;
                $label = self::getLabel($optionKey, $id);
            }
        }
        if ($label === null) {
            return null;
        }

        return [
            'id' => is_numeric($id) ? (int) $id : $id,
            'label' => $label,
        ];
    }
}
