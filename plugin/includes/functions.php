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
 */
function commucore_format_date_long(int $ts): string
{
    $months = [
        1  => 'Januar',   2  => 'Februar', 3  => 'März',
        4  => 'April',    5  => 'Mai',     6  => 'Juni',
        7  => 'Juli',     8  => 'August',  9  => 'September',
        10 => 'Oktober',  11 => 'November', 12 => 'Dezember',
    ];

    $day   = (int) date('j', $ts);
    $month = (int) date('n', $ts);
    $year  = date('Y', $ts);

    return sprintf('%d. %s %s', $day, $months[$month], $year);
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
