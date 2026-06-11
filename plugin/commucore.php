<?php

/**
 * Plugin Name:       CommuCore
 * Plugin URI:        https://commu-core.com
 * Description:       Veranstaltungen und Beiträge aus CommuCore auf deiner WordPress-Seite einbinden.
 * Version:           1.0.0
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

define('COMMUCORE_VERSION', '1.0.0');
define('COMMUCORE_PATH', plugin_dir_path(__FILE__));
define('COMMUCORE_URL', plugin_dir_url(__FILE__));
define('COMMUCORE_OPTION_KEY', 'commucore_settings');
define('COMMUCORE_SSL_VERIFY', ! (defined('WP_DEBUG') && WP_DEBUG));

require_once COMMUCORE_PATH . 'includes/functions.php';
require_once COMMUCORE_PATH . 'includes/class-settings.php';
require_once COMMUCORE_PATH . 'includes/class-api-client.php';
require_once COMMUCORE_PATH . 'includes/class-shortcodes.php';
require_once COMMUCORE_PATH . 'includes/class-assets.php';

add_action('init', function (): void {
    load_plugin_textdomain('commucore', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

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
    $posts_list_slug   = $options['posts_list_slug']   ?? 'beitraege';
    $posts_detail_slug = $options['posts_detail_slug'] ?? 'beitrag';

    $pages = [
        'commucore_events_list_page_id' => [
            'title' => __('Veranstaltungen', 'commucore'),
            'slug'  => $list_slug,
            'shortcode' => '[commucore_events]',
        ],
        'commucore_events_detail_page_id' => [
            'title' => __('Veranstaltung', 'commucore'),
            'slug'  => $detail_slug,
            'shortcode' => '[commucore_event_single]',
        ],
        'commucore_posts_list_page_id' => [
            'title' => __('Beiträge', 'commucore'),
            'slug'  => $posts_list_slug,
            'shortcode' => '[commucore_posts]',
        ],
        'commucore_posts_detail_page_id' => [
            'title' => __('Beitrag', 'commucore'),
            'slug'  => $posts_detail_slug,
            'shortcode' => '[commucore_post_single]',
        ],
    ];

    foreach ($pages as $option_key => $page) {
        $page_id = get_option($option_key, 0);

        if ($page_id && get_post($page_id)) {
            continue;
        }

        // Nach vorhandener Seite mit dem Shortcode suchen (Fallback für Migration)
        $existing = get_posts([
            'post_type'   => 'page',
            'post_status' => 'any',
            's'           => $page['shortcode'],
            'fields'      => 'ids',
        ]);
        foreach ($existing as $found_id) {
            $content = get_post_field('post_content', $found_id);
            if (trim($content) === $page['shortcode']) {
                update_option($option_key, $found_id);
                continue 2;
            }
        }

        $new_id = wp_insert_post([
            'post_title'   => $page['title'],
            'post_name'    => $page['slug'],
            'post_content' => $page['shortcode'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        if (! is_wp_error($new_id)) {
            update_option($option_key, $new_id);
        }
    }
}

function commucore_register_rewrite_rules(): void
{
    $options           = get_option(COMMUCORE_OPTION_KEY, []);
    $detail_slug       = $options['events_detail_slug'] ?? 'veranstaltung';
    $posts_detail_slug = $options['posts_detail_slug'] ?? 'beitrag';

    add_rewrite_rule(
        $detail_slug . '/([0-9]+)/?$',
        'index.php?pagename=' . $detail_slug . '&event_id=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        $posts_detail_slug . '/([0-9]+)/?$',
        'index.php?pagename=' . $posts_detail_slug . '&post_id=$matches[1]',
        'top'
    );
}

add_action('init', 'commucore_register_rewrite_rules');
add_filter('query_vars', function (array $vars): array {
    $vars[] = 'event_id';
    $vars[] = 'post_id';
    return $vars;
});

new CommuCore_Settings();
new CommuCore_Shortcodes();
new CommuCore_Assets();
