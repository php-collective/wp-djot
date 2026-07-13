<?php

declare(strict_types=1);

namespace WpDjot;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Djot\DjotConverter;
use Djot\Event\RenderEvent;
use WP_CLI;
use WP_Post;
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
     * Adds support for video embeds via oEmbed.
     */
    private function registerConverterFilters(): void
    {
        add_filter('wpdjot_converter', [$this, 'customizeConverter'], 10, 2);
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
        $converter->on('render.image', function (RenderEvent $event): void {
            $this->handleVideoEmbed($event);
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

        // Build original Djot source for round-trip preservation
        $djotSrc = '![' . $alt . '](' . $url . '){video';
        if ($width) {
            $djotSrc .= ' width=' . $width;
        }
        if ($height) {
            $djotSrc .= ' height=' . $height;
        }
        $djotSrc .= '}';
        $escapedDjotSrc = htmlspecialchars($djotSrc, ENT_QUOTES, 'UTF-8');

        // Wrap in figure if there's a caption (alt text)
        if ($alt) {
            $embedHtml = '<figure class="wpdjot-embed" data-djot-src="' . $escapedDjotSrc . '">'
                . $embedHtml
                . '<figcaption>' . esc_html($alt) . '</figcaption>'
                . '</figure>';
        } else {
            $embedHtml = '<div class="wpdjot-embed" data-djot-src="' . $escapedDjotSrc . '">' . $embedHtml . '</div>';
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
        if (str_contains($content, 'wpdjot-block-rendered')) {
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
            $html = $this->converter->convertExcerpt($excerpt);

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

        // Convert Djot to HTML (without TOC/permalinks)
        $html = $this->converter->convertExcerpt($content);

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
        // Check for wpdjot/djot Gutenberg blocks (supports both old wp-djot/djot and new wpdjot/djot)
        if (preg_match_all('/<!-- wp:(?:wp-djot|wpdjot)\/djot \{["\']content["\']:["\'](.+?)["\']\} \/-->/s', $content, $matches)) {
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
        if (preg_match_all('/<!-- wp:(?:wp-djot|wpdjot)\/djot (\{.+?\}) \/-->/s', $content, $matches)) {
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
     * Whether the current request should enqueue Mermaid.
     * Default is a main-query content sniff (posts are already in memory) that
     * errs on loading; a prose mention costs one extra script load. The
     * wpdjot_load_mermaid filter can override this for other sources.
     */
    private function pageNeedsMermaid(): bool
    {
        global $wp_query;

        $needed = false;
        // Post content only renders on singular views (shouldFilterContent
        // skips archives/feeds). Djot emitted through the shortcode or the
        // wpdjot_to_html() template tag (widgets, page builders, custom
        // templates) is outside the main query - sites doing that with
        // diagrams force-load via the wpdjot_load_mermaid filter below.
        foreach (is_singular() ? (array)($wp_query->posts ?? []) : [] as $post) {
            // Custom main queries can return ids (fields => 'ids'); resolve
            // through the post cache.
            $post = $post instanceof WP_Post ? $post : get_post($post);
            if ($post instanceof WP_Post && str_contains($post->post_content, 'mermaid')) {
                $needed = true;

                break;
            }
        }

        /**
         * Filter whether the Mermaid library is enqueued for this request.
         * Return true to force-load it (e.g. for mermaid rendered outside the
         * main query - widgets, page builders).
         *
         * @param bool $needed Result of the main-query content sniff.
         */
        return (bool)apply_filters('wpdjot_load_mermaid', $needed);
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueAssets(): void
    {
        // Plugin CSS
        wp_enqueue_style(
            'djot-markup',
            WPDJOT_PLUGIN_URL . 'assets/css/djot.css',
            [],
            WPDJOT_VERSION,
        );

        // Code block enhancements (copy button)
        // Note: Syntax highlighting is handled server-side by Torchlight
        wp_enqueue_script(
            'wpdjot-code-blocks',
            WPDJOT_PLUGIN_URL . 'assets/js/code-blocks.js',
            [],
            WPDJOT_VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );

        // Heading permalink copy-to-clipboard
        if ($this->options['permalinks_enabled']) {
            $copyJs = 'document.addEventListener("click",function(e){'
                . 'var a=e.target.closest(".wpdjot-permalink");'
                . 'if(!a)return;'
                . 'e.preventDefault();'
                . 'var hash=a.getAttribute("href");'
                . 'history.replaceState(null,null,hash);'
                . 'navigator.clipboard.writeText(location.href);'
                . '});';
            wp_register_script('wpdjot-permalink', false, [], (string)WPDJOT_VERSION, ['in_footer' => true]);
            wp_enqueue_script('wpdjot-permalink');
            wp_add_inline_script('wpdjot-permalink', $copyJs);
        }

        // Mermaid.js for diagram rendering: ~2MB even minified, so load it
        // only when something in the main query can actually use it (its raw
        // source mentions mermaid). Rendering happens after
        // wp_enqueue_scripts, but the queried posts' source is already
        // available here - same content-sniff approach as wp-carve.
        if ($this->options['mermaid_enabled'] && $this->pageNeedsMermaid()) {
            wp_enqueue_script(
                'mermaid',
                WPDJOT_PLUGIN_URL . 'assets/js/vendor/mermaid.min.js',
                [],
                '11.15.0',
                ['in_footer' => true, 'strategy' => 'defer'],
            );

            // Scheme-aware init: pick the mermaid theme from the effective
            // color scheme (a site toggle via html[data-theme] wins over the
            // OS preference) and re-render on scheme changes - a fixed
            // "default" theme rendered unreadable diagrams on dark pages.
            $mermaidInit = 'document.addEventListener("DOMContentLoaded",function(){'
                . 'if(typeof mermaid==="undefined"){return;}'
                . 'function stash(){document.querySelectorAll("pre.mermaid").forEach(function(n){if(n.dataset.djotSource===undefined){n.dataset.djotSource=n.textContent;}if(n.getAttribute("data-processed")==="djot-defer"){n.removeAttribute("data-processed");}});}'
                . 'function dark(){var t=document.documentElement.dataset.theme;if(t==="dark"){return true;}if(t==="light"){return false;}return !!(window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches);}'
                . 'function render(){mermaid.initialize({startOnLoad:false,theme:dark()?"dark":"default"});mermaid.run({nodes:document.querySelectorAll("pre.mermaid")});}'
                . 'function rerender(){var ns=document.querySelectorAll("pre.mermaid");if(!ns.length){return;}ns.forEach(function(n){if(n.dataset.djotSource===undefined){return;}n.removeAttribute("data-processed");n.innerHTML="";n.textContent=n.dataset.djotSource;});render();}'
                . 'function later(){setTimeout(rerender,300);}'
                . 'stash();render();'
                . 'document.addEventListener("wpdjot:scheme-change",later);'
                . 'document.addEventListener("wpcarve:scheme-change",later);'
                . 'var q=window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)");'
                . 'if(q){if(q.addEventListener){q.addEventListener("change",later);}else if(q.addListener){q.addListener(later);}}'
                . '});';
            // Park diagrams before the vendor script loads: mermaid auto-runs
            // on DOMContentLoaded and would race the source stash (a later
            // scheme-change re-render then feeds rendered SVG back to mermaid
            // as "source" - the visible "Syntax error in text" bomb).
            $mermaidPark = 'document.querySelectorAll("pre.mermaid").forEach(function(n){'
                . 'if(n.dataset.djotSource===undefined){'
                . 'n.dataset.djotSource=n.textContent;'
                . 'n.setAttribute("data-processed","djot-defer");'
                . '}});';
            wp_add_inline_script('mermaid', $mermaidPark, 'before');
            wp_add_inline_script('mermaid', $mermaidInit);
        }

        // Comment toolbar (when comments are enabled in settings)
        if ($this->options['enable_comments']) {
            wp_enqueue_script(
                'wpdjot-comment-toolbar',
                WPDJOT_PLUGIN_URL . 'assets/js/comment-toolbar.js',
                [],
                WPDJOT_VERSION,
                ['in_footer' => true, 'strategy' => 'defer'],
            );

            // Pass REST API settings to JavaScript for preview
            wp_localize_script('wpdjot-comment-toolbar', 'wpDjotSettings', [
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
            'shortcode_tag' => 'djot',
            'heading_shift' => 0,
            'mermaid_enabled' => false,
            'toc_enabled' => false,
            'toc_position' => 'top',
            'toc_min_level' => 2,
            'toc_max_level' => 4,
            'toc_list_type' => 'ul',
            'permalinks_enabled' => false,
            'smart_quotes_locale' => 'auto',
            'visual_editor_mode' => 'disabled',
        ];

        $options = get_option('wpdjot_settings', []);

        return array_merge($defaults, (array)$options);
    }
}
