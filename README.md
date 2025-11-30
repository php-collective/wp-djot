# WP Djot

[![CI](https://github.com/php-collective/wp-djot/actions/workflows/ci.yml/badge.svg)](https://github.com/php-collective/wp-djot/actions/workflows/ci.yml)
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
- **Safe Mode**: XSS protection for untrusted content (enabled by default for comments)
- **Syntax Highlighting**: Built-in highlight.js integration with multiple themes
- **Admin Settings**: Easy configuration through WordPress admin
- **Template Tags**: `djot_to_html()` and `the_djot()` for theme developers
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

### From WordPress.org (coming soon)

Search for "WP Djot" in the WordPress plugin directory.

## Usage

### Block Editor (Gutenberg)

Add a **Djot** block from the block inserter (search for "Djot"). The block provides:

- A code editor for writing Djot markup
- Live preview toggle in the sidebar
- Server-side rendering for accurate output

Simply write your Djot content and toggle preview to see the rendered HTML.

### Shortcode

```
[djot]
# Hello World

This is _emphasized_ and this is *strong*.

- List item 1
- List item 2

```php
echo "Hello, World!";
```
[/djot]
```

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
$html = djot_to_html('# Hello *World*!');

// Convert and echo HTML
the_djot('# _Hello_ *World*!');

// Check if content has Djot
if (has_djot($content)) {
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

Go to **Settings → WP Djot** to configure:

- **Content Settings**: Enable/disable for posts, pages, comments
- **Security Settings**: Safe mode for XSS protection
- **Code Highlighting**: Enable/disable and choose theme
- **Advanced**: Custom shortcode tag, filter priority

## Djot Syntax Quick Reference

### Basic Formatting

| Syntax | Result |
|--------|--------|
| `_emphasis_` | _emphasis_ |
| `*strong*` | **strong** |
| `` `code` `` | `code` |
| `[link](url)` | [link](url) |
| `![alt](image.jpg)` | Image |
| `# Heading` | Heading (h1-h6) |
| `> blockquote` | Blockquote |
| `- item` | Unordered list |
| `1. item` | Ordered list |

### Djot-Specific Features (not in Markdown)

| Syntax | Result |
|--------|--------|
| `x^2^` | Superscript |
| `H~2~O` | Subscript |
| `{=marked=}` | Highlighted text |
| `{+inserted+}` | Inserted text |
| `{-deleted-}` | Deleted text |
| `[text]{.class}` | Span with CSS class |
| `::: note ... :::` | Div with CSS class |
| `"quotes" -- Pro` | Smart typography |

> **Note:** Djot uses different emphasis syntax than Markdown: `_underscores_` for emphasis (italic) and `*single asterisks*` for strong (bold).

For complete syntax documentation, visit [djot.net](https://djot.net/).

## Hooks and Filters

### Filters

```php
// Modify Djot content before conversion
add_filter('wp_djot_pre_convert', function(string $djot): string {
    // Modify $djot
    return $djot;
});

// Modify HTML after conversion
add_filter('wp_djot_post_convert', function(string $html): string {
    // Modify $html
    return $html;
});
```

## WP-CLI Commands

Migrate existing HTML or Markdown content to Djot format using WP-CLI.

### Analyze Content

Analyze posts to determine migration complexity before converting:

```bash
# Analyze all posts and pages
wp djot analyze

# Analyze a specific post
wp djot analyze --post-id=123

# Analyze only posts, limit to 10
wp djot analyze --post-type=post --limit=10

# Output as JSON
wp djot analyze --format=json
```

The analysis shows:
- **Complexity**: none, low, medium, high
- **Content types**: HTML, Markdown, Gutenberg blocks, shortcodes
- **Auto-migrate**: Whether the post can be safely auto-migrated

### Migrate Content

Convert posts from HTML/Markdown to Djot:

```bash
# Migrate a single post
wp djot migrate --post-id=123

# Preview migration without saving (dry run)
wp djot migrate --dry-run

# Preview with content diff
wp djot migrate --dry-run --show-diff --post-id=123

# Migrate posts in batches
wp djot migrate --post-type=post --limit=10

# Force migration of high-complexity posts
wp djot migrate --post-id=123 --force
```

**Features:**
- Automatic backup of original content
- Preserves WordPress shortcodes
- Preserves Gutenberg blocks
- Converts HTML tags to Djot syntax
- Converts Markdown syntax to Djot

### Rollback Migrations

Restore posts to their original content:

```bash
# Rollback a single post
wp djot rollback --post-id=123

# Rollback all migrated posts
wp djot rollback --all
```

### Migration Status

View migration statistics:

```bash
wp djot status
```

Shows count of migrated posts, pending posts, and complexity distribution.

## Credits

- [Djot](https://djot.net/) by John MacFarlane
- [djot-php](https://github.com/php-collective/djot-php) by PHP Collective
- [highlight.js](https://highlightjs.org/) for syntax highlighting

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full history.
