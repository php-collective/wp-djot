# WP-Djot Project Rules

## Creating Djot Blocks via WP-CLI

Djot blocks store content as JSON-encoded attribute, NOT as inner HTML.

**Correct format:**
```html
<!-- wp:wpdjot/djot {"content":"# Heading\n\nParagraph text"} /-->
```

**To create via WP-CLI**, use PHP to JSON-encode the content:

```bash
cat > /tmp/create-block.php << 'PHPEOF'
<?php
$content = 'Your djot content here...';
$json = json_encode(['content' => $content], JSON_UNESCAPED_UNICODE);
echo '<!-- wp:wpdjot/djot ' . $json . ' /-->';
PHPEOF

ddev exec 'cd /var/www/html/wordpress && php /path/to/create-block.php > block.html'
ddev exec 'cd /var/www/html/wordpress && wp post update POST_ID --post_content="$(cat block.html)"'
```

**DO NOT** use this format (it won't work):
```html
<!-- wp:wpdjot/djot -->
<div class="wp-block-wpdjot-djot">Content here</div>
<!-- /wp:wpdjot/djot -->
```
