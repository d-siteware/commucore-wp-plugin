<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Datum formatieren nach Plugin-Einstellung.
 *
 * @param string $date   ISO-Datum z.B. "2025-09-15"
 * @param string $format 'long' | 'short' | 'iso'
 */
function commucore_format_date(string $date, string $format = 'long'): string
{
    if (empty($date)) {
        return '';
    }

    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }

    return match ($format) {
        'short' => date('d.m.Y', $ts),
        'iso'   => date('Y-m-d', $ts),
        default => commucore_format_date_long($ts),
    };
}

/**
 * Deutsches Langformat: "15. Juni 2025"
 * Übersetzt via date_i18n() basierend auf der Site-Locale.
 */
function commucore_format_date_long(int $ts): string
{
    return date_i18n('j. F Y', $ts);
}

/**
 * Centbetrag in lesbaren Euro-Betrag umwandeln.
 * entry_fee wird in Cent gespeichert.
 *
 * @param int|null $cents
 */
function commucore_format_amount(?int $cents): string
{
    if ($cents === null || $cents === 0) {
        return __('Kostenlos', 'commucore');
    }

    return number_format($cents / 100, 2, ',', '.') . ' €';
}

/**
 * Detail-URL für ein Item (Event oder Post) generieren,
 * abhängig von der aktuellen Permalink-Einstellung.
 *
 * @param string $slug           Detail-Slug, z.B. 'veranstaltung' oder 'beitrag'
 * @param string $query_var      Query-Variable, z.B. 'event_id' oder 'post_id'
 * @param string $page_option_key WP-Option-Name für die Detailseiten-ID
 * @param int    $item_id        ID des anzuzeigenden Items
 */
function commucore_detail_url(string $slug, string $query_var, string $page_option_key, int $item_id): string
{
    if (get_option('permalink_structure')) {
        return home_url('/' . $slug . '/' . $item_id . '/');
    }

    $page_id = get_option($page_option_key, 0);

    if ($page_id) {
        return add_query_arg($query_var, $item_id, get_permalink($page_id));
    }

    return home_url('/?' . $query_var . '=' . $item_id);
}
