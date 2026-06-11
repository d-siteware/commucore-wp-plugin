<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Template: Beitrag Einzelansicht
 *
 * Verfügbare Variablen:
 *   $data    — API-Response Array mit 'data' (Post-Objekt)
 *   $options — Plugin-Einstellungen
 */

$post        = $data['data'] ?? [];
$date_format = $options['date_format']    ?? 'long';
$thumb_size  = $options['thumbnail_size'] ?? 'medium';

if (empty($post)) {
    echo '<p class="commucore-empty">' . esc_html__('Beitrag nicht gefunden.', 'commucore') . '</p>';
    return;
}

$thumb_widths = ['small' => 150, 'medium' => 300, 'large' => 600];
$thumb_width  = $thumb_widths[$thumb_size] ?? 300;
?>

<article class="commucore-post-single">

    <header class="commucore-post-header">

        <?php if (! empty($post['label'])) : ?>
            <span class="commucore-post-label">
                <?php echo esc_html($post['label']); ?>
            </span>
        <?php endif; ?>

        <h2 class="commucore-post-title">
            <?php echo esc_html($post['title'] ?? ''); ?>
        </h2>

        <div class="commucore-post-meta">
            <?php if (! empty($post['published_at'])) : ?>
                <span class="commucore-post-date">
                    <?php echo esc_html(commucore_format_date($post['published_at'], $date_format)); ?>
                </span>
            <?php endif; ?>

            <?php if (! empty($post['event'])) : ?>
                <span class="commucore-post-event">
                    <?php esc_html_e('Veranstaltung:', 'commucore'); ?>
                    <?php echo esc_html($post['event']['title'] ?? ''); ?>
                    <?php if (! empty($post['event']['event_date'])) : ?>
                        (<?php echo esc_html(commucore_format_date($post['event']['event_date'], $date_format)); ?>)
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>

    </header>

    <?php if (! empty($post['body'])) : ?>
        <div class="commucore-post-body">
            <?php echo wp_kses_post($post['body']); ?>
        </div>
    <?php endif; ?>

    <?php if ($thumb_size !== 'none' && ! empty($post['images'])) : ?>
        <div class="commucore-post-gallery">
            <?php foreach ($post['images'] as $image) : ?>
                <figure class="commucore-gallery-item">
                    <img
                        src="<?php echo esc_url($image['url']); ?>"
                        alt="<?php echo esc_attr($image['caption'] ?? ''); ?>"
                        width="<?php echo esc_attr((string) $thumb_width); ?>"
                        loading="lazy"
                    />
                    <?php if (! empty($image['caption'])) : ?>
                        <figcaption>
                            <?php echo esc_html($image['caption']); ?>
                            <?php if (! empty($image['author'])) : ?>
                                <span class="commucore-image-credit">
                                    © <?php echo esc_html($image['author']); ?>
                                </span>
                            <?php endif; ?>
                        </figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</article>
