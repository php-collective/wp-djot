# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.5.10] - 2026-03-31

### Added

- Visual diff modal shows exactly what changes when entering visual mode (replaces generic warning)
- Visual editor round-trip tests (vitest)

### Fixed

- Visual editor round-trip for `[[Heading]]` references
- Visual editor round-trip for mermaid code blocks
- Visual editor round-trip for code groups (`::: code-group`)
- Visual editor round-trip for tabs (`:::: tabs`)
- Combined `{dfn abbr="..."}` attributes now serialize correctly

## [1.5.9] - 2026-03-29

### Added

- Mermaid diagram preview rendering in block editor

### Fixed

- Visual editor round-trip for `kbd`, `abbr`, `dfn` semantic spans
- Visual editor round-trip for definition lists

## [1.5.8] - 2026-03-28

### Added

- Mermaid diagram support via `mermaid_enabled` setting
- CodeGroupExtension for tabbed code blocks (`::: code-group`)
- TabsExtension for tabbed content sections (`:::: tabs`)
- HeadingReferenceExtension for `[[Heading Text]]` intra-document links

### Changed

- Upgraded to djot-php 0.1.22
- Switched to upstream HeadingLevelShiftExtension (replaces custom output transformer)
- Switched to upstream SemanticSpanExtension for `kbd`/`abbr`/`dfn` attributes
- Simplified mermaid script loading (always enqueue when enabled)

### Fixed

- wp_kses now allows `id`/`name` on radio inputs and `for` on labels (required for tabs/code-groups)

## [1.5.7] - 2026-03-25

### Added

- Experimental visual editor (WYSIWYG) for Djot blocks powered by Tiptap
- Experimental settings section with visual editor mode options (disabled/enabled/enabled as default)

### Fixed

- Visual editor round-trip preservation for all Djot elements
- Different list types (bullet/ordered/task) colliding without blank lines between them

## [1.5.6] - 2026-03-22

### Fixed

- Task list checkboxes stripped by wp_kses_post
- Dark mode TOC text color missing

## [1.5.5] - 2026-03-16

### Added

