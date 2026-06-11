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
 *
 * <a href="http://localhost:8080/veranstaltung/2/" class="commucore-event-more">
 * Mehr erfahren →                </a>
 */

$events      = $data['data'] ?? [];
$date_format = $options['date_format']       ?? 'long';
$detail_slug = $options['events_detail_slug'] ?? 'veranstaltung';

if (empty($events)) {
    echo '<p class="commucore-empty">' . esc_html__('Keine Veranstaltungen gefunden.', 'commucore') . '</p>';
    return;
}
?>

<div class="commucore-events-list">
    <?php foreach ($events as $event) :
        $thumbnail  = $event['image_medium'] ?? $event['image'] ?? $event['poster'] ?? null;
        $detail_url = commucore_detail_url($detail_slug, 'event_id', 'commucore_events_detail_page_id', (int) $event['id']);

        $srcset_parts = [];
        foreach (['small' => 150, 'medium' => 300, 'large' => 600] as $name => $w) {
            $field = 'image_' . $name;
            if (! empty($event[$field])) {
                $srcset_parts[] = $event[$field] . ' ' . $w . 'w';
            }
        }
        $srcset = ! empty($srcset_parts) ? implode(', ', $srcset_parts) : '';
    ?>
        <article class="commucore-event-card">

            <?php if (! empty($thumbnail)) : ?>
                <div class="commucore-event-image">
                    <a href="<?php echo esc_url($detail_url); ?>">
                        <img
                            src="<?php echo esc_url($thumbnail); ?>"
                            <?php if (! empty($srcset)) : ?>
                            srcset="<?php echo esc_attr($srcset); ?>"
                            sizes="(max-width: 768px) 100vw, 300px"
                            <?php endif; ?>
                            alt="<?php echo esc_attr($event['title'] ?? ''); ?>"
                            width="300"
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
                        <?php echo wp_kses_post($event['excerpt']); ?>
                    </p>
                <?php endif; ?>

                <a href="<?php echo esc_url($detail_url); ?>" class="commucore-event-more">
                    <?php esc_html_e('Mehr erfahren →', 'commucore'); ?>
                </a>
            </div>

        </article>
    <?php endforeach; ?>
</div>
