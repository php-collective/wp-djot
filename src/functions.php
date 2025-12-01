<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- wp_djot_ is our plugin prefix

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
function wp_djot_to_html(string $djot, bool $safeMode = true): string
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
function wp_djot_the(string $djot, bool $safeMode = true): void
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output is intentional, already sanitized by converter
    echo wp_kses_post(wp_djot_to_html($djot, $safeMode));
}

/**
 * Check if content contains Djot markup.
 *
 * @param string $content The content to check.
 * @return bool True if content contains Djot markup.
 */
function wp_djot_has(string $content): bool
{
    return str_contains($content, '{djot}') || str_contains($content, '[djot]');
}
