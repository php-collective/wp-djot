<?php
/**
 * Plugin Name: Djot Markup
 * Plugin URI: https://wordpress.org/plugins/djot-markup/
 * Description: <a href="https://djot.net/" target="_blank">Djot</a> markup language support for WordPress – a modern, cleaner alternative to Markdown with syntax highlighting. Convert Djot syntax to HTML in posts, pages, and comments.
 * Version: 1.4.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Mark Scherer
 * Author URI: https://github.com/php-collective
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: djot-markup
 * Domain Path: /languages
 *
 * @package WpDjot
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPDJOT_VERSION', '1.4.0');
define('WPDJOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPDJOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPDJOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
$wpdjot_autoloader = WPDJOT_PLUGIN_DIR . 'vendor/autoload.php';
if (!file_exists($wpdjot_autoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('WP Djot: Please run "composer install" in the plugin directory.', 'djot-markup');
        echo '</p></div>';
    });

    return;
}

require_once $wpdjot_autoloader;

// Migrate settings from old option name (wp_djot_settings -> wpdjot_settings)
add_action('plugins_loaded', static function (): void {
    $oldOption = get_option('wp_djot_settings');
    if ($oldOption !== false && get_option('wpdjot_settings') === false) {
        add_option('wpdjot_settings', $oldOption);
        delete_option('wp_djot_settings');
    }
}, 5);

// Initialize plugin
add_action('plugins_loaded', static function (): void {
    $plugin = new WpDjot\Plugin();
    $plugin->init();
});

// Activation hook
register_activation_hook(__FILE__, static function (): void {
    $defaults = [
        'enable_posts' => true,
        'enable_pages' => true,
        'enable_comments' => false,
        'process_full_content' => true,
        'safe_mode' => true,
        'highlight_code' => true,
        'highlight_theme' => 'github',
        'shortcode_tag' => 'djot',
        'filter_priority' => 5,
    ];

    if (get_option('wpdjot_settings') === false) {
        add_option('wpdjot_settings', $defaults);
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, static function (): void {
    // Cleanup if needed
});

// Uninstall is handled by uninstall.php
