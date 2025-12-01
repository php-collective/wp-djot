<?php

declare(strict_types=1);

/**
 * Admin settings page template.
 *
 * @package WpDjot
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('wp_djot_settings');
        do_settings_sections('wp-djot-settings');
        submit_button();
        ?>
    </form>

    <hr>

    <h2><?php esc_html_e('Usage', 'djot-markup-for-wp'); ?></h2>

    <h3><?php esc_html_e('Shortcode', 'djot-markup-for-wp'); ?></h3>
    <p><?php esc_html_e('Use the shortcode to wrap Djot markup in your posts or pages:', 'djot-markup-for-wp'); ?></p>
    <pre><code>[djot]
# Hello World

This is _emphasized_ and this is *strong*.

- List item 1
- List item 2

```php
echo "Hello, World!";
```
[/djot]</code></pre>

    <h3><?php esc_html_e('Curly Brace Syntax', 'djot-markup-for-wp'); ?></h3>
    <p><?php esc_html_e('You can also use curly braces if content filtering is enabled:', 'djot-markup-for-wp'); ?></p>
    <pre><code>{djot}
Your Djot content here...
{/djot}</code></pre>

    <h3><?php esc_html_e('Template Tags', 'djot-markup-for-wp'); ?></h3>
    <p><?php esc_html_e('Use these functions in your theme templates:', 'djot-markup-for-wp'); ?></p>
    <pre><code>&lt;?php
// Convert and return HTML
$html = wp_djot_to_html('# Hello *World*!');

// Convert and echo HTML
wp_djot_the('# _Hello_ *World*!');

// Check if content has Djot
if (wp_djot_has($content)) {
    // ...
}
?&gt;</code></pre>

    <h3><?php esc_html_e('Djot Syntax Reference', 'djot-markup-for-wp'); ?></h3>
    <p>
        <?php
        printf(
            /* translators: %s: link to Djot documentation */
            esc_html__('For complete Djot syntax documentation, visit %s', 'djot-markup-for-wp'),
            '<a href="https://djot.net/" target="_blank" rel="noopener noreferrer">djot.net</a>',
        );
        ?>
    </p>

    <h4><?php esc_html_e('Basic Formatting', 'djot-markup-for-wp'); ?></h4>
    <table class="widefat" style="max-width: 600px;">
        <thead>
            <tr>
                <th><?php esc_html_e('Syntax', 'djot-markup-for-wp'); ?></th>
                <th><?php esc_html_e('Result', 'djot-markup-for-wp'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>_emphasis_</code></td>
                <td><em>emphasis</em></td>
            </tr>
            <tr>
                <td><code>*strong*</code></td>
                <td><strong>strong</strong></td>
            </tr>
            <tr>
                <td><code>`code`</code></td>
                <td><code>code</code></td>
            </tr>
            <tr>
                <td><code>[link](url)</code></td>
                <td><a href="#">link</a></td>
            </tr>
            <tr>
                <td><code>![alt](image.jpg)</code></td>
                <td><?php esc_html_e('Image', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code># Heading</code></td>
                <td><?php esc_html_e('Heading (h1-h6)', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>> blockquote</code></td>
                <td><?php esc_html_e('Blockquote', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>- item</code></td>
                <td><?php esc_html_e('Unordered list', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>1. item</code></td>
                <td><?php esc_html_e('Ordered list', 'djot-markup-for-wp'); ?></td>
            </tr>
        </tbody>
    </table>

    <h4><?php esc_html_e('Djot-Specific Features (not in Markdown)', 'djot-markup-for-wp'); ?></h4>
    <table class="widefat" style="max-width: 600px;">
        <thead>
            <tr>
                <th><?php esc_html_e('Syntax', 'djot-markup-for-wp'); ?></th>
                <th><?php esc_html_e('Result', 'djot-markup-for-wp'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>x^2^</code></td>
                <td>x<sup>2</sup> <?php esc_html_e('(superscript)', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>H~2~O</code></td>
                <td>H<sub>2</sub>O <?php esc_html_e('(subscript)', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>{=marked text=}</code></td>
                <td><mark>marked text</mark> <?php esc_html_e('(highlight)', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>{+inserted+}</code></td>
                <td><ins>inserted</ins> <?php esc_html_e('(insertion)', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>{-deleted-}</code></td>
                <td><del>deleted</del> <?php esc_html_e('(deletion)', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>[text]{.class}</code></td>
                <td><?php esc_html_e('Span with CSS class', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>::: warning<br>content<br>:::</code></td>
                <td><?php esc_html_e('Div with CSS class', 'djot-markup-for-wp'); ?></td>
            </tr>
            <tr>
                <td><code>"quotes" -- Pro</code></td>
                <td>"quotes" – Pro <?php esc_html_e('(smart typography)', 'djot-markup-for-wp'); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="notice notice-info inline" style="max-width: 600px; margin-top: 1em;">
        <p>
            <strong><?php esc_html_e('Note:', 'djot-markup-for-wp'); ?></strong>
            <?php esc_html_e('Djot uses different emphasis syntax than Markdown: _underscores_ for emphasis (italic) and *single asterisks* for strong (bold).', 'djot-markup-for-wp'); ?>
        </p>
    </div>
</div>
