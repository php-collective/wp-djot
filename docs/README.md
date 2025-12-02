# WP Djot Documentation

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
- **Security Settings**: Safe mode for XSS protection, content profiles
- **Code Highlighting**: Enable/disable and choose theme
- **Advanced**: Custom shortcode tag, filter priority

### Optional: HTMLPurifier for Enhanced Security

For additional XSS protection on comments, you can optionally install HTMLPurifier:

```bash
composer require ezyang/htmlpurifier
```

When installed, HTMLPurifier will automatically be used for sanitizing comment output.

## More Documentation

- [Content Profiles](profiles.md) - Configure feature restrictions for posts and comments
- [WP-CLI Commands](wp-cli.md) - Migrate existing content to Djot
- [Hooks and Filters](hooks.md) - Customize plugin behavior
- [Djot Syntax](syntax.md) - Quick reference for Djot markup
