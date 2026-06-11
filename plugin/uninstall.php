<?php

/**
 * CommuCore — Uninstall
 *
 * Löscht alle Plugin-Optionen und selbst erstellten Seiten.
 * Läuft nur, wenn WordPress über WP_UNINSTALL_PLUGIN aufgerufen wird.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$option_keys = [
    'commucore_events_list_page_id',
    'commucore_events_detail_page_id',
    'commucore_posts_list_page_id',
    'commucore_posts_detail_page_id',
    'commucore_settings',
];

foreach ($option_keys as $key) {
    delete_option($key);
}

// Transients aufräumen
global $wpdb;

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_commucore_') . '%',
        $wpdb->esc_like('_transient_timeout_commucore_') . '%'
    )
);

// Automatisch erstellte Seiten löschen
$page_ids = array_filter([
    get_option('commucore_events_list_page_id', 0),
    get_option('commucore_events_detail_page_id', 0),
    get_option('commucore_posts_list_page_id', 0),
    get_option('commucore_posts_detail_page_id', 0),
]);

foreach ($page_ids as $page_id) {
    if (get_post($page_id)) {
        wp_delete_post($page_id, true);
    }
}
