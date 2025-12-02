# Hooks and Filters

## Filters

### wp_djot_pre_convert

Modify Djot content before conversion to HTML.

```php
add_filter('wp_djot_pre_convert', function(string $djot): string {
    // Modify $djot before conversion
    return $djot;
});
```

### wp_djot_post_convert

Modify HTML after conversion from Djot.

```php
add_filter('wp_djot_post_convert', function(string $html): string {
    // Modify $html after conversion
    return $html;
});
```

## Examples

### Add Custom CSS Class to All Code Blocks

```php
add_filter('wp_djot_post_convert', function(string $html): string {
    return str_replace('<pre><code', '<pre class="my-code"><code', $html);
});
```

### Replace Custom Shortcodes Before Conversion

```php
add_filter('wp_djot_pre_convert', function(string $djot): string {
    // Replace {{date}} with current date
    return str_replace('{{date}}', date('Y-m-d'), $djot);
});
```

### Add Wrapper Div for Styling

```php
add_filter('wp_djot_post_convert', function(string $html): string {
    return '<div class="djot-wrapper">' . $html . '</div>';
});
```
