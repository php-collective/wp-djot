# Customizing Djot in WordPress

WP Djot provides filter hooks to customize the Djot converter for different contexts. This guide shows how theme and plugin developers can extend and customize Djot processing.

## Filter Hooks

### `wpdjot_converter`

Modify the converter instance before conversion. This is the main hook for adding custom render handlers and patterns.

```php
add_filter('wpdjot_converter', function(Djot\DjotConverter $converter, string $context): Djot\DjotConverter {
    // $context is 'article' or 'comment'
    // Add your customizations here
    return $converter;
}, 10, 2);
```

### `wpdjot_converter_{post_type}`

Customize the converter for a specific post type only.

```php
add_filter('wpdjot_converter_product', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    // Customization for WooCommerce products only
    return $converter;
});
```

## Built-in Customizations

The plugin includes these customizations out of the box for semantic HTML elements:

### Abbreviations (`abbr`)

Spans with an `abbr` attribute are automatically rendered as `<abbr>` elements:

**Djot syntax:**
```
[CSS]{abbr="Cascading Style Sheets"} is used for styling.
[HTML]{abbr="HyperText Markup Language" .tech-term} is the foundation.
```

**Renders as:**
```html
<abbr title="Cascading Style Sheets">CSS</abbr> is used for styling.
<abbr title="HyperText Markup Language" class="tech-term">HTML</abbr> is the foundation.
```

### Keyboard Input (`kbd`)

Use `kbd=""` for keyboard shortcuts and user input:

**Djot syntax:**
```
Press [Ctrl+C]{kbd=""} to copy and [Ctrl+V]{kbd=""} to paste.
Use [Ctrl+Shift+P]{kbd="" .important} for the command palette.
```

**Renders as:**
```html
Press <kbd>Ctrl+C</kbd> to copy and <kbd>Ctrl+V</kbd> to paste.
Use <kbd class="important">Ctrl+Shift+P</kbd> for the command palette.
```

### Definitions (`dfn`)

Use `dfn` for terms being defined. Optionally include a title for the full definition:

**Djot syntax:**
```
A [closure]{dfn=""} is a function that captures its environment.
An [API]{dfn="Application Programming Interface"} allows programs to communicate.
```

**Renders as:**
```html
A <dfn>closure</dfn> is a function that captures its environment.
An <dfn title="Application Programming Interface">API</dfn> allows programs to communicate.
```

### Summary

| Attribute | Syntax | Output |
|-----------|--------|--------|
| `abbr="..."` | `[CSS]{abbr="Cascading Style Sheets"}` | `<abbr title="...">CSS</abbr>` |
| `kbd=""` | `[Ctrl+C]{kbd=""}` | `<kbd>Ctrl+C</kbd>` |
| `dfn=""` | `[term]{dfn=""}` | `<dfn>term</dfn>` |
| `dfn="..."` | `[API]{dfn="Full name"}` | `<dfn title="...">API</dfn>` |

Additional attributes like classes and IDs are preserved on all semantic elements.

## Render Event Handlers

The converter fires events for each element type during rendering. You can intercept these to customize output.

### External Links with Target Blank

```php
use Djot\Event\RenderEvent;
use Djot\Node\Inline\Link;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $converter->on('render.link', function (RenderEvent $event): void {
        $link = $event->getNode();
        if (!$link instanceof Link) {
            return;
        }

        $href = $link->getDestination();

        // External links open in new tab
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener noreferrer');
        }
    });

    return $converter;
});
```

### Lazy Loading Images

```php
use Djot\Event\RenderEvent;
use Djot\Node\Inline\Image;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $converter->on('render.image', function (RenderEvent $event): void {
        $image = $event->getNode();
        if (!$image instanceof Image) {
            return;
        }

        $image->setAttribute('loading', 'lazy');
        $image->setAttribute('decoding', 'async');
    });

    return $converter;
});
```

### Images as Figures with Captions

```php
use Djot\Event\RenderEvent;
use Djot\Node\Inline\Image;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $converter->on('render.image', function (RenderEvent $event): void {
        $image = $event->getNode();
        if (!$image instanceof Image) {
            return;
        }

        $src = htmlspecialchars($image->getDestination(), ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($image->getAlt(), ENT_QUOTES, 'UTF-8');

        $html = '<figure class="wpdjot-figure">';
        $html .= '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">';
        if ($alt) {
            $html .= '<figcaption>' . $alt . '</figcaption>';
        }
        $html .= '</figure>';

        $event->setHtml($html);
    });

    return $converter;
});
```

### Custom Emoji/Symbols

Convert `:name:` symbols to emoji:

