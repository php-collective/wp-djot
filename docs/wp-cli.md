# WP-CLI Commands

Migrate existing HTML or Markdown content to Djot format using WP-CLI.

## Analyze Content

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

## Migrate Content

> **Warning**: Always create a full database backup before migrating content.
> While the plugin stores original content in post meta for rollback,
> a complete backup is recommended.

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

## Rollback Migrations

Restore posts to their original content:

```bash
# Rollback a single post
wp djot rollback --post-id=123

# Rollback all migrated posts
wp djot rollback --all
```

## Migration Status

View migration statistics:

```bash
wp djot status
```

Shows count of migrated posts, pending posts, and complexity distribution.

## Comment Migration

Migrate comments similarly to posts:

```bash
# Analyze all comments
wp djot analyze-comments

# Analyze comments for a specific post
wp djot analyze-comments --post-id=123

# Migrate comments (dry run first)
wp djot migrate-comments --dry-run

# Migrate a specific comment
wp djot migrate-comments --comment-id=456

# Migrate all comments for a post
wp djot migrate-comments --post-id=123

# Force migration of complex comments
wp djot migrate-comments --force

# Rollback a comment
wp djot rollback-comments --comment-id=456

# Rollback all migrated comments
wp djot rollback-comments --all
```
