# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-12

### Added

- Initial release
- Full Djot markup syntax support via php-collective/djot
- Configurable content profiles for posts/pages and comments
  - **Full**: All features enabled (trusted content only)
  - **Article**: All formatting, no raw HTML (default for posts)
  - **Comment**: Basic formatting, nofollow links (default for comments)
  - **Minimal**: Text formatting and lists only
- Profile settings in admin under Security Settings
- `[djot]...[/djot]` shortcode for posts and pages
- `{djot}...{/djot}` curly brace syntax for content filtering
- Admin settings page under Settings → WP Djot
- Content settings: enable/disable for posts, pages, comments
- Security settings: safe mode for XSS protection
- Syntax highlighting with highlight.js (12+ themes)
- Advanced settings: custom shortcode tag, filter priority
- Template tags: `wp_djot_to_html()`, `wp_djot_the()`, `wp_djot_has()`
- WordPress filters: `wp_djot_pre_convert`, `wp_djot_post_convert`
- Dark mode CSS support
- Multisite compatible
- Clean uninstall (removes all options)
- Gutenberg block editor support with live preview
  - Native "Djot" block for the block editor
  - REST API endpoint for server-side preview rendering
  - Toggle between edit and preview modes
- WP-CLI migration commands for converting existing content to Djot
  - `wp djot analyze` - Analyze posts for migration complexity
  - `wp djot migrate` - Migrate posts from HTML/Markdown to Djot
  - `wp djot rollback` - Restore posts to original content
  - `wp djot status` - View migration statistics
- Content migration from HTML to Djot (using djot-php HtmlToDjot converter)
- Content migration from Markdown to Djot (using djot-php MarkdownToDjot converter)
- Automatic backup of original content before migration
- Complexity analysis (none, low, medium, high) for migration planning
- Dry-run mode with diff preview
- Batch processing with progress bar
- Preservation of WordPress shortcodes during migration
- Preservation of Gutenberg blocks during migration
- Documentation in `docs/` folder (profiles, WP-CLI, hooks, syntax)

### Security

- Safe mode enabled by default for untrusted content
- Comments always processed with safe mode
- XSS protection via djot-php safe mode

[Unreleased]: https://github.com/php-collective/wp-djot/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/php-collective/wp-djot/releases/tag/v1.0.0
