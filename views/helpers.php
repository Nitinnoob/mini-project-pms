<?php
// Pure presentation helpers shared by views. No SQL here.
// Guarded so repeated includes never trigger "cannot redeclare" fatals.

if (!function_exists('e')) {
    // PHP 8 null-safe HTML escape. Accepts null/int/float/bool; never throws
    // TypeError the way htmlspecialchars(null) does. Extra args mirror
    // htmlspecialchars() so existing call sites can be swapped 1:1.
    function e(mixed $value, int $flags = ENT_QUOTES, ?string $encoding = 'UTF-8'): string
    {
        if ($value === null || is_array($value) || is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }
        return htmlspecialchars((string) $value, $flags | ENT_SUBSTITUTE, $encoding ?? 'UTF-8');
    }
}
