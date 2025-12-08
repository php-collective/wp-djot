<?php

declare(strict_types=1);

/**
 * Template tags and helper functions for WP Djot.
 *
 * @package WpDjot
 */

use WpDjot\Converter;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- wp_djot_ is our plugin prefix

/**
 * Convert Djot markup to HTML.
 *
 * @param string $djot The Djot markup to convert.
 * @param bool $safeMode Whether to use safe mode (default: true).
 * @param string|null $context Context: 'post' uses post profile, 'comment' uses comment profile.
 * @return string The converted HTML.
 */
function wp_djot_to_html(string $djot, bool $safeMode = true, ?string $context = 'post'): string
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
function wp_djot_the(string $djot, bool $safeMode = true, ?string $context = 'post'): void
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output is intentional, already sanitized by converter
    echo wp_kses_post(wp_djot_to_html($djot, $safeMode, $context));
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