```php
use Djot\Event\RenderEvent;
use Djot\Node\Inline\Symbol;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $emojis = [
        'heart' => '❤️',
        'star' => '⭐',
        'check' => '✅',
        'warning' => '⚠️',
        'fire' => '🔥',
    ];

    $converter->on('render.symbol', function (RenderEvent $event) use ($emojis): void {
        $symbol = $event->getNode();
        if (!$symbol instanceof Symbol) {
            return;
        }

        $name = $symbol->getName();
        if (isset($emojis[$name])) {
            $event->setHtml('<span class="emoji" title="' . $name . '">' . $emojis[$name] . '</span>');
        }
    });

    return $converter;
});
```

**Usage in Djot:**
```
I :heart: this :fire: feature!
```

### Admonition Divs (Note, Warning, Tip)

Style `::: note`, `::: warning`, etc. as admonition boxes:

```php
use Djot\Event\RenderEvent;
use Djot\Node\Block\Div;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $icons = [
        'note' => 'ℹ️',
        'tip' => '💡',
        'warning' => '⚠️',
        'danger' => '🚨',
    ];

    $converter->on('render.div', function (RenderEvent $event) use ($icons): void {
        $div = $event->getNode();
        if (!$div instanceof Div) {
            return;
        }

        $class = $div->getAttribute('class') ?? '';
        foreach ($icons as $type => $icon) {
            if (str_contains($class, $type)) {
                $div->setAttribute('class', 'admonition ' . $class);
                $div->setAttribute('data-icon', $icon);
                return;
            }
        }
    });

    return $converter;
});
```

**Usage in Djot:**
```
::: warning
Be careful with this operation!
:::

::: tip
Here's a helpful hint.
:::
```

### Heading Anchors

Add ID anchors to headings for linking:

```php
use Djot\Event\RenderEvent;
use Djot\Node\Block\Heading;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $converter->on('render.heading', function (RenderEvent $event): void {
        $heading = $event->getNode();
        if (!$heading instanceof Heading) {
            return;
        }

        // Extract text content
        $text = '';
        foreach ($heading->getChildren() as $child) {
            if (method_exists($child, 'getContent')) {
                $text .= $child->getContent();
            }
        }

        // Create slug
        $slug = sanitize_title($text);
        $heading->setAttribute('id', $slug);
    });

    return $converter;
});
```

## Custom Inline Patterns

Add custom syntax patterns that are processed during parsing.

### @Mentions

Convert `@username` to profile links:

```php
use Djot\Node\Inline\Link;
use Djot\Node\Inline\Text;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $parser = $converter->getParser()->getInlineParser();

    $parser->addInlinePattern('/@([a-zA-Z0-9_]+)/', function ($match, $groups, $p) {
        $username = $groups[1];

        // Link to WordPress author page
        $url = home_url('/author/' . $username);

        $link = new Link($url);
        $link->appendChild(new Text('@' . $username));
        $link->setAttribute('class', 'mention');
        return $link;
    });

    return $converter;
});
```

**Usage:**
```
Hello @john_doe, meet @jane_smith!
```

### #Hashtags

Convert `#hashtag` to tag archive links:

```php
use Djot\Node\Inline\Link;
use Djot\Node\Inline\Text;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $parser = $converter->getParser()->getInlineParser();

    $parser->addInlinePattern('/#([a-zA-Z][a-zA-Z0-9_]*)/', function ($match, $groups, $p) {
        $tag = $groups[1];

        // Link to WordPress tag archive
        $url = get_tag_link(get_term_by('name', $tag, 'post_tag'));
        if (!$url) {
            $url = home_url('/tag/' . strtolower($tag));
        }

        $link = new Link($url);
        $link->appendChild(new Text('#' . $tag));
        $link->setAttribute('class', 'hashtag');
        return $link;
    });

    return $converter;
});
```

### Wiki Links

Support `wiki:` URL scheme in links:

```php
use Djot\Event\RenderEvent;

add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $converter->on('render.link', function (RenderEvent $event): void {
        $link = $event->getNode();
        $url = $link->getDestination() ?? '';

        if (str_starts_with($url, 'wiki:')) {
            $target = substr($url, 5);

            // If empty, use link text as target
            if ($target === '') {
                $text = '';
                foreach ($link->getChildren() as $child) {
                    if ($child instanceof \Djot\Node\Inline\Text) {
                        $text .= $child->getContent();
                    }
                }
                $target = $text;
            }

            $slug = sanitize_title($target);
            $link->setDestination(home_url('/wiki/' . $slug));
            $link->setAttribute('class', 'wikilink');
        }
    });

    return $converter;
});
```

**Usage:**
```
See [Home Page](wiki:) and [the docs](wiki:Documentation).
```

## Context-Specific Customization

### Different Settings for Posts vs Comments

