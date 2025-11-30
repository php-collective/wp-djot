<?php

declare(strict_types=1);

namespace WpDjot\Shortcodes;

use WpDjot\Converter;

/**
 * Shortcode handler for Djot markup.
 *
 * Usage: [djot]Your *Djot* markup here[/djot]
 * Or with safe mode: [djot safe="true"]Untrusted content[/djot]
 */
class DjotShortcode
{
    private Converter $converter;

    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Register the shortcode with WordPress.
     */
    public function register(string $tag = 'djot'): void
    {
        add_shortcode($tag, [$this, 'handle']);
    }

    /**
     * Handle the shortcode.
     *
     * @param array<string, string>|string $atts Shortcode attributes.
     * @param string|null $content Content between shortcode tags.
     * @param string $tag Shortcode tag.
     */
    public function handle(array|string $atts, ?string $content, string $tag): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        // Parse attributes
        $attributes = shortcode_atts(
            [
                'safe' => null,
                'class' => '',
            ],
            is_array($atts) ? $atts : [],
            $tag,
        );

        // Determine safe mode
        $safeMode = null;
        if ($attributes['safe'] !== null) {
            $safeMode = filter_var($attributes['safe'], FILTER_VALIDATE_BOOLEAN);
        }

        // Convert content
        $html = $this->converter->convert($content, $safeMode);

        // Add custom class if provided
        if (!empty($attributes['class'])) {
            $html = str_replace(
                'class="djot-content"',
                'class="djot-content ' . esc_attr($attributes['class']) . '"',
                $html,
            );
        }

        return $html;
    }
}
