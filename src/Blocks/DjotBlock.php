<?php

declare(strict_types=1);

namespace WpDjot\Blocks;

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
        $options = get_option('wp_djot_settings', []);
        $highlightCode = $options['highlight_code'] ?? true;
        $theme = $options['highlight_theme'] ?? 'github';

        if ($highlightCode) {
            // Register highlight.js style
            wp_register_style(
                'wp-djot-highlight',
                WP_DJOT_PLUGIN_URL . "assets/vendor/highlight.js/styles/{$theme}.min.css",
                [],
                WP_DJOT_VERSION,
            );
        }

        // Register the block - this auto-registers wp-djot-djot-editor-style from block.json
        register_block_type(WP_DJOT_PLUGIN_DIR . 'assets/blocks/djot');

        if ($highlightCode) {
            // Add highlight.js CSS as dependency of the block's editor style
            $styles = wp_styles();
            if (isset($styles->registered['wp-djot-djot-editor-style'])) {
                $styles->registered['wp-djot-djot-editor-style']->deps[] = 'wp-djot-highlight';
            }
        }
    }

    /**
     * Enqueue highlight.js script for block editor preview.
     */
    public function enqueueEditorAssets(): void
    {
        $options = get_option('wp_djot_settings', []);
        $highlightCode = $options['highlight_code'] ?? true;

        if (!$highlightCode) {
            return;
        }

        wp_enqueue_script(
            'wp-djot-highlight-editor',
            WP_DJOT_PLUGIN_URL . 'assets/vendor/highlight.js/highlight.min.js',
            [],
            WP_DJOT_VERSION,
            false,
        );
    }

    /**
     * Register REST API routes for live preview.
     */
    public function registerRestRoute(): void
    {
        // Editor preview (requires edit_posts capability)
        register_rest_route('wp-djot/v1', '/render', [
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
        register_rest_route('wp-djot/v1', '/convert-markdown', [
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
        register_rest_route('wp-djot/v1', '/convert-html', [
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
        register_rest_route('wp-djot/v1', '/preview-comment', [
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
