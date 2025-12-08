<?php

declare(strict_types=1);

namespace WpDjot;

use Djot\DjotConverter;
use Djot\Event\RenderEvent;
use Djot\Node\Inline\Text;
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
        $this->converter = Converter::fromSettings();
        $this->shortcode = new DjotShortcode($this->converter);

        // Register shortcode
        $this->shortcode->register($this->options['shortcode_tag']);

        // Register content filters
        $this->registerFilters();

        // Register converter customizations
        $this->registerConverterFilters();

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
     * Register converter customizations via WordPress filters.
     *
     * Adds support for special attribute handling like abbreviations.
     */
    private function registerConverterFilters(): void
    {
        add_filter('wp_djot_converter', [$this, 'customizeConverter'], 10, 2);
    }

    /**
     * Customize the Djot converter with additional rendering rules.
     *
     * @param \Djot\DjotConverter $converter
     * @param string $context
     */
    public function customizeConverter(DjotConverter $converter, string $context): DjotConverter
    {
        // Video embed support - convert image syntax with video URLs to oEmbed
        $converter->getRenderer()->on('render.image', function (RenderEvent $event): void {
            $this->handleVideoEmbed($event);
        });

        $converter->getRenderer()->on('render.span', function (RenderEvent $event): void {
            /** @var \Djot\Node\Inline\Span $node */
            $node = $event->getNode();

            // Get semantic attributes
            $abbr = $node->getAttribute('abbr');
            $kbd = $node->getAttribute('kbd');
            $dfn = $node->getAttribute('dfn');

            // Track which attributes to exclude from passthrough
            $excludeAttrs = [];

            // Render children first
            $children = '';
            foreach ($node->getChildren() as $child) {
                if ($child instanceof Text) {
                    $children .= htmlspecialchars($child->getContent(), ENT_NOQUOTES, 'UTF-8');
                }
            }

            $content = $children;

            // Build inner element (abbr or kbd) - these are mutually exclusive
            if ($abbr !== null) {
                $abbrTitle = ' title="' . htmlspecialchars((string)$abbr, ENT_QUOTES, 'UTF-8') . '"';
                $content = '<abbr' . $abbrTitle . '>' . $children . '</abbr>';
                $excludeAttrs[] = 'abbr';
            } elseif ($kbd !== null) {
                $content = '<kbd>' . $children . '</kbd>';
                $excludeAttrs[] = 'kbd';
            }

            // Wrap in dfn if present (can combine with abbr/kbd)
            if ($dfn !== null) {
                $dfnAttr = '';
                if ($dfn !== '' && $dfn !== true) {
                    $dfnAttr = ' title="' . htmlspecialchars((string)$dfn, ENT_QUOTES, 'UTF-8') . '"';
                }
                $content = '<dfn' . $dfnAttr . '>' . $content . '</dfn>';
                $excludeAttrs[] = 'dfn';
            }

            // If no semantic attributes found, use default rendering
            if (!$excludeAttrs) {
                return;
            }

            // Add remaining attributes (class, id, etc.) to outermost element if any
            $remainingAttrs = [];
            foreach ($node->getAttributes() as $key => $value) {
                if (in_array($key, $excludeAttrs, true)) {
                    continue;
                }
                $remainingAttrs[$key] = $value;
            }

            // If there are extra attributes, wrap in span
            if ($remainingAttrs) {
                $attrStr = '';
                foreach ($remainingAttrs as $key => $value) {
                    $attrStr .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
                        . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
                }
                $content = '<span' . $attrStr . '>' . $content . '</span>';
            }

            $event->setHtml($content);
            $event->preventDefault();
        });

        return $converter;
    }

    /**
     * Handle video embeds using WordPress oEmbed.
     *
     * Converts image syntax with video attribute to embedded players:
     * ![Optional caption](https://www.youtube.com/watch?v=VIDEO_ID){video}
     * ![Optional caption](https://www.youtube.com/watch?v=VIDEO_ID){video width=650 height=400}
     */
    public function handleVideoEmbed(RenderEvent $event): void
    {
        /** @var \Djot\Node\Inline\Image $node */
        $node = $event->getNode();

        // Only process images with video attribute
        if ($node->getAttribute('video') === null) {
            return;
        }

        $url = $node->getSource();

        // Get width/height from attributes
        $width = $node->getAttribute('width');
        $height = $node->getAttribute('height');

        // Build oEmbed args
        $args = [];
        if ($width) {
            $args['width'] = (int)$width;
        }
        if ($height) {
            $args['height'] = (int)$height;
        }

        // Try to get oEmbed HTML from WordPress
        $embedHtml = wp_oembed_get($url, $args);
        if (!$embedHtml) {
            return;
        }

        // Get alt text for optional caption
        $alt = $node->getAlt();

        // Wrap in figure if there's a caption (alt text)
        if ($alt) {
            $embedHtml = '<figure class="wp-djot-embed">'
                . $embedHtml
                . '<figcaption>' . esc_html($alt) . '</figcaption>'
                . '</figure>';
        } else {
            $embedHtml = '<div class="wp-djot-embed">' . $embedHtml . '</div>';
        }

        $event->setHtml($embedHtml);
        $event->preventDefault();
    }

    /**
     * Register content filters based on settings.
     *
     * We run at priority 5 (before wptexturize/wpautop at 10) to get clean content.
     * Our HTML output uses <pre><code> which these filters respect and skip.
     */
    private function registerFilters(): void
    {
        if ($this->options['enable_posts'] || $this->options['enable_pages']) {
            add_filter('the_content', [$this, 'filterContent'], 5);
            add_filter('get_the_excerpt', [$this, 'filterExcerpt'], 5);
        }

        if ($this->options['enable_comments']) {
            add_filter('comment_text', [$this, 'filterComment'], 5);
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

        // Skip if content contains Gutenberg blocks - let do_blocks() handle them at priority 9
        if (str_contains($content, '<!-- wp:')) {
            return $content;
        }

        // Process full content as Djot using configured post profile
        if ($this->options['process_full_content']) {
            return $this->converter->convertArticle($content);
        }

        // Only process {djot}...{/djot} blocks
        return $this->processContent($content, false);
    }

    /**
     * Filter excerpt for archive pages.
     *
     * Renders Djot content and extracts plain text for excerpts.
     */
    public function filterExcerpt(string $excerpt): string
    {
        // If there's already a manual excerpt, use it
        if ($excerpt) {
            // Still process it as Djot in case it contains markup
            $html = $this->converter->convertArticle($excerpt);

            // Strip tags for clean excerpt
            return wp_strip_all_tags($html);
        }

        // Get the post content and generate excerpt from it
        $post = get_post();
        if (!$post) {
            return $excerpt;
        }

        $content = $post->post_content;

        // Extract Djot content from Gutenberg blocks if present
        $content = $this->extractDjotContent($content);

        // Handle <!-- more --> tag - get content before it
        if (str_contains($content, '<!--more-->')) {
            $content = explode('<!--more-->', $content)[0];
        }

        // Convert Djot to HTML
        $html = $this->converter->convertArticle($content);

        // Strip HTML tags to get plain text
        $text = wp_strip_all_tags($html);

        // Trim to excerpt length (default 55 words)
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP filter
        $excerptLength = (int)apply_filters('excerpt_length', 55);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP filter
        $excerptMore = apply_filters('excerpt_more', ' [&hellip;]');

        $words = explode(' ', $text, $excerptLength + 1);
        if (count($words) > $excerptLength) {
            array_pop($words);
            $text = implode(' ', $words) . $excerptMore;
        }

        return $text;
    }

    /**
     * Extract Djot content from Gutenberg blocks or raw content.
     */
    private function extractDjotContent(string $content): string
    {
        // Check for wp-djot/djot Gutenberg blocks
        if (preg_match_all('/<!-- wp:wp-djot\/djot \{["\']content["\']:["\'](.+?)["\']\} \/-->/s', $content, $matches)) {
            $djotParts = [];
            foreach ($matches[1] as $match) {
                // Decode JSON-encoded content
                $decoded = json_decode('"' . $match . '"');
                if ($decoded !== null) {
                    $djotParts[] = $decoded;
                }
            }
            if ($djotParts) {
                return implode("\n\n", $djotParts);
            }
        }

        // Check for JSON attribute format (content may have complex escaping)
        if (preg_match_all('/<!-- wp:wp-djot\/djot (\{.+?\}) \/-->/s', $content, $matches)) {
            $djotParts = [];
            foreach ($matches[1] as $jsonStr) {
                $data = json_decode($jsonStr, true);
                if ($data && isset($data['content'])) {
                    $djotParts[] = $data['content'];
                }
            }
            if ($djotParts) {
                return implode("\n\n", $djotParts);
            }
        }

        // Return original content if no blocks found
        return $content;
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
     * @param string $content
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

        // Skip archive pages (home, category, tag, search, author, date archives)
        // These show excerpts/teasers where Djot processing would break the "Read more" link
        if (!is_singular()) {
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
                ['in_footer' => true, 'strategy' => 'defer'],
            );

            wp_add_inline_script(
                'wp-djot-highlight',
                'document.addEventListener("DOMContentLoaded", function() { hljs.highlightAll(); });',
            );
        }

        // Comment toolbar (when comments are enabled in settings)
        if ($this->options['enable_comments']) {
            wp_enqueue_script(
                'wp-djot-comment-toolbar',
                WP_DJOT_PLUGIN_URL . 'assets/js/comment-toolbar.js',
                [],
                WP_DJOT_VERSION,
                ['in_footer' => true, 'strategy' => 'defer'],
            );

            // Pass REST API settings to JavaScript for preview
            wp_localize_script('wp-djot-comment-toolbar', 'wpDjotSettings', [
                'restUrl' => rest_url(),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);
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
            'enable_comments' => true,
            'process_full_content' => true,
            'process_full_comments' => true,
            'safe_mode' => true,
            'post_profile' => 'article',
            'comment_profile' => 'comment',
            'markdown_mode' => false,
            'post_soft_break' => 'newline',
            'comment_soft_break' => 'newline',
            'highlight_code' => true,
            'highlight_theme' => 'github',
            'shortcode_tag' => 'djot',
        ];

        $options = get_option('wp_djot_settings', []);

        return array_merge($defaults, (array)$options);
    }
}
