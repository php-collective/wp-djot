# Djot Markup for WP

[![CI](https://github.com/php-collective/wp-djot/actions/workflows/ci.yml/badge.svg)](https://github.com/php-collective/wp-djot/actions/workflows/ci.yml)
[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/djot-markup-for-wp)](https://wordpress.org/plugins/djot-markup-for-wp/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/djot-markup-for-wp)](https://wordpress.org/plugins/djot-markup-for-wp/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/stars/djot-markup-for-wp)](https://wordpress.org/plugins/djot-markup-for-wp/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net)
[![WordPress 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)

A WordPress plugin for [Djot](https://djot.net/) markup language support. Convert Djot syntax to HTML in posts, pages, and comments.

## What is Djot?

Djot is a light markup syntax created by John MacFarlane (creator of CommonMark and Pandoc). It aims to be a successor to Markdown with cleaner syntax and more consistent parsing rules.

[![Watch the demo video](https://img.youtube.com/vi/z0Nsjzp0gnw/mqdefault.jpg)](https://www.youtube.com/watch?v=z0Nsjzp0gnw)

## Features

- **Full Djot Support**: Headings, emphasis, links, images, code blocks, tables, footnotes, and more
- **Block Editor Support**: Native Gutenberg block for writing Djot with live preview
- **Shortcode Support**: Use `[djot]...[/djot]` in your content
- **Content Profiles**: Configurable feature restrictions (full, article, comment, minimal)
- **Safe Mode**: XSS protection for untrusted content
- **Syntax Highlighting**: Built-in highlight.js integration with multiple themes
- **WP-CLI Migration**: Migrate existing HTML/Markdown content to Djot

## Example

````
# Welcome to My Blog

This is _emphasized_ and this is *strong*.

Here's a [link to Djot](https://djot.net/) and some `inline code`.

- First item
- Second item
- Third item

> A blockquote with some wisdom.

And a code block:

``` php
<?php
echo "Hello, World!";
```
````

**Renders as:**

> # Welcome to My Blog
>
> This is _emphasized_ and this is **strong**.
>
> Here's a [link to Djot](https://djot.net/) and some `inline code`.
>
> - First item
> - Second item
> - Third item
>
> > A blockquote with some wisdom.
>
> ```php
> <?php
> echo "Hello, World!";
> ```

## Requirements

- PHP 8.2 or higher
- WordPress 6.0 or higher

## Installation

### From WordPress.org

Search for "Djot Markup" in the WordPress plugin directory, or visit:
[wordpress.org/plugins/djot-markup-for-wp](https://wordpress.org/plugins/djot-markup-for-wp/)

### From GitHub

```bash
cd wp-content/plugins
git clone https://github.com/php-collective/wp-djot.git
cd wp-djot
composer install --no-dev
```

## Documentation

- [Usage & Configuration](docs/README.md) - Getting started guide
- [Content Profiles](docs/profiles.md) - Configure feature restrictions
- [Customization](docs/customization.md) - Custom patterns, @mentions, render handlers
- [WP-CLI Commands](docs/wp-cli.md) - Migrate existing content
- [Hooks and Filters](docs/hooks.md) - Customize plugin behavior
- [Djot Syntax](docs/syntax.md) - Quick reference

For complete Djot syntax documentation, visit [djot.net](https://djot.net/).

## See Also

- [Djot](https://djot.net/) - Official Djot website with syntax reference and playground
- [jgm/djot](https://github.com/jgm/djot) - Reference implementation in JavaScript by John MacFarlane
- [JetBrains IDE support](https://github.com/php-collective/djot-intellij) - Plugin for PhpStorm, IntelliJ IDEA, WebStorm, etc.
- [Djot playground](https://sandbox.dereuromark.de/sandbox/djot) - Live demo to check out how this markup language works.
 
## Credits

- [djot-php](https://github.com/php-collective/djot-php) by PHP Collective
- [highlight.js](https://highlightjs.org/) for syntax highlighting

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.
