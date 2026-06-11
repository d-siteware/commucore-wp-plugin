<?php

/**
 * Plugin Name:       CommuCore
 * Plugin URI:        https://commu-core.com
 * Description:       Veranstaltungen und Beiträge aus CommuCore auf deiner WordPress-Seite einbinden.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            CommuCore
 * Author URI:        https://commu-core.com
 * License:           GPL v2 or later
 * Text Domain:       commucore
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('COMMUCORE_VERSION', '0.1.0');
define('COMMUCORE_PATH', plugin_dir_path(__FILE__));
define('COMMUCORE_URL', plugin_dir_url(__FILE__));
define('COMMUCORE_OPTION_KEY', 'commucore_settings');
define('COMMUCORE_SSL_VERIFY', ! (defined('WP_DEBUG') && WP_DEBUG));

require_once COMMUCORE_PATH . 'includes/functions.php';
require_once COMMUCORE_PATH . 'includes/class-settings.php';
require_once COMMUCORE_PATH . 'includes/class-api-client.php';
require_once COMMUCORE_PATH . 'includes/class-shortcodes.php';
require_once COMMUCORE_PATH . 'includes/class-assets.php';

register_activation_hook(__FILE__, 'commucore_activate');
register_deactivation_hook(__FILE__, 'commucore_deactivate');

function commucore_activate(): void
{
    commucore_create_pages();
    commucore_register_rewrite_rules();
    flush_rewrite_rules();
}

function commucore_deactivate(): void
{
    flush_rewrite_rules();
}

function commucore_create_pages(): void
{
    $options      = get_option(COMMUCORE_OPTION_KEY, []);
    $list_slug    = $options['events_list_slug']   ?? 'veranstaltungen';
    $detail_slug  = $options['events_detail_slug'] ?? 'veranstaltung';

    // Listenseite
    $list_id = get_option('commucore_events_list_page_id', 0);
    if (! $list_id || ! get_post($list_id)) {
        $list_id = wp_insert_post([
            'post_title'   => __('Veranstaltungen', 'commucore'),
            'post_name'    => $list_slug,
            'post_content' => '[commucore_events]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        if (! is_wp_error($list_id)) {
            update_option('commucore_events_list_page_id', $list_id);
        }
    }

    // Detailseite
    $detail_id = get_option('commucore_events_detail_page_id', 0);
    if (! $detail_id || ! get_post($detail_id)) {
        $detail_id = wp_insert_post([
            'post_title'   => __('Veranstaltung', 'commucore'),
            'post_name'    => $detail_slug,
            'post_content' => '[commucore_event_single]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        if (! is_wp_error($detail_id)) {
            update_option('commucore_events_detail_page_id', $detail_id);
        }
    }
}

function commucore_register_rewrite_rules(): void
{
    $options     = get_option(COMMUCORE_OPTION_KEY, []);
    $detail_slug = $options['events_detail_slug'] ?? 'veranstaltung';

    add_rewrite_rule(
        $detail_slug . '/([0-9]+)/?$',
        'index.php?pagename=' . $detail_slug . '&event_id=$matches[1]',
        'top'
    );
}

add_action('init', 'commucore_register_rewrite_rules');
add_filter('query_vars', function (array $vars): array {
    $vars[] = 'event_id';
    return $vars;
});

new CommuCore_Settings();
new CommuCore_Shortcodes();
new CommuCore_Assets();
