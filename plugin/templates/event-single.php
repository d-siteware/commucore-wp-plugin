<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Template: Veranstaltung Einzelansicht
 *
 * Verfügbare Variablen:
 *   $data    — API-Response Array mit 'data' (Event-Objekt)
 *   $options — Plugin-Einstellungen
 */

$event       = $data['data'] ?? [];
$date_format = $options['date_format'] ?? 'long';

$srcset_parts = [];
foreach (['small' => 150, 'medium' => 300, 'large' => 600] as $name => $w) {
    $field = 'image_' . $name;
    if (! empty($event[$field])) {
        $srcset_parts[] = $event[$field] . ' ' . $w . 'w';
    }
}
$srcset = ! empty($srcset_parts) ? implode(', ', $srcset_parts) : '';


if (empty($event)) {
    echo '<p class="commucore-empty">' . esc_html__('Veranstaltung nicht gefunden.', 'commucore') . '</p>';
    return;
}
?>

<article class="commucore-event-single">

    <div class="commucore-event-content">

        <h2 class="commucore-event-title">
            <?php echo esc_html($event['title'] ?? ''); ?>
        </h2>

        <div class="commucore-event-hero-section">
            <div class="commucore-event-meta">
                <?php if (! empty($event['event_date'])) : ?>
                    <div class="commucore-meta-row">
                        <span class="commucore-meta-label"><?php esc_html_e('Datum', 'commucore'); ?></span>
                        <span class="commucore-meta-value">
                            <?php echo esc_html(commucore_format_date($event['event_date'], $date_format)); ?>
                            <?php if (! empty($event['start_time'])) : ?>
                                , <?php echo esc_html($event['start_time']); ?>
                                <?php if (! empty($event['end_time'])) : ?>
                                    – <?php echo esc_html($event['end_time']); ?>
                                <?php endif; ?>
                                <?php esc_html_e('Uhr', 'commucore'); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (! empty($event['venue'])) : ?>
                    <div class="commucore-meta-row">
                        <span class="commucore-meta-label"><?php esc_html_e('Ort', 'commucore'); ?></span>
                        <span class="commucore-meta-value">
                            <?php echo esc_html($event['venue']['name'] ?? ''); ?>
                            <?php if (! empty($event['venue']['address'])) : ?>
                                , <?php echo esc_html($event['venue']['address']); ?>
                            <?php endif; ?>
                            <?php if (! empty($event['venue']['city'])) : ?>
                                , <?php echo esc_html($event['venue']['city']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (! empty($event['entry_fee'])) : ?>
                    <div class="commucore-meta-row">
                        <span class="commucore-meta-label"><?php esc_html_e('Eintritt', 'commucore'); ?></span>
                        <span class="commucore-meta-value">
                            <?php echo esc_html(commucore_format_amount($event['entry_fee'])); ?>
                            <?php if (! empty($event['entry_fee_discounted'])) : ?>
                                (<?php esc_html_e('ermäßigt', 'commucore'); ?>:
                                <?php echo esc_html(commucore_format_amount($event['entry_fee_discounted'])); ?>)
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <?php
                $display_src = $event['image_large'] ?? $event['image'] ?? $event['poster'] ?? null;
                $poster_url  = $event['poster'] ?? $event['image'] ?? null;
            ?>
            <?php if (! empty($display_src)) : ?>
                <div class="commucore-event-hero-image-wrapper">
                    <a href="<?php echo esc_url($poster_url ?: $display_src); ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        <img class="commucore-event-hero-image"
                             src="<?php echo esc_url($display_src); ?>"
                             <?php if (! empty($srcset)) : ?>
                             srcset="<?php echo esc_attr($srcset); ?>"
                             sizes="(max-width: 768px) 100vw, 600px"
                             <?php endif; ?>
                             width="600"
                             alt="<?php echo esc_attr($event['title'] ?? ''); ?>"
                             loading="lazy" />
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="commucore-event-main">
            <?php if (! empty($event['description'])) : ?>
                <div class="commucore-event-description">
                    <?php echo wp_kses_post($event['description']); ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($event['program'])) : ?>
                <div class="commucore-event-sidebar">
                    <div class="commucore-program">
                        <h3><?php esc_html_e('Programm', 'commucore'); ?></h3>
                        <ul class="commucore-program-list">
                            <?php foreach ($event['program'] as $item) : ?>
                                <li class="commucore-program-item">
                                    <span class="commucore-program-time">
                                        <?php echo esc_html($item['start'] ?? ''); ?>
                                        <?php if (! empty($item['end'])) : ?>
                                            – <?php echo esc_html($item['end']); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="commucore-program-title">
                                        <?php echo esc_html($item['title'] ?? ''); ?>
                                    </span>
                                    <?php if (! empty($item['description'])) : ?>
                                        <span class="commucore-program-desc">
                                            <?php echo wp_kses_post($item['description']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (! empty($item['performer'])) : ?>
                                        <span class="commucore-program-performer">
                                            <?php echo esc_html($item['performer']); ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (! empty($event['registration_url'])) : ?>
            <div class="commucore-event-footer">
                <a href="<?php echo esc_url($event['registration_url']); ?>"
                   class="commucore-btn"
                   target="_blank"
                   rel="noopener noreferrer">
                    <?php esc_html_e('Jetzt anmelden', 'commucore'); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</article>
