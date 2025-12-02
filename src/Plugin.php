<?php

declare(strict_types=1);

namespace WpDjot;

use WP_CLI;
use WpDjot\Admin\Settings;
use WpDjot\Blocks\DjotBlock;
use WpDjot\CLI\MigrateCommand;
use WpDjot\Shortcodes\DjotShortcode;

/**
 * Main plugin class.
 */
class Plugin
{
    private Converter $converter;

    private Settings $settings;

    private DjotShortcode $shortcode;

    private DjotBlock $block;

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
        $this->converter = new Converter(
            $this->options['safe_mode'],
            $this->options['post_profile'],
            $this->options['comment_profile'],
        );
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

        // Register WP-CLI commands
        $this->registerCliCommands();

        // Register Gutenberg block
        $this->block = new DjotBlock($this->converter);
        $this->block->init();

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Register WP-CLI commands.
     */
    private function registerCliCommands(): void
    {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        WP_CLI::add_command('djot', MigrateCommand::class);
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

        // Skip if content contains rendered Djot blocks (already processed by Gutenberg)
        if (str_contains($content, 'wp-djot-block-rendered')) {
            // Only process {djot}...{/djot} blocks outside of rendered blocks
            return $this->processContent($content, false);
        }

        // Process full content as Djot using configured post profile
        if ($this->options['process_full_content']) {
            return $this->converter->convertArticle($content);
        }

        // Only process {djot}...{/djot} blocks
        return $this->processContent($content, false);
    }

    /**
     * Filter comment content (uses comment profile with safe mode).
     *
     * Comment profile restrictions:
     * - No headings (prevents disrupting page structure)
     * - No images (prevents spam/inappropriate content)
     * - No tables (too complex for comments)
     * - No raw HTML (XSS prevention)
     * - Links get rel="nofollow ugc" (SEO spam prevention)
     */
    public function filterComment(string $content): string
    {
        // Process full comment as Djot with comment profile
        if ($this->options['process_full_comments']) {
            return $this->converter->convertComment($content);
        }

        // Only process {djot}...{/djot} blocks
        return $this->processContent($content, true);
    }

    /**
     * Process content with Djot converter.
     *
     * @param bool $isComment Whether this is comment content (uses comment profile)
     */
    private function processContent(string $content, bool $isComment): string
    {
        // Check for {djot}...{/djot} blocks
        $pattern = '/\{djot\}(.*?)\{\/djot\}/s';

        return (string)preg_replace_callback($pattern, function (array $matches) use ($isComment): string {
            $djotContent = $matches[1];

            if ($isComment) {
                return $this->converter->convertComment($djotContent);
            }

            return $this->converter->convertArticle($djotContent);
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
            'djot-markup-for-wp',
            WP_DJOT_PLUGIN_URL . 'assets/css/djot.css',
            [],
            WP_DJOT_VERSION,
        );

        // Code highlighting
        if ($this->options['highlight_code']) {
            $theme = $this->options['highlight_theme'];

            wp_enqueue_style(
                'wp-djot-highlight',
                WP_DJOT_PLUGIN_URL . "assets/vendor/highlight.js/styles/{$theme}.min.css",
                [],
                WP_DJOT_VERSION,
            );

            wp_enqueue_script(
                'wp-djot-highlight',
                WP_DJOT_PLUGIN_URL . 'assets/vendor/highlight.js/highlight.min.js',
                [],
                WP_DJOT_VERSION,
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
            'process_full_comments' => true,
            'safe_mode' => true,
            'post_profile' => 'article',
            'comment_profile' => 'comment',
            'highlight_code' => true,
            'highlight_theme' => 'github',
            'shortcode_tag' => 'djot',
            'filter_priority' => 6,
        ];

        $options = get_option('wp_djot_settings', []);

        return array_merge($defaults, (array)$options);
    }
}
