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
$html = wpdjot_to_html('# Hello *World*!');

// Convert and echo HTML
wpdjot_the('# _Hello_ *World*!');

// Check if content has Djot
if (wpdjot_has($content)) {
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
- **Rendering Settings**: Line break handling and Markdown compatibility mode
- **Code Highlighting**: Enable/disable and choose theme
- **Table of Contents**: Automatic TOC generation from headings
- **Advanced**: Custom shortcode tag, filter priority

### Table of Contents

The plugin can automatically generate a table of contents from headings in your posts and pages.

| Setting | Options | Default | Description |
|---------|---------|---------|-------------|
| Enable TOC | on/off | off | Enable automatic TOC generation |
| Position | top / bottom | top | Where to insert the TOC in the content |
| Min heading level | H1–H6 | H2 | Start collecting headings from this level (H2 skips the page title) |
| Max heading level | H1–H6 | H4 | Stop collecting headings at this level |
| List type | ul / ol | ul | Bulleted or numbered list |

The TOC is rendered as a `<nav class="wpdjot-toc">` element with linked headings. It includes light and dark mode styling out of the box.

The TOC is only generated for posts and pages (article context), not for comments.

### Rendering Settings

#### Markdown Compatibility Mode

Enable this if you're migrating from Markdown and haven't converted your content yet. When enabled:
- Single line breaks become visible `<br>` tags
- Blocks (lists, blockquotes, code) can interrupt paragraphs without blank lines

**Warning**: This deviates from the Djot specification.

#### Line Break Modes

Control how single line breaks (soft breaks) are rendered:

| Mode | Description |
|------|-------------|
| Default (invisible) | Standard Djot behavior - line breaks are not visible in output |
| Space | Render as a space character |
| Visible line break | Render as `<br>` tag - useful for poetry, addresses, or lyrics |

Separate settings are available for posts/pages and comments.

### Optional: HTMLPurifier for Enhanced Security

For additional XSS protection on comments, you can optionally install HTMLPurifier:

```bash
composer require ezyang/htmlpurifier
```

When installed, HTMLPurifier will automatically be used for sanitizing comment output.

## More Documentation

- [Content Profiles](profiles.md) - Configure feature restrictions for posts and comments
- [Customization](customization.md) - Extend Djot with custom patterns, render handlers, @mentions, and more
- [WP-CLI Commands](wp-cli.md) - Migrate existing content to Djot
- [Hooks and Filters](hooks.md) - Customize plugin behavior
- [Djot Syntax](syntax.md) - Quick reference for Djot markup
