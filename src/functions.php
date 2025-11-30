<?php

declare(strict_types=1);

/**
 * Template tags and helper functions for WP Djot.
 *
 * @package WpDjot
 */

use WpDjot\Converter;

/**
 * Convert Djot markup to HTML.
 *
 * @param string $djot The Djot markup to convert.
 * @param bool $safeMode Whether to use safe mode (default: true).
 * @return string The converted HTML.
 */
function djot_to_html(string $djot, bool $safeMode = true): string
{
    static $converter = null;

    if ($converter === null) {
        $converter = new Converter($safeMode);
    }

    return $converter->convert($djot, $safeMode);
}

/**
 * Echo Djot markup converted to HTML.
 *
 * @param string $djot The Djot markup to convert.
 * @param bool $safeMode Whether to use safe mode (default: true).
 */
function the_djot(string $djot, bool $safeMode = true): void
{
    echo djot_to_html($djot, $safeMode);
}

/**
 * Check if content contains Djot markup.
 *
 * @param string $content The content to check.
 * @return bool True if content contains Djot markup.
 */
function has_djot(string $content): bool
{
    return str_contains($content, '{djot}') || str_contains($content, '[djot]');
}
