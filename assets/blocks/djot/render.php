<?php
/**
 * Server-side rendering for the Djot block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to block render context
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes is pre-escaped by get_block_wrapper_attributes()

$content = $attributes['content'] ?? '';

if (empty($content)) {
    return;
}

// Get settings to use configured post profile
$options = get_option('wp_djot_settings', []);
$postProfile = $options['post_profile'] ?? 'article';

$converter = new WpDjot\Converter(false, $postProfile, 'comment');
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

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'wp-djot-block-rendered']);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes is pre-escaped by get_block_wrapper_attributes()
printf(
    '<div %s>%s</div>',
    $wrapper_attributes,
    wp_kses_post($html)
);
