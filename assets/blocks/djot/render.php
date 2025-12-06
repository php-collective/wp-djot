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

// Get settings to use configured profiles and rendering options
$options = get_option('wp_djot_settings', []);
$safeMode = !empty($options['safe_mode']);
$postProfile = $options['post_profile'] ?? 'article';
$commentProfile = $options['comment_profile'] ?? 'comment';
$postSoftBreak = $options['post_soft_break'] ?? 'newline';
$commentSoftBreak = $options['comment_soft_break'] ?? 'newline';
$markdownMode = !empty($options['markdown_mode']);

$converter = new WpDjot\Converter($safeMode, $postProfile, $commentProfile, $postSoftBreak, $commentSoftBreak, $markdownMode);
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

// Allow checkbox inputs and ul class for task lists in addition to standard post HTML
$allowed_html = wp_kses_allowed_html('post');
$allowed_html['input'] = [
    'type' => true,
    'checked' => true,
    'disabled' => true,
    'class' => true,
];
$allowed_html['ul']['class'] = true;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes is pre-escaped, $html is sanitized by wp_kses
printf(
    '<div %s>%s</div>',
    $wrapper_attributes,
    wp_kses($html, $allowed_html)
);
