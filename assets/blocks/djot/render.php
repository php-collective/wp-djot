<?php
/**
 * Server-side rendering for the Djot block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to block render context

$content = $attributes['content'] ?? '';

if (!$content) {
    return;
}

// Get settings
$options = get_option('wpdjot_settings', []);
$postProfile = $options['post_profile'] ?? 'article';

// Use factory method to ensure all settings are applied
$converter = WpDjot\Converter::fromSettings();
$html = $converter->convertArticle($content);

// Escape shortcode brackets inside <code> and <pre> tags to prevent WordPress processing
$html = preg_replace_callback(
    '/<(code|pre)[^>]*>.*?<\/\1>/is',
    static function (array $matches): string {
        // Replace [ and ] with HTML entities inside code blocks
        return str_replace(['[', ']'], ['&#91;', '&#93;'], $matches[0]);
    },
    $html
) ?? $html;

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'wpdjot-block-rendered djot-content']);

// Build output: wp_kses_post covers both $wrapper_attributes (pre-escaped by get_block_wrapper_attributes) and $html
if ($postProfile !== 'none' && $postProfile !== 'full') {
    $html = wp_kses($html, WpDjot\Converter::getAllowedHtml());
}

echo wp_kses_post(sprintf('<div %s>%s</div>', $wrapper_attributes, $html));
