<?php
/**
 * Server-side rendering for the Djot block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

$content = $attributes['content'] ?? '';

if (empty($content)) {
    return;
}

$converter = new WpDjot\Converter(false);
$html = $converter->convert($content);

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

printf(
    '<div %s>%s</div>',
    $wrapper_attributes,
    $html
);
