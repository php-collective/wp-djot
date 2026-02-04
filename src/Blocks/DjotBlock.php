<?php

declare(strict_types=1);

namespace WpDjot\Blocks;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Request;
use WP_REST_Response;
use WpDjot\Converter;
use WpDjot\Converter\WpHtmlToDjot;
use WpDjot\Converter\WpMarkdownToDjot;

/**
 * Registers the Djot Gutenberg block.
 */
class DjotBlock
{
    private Converter $converter;

    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Initialize block registration.
     */
    public function init(): void
    {
        add_action('init', [$this, 'register']);
        add_action('rest_api_init', [$this, 'registerRestRoute']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorAssets']);
    }

    /**
     * Register the block with highlight.js styles as editor style dependency.
     */
    public function register(): void
    {
        $options = get_option('wpdjot_settings', []);
        $highlightCode = $options['highlight_code'] ?? true;
        $theme = $options['highlight_theme'] ?? 'github';

        if ($highlightCode) {
            // Register highlight.js style
            wp_register_style(
                'wpdjot-highlight',
                WPDJOT_PLUGIN_URL . "assets/vendor/highlight.js/styles/{$theme}.min.css",
                [],
                WPDJOT_VERSION,
            );
        }

        // Register the block - this auto-registers wpdjot-djot-editor-style from block.json
        register_block_type(WPDJOT_PLUGIN_DIR . 'assets/blocks/djot');

        // Register old block name as alias for backward compatibility with existing content
        register_block_type('wp-djot/djot', [
            'render_callback' => function (array $attributes): string {
                return $this->renderLegacyBlock($attributes);
            },
        ]);

        if ($highlightCode) {
            // Add highlight.js CSS as dependency of the block's editor style
            $styles = wp_styles();
            if (isset($styles->registered['wpdjot-djot-editor-style'])) {
                $styles->registered['wpdjot-djot-editor-style']->deps[] = 'wpdjot-highlight';
            }
        }
    }

    /**
     * Enqueue highlight.js script for block editor preview.
     */
    public function enqueueEditorAssets(): void
    {
        $options = get_option('wpdjot_settings', []);
        $highlightCode = $options['highlight_code'] ?? true;

        if (!$highlightCode) {
            return;
        }

        wp_enqueue_script(
            'wpdjot-highlight-editor',
            WPDJOT_PLUGIN_URL . 'assets/vendor/highlight.js/highlight.min.js',
            [],
            WPDJOT_VERSION,
            false,
        );
    }

    /**
     * Register REST API routes for live preview.
     */
    public function registerRestRoute(): void
    {
        // Editor preview (requires edit_posts capability)
        register_rest_route('wpdjot/v1', '/render', [
            'methods' => 'POST',
            'callback' => [$this, 'renderPreview'],
            'permission_callback' => [$this, 'canEdit'],
            'args' => [
                'content' => [
                    'required' => true,
                    'type' => 'string',
                    // Don't use sanitize_textarea_field - it strips HTML tags
                    // which breaks raw HTML blocks. Security is handled by:
                    // 1. permission_callback requiring edit_posts capability
                    // 2. Profile-based feature restrictions in the converter
                    // Note: No sanitization needed - apiFetch sends JSON which
                    // doesn't have WordPress magic quotes issues
                ],
            ],
        ]);

        // Markdown to Djot conversion (requires edit_posts capability)
        register_rest_route('wpdjot/v1', '/convert-markdown', [
            'methods' => 'POST',
            'callback' => [$this, 'convertMarkdown'],
            'permission_callback' => [$this, 'canEdit'],
            'args' => [
                'content' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // HTML to Djot conversion (requires edit_posts capability)
        register_rest_route('wpdjot/v1', '/convert-html', [
            'methods' => 'POST',
            'callback' => [$this, 'convertHtml'],
            'permission_callback' => [$this, 'canEdit'],
            'args' => [
                'content' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // Comment preview (public, uses restricted comment profile)
        register_rest_route('wpdjot/v1', '/preview-comment', [
            'methods' => 'POST',
            'callback' => [$this, 'renderCommentPreview'],
            'permission_callback' => '__return_true',
            'args' => [
                'content' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);
    }

    /**
     * Check if user can edit posts.
     */
    public function canEdit(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Render legacy wp-djot/djot blocks for backward compatibility.
     *
     * @param array<string, mixed> $attributes Block attributes.
     */
    public function renderLegacyBlock(array $attributes): string
    {
        $content = $attributes['content'] ?? '';

        if (!$content) {
            return '';
        }

        $options = get_option('wpdjot_settings', []);
        $postProfile = $options['post_profile'] ?? 'article';

        $html = $this->converter->convertArticle($content);

        // Escape shortcode brackets inside <code> and <pre> tags
        $html = preg_replace_callback(
            '/<(code|pre)[^>]*>.*?<\/\1>/is',
            static function (array $matches): string {
                return str_replace(['[', ']'], ['&#91;', '&#93;'], $matches[0]);
            },
            $html,
        ) ?? $html;

        $wrapperClass = 'wp-block-wpdjot-djot wpdjot-block-rendered djot-content';

        if ($postProfile === 'none' || $postProfile === 'full') {
            return sprintf('<div class="%s">%s</div>', esc_attr($wrapperClass), $html);
        }

        return sprintf('<div class="%s">%s</div>', esc_attr($wrapperClass), wp_kses($html, Converter::getAllowedHtml()));
    }

    /**
     * Render Djot content for preview.
     */
    public function renderPreview(WP_REST_Request $request): WP_REST_Response
    {
        $content = $request->get_param('content');

        if (!$content) {
            return new WP_REST_Response(['html' => ''], 200);
        }

        $html = $this->converter->convertArticle($content);

        // Remove the wrapper div for preview (it's added by the block itself)
        $html = preg_replace('/^<div class="djot-content">(.*)<\/div>$/s', '$1', $html) ?? $html;

        return new WP_REST_Response(['html' => $html], 200);
    }

    /**
     * Render Djot comment content for preview.
     *
     * Uses comment profile with restricted features for safety.
     */
    public function renderCommentPreview(WP_REST_Request $request): WP_REST_Response
    {
        $content = $request->get_param('content');

        if (!$content) {
            return new WP_REST_Response(['html' => ''], 200);
        }

        $html = $this->converter->convertComment($content);

        // Remove the wrapper div for preview
        $html = preg_replace('/^<div class="djot-content">(.*)<\/div>$/s', '$1', $html) ?? $html;

        return new WP_REST_Response(['html' => $html], 200);
    }

    /**
     * Convert Markdown to Djot.
     */
    public function convertMarkdown(WP_REST_Request $request): WP_REST_Response
    {
        $content = $request->get_param('content');

        if (!$content) {
            return new WP_REST_Response(['djot' => ''], 200);
        }

        $converter = new WpMarkdownToDjot();
        $djot = $converter->convert($content);

        return new WP_REST_Response(['djot' => $djot], 200);
    }

    /**
     * Convert HTML to Djot.
     */
    public function convertHtml(WP_REST_Request $request): WP_REST_Response
    {
        $content = $request->get_param('content');

        if (!$content) {
            return new WP_REST_Response(['djot' => ''], 200);
        }

        $converter = new WpHtmlToDjot();
        $djot = $converter->convert($content);

        return new WP_REST_Response(['djot' => $djot], 200);
    }
}
