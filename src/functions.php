<?php

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template tags and helper functions for WP Djot.
 *
 * @package WpDjot
 */

use WpDjot\Converter;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- wpdjot_ is our plugin prefix

/**
 * Convert Djot markup to HTML.
 *
 * @param string $djot The Djot markup to convert.
 * @param bool $safeMode Whether to use safe mode (default: true).
 * @param string|null $context Context: 'post' uses post profile, 'comment' uses comment profile.
 * @return string The converted HTML.
 */
function wpdjot_to_html(string $djot, bool $safeMode = true, ?string $context = 'post'): string
{
    static $converter = null;

    if ($converter === null) {
        $converter = Converter::fromSettings();
    }

    if ($context === 'comment') {
        return $converter->convertComment($djot);
    }

    return $converter->convertArticle($djot);
}

/**
 * Echo Djot markup converted to HTML.
 *
 * @param string $djot The Djot markup to convert.
 * @param bool $safeMode Whether to use safe mode (default: true).
 * @param string|null $context Context: 'post' uses post profile, 'comment' uses comment profile.
 */
function wpdjot_the(string $djot, bool $safeMode = true, ?string $context = 'post'): void
{
    echo wp_kses_post(wpdjot_to_html($djot, $safeMode, $context));
}

/**
 * Check if content contains Djot markup.
 *
 * @param string $content The content to check.
 * @return bool True if content contains Djot markup.
 */
function wpdjot_has(string $content): bool
{
    return str_contains($content, '{djot}') || str_contains($content, '[djot]');
}
