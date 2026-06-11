<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Template: Veranstaltungsliste
 *
 * Verfügbare Variablen:
 *   $data    — API-Response Array mit 'data' (Items) und 'meta' (Pagination)
 *   $options — Plugin-Einstellungen
 */

$events      = $data['data'] ?? [];
$thumb_size  = $options['thumbnail_size']    ?? 'medium';
$date_format = $options['date_format']       ?? 'long';
$detail_slug = $options['events_detail_slug'] ?? 'veranstaltung';

if (empty($events)) {
    echo '<p class="commucore-empty">' . esc_html__('Keine Veranstaltungen gefunden.', 'commucore') . '</p>';
    return;
}

$thumb_widths = ['small' => 150, 'medium' => 300, 'large' => 600];
$thumb_width  = $thumb_widths[$thumb_size] ?? 300;
?>

<div class="commucore-events-list">
    <?php foreach ($events as $event) :
        $thumbnail   = $event['poster'] ?? $event['image'] ?? null;
        $detail_url  = home_url('/' . $detail_slug . '/' . $event['id'] . '/');
    ?>
        <article class="commucore-event-card">

            <?php if ($thumb_size !== 'none' && ! empty($thumbnail)) : ?>
                <div class="commucore-event-image">
                    <a href="<?php echo esc_url($detail_url); ?>">
                        <img
                            src="<?php echo esc_url($thumbnail); ?>"
                            alt="<?php echo esc_attr($event['title'] ?? ''); ?>"
                            width="<?php echo esc_attr((string) $thumb_width); ?>"
                            loading="lazy"
                        />
                    </a>
                </div>
            <?php endif; ?>

            <div class="commucore-event-body">
                <h3 class="commucore-event-title">
                    <a href="<?php echo esc_url($detail_url); ?>">
                        <?php echo esc_html($event['title'] ?? ''); ?>
                    </a>
                </h3>

                <div class="commucore-event-meta">
                    <?php if (! empty($event['event_date'])) : ?>
                        <span class="commucore-event-date">
                            <?php echo esc_html(commucore_format_date($event['event_date'], $date_format)); ?>
                        </span>
                    <?php endif; ?>

                    <?php if (! empty($event['start_time'])) : ?>
                        <span class="commucore-event-time">
                            <?php echo esc_html($event['start_time']); ?>
                            <?php if (! empty($event['end_time'])) : ?>
                                – <?php echo esc_html($event['end_time']); ?>
                            <?php endif; ?>
                            <?php esc_html_e('Uhr', 'commucore'); ?>
                        </span>
                    <?php endif; ?>

                    <?php if (! empty($event['entry_fee'])) : ?>
                        <span class="commucore-event-fee">
                            <?php echo esc_html(commucore_format_amount($event['entry_fee'])); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (! empty($event['excerpt'])) : ?>
                    <p class="commucore-event-excerpt">
                        <?php echo esc_html($event['excerpt']); ?>
                    </p>
                <?php endif; ?>

                <a href="<?php echo esc_url($detail_url); ?>" class="commucore-event-more">
                    <?php esc_html_e('Mehr erfahren →', 'commucore'); ?>
                </a>
            </div>

        </article>
    <?php endforeach; ?>
</div>