```php
add_filter('wpdjot_converter', function(Djot\DjotConverter $converter, string $context): Djot\DjotConverter {
    if ($context === 'comment') {
        // Comments: add nofollow to all links
        $converter->on('render.link', function (RenderEvent $event): void {
            $event->getNode()->setAttribute('rel', 'nofollow ugc');
        });
    } else {
        // Posts: external links open in new tab
        $converter->on('render.link', function (RenderEvent $event): void {
            $href = $event->getNode()->getDestination();
            if (str_starts_with($href, 'http')) {
                $event->getNode()->setAttribute('target', '_blank');
            }
        });
    }

    return $converter;
}, 10, 2);
```

### Per-User Customization

```php
add_filter('wpdjot_converter', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    // Only allow images for users who can upload files
    if (!current_user_can('upload_files')) {
        $converter->on('render.image', function (RenderEvent $event): void {
            $alt = $event->getNode()->getAlt();
            $event->setHtml('[Image: ' . htmlspecialchars($alt) . ']');
        });
    }

    return $converter;
});
```

## Complete Example: Forum Plugin

A comprehensive example for a forum plugin:

```php
<?php
/**
 * Plugin Name: My Forum Djot Customizations
 */

add_filter('wpdjot_converter_forum_topic', function(Djot\DjotConverter $converter): Djot\DjotConverter {
    $parser = $converter->getParser()->getInlineParser();

    // @mentions link to user profiles
    $parser->addInlinePattern('/@([a-zA-Z0-9_]+)/', function ($match, $groups, $p) {
        $username = $groups[1];
        $user = get_user_by('login', $username);

        if ($user) {
            $link = new \Djot\Node\Inline\Link(get_author_posts_url($user->ID));
            $link->appendChild(new \Djot\Node\Inline\Text('@' . $username));
            $link->setAttribute('class', 'mention');
            return $link;
        }

        // User not found - just return text
        return new \Djot\Node\Inline\Text('@' . $username);
    });

    // External links get nofollow and open in new tab
    $converter->on('render.link', function (\Djot\Event\RenderEvent $event): void {
        $link = $event->getNode();
        $href = $link->getDestination();

        if (str_starts_with($href, 'http')) {
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'nofollow noopener');
        }
    });

    // Lazy load images
    $converter->on('render.image', function (\Djot\Event\RenderEvent $event): void {
        $event->getNode()->setAttribute('loading', 'lazy');
    });

    // Style admonitions
    $converter->on('render.div', function (\Djot\Event\RenderEvent $event): void {
        $div = $event->getNode();
        $class = $div->getAttribute('class') ?? '';

        if (preg_match('/\b(note|tip|warning|danger)\b/', $class, $m)) {
            $div->setAttribute('class', 'admonition admonition-' . $m[1] . ' ' . $class);
        }
    });

    return $converter;
});
```

## Available Event Types

### Inline Elements

| Event | Node Class | Description |
|-------|------------|-------------|
| `render.text` | `Text` | Plain text |
| `render.emphasis` | `Emphasis` | `_italic_` |
| `render.strong` | `Strong` | `*bold*` |
| `render.code` | `Code` | `` `code` `` |
| `render.link` | `Link` | `[text](url)` |
| `render.image` | `Image` | `![alt](url)` |
| `render.footnote_ref` | `FootnoteRef` | `[^1]` |
| `render.symbol` | `Symbol` | `:name:` |
| `render.math` | `Math` | `$...$` |
| `render.superscript` | `Superscript` | `x^2^` |
| `render.subscript` | `Subscript` | `H~2~O` |
| `render.mark` | `Mark` | `{=highlight=}` |
| `render.insert` | `Insert` | `{+insert+}` |
| `render.delete` | `Delete` | `{-delete-}` |
| `render.span` | `Span` | `[text]{.class}` |
| `render.raw_inline` | `RawInline` | `` `<b>`{=html} `` |

### Block Elements

| Event | Node Class | Description |
|-------|------------|-------------|
| `render.paragraph` | `Paragraph` | Regular paragraph |
| `render.heading` | `Heading` | `# Heading` |
| `render.code_block` | `CodeBlock` | Fenced code block |
| `render.blockquote` | `Blockquote` | `> quote` |
| `render.list` | `BulletList`/`OrderedList` | Lists |
| `render.list_item` | `ListItem` | List items |
| `render.table` | `Table` | Tables |
| `render.div` | `Div` | `::: div :::` |
| `render.footnote` | `Footnote` | Footnote definition |
| `render.raw_block` | `RawBlock` | ``` =html block |
| `render.thematic_break` | `ThematicBreak` | `---` |

## See Also

- [Content Profiles](profiles.md) - Configure which elements are allowed
- [Hooks and Filters](hooks.md) - Other available hooks
- [djot-php Cookbook](https://github.com/php-collective/djot-php/blob/main/docs/cookbook.md) - More examples
