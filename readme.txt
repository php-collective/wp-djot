=== WP Djot ===
Contributors: dereuromark
Tags: djot, markup, markdown, syntax-highlighting, code
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Djot markup language support for WordPress. A modern, cleaner alternative to Markdown.

== Description ==

WP Djot adds support for the [Djot](https://djot.net/) markup language in WordPress. Djot is created by John MacFarlane (creator of CommonMark and Pandoc) and offers cleaner syntax with more consistent parsing rules than Markdown.

= Features =

* **Full Djot Support**: Headings, emphasis, links, images, code blocks, tables, footnotes, and more
* **Shortcode Support**: Use `[djot]...[/djot]` in your content
* **Content Filtering**: Automatically process `{djot}...{/djot}` blocks
* **Safe Mode**: XSS protection for untrusted content
* **Syntax Highlighting**: Built-in highlight.js integration with 12+ themes
* **Admin Settings**: Easy configuration through WordPress admin
* **Template Tags**: Functions for theme developers
* **Dark Mode Support**: CSS adapts to dark mode preferences

= Usage =

**Shortcode:**

    [djot]
    # Hello World

    This is _emphasized_ and this is *strong*.

    - List item 1
    - List item 2
    [/djot]

**Template Tags:**

    <?php
    $html = wp_djot_to_html('# Hello *World*!');
    wp_djot_the('# _Hello_ *World*!');
    ?>

= Links =

* [Djot Syntax Reference](https://djot.net/)
* [GitHub Repository](https://github.com/php-collective/wp-djot)
* [Report Issues](https://github.com/php-collective/wp-djot/issues)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-djot`
2. Run `composer install` in the plugin directory
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Configure settings under Settings → WP Djot

== Frequently Asked Questions ==

= What is Djot? =

Djot is a light markup syntax created by John MacFarlane, the creator of CommonMark and Pandoc. It's designed to be a cleaner, more consistent alternative to Markdown.

= Why use Djot instead of Markdown? =

Djot has cleaner syntax rules, proper footnote support, attributes for styling, and avoids many of Markdown's parsing ambiguities.

= Is it safe for user comments? =

Yes! Enable "Safe Mode" in settings to sanitize untrusted content and prevent XSS attacks. Comments always use safe mode by default.

= Can I use my own CSS? =

Yes, you can override the default styles by targeting the `.djot-content` class in your theme's CSS.

= Does it work with Gutenberg? =

Yes, you can use the shortcode block to add Djot content in the block editor.

== Screenshots ==

1. Admin settings page
2. Shortcode usage example
3. Rendered output with syntax highlighting

== Changelog ==

= 1.0.0 =
* Initial release
* Full Djot syntax support
* Shortcode and content filtering
* Admin settings page
* Syntax highlighting with highlight.js
* Safe mode for untrusted content
* Template tags for theme developers

== Upgrade Notice ==

= 1.0.0 =
Initial release.
