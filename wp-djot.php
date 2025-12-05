<?php
/**
 * Plugin Name: Djot Markup for WP
 * Plugin URI: https://github.com/php-collective/wp-djot
 * Description: <a href="https://djot.net/" target="_blank">Djot</a> markup language support for WordPress – a modern, cleaner alternative to Markdown with syntax highlighting. Convert Djot syntax to HTML in posts, pages, and comments.
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Mark Scherer
 * Author URI: https://github.com/php-collective
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: djot-markup-for-wp
 * Domain Path: /languages
 *
 * @package WpDjot
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP_DJOT_ is our plugin prefix
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this file

// Plugin constants
define('WP_DJOT_VERSION', '1.1.0');
define('WP_DJOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DJOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_DJOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
$autoloader = WP_DJOT_PLUGIN_DIR . 'vendor/autoload.php';
if (!file_exists($autoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('WP Djot: Please run "composer install" in the plugin directory.', 'djot-markup-for-wp');
        echo '</p></div>';
    });

    return;
}

require_once $autoloader;

// Initialize pluginhttps://dereuromark.ddev.site:33003/2025/11/27/cakephp-file-management-solution/#-2-comments-
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

    if (get_option('wp_djot_settings') === false) {
        add_option('wp_djot_settings', $defaults);
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, static function (): void {
    // Cleanup if needed
});

// Uninstall is handled by uninstall.php
