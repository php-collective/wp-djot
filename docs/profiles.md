# Content Profiles

WP Djot uses content profiles to control which Djot features are allowed in different contexts. This provides security and prevents misuse while still allowing rich formatting where appropriate.

## Available Profiles

### Full

All Djot features enabled, including raw HTML blocks.

**Use case:** Trusted content from site administrators only.

**Features:** Everything including ```` ``` =html ```` raw HTML blocks.

**Warning:** Never use for user-generated content (comments). The "Full" profile is not available for comments.

### Article (Default for Posts/Pages)

All formatting features, but no raw HTML.

**Use case:** Blog posts, pages, and other editorial content.

**Features:**
- Headings, paragraphs, blockquotes
- Emphasis, strong, code
- Links, images
- Lists (ordered, unordered, definition)
- Tables
- Footnotes
- Code blocks with syntax highlighting
- Superscript, subscript, mark, insert, delete

**Restrictions:**
- No raw HTML blocks

### Comment (Default for Comments)

Basic formatting suitable for user-generated content.

**Use case:** Blog comments, guest book entries, forum posts.

**Features:**
- Paragraphs
- Emphasis, strong, code
- Links (with `rel="nofollow ugc"`)
- Lists (ordered, unordered)
- Blockquotes
- Code blocks

**Restrictions:**
- No headings (prevents disrupting page structure)
- No images (prevents spam/inappropriate content)
- No tables (too complex for comments)
- No raw HTML (XSS prevention)
- No footnotes
- Links automatically get `rel="nofollow ugc"` for SEO spam prevention

### Minimal

Text formatting and lists only. Most restrictive profile.

**Use case:** Highly restricted environments, simple text fields.

**Features:**
- Paragraphs
- Emphasis, strong, code
- Lists (ordered, unordered)

**Restrictions:**
- No links
- No images
- No headings
- No tables
- No blockquotes
- No code blocks
- No raw HTML

## Configuration

Go to **Settings → Djot Markup → Security Settings** to configure:

- **Posts/Pages Profile:** Choose the profile for post and page content
- **Comments Profile:** Choose the profile for user comments (Full is not available)

## Safe Mode

In addition to profiles, WP Djot has a "Safe Mode" setting that:

- Blocks dangerous URL schemes (javascript:, data:, vbscript:)
- Strips event handlers (onclick, onerror, etc.)

Safe Mode is always enabled for comments regardless of the profile setting.

## Template Tag Usage

When using template tags, you can specify the context:

```php
// Use post profile
$html = wp_djot_to_html($content, true, 'post');

// Use comment profile
$html = wp_djot_to_html($content, true, 'comment');
```

## Programmatic Access

For theme/plugin developers, the Converter class provides profile-aware methods:

```php
use WpDjot\Converter;

$converter = new Converter(
    safeMode: true,
    postProfile: 'article',
    commentProfile: 'comment'
);

// Convert with post profile
$html = $converter->convertArticle($djot);

// Convert with comment profile (always uses safe mode)
$html = $converter->convertComment($djot);
```
