# Djot Markup for WP

[![CI](https://github.com/php-collective/wp-djot/actions/workflows/ci.yml/badge.svg)](https://github.com/php-collective/wp-djot/actions/workflows/ci.yml)
[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/djot-markup-for-wp)](https://wordpress.org/plugins/djot-markup-for-wp/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/djot-markup-for-wp)](https://wordpress.org/plugins/djot-markup-for-wp/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/stars/djot-markup-for-wp)](https://wordpress.org/plugins/djot-markup-for-wp/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net)
[![WordPress 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)

A WordPress plugin for [Djot](https://djot.net/) markup language support. Convert Djot syntax to HTML in posts, pages, and comments.

## What is Djot?

Djot is a light markup syntax created by John MacFarlane (creator of CommonMark and Pandoc). It aims to be a successor to Markdown with cleaner syntax and more consistent parsing rules.

## Features

- **Full Djot Support**: Headings, emphasis, links, images, code blocks, tables, footnotes, and more
- **Block Editor Support**: Native Gutenberg block for writing Djot with live preview
- **Shortcode Support**: Use `[djot]...[/djot]` in your content
- **Content Filtering**: Automatically process `{djot}...{/djot}` blocks in posts and pages
- **Content Profiles**: Configurable feature restrictions (full, article, comment, minimal)
- **Safe Mode**: XSS protection for untrusted content (enabled by default for comments)
- **Syntax Highlighting**: Built-in highlight.js integration with multiple themes
- **Admin Settings**: Easy configuration through WordPress admin
- **Template Tags**: `wp_djot_to_html()` and `wp_djot_the()` for theme developers
- **Dark Mode Support**: CSS automatically adapts to dark mode preferences
- **WP-CLI Migration**: Migrate existing HTML/Markdown content to Djot with rollback support

## Requirements

- PHP 8.2 or higher
- WordPress 6.0 or higher
- Composer (for installation)

## Installation

### From GitHub

1. Clone or download this repository to `wp-content/plugins/wp-djot`
2. Run `composer install` in the plugin directory
3. Activate the plugin in WordPress admin

```bash
cd wp-content/plugins/wp-djot
composer install
```

### From WordPress.org

Search for "Djot Markup" in the WordPress plugin directory, or visit:
[wordpress.org/plugins/djot-markup-for-wp](https://wordpress.org/plugins/djot-markup-for-wp/)

## Usage

### Block Editor (Gutenberg)

Add a **Djot** block from the block inserter (search for "Djot"). The block provides:

- A code editor for writing Djot markup
- Live preview toggle in the sidebar
- Server-side rendering for accurate output

Simply write your Djot content and toggle preview to see the rendered HTML.

### Shortcode

````
[djot]
# Hello World

This is _emphasized_ and this is *strong*.

- List item 1
- List item 2

```php
echo "Hello, World!";
```
[/djot]
````

### Curly Brace Syntax

If content filtering is enabled in settings:

```
{djot}
Your Djot content here...
{/djot}
```

### Template Tags

```php
// Convert and return HTML
$html = wp_djot_to_html('# Hello *World*!');

// Convert and echo HTML
wp_djot_the('# _Hello_ *World*!');

// Check if content has Djot
if (wp_djot_has($content)) {
    // ...
}
```

### Shortcode Attributes

```
[djot safe="true"]
Untrusted content - will use safe mode
[/djot]

[djot class="my-custom-class"]
Content with custom CSS class
[/djot]
```

## Configuration

Go to **Settings → Djot Markup** to configure:

- **Content Settings**: Enable/disable for posts, pages, comments
- **Comment Processing**: Process full comment content as Djot (not just `{djot}` blocks)
- **Security Settings**: Safe mode for XSS protection
- **Code Highlighting**: Enable/disable and choose theme
- **Advanced**: Custom shortcode tag, filter priority

### Optional: HTMLPurifier for Enhanced Security

For additional XSS protection on comments, you can optionally install HTMLPurifier:

```bash
composer require ezyang/htmlpurifier
```

When installed, HTMLPurifier will automatically be used for sanitizing comment output.

## Documentation

- [Content Profiles](docs/profiles.md) - Configure feature restrictions for posts and comments
- [WP-CLI Commands](docs/wp-cli.md) - Migrate existing content to Djot
- [Hooks and Filters](docs/hooks.md) - Customize plugin behavior
- [Djot Syntax](docs/syntax.md) - Quick reference for Djot markup

For complete Djot syntax documentation, visit [djot.net](https://djot.net/).

## Credits

- [Djot](https://djot.net/) by John MacFarlane
- [djot-php](https://github.com/php-collective/djot-php) by PHP Collective
- [highlight.js](https://highlightjs.org/) for syntax highlighting

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full history.
