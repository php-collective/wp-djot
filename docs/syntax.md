# Djot Syntax Quick Reference

## Basic Formatting

| Syntax | Result |
|--------|--------|
| `_emphasis_` | _emphasis_ (italic) |
| `*strong*` | **strong** (bold) |
| `` `code` `` | `code` |
| `[link](url)` | [link](url) |
| `![alt](image.jpg)` | Image |
| `# Heading` | Heading (h1-h6) |
| `> blockquote` | Blockquote |
| `- item` | Unordered list |
| `1. item` | Ordered list |

## Djot-Specific Features

These features are unique to Djot (not in Markdown):

| Syntax | Result |
|--------|--------|
| `x^2^` | Superscript (x²) |
| `H~2~O` | Subscript (H₂O) |
| `{=marked=}` | Highlighted text |
| `{+inserted+}` | Inserted text |
| `{-deleted-}` | Deleted text |
| `[text]{.class}` | Span with CSS class |
| `::: note ... :::` | Div with CSS class |
| `"quotes" -- Pro` | Smart typography |

## Key Differences from Markdown

> **Note:** Djot uses different emphasis syntax than Markdown:
> - `_underscores_` for emphasis (italic)
> - `*single asterisks*` for strong (bold)
>
> This is the opposite of Markdown's convention.

## Code Blocks

Fenced code blocks with optional language:

````
``` php
<?php
echo "Hello, World!";
```
````

### Line Numbers

Add `#` after the language to display line numbers:

````
``` php #
$foo = 1;
$bar = 2;
$baz = 3;
```
````

Start line numbers at a specific number with `#=N`:

````
``` php #=9
// This starts at line 9
$offset = 9;
$next = 10;
```
````

### Line Highlighting

Highlight specific lines with `{lines}`:

````
``` php {2,4}
$line1 = "normal";
$line2 = "highlighted";
$line3 = "normal";
$line4 = "highlighted";
```
````

Use ranges for consecutive lines:

````
``` php {2,4-6}
// Line 1 - normal
// Line 2 - highlighted
// Line 3 - normal
// Lines 4-6 - highlighted
// ...
```
````

### Combined

Line numbers and highlighting can be combined:

````
``` php # {2,4-5}
function example() {
    $highlighted = true;
    $normal = false;
    $alsoHighlighted = true;
    $andThisToo = true;
    return $result;
}
```
````

Or with a starting offset:

````
``` php #=10 {11,13}
// Line 10
// Line 11 - highlighted
// Line 12
// Line 13 - highlighted
```
````

## Links

```
[Link text](https://example.com)
[Link with title](https://example.com "Title")
<https://example.com>  (autolink)
```

## Images

```
![Alt text](image.jpg)
![Alt text](image.jpg "Title")
```

## Tables

```
| Header 1 | Header 2 |
|----------|----------|
| Cell 1   | Cell 2   |
| Cell 3   | Cell 4   |
```

## Footnotes

```
Here is a footnote reference[^1].

[^1]: Here is the footnote content.
```

## Raw HTML (Full profile only)

````
``` =html
<div class="custom">
  Raw HTML content
</div>
```
````

## Custom Attributes

Djot supports adding custom attributes to spans:

| Djot Syntax | HTML Output |
|-------------|-------------|
| `[text]{.class}` | `<span class="class">text</span>` |
| `[text]{#id}` | `<span id="id">text</span>` |
| `[text]{key="value"}` | `<span key="value">text</span>` |

## Semantic Elements (WP Djot)

WP Djot adds support for semantic HTML elements via special attributes:

| Djot Syntax | HTML Output | Use Case |
|-------------|-------------|----------|
| `[CSS]{abbr="Cascading Style Sheets"}` | `<abbr title="...">CSS</abbr>` | Abbreviation |
| `[Ctrl+C]{kbd}` | `<kbd>Ctrl+C</kbd>` | Keyboard input |
| `[term]{dfn}` | `<dfn>term</dfn>` | Definition term |
| `[term]{dfn="explanation"}` | `<dfn title="...">term</dfn>` | Definition with title |

These can be combined: `[CSS]{abbr="Cascading Style Sheets" dfn}` renders as `<dfn><abbr title="...">CSS</abbr></dfn>`.

## Keyboard Shortcuts (Editor)

| Shortcut | Action |
|----------|--------|
| `Ctrl+B` | Bold |
| `Ctrl+I` | Italic |
| `Ctrl+K` | Insert link |
| `Ctrl+Shift+C` | Inline code |
| `Escape` | Exit preview mode |

## More Information

For complete syntax documentation, visit [djot.net](https://djot.net/).
