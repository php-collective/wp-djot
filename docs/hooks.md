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

### wp_djot_converter

Customize the Djot converter instance. Useful for adding custom render handlers.

```php
add_filter('wp_djot_converter', function(\Djot\DjotConverter $converter, string $context): \Djot\DjotConverter {
    // $context is 'article' or 'comment'
    // Add custom render handlers, modify settings, etc.
    return $converter;
}, 10, 2);
```

### wp_djot_converter_{post_type}

Post-type specific converter customization (e.g., `wp_djot_converter_post`, `wp_djot_converter_page`).

```php
add_filter('wp_djot_converter_page', function(\Djot\DjotConverter $converter, string $context): \Djot\DjotConverter {
    // Custom handling for pages only
    return $converter;
}, 10, 2);
```

### wp_djot_htmlpurifier_config

Customize HTMLPurifier configuration for comment sanitization. Only applies when HTMLPurifier is installed.

```php
add_filter('wp_djot_htmlpurifier_config', function(\HTMLPurifier_Config $config): \HTMLPurifier_Config {
    // Allow additional HTML elements/attributes
    $config->set('HTML.Allowed', 'p,br,strong,em,a[href|title|rel],iframe[src|width|height],...');
    return $config;
});
```

### wp_djot_allowed_html

Customize allowed HTML tags for wp_kses sanitization (fallback when HTMLPurifier is not installed).

```php
add_filter('wp_djot_allowed_html', function(array $allowed): array {
    // Allow iframes for embedded content
    $allowed['iframe'] = [
        'src' => true,
        'width' => true,
        'height' => true,
        'frameborder' => true,
        'allowfullscreen' => true,
    ];
    return $allowed;
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
