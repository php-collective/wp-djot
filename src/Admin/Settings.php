<?php

declare(strict_types=1);

namespace WpDjot\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Djot\Extension\SmartQuotesExtension;

/**
 * Admin settings page for WP Djot.
 */
class Settings
{
    /**
     * @var string
     */
    private const OPTION_GROUP = 'wpdjot_settings';

    /**
     * @var string
     */
    private const PAGE_SLUG = 'wpdjot-settings';

    /**
     * Initialize admin hooks.
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_filter('plugin_action_links_' . WPDJOT_PLUGIN_BASENAME, [$this, 'addSettingsLink']);
    }

    /**
     * Add settings page to admin menu.
     */
    public function addMenuPage(): void
    {
        add_options_page(
            __('Djot Markup Settings', 'djot-markup'),
            __('Djot Markup', 'djot-markup'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderSettingsPage'],
        );
    }

    /**
     * Add settings link to plugin list.
     *
     * @param array<string> $links
     *
     * @return array<string>
     */
    public function addSettingsLink(array $links): array
    {
        $settingsLink = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=' . self::PAGE_SLUG),
            __('Settings', 'djot-markup'),
        );

        array_unshift($links, $settingsLink);

        return $links;
    }

    /**
     * Register settings and fields.
     */
    public function registerSettings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_GROUP,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeSettings'],
            ],
        );

        // Content Settings Section
        add_settings_section(
            'wpdjot_content',
            __('Content Settings', 'djot-markup'),
            [$this, 'renderContentSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'enable_posts',
            __('Enable for Posts', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_content',
            ['field' => 'enable_posts', 'description' => __('Process Djot markup in blog posts.', 'djot-markup')],
        );

        add_settings_field(
            'enable_pages',
            __('Enable for Pages', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_content',
            ['field' => 'enable_pages', 'description' => __('Process Djot markup in pages.', 'djot-markup')],
        );

        add_settings_field(
            'enable_comments',
            __('Enable for Comments', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_content',
            ['field' => 'enable_comments', 'description' => __('Process Djot markup in comments (always uses safe mode).', 'djot-markup')],
        );

        add_settings_field(
            'process_full_content',
            __('Process Full Content', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_content',
            ['field' => 'process_full_content', 'description' => __('Process entire post/page content as Djot. When disabled, only {djot}...{/djot} blocks are processed.', 'djot-markup')],
        );

        add_settings_field(
            'process_full_comments',
            __('Process Full Comments', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_content',
            ['field' => 'process_full_comments', 'description' => __('Process entire comment content as Djot. When disabled, only {djot}...{/djot} blocks are processed.', 'djot-markup')],
        );

        // Security Settings Section
        add_settings_section(
            'wpdjot_security',
            __('Security Settings', 'djot-markup'),
            [$this, 'renderSecuritySectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'safe_mode',
            __('Safe Mode', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_security',
            ['field' => 'safe_mode', 'description' => __('Block dangerous URL schemes and strip event handlers. Recommended for untrusted content.', 'djot-markup')],
        );

        add_settings_field(
            'post_profile',
            __('Posts/Pages Profile', 'djot-markup'),
            [$this, 'renderProfileSelect'],
            self::PAGE_SLUG,
            'wpdjot_security',
            ['field' => 'post_profile', 'description' => __('Feature restrictions for posts and pages.', 'djot-markup')],
        );

        add_settings_field(
            'comment_profile',
            __('Comments Profile', 'djot-markup'),
            [$this, 'renderProfileSelect'],
            self::PAGE_SLUG,
            'wpdjot_security',
            ['field' => 'comment_profile', 'description' => __('Feature restrictions for user comments.', 'djot-markup')],
        );

        // Rendering Settings Section
        add_settings_section(
            'wpdjot_rendering',
            __('Rendering Settings', 'djot-markup'),
            [$this, 'renderRenderingSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'markdown_mode',
            __('Markdown Compatibility', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_rendering',
            ['field' => 'markdown_mode', 'description' => __('Enable Markdown-like behavior: single line breaks become visible, and blocks can interrupt paragraphs without blank lines.', 'djot-markup') . '<br>' . __('Recommended for users migrating from Markdown without having migrated their texts yet.', 'djot-markup') . '<br><strong>' . __('Warning: This deviates from the Djot specification.', 'djot-markup') . '</strong>'],
        );

        add_settings_field(
            'post_soft_break',
            __('Posts/Pages Line Breaks', 'djot-markup'),
            [$this, 'renderSoftBreakSelect'],
            self::PAGE_SLUG,
            'wpdjot_rendering',
            ['field' => 'post_soft_break', 'description' => __('How single line breaks are rendered in posts and pages.', 'djot-markup')],
        );

        add_settings_field(
            'comment_soft_break',
            __('Comment Line Breaks', 'djot-markup'),
            [$this, 'renderSoftBreakSelect'],
            self::PAGE_SLUG,
            'wpdjot_rendering',
            ['field' => 'comment_soft_break', 'description' => __('How single line breaks are rendered in comments.', 'djot-markup')],
        );

        add_settings_field(
            'smart_quotes_locale',
            __('Smart Quotes', 'djot-markup'),
            [$this, 'renderSmartQuotesLocaleSelect'],
            self::PAGE_SLUG,
            'wpdjot_rendering',
            ['field' => 'smart_quotes_locale', 'description' => __('Choose which typographic quote characters to use. Default English uses curly quotes.', 'djot-markup')],
        );

        // Advanced Settings Section
        add_settings_section(
            'wpdjot_advanced',
            __('Advanced Settings', 'djot-markup'),
            [$this, 'renderAdvancedSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'shortcode_tag',
            __('Shortcode Tag', 'djot-markup'),
            [$this, 'renderTextField'],
            self::PAGE_SLUG,
            'wpdjot_advanced',
            ['field' => 'shortcode_tag', 'description' => __('The shortcode tag to use (default: djot).', 'djot-markup')],
        );

        add_settings_field(
            'heading_shift',
            __('Heading Level Shift', 'djot-markup'),
            [$this, 'renderHeadingShiftSelect'],
            self::PAGE_SLUG,
            'wpdjot_advanced',
            ['field' => 'heading_shift', 'description' => __('Shift heading levels down. Useful when h1 is reserved for page title.', 'djot-markup')],
        );

        add_settings_field(
            'mermaid_enabled',
            __('Mermaid Diagrams', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_advanced',
            ['field' => 'mermaid_enabled', 'description' => __('Enable Mermaid.js diagram rendering from code blocks.', 'djot-markup') . '<br><code>``` mermaid</code> ' . __('blocks will be rendered as diagrams.', 'djot-markup')],
        );

        // Table of Contents Section
        add_settings_section(
            'wpdjot_toc',
            __('Table of Contents', 'djot-markup'),
            [$this, 'renderTocSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'toc_enabled',
            __('Enable TOC', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_toc',
            ['field' => 'toc_enabled', 'description' => __('Automatically generate a table of contents from headings in posts and pages.', 'djot-markup')],
        );

        add_settings_field(
            'toc_position',
            __('TOC Position', 'djot-markup'),
            [$this, 'renderTocPositionSelect'],
            self::PAGE_SLUG,
            'wpdjot_toc',
            ['field' => 'toc_position', 'description' => __('Where to insert the table of contents.', 'djot-markup')],
        );

        add_settings_field(
            'toc_min_level',
            __('Minimum Heading Level', 'djot-markup'),
            [$this, 'renderHeadingLevelSelect'],
            self::PAGE_SLUG,
            'wpdjot_toc',
            ['field' => 'toc_min_level', 'description' => __('Include headings starting from this level (default: 2 to skip page title).', 'djot-markup')],
        );

        add_settings_field(
            'toc_max_level',
            __('Maximum Heading Level', 'djot-markup'),
            [$this, 'renderHeadingLevelSelect'],
            self::PAGE_SLUG,
            'wpdjot_toc',
            ['field' => 'toc_max_level', 'description' => __('Include headings up to this level.', 'djot-markup')],
        );

        add_settings_field(
            'toc_list_type',
            __('TOC List Type', 'djot-markup'),
            [$this, 'renderTocListTypeSelect'],
            self::PAGE_SLUG,
            'wpdjot_toc',
            ['field' => 'toc_list_type', 'description' => __('Use ordered (numbered) or unordered (bulleted) list.', 'djot-markup')],
        );

        add_settings_field(
            'permalinks_enabled',
            __('Heading Permalinks', 'djot-markup'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wpdjot_toc',
            ['field' => 'permalinks_enabled', 'description' => __('Add clickable # symbols to headings. Shown on hover, clicking copies the heading URL to clipboard.', 'djot-markup')],
        );

        // Experimental Settings Section
        add_settings_section(
            'wpdjot_experimental',
            __('Experimental', 'djot-markup'),
            [$this, 'renderExperimentalSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'visual_editor_mode',
            __('Visual Editor', 'djot-markup'),
            [$this, 'renderVisualEditorModeSelect'],
            self::PAGE_SLUG,
            'wpdjot_experimental',
            ['field' => 'visual_editor_mode', 'description' => __('Enable the WYSIWYG visual editor for Djot blocks.', 'djot-markup') . '<br><em>' . __('Note: Some edge cases may not round-trip perfectly. Verify in Write mode for critical content.', 'djot-markup') . '</em>'],
        );
    }

    /**
     * Sanitize settings before saving.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function sanitizeSettings(array $input): array
    {
        // Posts/pages can use 'none' (no profile restrictions) or any profile
        $validPostProfiles = ['none', 'full', 'article', 'comment', 'minimal'];
        // Comments can never use 'none' or 'full' profile for security reasons
        $validCommentProfiles = ['article', 'comment', 'minimal'];

        return [
            'enable_posts' => !empty($input['enable_posts']),
            'enable_pages' => !empty($input['enable_pages']),
            'enable_comments' => !empty($input['enable_comments']),
            'process_full_content' => !empty($input['process_full_content']),
            'process_full_comments' => !empty($input['process_full_comments']),
            'safe_mode' => !empty($input['safe_mode']),
            'post_profile' => in_array($input['post_profile'] ?? '', $validPostProfiles, true)
                ? $input['post_profile']
                : 'article',
            'comment_profile' => in_array($input['comment_profile'] ?? '', $validCommentProfiles, true)
                ? $input['comment_profile']
                : 'comment',
            'shortcode_tag' => sanitize_key($input['shortcode_tag'] ?? 'djot'),
            'heading_shift' => in_array((int) ($input['heading_shift'] ?? 0), [0, 1, 2], true)
                ? (int) $input['heading_shift']
                : 0,
            'mermaid_enabled' => !empty($input['mermaid_enabled']),
            'markdown_mode' => !empty($input['markdown_mode']),
            'post_soft_break' => in_array($input['post_soft_break'] ?? '', ['newline', 'space', 'br'], true)
                ? $input['post_soft_break']
                : 'newline',
            'comment_soft_break' => in_array($input['comment_soft_break'] ?? '', ['newline', 'space', 'br'], true)
                ? $input['comment_soft_break']
                : 'newline',
            'smart_quotes_locale' => in_array($input['smart_quotes_locale'] ?? '', array_merge(['auto'], SmartQuotesExtension::getSupportedLocales()), true)
                ? $input['smart_quotes_locale']
                : 'en',
            'toc_enabled' => !empty($input['toc_enabled']),
            'toc_position' => in_array($input['toc_position'] ?? '', ['top', 'bottom'], true)
                ? $input['toc_position']
                : 'top',
            'toc_min_level' => in_array((int)($input['toc_min_level'] ?? 2), [1, 2, 3, 4, 5, 6], true)
                ? (int)$input['toc_min_level']
                : 2,
            'toc_max_level' => in_array((int)($input['toc_max_level'] ?? 4), [1, 2, 3, 4, 5, 6], true)
                ? (int)$input['toc_max_level']
                : 4,
            'toc_list_type' => in_array($input['toc_list_type'] ?? '', ['ul', 'ol'], true)
                ? $input['toc_list_type']
                : 'ul',
            'permalinks_enabled' => !empty($input['permalinks_enabled']),
            'visual_editor_mode' => in_array($input['visual_editor_mode'] ?? '', ['disabled', 'enabled', 'enabled_default'], true)
                ? $input['visual_editor_mode']
                : 'disabled',
        ];
    }

    /**
     * Render the settings page.
     */
    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        require WPDJOT_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Section descriptions.
     */
    public function renderContentSectionDescription(): void
    {
        echo '<p>' . esc_html__('Choose where Djot markup should be processed.', 'djot-markup') . '</p>';
    }

    public function renderSecuritySectionDescription(): void
    {
        echo '<p>' . esc_html__('Security options for handling potentially unsafe content.', 'djot-markup') . '</p>';
    }

    public function renderRenderingSectionDescription(): void
    {
        echo '<p>' . esc_html__('Configure how Djot content is parsed and rendered to HTML.', 'djot-markup') . '</p>';
    }

    public function renderAdvancedSectionDescription(): void
    {
        echo '<p>' . esc_html__('Advanced configuration options.', 'djot-markup') . '</p>';
    }

    /**
     * Render checkbox field.
     *
     * @param array<string, mixed> $args
     */
    public function renderCheckboxField(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $checked = !empty($options[$field]);

        printf(
            '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s />',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
            checked($checked, true, false),
        );

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', wp_kses($args['description'], ['br' => [], 'strong' => [], 'code' => []]));
        }
    }

    /**
     * Render text field.
     *
     * @param array<string, mixed> $args
     */
    public function renderTextField(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $value = $options[$field] ?? '';

        printf(
            '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
            esc_attr($value),
        );

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Render profile select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderProfileSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $isCommentProfile = $field === 'comment_profile';
        $default = $isCommentProfile ? 'comment' : 'article';
        $current = $options[$field] ?? $default;

        $profiles = [
            'none' => [
                'label' => __('None', 'djot-markup'),
                'description' => __('No restrictions. All Djot features including raw HTML. Use only for fully trusted content.', 'djot-markup'),
                'posts_only' => true,
            ],
            'full' => [
                'label' => __('Full', 'djot-markup'),
                'description' => __('All Djot features including raw HTML, but respects safe mode settings.', 'djot-markup'),
                'posts_only' => true,
            ],
            'article' => [
                'label' => __('Article', 'djot-markup'),
                'description' => __('All formatting, no raw HTML. Good for blog posts.', 'djot-markup'),
                'posts_only' => false,
            ],
            'comment' => [
                'label' => __('Comment', 'djot-markup'),
                'description' => __('Basic formatting only. No headings, images, or tables. Links get nofollow.', 'djot-markup'),
                'posts_only' => false,
            ],
            'minimal' => [
                'label' => __('Minimal', 'djot-markup'),
                'description' => __('Text formatting and lists only. No links or images.', 'djot-markup'),
                'posts_only' => false,
            ],
        ];

        // Filter out posts-only profiles for comments
        if ($isCommentProfile) {
            $profiles = array_filter($profiles, fn ($p) => !$p['posts_only']);
        }

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($profiles as $value => $profile) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($profile['label']),
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }

        // Show profile details
        echo '<ul class="description" style="margin-top: 5px; font-size: 12px;">';
        foreach ($profiles as $value => $profile) {
            printf(
                '<li><strong>%s:</strong> %s</li>',
                esc_html($profile['label']),
                esc_html($profile['description']),
            );
        }
        echo '</ul>';
    }

    /**
     * Render soft break mode select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderSoftBreakSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $current = $options[$field] ?? 'newline';

        $modes = [
            'newline' => [
                'label' => __('Default (invisible)', 'djot-markup'),
                'description' => __('Standard behavior - line breaks are not visible in output.', 'djot-markup'),
            ],
            'space' => [
                'label' => __('Space', 'djot-markup'),
                'description' => __('Render as a space character.', 'djot-markup'),
            ],
            'br' => [
                'label' => __('Visible line break', 'djot-markup'),
                'description' => __('Render as <br> tag. Useful for poetry, addresses, or preserving line breaks.', 'djot-markup'),
            ],
        ];

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($modes as $value => $mode) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($mode['label']),
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }

        // Show mode details
        echo '<ul class="description" style="margin-top: 5px; font-size: 12px;">';
        foreach ($modes as $value => $mode) {
            printf(
                '<li><strong>%s:</strong> %s</li>',
                esc_html($mode['label']),
                esc_html($mode['description']),
            );
        }
        echo '</ul>';
    }

    /**
     * Render heading shift select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderHeadingShiftSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $current = (int) ($options[$field] ?? 0);

        $shifts = [
            0 => __('None (h1 stays h1)', 'djot-markup'),
            1 => __('Shift +1 (h1 → h2, h2 → h3, ...)', 'djot-markup'),
            2 => __('Shift +2 (h1 → h3, h2 → h4, ...)', 'djot-markup'),
        ];

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($shifts as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr((string) $value),
                selected($current, $value, false),
                esc_html($label),
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Render smart quotes locale select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderSmartQuotesLocaleSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $current = $options[$field] ?? 'en';

        $localeLabels = [
            'en' => "Default (English \u{201C}\u{2026}\u{201D} \u{2018}\u{2026}\u{2019})",
            'auto' => 'Auto (from site language)',
            'cs' => "Czech \u{201E}\u{2026}\u{201C} \u{201A}\u{2026}\u{2018}",
            'da' => "Danish \u{201E}\u{2026}\u{201C} \u{201A}\u{2026}\u{2018}",
            'nl' => "Dutch \u{201C}\u{2026}\u{201D} \u{2018}\u{2026}\u{2019}",
            'fi' => "Finnish \u{201D}\u{2026}\u{201D} \u{2019}\u{2026}\u{2019}",
            'fr' => "French \u{00AB}\u{2026}\u{00BB} \u{2039}\u{2026}\u{203A}",
            'de' => "German \u{201E}\u{2026}\u{201C} \u{201A}\u{2026}\u{2018}",
            'de-CH' => "Swiss German \u{00AB}\u{2026}\u{00BB} \u{2039}\u{2026}\u{203A}",
            'hu' => "Hungarian \u{201E}\u{2026}\u{201D} \u{201A}\u{2026}\u{2019}",
            'it' => "Italian \u{00AB}\u{2026}\u{00BB} \u{201C}\u{2026}\u{201D}",
            'ja' => "Japanese \u{300C}\u{2026}\u{300D} \u{300E}\u{2026}\u{300F}",
            'nb' => "Norwegian Bokm\u{00E5}l \u{00AB}\u{2026}\u{00BB} \u{2018}\u{2026}\u{2019}",
            'nn' => "Norwegian Nynorsk \u{00AB}\u{2026}\u{00BB} \u{2018}\u{2026}\u{2019}",
            'pl' => "Polish \u{201E}\u{2026}\u{201D} \u{201A}\u{2026}\u{2019}",
            'pt' => "Portuguese \u{00AB}\u{2026}\u{00BB} \u{201C}\u{2026}\u{201D}",
            'ru' => "Russian \u{00AB}\u{2026}\u{00BB} \u{201E}\u{2026}\u{201C}",
            'es' => "Spanish \u{00AB}\u{2026}\u{00BB} \u{201C}\u{2026}\u{201D}",
            'sv' => "Swedish \u{201D}\u{2026}\u{201D} \u{2019}\u{2026}\u{2019}",
            'uk' => "Ukrainian \u{00AB}\u{2026}\u{00BB} \u{201E}\u{2026}\u{201C}",
            'zh' => "Chinese \u{300C}\u{2026}\u{300D} \u{300E}\u{2026}\u{300F}",
        ];

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($localeLabels as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($label),
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function renderTocSectionDescription(): void
    {
        echo '<p>' . esc_html__('Configure automatic table of contents generation for posts and pages.', 'djot-markup') . '</p>';
    }

    /**
     * Render TOC position select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderTocPositionSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $current = $options[$field] ?? 'top';

        $positions = [
            'top' => __('Top of content', 'djot-markup'),
            'bottom' => __('Bottom of content', 'djot-markup'),
        ];

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($positions as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($label),
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Render heading level select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderHeadingLevelSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $default = $field === 'toc_min_level' ? 2 : 4;
        $current = $options[$field] ?? $default;

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        for ($level = 1; $level <= 6; $level++) {
            printf(
                '<option value="%d" %s>H%d</option>',
                $level,
                selected((int)$current, $level, false),
                $level,
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Render TOC list type select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderTocListTypeSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $current = $options[$field] ?? 'ul';

        $listTypes = [
            'ul' => __('Unordered (bullets)', 'djot-markup'),
            'ol' => __('Ordered (numbers)', 'djot-markup'),
        ];

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($listTypes as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($label),
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function renderExperimentalSectionDescription(): void
    {
        echo '<p>' . esc_html__('These features are not yet fully stable and may change in future versions.', 'djot-markup') . '</p>';
    }

    /**
     * Render visual editor mode select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderVisualEditorModeSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $current = $options[$field] ?? 'disabled';

        $modes = [
            'disabled' => [
                'label' => __('Disabled', 'djot-markup'),
                'description' => __('Visual editor tab is hidden. Only Write and Preview modes available.', 'djot-markup'),
            ],
            'enabled' => [
                'label' => __('Enabled (Write default)', 'djot-markup'),
                'description' => __('Visual editor available. Write mode is selected by default.', 'djot-markup'),
            ],
            'enabled_default' => [
                'label' => __('Enabled (Visual default)', 'djot-markup'),
                'description' => __('Visual editor available and selected by default when opening documents.', 'djot-markup'),
            ],
        ];

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($modes as $value => $mode) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($mode['label']),
            );
        }

        echo '</select>';

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', wp_kses($args['description'], ['br' => [], 'em' => [], 'strong' => [], 'code' => []]));
        }

        // Show mode details
        echo '<ul class="description" style="margin-top: 5px; font-size: 12px;">';
        foreach ($modes as $value => $mode) {
            printf(
                '<li><strong>%s:</strong> %s</li>',
                esc_html($mode['label']),
                esc_html($mode['description']),
            );
        }
        echo '</ul>';
    }
}
