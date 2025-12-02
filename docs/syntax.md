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

## More Information

For complete syntax documentation, visit [djot.net](https://djot.net/).
