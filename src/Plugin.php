<?php

declare(strict_types=1);

namespace WpDjot;

use WpDjot\Admin\Settings;
use WpDjot\Shortcodes\DjotShortcode;

/**
 * Main plugin class.
 */
class Plugin
{
    private Converter $converter;

    private Settings $settings;

    private DjotShortcode $shortcode;

    /**
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * Initialize the plugin.
     */
    public function init(): void
    {
        $this->options = $this->getOptions();
        $this->converter = new Converter($this->options['safe_mode']);
        $this->shortcode = new DjotShortcode($this->converter);

        // Register shortcode
        $this->shortcode->register($this->options['shortcode_tag']);

        // Register content filters
        $this->registerFilters();

        // Admin settings
        if (is_admin()) {
            $this->settings = new Settings();
            $this->settings->init();
        }

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Register content filters based on settings.
     */
    private function registerFilters(): void
    {
        $priority = (int)$this->options['filter_priority'];

        if ($this->options['enable_posts'] || $this->options['enable_pages']) {
            add_filter('the_content', [$this, 'filterContent'], $priority);
        }

        if ($this->options['enable_comments']) {
            add_filter('comment_text', [$this, 'filterComment'], $priority);
        }
    }

    /**
     * Filter post/page content.
     */
    public function filterContent(string $content): string
    {
        if (!$this->shouldFilterContent()) {
            return $content;
        }

        // Process full content as Djot
        if ($this->options['process_full_content']) {
            return $this->converter->convert($content, false);
        }

        // Only process {djot}...{/djot} blocks
        return $this->processContent($content, false);
    }

    /**
     * Filter comment content (always uses safe mode).
     */
    public function filterComment(string $content): string
    {
        return $this->processContent($content, true);
    }

    /**
     * Process content with Djot converter.
     */
    private function processContent(string $content, bool $forceSafeMode): string
    {
        // Check for {djot}...{/djot} blocks
        $pattern = '/\{djot\}(.*?)\{\/djot\}/s';

        return (string)preg_replace_callback($pattern, function (array $matches) use ($forceSafeMode): string {
            $djotContent = $matches[1];

            if ($forceSafeMode) {
                return $this->converter->convertSafe($djotContent);
            }

            return $this->converter->convert($djotContent);
        }, $content);
    }

    /**
     * Check if current content should be filtered.
     */
    private function shouldFilterContent(): bool
    {
        // Skip admin, feeds, and REST API
        if (is_admin() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }

        // Check post type
        $postType = get_post_type();

        if ($postType === 'post' && $this->options['enable_posts']) {
            return true;
        }

        if ($postType === 'page' && $this->options['enable_pages']) {
            return true;
        }

        return false;
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueAssets(): void
    {
        // Plugin CSS
        wp_enqueue_style(
            'wp-djot',
            WP_DJOT_PLUGIN_URL . 'assets/css/djot.css',
            [],
            WP_DJOT_VERSION,
        );

        // Code highlighting
        if ($this->options['highlight_code']) {
            $theme = $this->options['highlight_theme'];

            wp_enqueue_style(
                'wp-djot-highlight',
                "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/{$theme}.min.css",
                [],
                '11.9.0',
            );

            wp_enqueue_script(
                'wp-djot-highlight',
                'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
                [],
                '11.9.0',
                true,
            );

            wp_add_inline_script(
                'wp-djot-highlight',
                'document.addEventListener("DOMContentLoaded", function() { hljs.highlightAll(); });',
            );
        }
    }

    /**
     * Get plugin options with defaults.
     *
     * @return array<string, mixed>
     */
    private function getOptions(): array
    {
        $defaults = [
            'enable_posts' => true,
            'enable_pages' => true,
            'enable_comments' => false,
            'process_full_content' => true,
            'safe_mode' => true,
            'highlight_code' => true,
            'highlight_theme' => 'github',
            'shortcode_tag' => 'djot',
            'filter_priority' => 6,
        ];

        $options = get_option('wp_djot_settings', []);

        return array_merge($defaults, (array)$options);
    }
}
