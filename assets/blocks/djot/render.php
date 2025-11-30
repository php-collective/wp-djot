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

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'wp-djot-block-rendered']);

printf(
    '<div %s>%s</div>',
    $wrapper_attributes,
    $html
);
