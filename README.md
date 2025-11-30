# WP Djot

A WordPress plugin for [Djot](https://djot.net/) markup language support. Convert Djot syntax to HTML in posts, pages, and comments.

## What is Djot?

Djot is a light markup syntax created by John MacFarlane (creator of CommonMark and Pandoc). It aims to be a successor to Markdown with cleaner syntax and more consistent parsing rules.

## Features

- **Full Djot Support**: Headings, emphasis, links, images, code blocks, tables, footnotes, and more
- **Shortcode Support**: Use `[djot]...[/djot]` in your content
- **Content Filtering**: Automatically process `{djot}...{/djot}` blocks in posts and pages
- **Safe Mode**: XSS protection for untrusted content (enabled by default for comments)
- **Syntax Highlighting**: Built-in highlight.js integration with multiple themes
- **Admin Settings**: Easy configuration through WordPress admin
- **Template Tags**: `djot_to_html()` and `the_djot()` for theme developers
- **Dark Mode Support**: CSS automatically adapts to dark mode preferences

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

## Development

### Running Tests

```bash
composer install
composer test
```

### Code Style

```bash
composer cs-check
composer cs-fix
```

### Static Analysis

```bash
composer stan
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

- [Djot](https://djot.net/) by John MacFarlane
- [djot-php](https://github.com/php-collective/djot-php) by PHP Collective
- [highlight.js](https://highlightjs.org/) for syntax highlighting

## Changelog

### 1.0.0

- Initial release
- Full Djot syntax support
- Shortcode and content filtering
- Admin settings page
- Syntax highlighting with highlight.js
- Safe mode for untrusted content
- Template tags for theme developers