- Djot syntax highlighting for code blocks via [djot-grammars](https://github.com/php-collective/djot-grammars)

### Fixed

- Code block filename markers leaking into output

### Changed

- Updated php-collective/djot to 0.1.17

## [1.5.4] - 2026-03-05

### Fixed

- Torchlight line highlighting CSS selectors (annotated lines lose `line` class)

### Changed

- Removed opinionated visual styling (blockquotes, links, tables, hr, images, captions, del/ins) - let themes handle it

## [1.5.3] - 2026-03-04

### Fixed

- PHP 8.2 trait constants compatibility (Rector downgrade)

## [1.5.2] - 2026-03-04

### Fixed

- More aggressive PHP 8.0 downgrade for WordPress.org compatibility

## [1.5.1] - 2026-03-04

### Fixed

- Deployment: remove missing dev dependencies from distribution

## [1.5.0] - 2026-03-03

### Added

- Torchlight Engine integration for advanced code block features
- Inline code annotations: highlight, focus, diff (+/-), and ranges
- Code Annotations panel in block editor sidebar for quick annotation insertion

### Changed

- Code highlighting now powered by Torchlight/Phiki instead of custom CSS/JS
- Simplified code block CSS (Torchlight handles syntax and annotation styling)
- Updated documentation with Torchlight annotation syntax

### Removed

- highlight.js dependency (replaced by server-side Torchlight Engine)
- Code Highlighting settings section (no longer needed)

## [1.4.3] - 2026-02-10

### Fixed

- Vertical spacing in highlighted code blocks

## [1.4.2] - 2026-02-10

### Added

- Code block line numbers (`#` after language, e.g., ``` php #)
- Line number offset (`#=N` to start at specific line)
- Code block line highlighting (`{lines}` syntax, e.g., ``` php {2,4-5})

### Fixed

- Legacy `wp-djot/djot` blocks now editable in Gutenberg again (migrates to `wpdjot/djot` on save)

## [1.4.1] - 2026-02-05

### Fixed

- Exclude Table of Contents and heading permalinks from post excerpts on archive pages

## [1.4.0] - 2026-02-05

### Added

- Automatic Table of Contents generation from headings using djot-php's `TableOfContentsExtension`
- TOC settings: enable/disable, position (top/bottom), min/max heading levels, list type (ul/ol)
- TOC styling with light and dark mode support
- Heading permalinks: clickable `#` symbols on headings, shown on hover, click copies URL to clipboard
- Permalink setting in the Table of Contents section of admin settings
- Locale-aware smart quotes via `SmartQuotesExtension` (20 locales + Auto from site language)
- Smart Quotes setting in Rendering section of admin settings

### Changed

- Bumped `php-collective/djot` dependency to `^0.1.13`

## [1.3.1] - 2026-02-01

### Fixed

- Text domain changed from `djot-markup-for-wp` to `djot-markup` to match plugin slug
- HTML output now properly escaped with `wp_kses_post()`/`wp_kses()` in block rendering and template tags
- Block wrapper attributes properly escaped for Plugin Check compliance
- Non-permitted files (vendor binaries, fuzz dictionaries) excluded from distribution
- Added `markmarkmark` to Contributors list

### Added

- Build script (`scripts/build.sh`) for generating WordPress.org distribution zip

## [1.1.5] - 2025-12-15

### Fixed

- PHPStan strict comparison fix for dfn attribute handling

## [1.1.4] - 2025-12-09

### Fixed

- Dark mode table readability (explicit background colors for all cells)

## [1.1.3] - 2025-12-09

### Added

- Definition list tool in block editor sidebar (insert multiple term/definition pairs)
- Video embed tool in block editor sidebar (YouTube, Vimeo, etc. via WordPress oEmbed)
- "None" profile option for posts/pages (no restrictions, allows raw HTML)
- `wp_djot_converter` filter for customizing the Djot converter instance
- `wp_djot_converter_{post_type}` filter for post-type specific customization
- `wp_djot_htmlpurifier_config` filter for customizing HTMLPurifier configuration
- `wp_djot_allowed_html` filter for customizing wp_kses allowed tags
- Figure/caption support for images, blockquotes, and tables
- Definition list styling in editor preview and frontend
- Video embed syntax: `![caption](url){video}` with optional width/height attributes

### Changed

- Upgrade to djot-php ^0.1.7 (fixes blank line handling, adds Figure/Caption support)
- Import Markdown/HTML now converts semantic elements (kbd, abbr, dfn) to Djot span syntax
- Removed redundant "Allow Raw HTML" setting (use None or Full profile instead)
- Improved editor preview styles (figures, captions, definition lists)

### Fixed

- Link/image insertion now correctly replaces selected text

## [1.1.2] - 2025-12-07

### Added

- Import HTML tool in block editor sidebar (converts HTML to Djot)
- Keyboard shortcuts section in syntax documentation

### Changed

- Upgrade to djot-php ^0.1.5
- Toolbar buttons now insert markers only, placing cursor between them for immediate typing
- Block markup (headings, quotes, lists, code blocks) now inserts prefix without placeholder text
- Simplified semantic attribute syntax (`{kbd}` instead of `{kbd=""}`)
- Frontend scripts now use `defer` loading strategy for better performance
- Updated syntax documentation with semantic elements table

## [1.1.1] - 2025-12-06

### Added

- Rendering Settings section in admin
- Markdown Compatibility mode for users migrating from Markdown (significant newlines)
- Soft break mode settings for posts/pages and comments (invisible, space, or visible `<br>`)
- Interactive task list tool in block editor sidebar
- Scroll sync when switching to preview mode in block editor

### Changed

- Upgrade to djot-php ^0.1.4
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

[Unreleased]: https://github.com/php-collective/wp-djot/compare/1.5.8...HEAD
[1.5.8]: https://github.com/php-collective/wp-djot/compare/1.5.7...1.5.8
[1.5.7]: https://github.com/php-collective/wp-djot/compare/1.5.6...1.5.7
[1.5.6]: https://github.com/php-collective/wp-djot/compare/1.5.5...1.5.6
[1.5.5]: https://github.com/php-collective/wp-djot/compare/1.5.4...1.5.5
[1.5.4]: https://github.com/php-collective/wp-djot/compare/1.5.3...1.5.4
[1.5.3]: https://github.com/php-collective/wp-djot/compare/1.5.2...1.5.3
[1.5.2]: https://github.com/php-collective/wp-djot/compare/1.5.1...1.5.2
[1.5.1]: https://github.com/php-collective/wp-djot/compare/1.5.0...1.5.1
[1.5.0]: https://github.com/php-collective/wp-djot/compare/1.4.3...1.5.0
[1.4.3]: https://github.com/php-collective/wp-djot/compare/1.4.2...1.4.3
[1.4.2]: https://github.com/php-collective/wp-djot/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/php-collective/wp-djot/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/php-collective/wp-djot/compare/1.3.1...1.4.0
[1.3.1]: https://github.com/php-collective/wp-djot/compare/1.1.5...1.3.1
[1.1.5]: https://github.com/php-collective/wp-djot/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/php-collective/wp-djot/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/php-collective/wp-djot/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/php-collective/wp-djot/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/php-collective/wp-djot/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/php-collective/wp-djot/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/php-collective/wp-djot/releases/tag/1.0.0
