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

**IMPORTANT:** Do NOT HTML-encode special characters in the djot content. Use raw characters:
- Mermaid arrows: `-->` NOT `--&gt;`
- Less/greater than: `<` and `>` NOT `&lt;` and `&gt;`

If content is HTML-encoded before storage, it will be double-encoded during rendering, causing syntax errors (e.g., mermaid sees `--&gt;` instead of `-->`).

## Release Rules

**CRITICAL - Never modify published releases:**
- NEVER edit release notes of a published (non-draft) release
- NEVER push commits to main after a release is published without bumping the version first
- Only create/edit DRAFT releases
- If you need to add features after a release, create a NEW draft release with the next version number

**After publishing a release:**
1. Immediately bump version to next patch (e.g., 1.5.9 → 1.5.10)
2. Any new commits go toward the next release
3. Create a new draft for the next version

**CHANGELOG format:**
- Unreleased versions use `YYYY-MM-DD` as date placeholder: `## [1.5.10] - YYYY-MM-DD`
- Only replace with actual date when publishing the release

## Release Checklist

Before publishing a release:

1. **Update version numbers:**
   ```bash
   ./scripts/version.sh X.X.X
   ```
   This updates: wp-djot.php (header + constant), block.json, index.asset.php, readme.txt

2. **Update CHANGELOG.md** with release notes

3. **Run checks:**
   ```bash
   composer stan      # Static analysis
   composer cs-check  # Code style
   composer test      # Tests (if available)
   ```

4. **Commit version bump:**
   ```bash
   git add -A && git commit -m "Bump version to X.X.X"
   git push origin main
   ```

5. **Create/update GitHub release draft:**
   - Verify release notes are complete
   - Ensure tag matches version (e.g., `1.5.8`)

6. **Publish release:**
   Important: Do NOT do this without explicit command to do so!
   ```bash
   gh release edit X.X.X --repo php-collective/wp-djot --draft=false
   ```

The deploy workflow will automatically:
- Validate version consistency
- Run PHPStan
- Downgrade PHP syntax for WordPress.org compatibility
- Deploy to WordPress.org SVN
- Upload zip to GitHub release
