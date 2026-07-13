<?php

declare(strict_types=1);

namespace WpDjot\Shortcodes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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

    private string $tag = 'djot';

    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Register the shortcode with WordPress.
     */
    public function register(string $tag = 'djot'): void
    {
        // Fall back to the default tag when the configured tag is blank, so we
        // never register an empty shortcode or propagate an empty tag.
        $this->tag = $tag !== '' ? $tag : 'djot';
        add_shortcode($this->tag, [$this, 'handle']);
        // wptexturize runs on the_content at priority 10, before shortcodes
        // execute at 11 - without this exclusion it curls the straight quotes
        // of a fence title (`::: tab "Overview"`), the line stops being a
        // fence, and the whole block degrades to a literal paragraph.
        add_filter('no_texturize_shortcodes', [$this, 'excludeFromTexturize']);
    }

    /**
     * Exclude the Djot shortcode from wptexturize so fence titles keep their
     * straight quotes.
     *
     * @param array<string> $shortcodes
     *
     * @return array<string>
     */
    public function excludeFromTexturize(array $shortcodes): array
    {
        if ($this->tag !== '' && !in_array($this->tag, $shortcodes, true)) {
            $shortcodes[] = $this->tag;
        }

        return $shortcodes;
    }

    /**
     * Straighten typographic quotes on `:::` opener lines only, outside fenced
     * code blocks. wptexturize (on this site the no_texturize_shortcodes
     * exclusion prevents that) or quotes pasted from a word processor can curl
     * the straight quotes of a fence title (`::: tab "Overview"`), which stops
     * the line being a fence and degrades the block to a literal paragraph. On
     * a `:::` line a curly quote is never intended, so straightening is
     * lossless; a code sample documenting a curly-quoted fence line stays
     * verbatim.
     */
    private static function straightenFenceQuotes(string $content): string
    {
        $lines = explode("\n", $content);
        $codeFence = null;
        foreach ($lines as $i => $line) {
            if ($codeFence !== null) {
                if (
                    preg_match('/^[ \t]*(`{3,}|~{3,})[ \t\r]*$/', $line, $m) === 1
                    && $m[1][0] === $codeFence[0]
                    && strlen($m[1]) >= strlen($codeFence)
                ) {
                    $codeFence = null;
                }

                continue;
            }
            if (preg_match('/^[ \t]*(`{3,}|~{3,})/', $line, $m) === 1) {
                $codeFence = $m[1];

                continue;
            }
            if (preg_match('/^[ \t]*:{3,}/', $line) === 1) {
                $lines[$i] = str_replace(['“', '”', '„'], '"', $line);
            }
        }

        return implode("\n", $lines);
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

        // Keep fence-opener quotes straight so titled fences (`::: tab "x"`)
        // survive content that arrived pre-curled.
        $content = self::straightenFenceQuotes($content);

        // Parse attributes
        $attributes = shortcode_atts(
            [
                'safe' => null,
                'class' => '',
            ],
            is_array($atts) ? $atts : [],
            $tag,
        );

        // Determine if safe mode is explicitly set
        $explicitSafeMode = null;
        if ($attributes['safe'] !== null) {
            $explicitSafeMode = filter_var($attributes['safe'], FILTER_VALIDATE_BOOLEAN);
        }

        // Convert content using post profile (shortcodes are typically in posts/pages)
        // If safe="true" is explicitly set, use safe converter instead
        if ($explicitSafeMode === true) {
            $html = $this->converter->convertSafe($content);
        } else {
            $html = $this->converter->convertArticle($content);
        }

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
