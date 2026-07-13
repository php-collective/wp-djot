<?php

declare(strict_types=1);

namespace WpDjot\Blocks;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Djot\Converter\HtmlToDjot;
use Djot\Converter\MarkdownToDjot;
use WP_Post;
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
        add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorAssets']);

        // @deprecated 1.4.2 Will be removed in 2.0.0. Legacy block name migration will be removed.
        // Migrate old block name to new when content is loaded in editor.
        add_filter('the_editor_content', [$this, 'migrateBlockName']);
        add_filter('content_edit_pre', [$this, 'migrateBlockName']);
        // Also filter REST API responses for Gutenberg editor.
        add_filter('rest_prepare_post', [$this, 'migrateBlockNameInRest'], 10, 2);
        add_filter('rest_prepare_page', [$this, 'migrateBlockNameInRest'], 10, 2);
    }

    /**
     * Migrate old wp-djot/djot block names to new wpdjot/djot format.
     *
     * This runs when content is loaded in the editor, transparently
     * updating the block name so existing content can be edited.
     *
     * @deprecated 1.4.2 Will be removed in 2.0.0. Legacy block name support will be removed.
     */
    public function migrateBlockName(string $content): string
    {
        // Replace old block name with new name in block comments
        return str_replace('<!-- wp:wp-djot/djot', '<!-- wp:wpdjot/djot', $content);
    }

    /**
     * Migrate old block name in REST API responses for Gutenberg editor.
     *
     * @deprecated 1.4.2 Will be removed in 2.0.0. Legacy block name support will be removed.
     *
     * @param \WP_REST_Response $response
     * @param \WP_Post $post
     *
     * @return \WP_REST_Response
     */
    public function migrateBlockNameInRest(WP_REST_Response $response, WP_Post $post): WP_REST_Response
    {
        $data = $response->get_data();

        if (isset($data['content']['raw'])) {
            $data['content']['raw'] = $this->migrateBlockName($data['content']['raw']);
            $response->set_data($data);
        }

        return $response;
    }

    /**
     * Register the Gutenberg block.
     */
    public function register(): void
    {
        // Register the block - this auto-registers wpdjot-djot-editor-style from block.json
        register_block_type(WPDJOT_PLUGIN_DIR . 'assets/blocks/djot');

        // Localize script with assets URL and settings for dynamic module loading (visual editor)
        $options = get_option('wpdjot_settings', []);
        wp_localize_script(
            'wpdjot-djot-editor-script',
            'wpdjotBlockData',
            [
                'assetsUrl' => WPDJOT_PLUGIN_URL . 'assets/',
                'visualEditorMode' => $options['visual_editor_mode'] ?? 'disabled',
            ],
        );

        // @deprecated 1.4.2 Will be removed in 2.0.0. Legacy block name support will be removed.
        // Register old block name as alias for backward compatibility with existing content.
        // Must include attributes so WordPress can parse the saved content in the editor.
        register_block_type('wp-djot/djot', [
            'attributes' => [
                'content' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
            'render_callback' => function (array $attributes): string {
                return $this->renderLegacyBlock($attributes);
            },
        ]);
    }

    /**
     * Enqueue editor assets.
     */
    public function enqueueEditorAssets(): void
    {
        // Frontend CSS for visual editor (tabs, code-groups, etc.)
        wp_enqueue_style(
            'djot-markup',
            WPDJOT_PLUGIN_URL . 'assets/css/djot.css',
            [],
            WPDJOT_VERSION,
        );

        // Torchlight annotations in block inspector
        wp_enqueue_script(
            'wpdjot-editor-torchlight',
            WPDJOT_PLUGIN_URL . 'assets/js/editor-torchlight.js',
            ['wp-compose', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-hooks', 'wp-data'],
            WPDJOT_VERSION,
            true,
        );

        // Mermaid.js for diagram rendering in editor preview
        $options = get_option('wpdjot_settings', []);
        if (!empty($options['mermaid_enabled'])) {
            wp_enqueue_script(
                'mermaid',
                WPDJOT_PLUGIN_URL . 'assets/js/vendor/mermaid.min.js',
                [],
                '11.15.0',
                true,
            );

            // Initialize mermaid - the block JS will call mermaid.run() after preview updates
            $mermaidInit = 'if(typeof mermaid!=="undefined"){'
                . 'mermaid.initialize({startOnLoad:false,theme:"default"});'
                . '}';
            wp_add_inline_script('mermaid', $mermaidInit);
        }
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
                'context' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'preview',
                    'enum' => ['preview', 'editor'],
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
     * @deprecated 1.4.2 Will be removed in 2.0.0. Legacy block name support will be removed.
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
     *
     * @param \WP_REST_Request $request Request with 'content' and optional 'context' params.
     *        context='editor' returns clean HTML without TOC/permalinks for visual editor.
     */
    public function renderPreview(WP_REST_Request $request): WP_REST_Response
    {
        $content = $request->get_param('content');

        if (!$content) {
            return new WP_REST_Response(['html' => ''], 200);
        }

        $context = $request->get_param('context') ?? 'preview';

        // Use convertExcerpt for visual editor (no TOC or permalinks)
        if ($context === 'editor') {
            $html = $this->converter->convertExcerpt($content);
        } else {
            $html = $this->converter->convertArticle($content);
        }

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
        // The preview mirrors what a published comment would render, so it is
        // only meaningful when Djot comment rendering is on. When it is off
        // there is nothing to preview and no reason to expose an
        // unauthenticated renderer at all. enable_comments defaults to true, so
        // only an explicit false disables it.
        $options = (array)get_option('wpdjot_settings', []);
        if (array_key_exists('enable_comments', $options) && empty($options['enable_comments'])) {
            return new WP_REST_Response(
                ['message' => __('Comment rendering is disabled.', 'djot-markup')],
                403,
            );
        }

        // Throttle anonymous callers: the endpoint runs the full Djot pipeline
        // without authentication, so an unbounded public renderer is a cheap
        // CPU-amplification vector. Trusted editors (block-editor preview) are
        // exempt.
        if (self::isRateLimited()) {
            return new WP_REST_Response(
                ['message' => __('Too many preview requests - please slow down.', 'djot-markup')],
                429,
            );
        }

        $content = (string)$request->get_param('content');

        if ($content === '') {
            return new WP_REST_Response(['html' => ''], 200);
        }

        // Same ballpark as WordPress' own comment length limit, but small
        // enough that anonymous preview calls stay cheap. Truncate on a UTF-8
        // boundary so a multibyte sequence is not split into invalid bytes.
        if (strlen($content) > 20000) {
            $content = function_exists('mb_strcut')
                ? mb_strcut($content, 0, 20000, 'UTF-8')
                : substr($content, 0, 20000);
        }

        $html = $this->converter->convertComment($content);

        // Remove the wrapper div for preview
        $html = preg_replace('/^<div class="djot-content">(.*)<\/div>$/s', '$1', $html) ?? $html;

        return new WP_REST_Response(['html' => $html], 200);
    }

    /**
     * Per-IP fixed-window throttle for the public comment preview. Returns true
     * when the caller has exceeded the allowance and the request should be
     * rejected with 429.
     *
     * Users who can edit posts are trusted (they drive the block editor's own
     * preview) and are never throttled. Both the request allowance and the
     * window are filterable, so a site behind a shared-IP CDN or reverse proxy
     * can widen them - or disable the limit with a non-positive allowance.
     */
    private static function isRateLimited(): bool
    {
        if (current_user_can('edit_posts')) {
            return false;
        }

        // Requests allowed per window; a non-positive value disables the limit.
        $max = (int)apply_filters('wpdjot_preview_rate_limit', 30);
        if ($max <= 0) {
            return false;
        }
        // Window length in seconds.
        $window = max(1, (int)apply_filters('wpdjot_preview_rate_window', MINUTE_IN_SECONDS));

        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field((string)wp_unslash($_SERVER['REMOTE_ADDR']))
            : 'unknown';
        $key = 'wpdjot_pcw_' . md5($ip);
        $now = time();
        $data = get_transient($key);

        // Start a fresh fixed window when none is active or the current one has
        // lapsed. The window's expiry is anchored to its first request: counting
        // a later request never extends it, so a client below the allowance is
        // never blocked (a true fixed window, not a sliding one).
        if (!is_array($data) || !isset($data['count'], $data['reset']) || $now >= (int)$data['reset']) {
            set_transient($key, ['count' => 1, 'reset' => $now + $window], $window);

            return false;
        }
        if ((int)$data['count'] >= $max) {
            return true;
        }
        set_transient(
            $key,
            ['count' => (int)$data['count'] + 1, 'reset' => (int)$data['reset']],
            max(1, (int)$data['reset'] - $now),
        );

        return false;
    }

    /**
     * Convert Markdown to Djot.
     */
    public function convertMarkdown(WP_REST_Request $request): WP_REST_Response
    {
        $content = (string)$request->get_param('content');

        if ($content === '') {
            return new WP_REST_Response(['djot' => ''], 200);
        }

        // Bound the work: converting an arbitrarily large paste is a needless
        // memory/CPU cost even for an authenticated editor. 512 KB is far above
        // any realistic paste, so reject rather than silently truncate (which
        // would corrupt the converted source mid-document).
        if (strlen($content) > 512000) {
            return new WP_REST_Response(
                ['message' => __('The pasted content is too large to convert.', 'djot-markup')],
                413,
            );
        }

        $converter = new MarkdownToDjot();
        $djot = $converter->convert($content);

        return new WP_REST_Response(['djot' => $djot], 200);
    }

    /**
     * Convert HTML to Djot.
     */
    public function convertHtml(WP_REST_Request $request): WP_REST_Response
    {
        $content = (string)$request->get_param('content');

        if ($content === '') {
            return new WP_REST_Response(['djot' => ''], 200);
        }

        // Bound the work: converting an arbitrarily large paste is a needless
        // memory/CPU cost even for an authenticated editor. 512 KB is far above
        // any realistic paste, so reject rather than silently truncate (which
        // would corrupt the converted source mid-document).
        if (strlen($content) > 512000) {
            return new WP_REST_Response(
                ['message' => __('The pasted content is too large to convert.', 'djot-markup')],
                413,
            );
        }

        $converter = new HtmlToDjot();
        $djot = $converter->convert($content);

        return new WP_REST_Response(['djot' => $djot], 200);
    }
}
