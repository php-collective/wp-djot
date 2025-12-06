# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### [1.1.1] - 2025-12-06

### Added

- Rendering Settings section in admin
- Markdown Compatibility mode for users migrating from Markdown (significant newlines)
- Soft break mode settings for posts/pages and comments (invisible, space, or visible `<br>`)
- Interactive task list tool in block editor sidebar
- Scroll sync when switching to preview mode in block editor

### Changed

- Upgrade to djot-php 0.1.4
- Import Markdown now uses server-side djot-php converter for more accurate results

### Fixed

- Task list checkboxes now render correctly (wp_kses fix for input elements)
- Task list styling now overrides theme styles
- Completed task items now show strikethrough
- Undo/redo now works properly with individual changes (switched to PlainText component)
- ToggleControl deprecation warning fixed

## [1.1.0] - 2025-12-05

### Added

- Comment formatting toolbar with buttons for Bold, Italic, Code, Link, Quote, and Code Block
- Write/Preview tabs for comment toolbar with live Djot preview via REST API
- Syntax highlighting in comment preview using highlight.js
- Excerpt filter to render Djot content on archive pages (homepage, category, tag, etc.)
- Support for extracting Djot content from Gutenberg blocks for excerpts

### Fixed

- Archive pages now properly show excerpts with "Read more" links instead of raw Djot/block markup
- Tab hover styles now properly override theme button styles
- Quote button inserts newline before blockquote when not at start of line

## [1.0.0] - 2025-12-02

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

[Unreleased]: https://github.com/php-collective/wp-djot/compare/1.1.0...HEAD
[1.1.0]: https://github.com/php-collective/wp-djot/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/php-collective/wp-djot/releases/tag/1.0.0
