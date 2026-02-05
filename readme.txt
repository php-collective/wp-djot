=== Djot Markup ===
Contributors: markmarkmark
Tags: djot, markup, markdown, syntax-highlighting, code
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.4.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Djot markup language support for WordPress. A modern, cleaner alternative to Markdown.

== Description ==

Djot Markup adds support for the [Djot](https://djot.net/) markup language in WordPress. Djot is created by John MacFarlane (creator of CommonMark and Pandoc) and offers cleaner syntax with more consistent parsing rules than Markdown.

= Features =

* **Full Djot Support**: Headings, emphasis, links, images, code blocks, tables, footnotes, and more
* **Shortcode Support**: Use `[djot]...[/djot]` in your content
* **Content Filtering**: Automatically process `{djot}...{/djot}` blocks
* **Table of Contents**: Automatic TOC generation from headings with configurable levels and position
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
    $html = wpdjot_to_html('# Hello *World*!');
    wpdjot_the('# _Hello_ *World*!');
    ?>

= Links =

* [WordPress.org Plugin Page](https://wordpress.org/plugins/djot-markup/)
* [Djot Syntax Reference](https://djot.net/)
* [GitHub Repository](https://github.com/php-collective/wp-djot)
* [Report Issues](https://github.com/php-collective/wp-djot/issues)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-djot`
2. Run `composer install` in the plugin directory
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Configure settings under Settings → Djot Markup

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

= 1.4.1 =
* Fixed Table of Contents and heading permalinks appearing in post excerpts on archive pages

= 1.4.0 =
* Added automatic Table of Contents generation from headings
* Configurable TOC position (top/bottom), heading levels, and list type
* Light and dark mode styling for TOC
* Added heading permalinks with show-on-hover and copy-to-clipboard
* Added locale-aware smart quotes (20 locales + Auto from site language)
* Bumped php-collective/djot to ^0.1.13

= 1.3.1 =
* Fixed text domain to match plugin slug (djot-markup)
* Properly escape HTML output with wp_kses_post/wp_kses
* Fixed block wrapper attributes escaping for Plugin Check compliance
* Excluded non-permitted files from distribution
* Added build script for distribution zip
* Added markmarkmark to Contributors list

= 1.2.1 =
* Renamed plugin to "Djot Markup" for WordPress.org compliance
* Added ABSPATH checks to all PHP files for security
* Prefixed global variables for WordPress coding standards compliance

= 1.2.0 =
* Changed code prefix from wp_djot/wp-djot to wpdjot for WordPress.org compliance
* Updated highlight.js from v11.9.0 to v11.11.1
* Added backward compatibility for existing blocks and settings
* Added settings migration from old option name

= 1.1.0 =
* Comment formatting toolbar with buttons for Bold, Italic, Code, Link, Quote, and Code Block
* Write/Preview tabs for comment toolbar with live Djot preview
* Syntax highlighting in comment preview
* Excerpt filter to render Djot content on archive pages
* Support for extracting Djot content from Gutenberg blocks for excerpts
* Fixed archive pages to properly show excerpts with "Read more" links

= 1.0.0 =
* Initial release
* Full Djot syntax support
* Shortcode and content filtering
* Admin settings page
* Syntax highlighting with highlight.js
* Safe mode for untrusted content
* Template tags for theme developers

== Upgrade Notice ==

= 1.4.1 =
Fixes TOC and heading permalinks leaking into post excerpts.

= 1.4.0 =
Adds Table of Contents, heading permalinks, and locale-aware smart quotes.

= 1.3.1 =
Text domain, escaping, and Plugin Check fixes for WordPress.org plugin review compliance.

= 1.2.1 =
Plugin renamed to "Djot Markup" for WordPress.org compliance.

= 1.2.0 =
Code prefix changes for WordPress.org compliance. Fully backward compatible.

= 1.1.0 =
Adds comment formatting toolbar with preview, and fixes archive page excerpts.

= 1.0.0 =
Initial release.
