<?php

declare(strict_types=1);

namespace WpDjot\Admin;

/**
 * Admin settings page for WP Djot.
 */
class Settings
{
    /**
     * @var string
     */
    private const OPTION_GROUP = 'wp_djot_settings';

    /**
     * @var string
     */
    private const PAGE_SLUG = 'wp-djot-settings';

    /**
     * Initialize admin hooks.
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_filter('plugin_action_links_' . WP_DJOT_PLUGIN_BASENAME, [$this, 'addSettingsLink']);
    }

    /**
     * Add settings page to admin menu.
     */
    public function addMenuPage(): void
    {
        add_options_page(
            __('Djot Markup Settings', 'djot-markup-for-wp'),
            __('Djot Markup', 'djot-markup-for-wp'),
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
            __('Settings', 'djot-markup-for-wp'),
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
            'wp_djot_content',
            __('Content Settings', 'djot-markup-for-wp'),
            [$this, 'renderContentSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'enable_posts',
            __('Enable for Posts', 'djot-markup-for-wp'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wp_djot_content',
            ['field' => 'enable_posts', 'description' => __('Process Djot markup in blog posts.', 'djot-markup-for-wp')],
        );

        add_settings_field(
            'enable_pages',
            __('Enable for Pages', 'djot-markup-for-wp'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wp_djot_content',
            ['field' => 'enable_pages', 'description' => __('Process Djot markup in pages.', 'djot-markup-for-wp')],
        );

        add_settings_field(
            'enable_comments',
            __('Enable for Comments', 'djot-markup-for-wp'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wp_djot_content',
            ['field' => 'enable_comments', 'description' => __('Process Djot markup in comments (always uses safe mode).', 'djot-markup-for-wp')],
        );

        add_settings_field(
            'process_full_content',
            __('Process Full Content', 'djot-markup-for-wp'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wp_djot_content',
            ['field' => 'process_full_content', 'description' => __('Process entire post/page content as Djot. When disabled, only {djot}...{/djot} blocks are processed.', 'djot-markup-for-wp')],
        );

        add_settings_field(
            'process_full_comments',
            __('Process Full Comments', 'djot-markup-for-wp'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wp_djot_content',
            ['field' => 'process_full_comments', 'description' => __('Process entire comment content as Djot. When disabled, only {djot}...{/djot} blocks are processed.', 'djot-markup-for-wp')],
        );

        // Security Settings Section
        add_settings_section(
            'wp_djot_security',
            __('Security Settings', 'djot-markup-for-wp'),
            [$this, 'renderSecuritySectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'safe_mode',
            __('Safe Mode', 'djot-markup-for-wp'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wp_djot_security',
            ['field' => 'safe_mode', 'description' => __('Block dangerous URL schemes and strip event handlers. Recommended for untrusted content.', 'djot-markup-for-wp')],
        );

        add_settings_field(
            'post_profile',
            __('Posts/Pages Profile', 'djot-markup-for-wp'),
            [$this, 'renderProfileSelect'],
            self::PAGE_SLUG,
            'wp_djot_security',
            ['field' => 'post_profile', 'description' => __('Feature restrictions for posts and pages.', 'djot-markup-for-wp')],
        );

        add_settings_field(
            'comment_profile',
            __('Comments Profile', 'djot-markup-for-wp'),
            [$this, 'renderProfileSelect'],
            self::PAGE_SLUG,
            'wp_djot_security',
            ['field' => 'comment_profile', 'description' => __('Feature restrictions for user comments.', 'djot-markup-for-wp')],
        );

        // Code Highlighting Section
        add_settings_section(
            'wp_djot_highlighting',
            __('Code Highlighting', 'djot-markup-for-wp'),
            [$this, 'renderHighlightingSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'highlight_code',
            __('Enable Highlighting', 'djot-markup-for-wp'),
            [$this, 'renderCheckboxField'],
            self::PAGE_SLUG,
            'wp_djot_highlighting',
            ['field' => 'highlight_code', 'description' => __('Enable syntax highlighting for code blocks using highlight.js.', 'djot-markup-for-wp')],
        );

        add_settings_field(
            'highlight_theme',
            __('Highlight Theme', 'djot-markup-for-wp'),
            [$this, 'renderThemeSelect'],
            self::PAGE_SLUG,
            'wp_djot_highlighting',
            ['field' => 'highlight_theme'],
        );

        // Advanced Settings Section
        add_settings_section(
            'wp_djot_advanced',
            __('Advanced Settings', 'djot-markup-for-wp'),
            [$this, 'renderAdvancedSectionDescription'],
            self::PAGE_SLUG,
        );

        add_settings_field(
            'shortcode_tag',
            __('Shortcode Tag', 'djot-markup-for-wp'),
            [$this, 'renderTextField'],
            self::PAGE_SLUG,
            'wp_djot_advanced',
            ['field' => 'shortcode_tag', 'description' => __('The shortcode tag to use (default: djot).', 'djot-markup-for-wp')],
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
        $validPostProfiles = ['full', 'article', 'comment', 'minimal'];
        // Comments can never use 'full' profile for security reasons
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
            'highlight_code' => !empty($input['highlight_code']),
            'highlight_theme' => sanitize_text_field($input['highlight_theme'] ?? 'github'),
            'shortcode_tag' => sanitize_key($input['shortcode_tag'] ?? 'djot'),
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

        require WP_DJOT_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Section descriptions.
     */
    public function renderContentSectionDescription(): void
    {
        echo '<p>' . esc_html__('Choose where Djot markup should be processed.', 'djot-markup-for-wp') . '</p>';
    }

    public function renderSecuritySectionDescription(): void
    {
        echo '<p>' . esc_html__('Security options for handling potentially unsafe content.', 'djot-markup-for-wp') . '</p>';
    }

    public function renderHighlightingSectionDescription(): void
    {
        echo '<p>' . esc_html__('Configure syntax highlighting for code blocks.', 'djot-markup-for-wp') . '</p>';
    }

    public function renderAdvancedSectionDescription(): void
    {
        echo '<p>' . esc_html__('Advanced configuration options.', 'djot-markup-for-wp') . '</p>';
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
            printf('<p class="description">%s</p>', esc_html($args['description']));
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
     * Render theme select dropdown.
     *
     * @param array<string, mixed> $args
     */
    public function renderThemeSelect(array $args): void
    {
        $options = get_option(self::OPTION_GROUP, []);
        $field = $args['field'];
        $current = $options[$field] ?? 'github';

        $themes = [
            'github' => 'GitHub',
            'github-dark' => 'GitHub Dark',
            'monokai' => 'Monokai',
            'dracula' => 'Dracula',
            'nord' => 'Nord',
            'atom-one-light' => 'Atom One Light',
            'atom-one-dark' => 'Atom One Dark',
            'vs' => 'Visual Studio',
            'vs2015' => 'Visual Studio 2015',
            'xcode' => 'Xcode',
            'stackoverflow-light' => 'Stack Overflow Light',
            'stackoverflow-dark' => 'Stack Overflow Dark',
        ];

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr($field),
            esc_attr(self::OPTION_GROUP),
        );

        foreach ($themes as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($label),
            );
        }

        echo '</select>';
        echo '<p class="description">' . esc_html__('Choose a syntax highlighting color scheme.', 'djot-markup-for-wp') . '</p>';
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
            'full' => [
                'label' => __('Full', 'djot-markup-for-wp'),
                'description' => __('All features enabled. Use only for fully trusted content.', 'djot-markup-for-wp'),
                'posts_only' => true,
            ],
            'article' => [
                'label' => __('Article', 'djot-markup-for-wp'),
                'description' => __('All formatting, no raw HTML. Good for blog posts.', 'djot-markup-for-wp'),
                'posts_only' => false,
            ],
            'comment' => [
                'label' => __('Comment', 'djot-markup-for-wp'),
                'description' => __('Basic formatting only. No headings, images, or tables. Links get nofollow.', 'djot-markup-for-wp'),
                'posts_only' => false,
            ],
            'minimal' => [
                'label' => __('Minimal', 'djot-markup-for-wp'),
                'description' => __('Text formatting and lists only. No links or images.', 'djot-markup-for-wp'),
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
}
