<?php

declare(strict_types=1);

/**
 * Uninstall script for WP Djot.
 *
 * This file is called when the plugin is uninstalled (deleted) from WordPress.
 *
 * @package WpDjot
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to uninstall script

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('wpdjot_settings');

// For multisite, delete options from all sites
if (is_multisite()) {
    $sites = get_sites(['fields' => 'ids']);
    foreach ($sites as $siteId) {
        switch_to_blog($siteId);
        delete_option('wpdjot_settings');
        restore_current_blog();
    }
}
