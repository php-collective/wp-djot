<?php

declare(strict_types=1);

namespace WpDjot\Blocks;

use WP_REST_Request;
use WP_REST_Response;
use WpDjot\Converter;

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
    }

    /**
     * Register the block.
     */
    public function register(): void
    {
        register_block_type(WP_DJOT_PLUGIN_DIR . 'assets/blocks/djot');
    }

    /**
     * Register REST API route for live preview.
     */
    public function registerRestRoute(): void
    {
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
                    'sanitize_callback' => static fn (string $value): string => wp_unslash($value),
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
}
