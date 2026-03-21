<?php

namespace App\Support;

/**
 * Plain-text + emoji only (Instagram-style DM body). No HTML, no attachments metadata.
 */
final class ChatPlainText
{
    public static function sanitize(string $text): string
    {
        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }

        $text = str_replace(["\x00", "\r\n", "\r"], ['', "\n", "\n"], $text);
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? '';
        $lines = preg_split('/\n/u', $text) ?: [];
        $lines = array_map(static fn (string $l): string => trim($l, " \t"), $lines);
        $text = implode("\n", $lines);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? '';

        return trim($text);
    }

    public static function isEmpty(string $text): bool
    {
        return trim(str_replace(["\u{200B}", "\u{FEFF}"], '', $text)) === '';
    }
}
