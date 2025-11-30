# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2024-XX-XX

### Added

- Initial release
- Full Djot markup syntax support via php-collective/djot
- `[djot]...[/djot]` shortcode for posts and pages
- `{djot}...{/djot}` curly brace syntax for content filtering
- Admin settings page under Settings → WP Djot
- Content settings: enable/disable for posts, pages, comments
- Security settings: safe mode for XSS protection
- Syntax highlighting with highlight.js (12+ themes)
- Advanced settings: custom shortcode tag, filter priority
- Template tags: `djot_to_html()`, `the_djot()`, `has_djot()`
- WordPress filters: `wp_djot_pre_convert`, `wp_djot_post_convert`
- Dark mode CSS support
- Multisite compatible
- Clean uninstall (removes all options)

### Security

- Safe mode enabled by default for untrusted content
- Comments always processed with safe mode
- XSS protection via djot-php safe mode

[Unreleased]: https://github.com/php-collective/wp-djot/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/php-collective/wp-djot/releases/tag/v1.0.0
