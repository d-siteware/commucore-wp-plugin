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
$date_format = $options['date_format'] ?? 'long';

if (empty($post)) {
    echo '<p class="commucore-empty">' . esc_html__('Beitrag nicht gefunden.', 'commucore') . '</p>';
    return;
}
?>

<article class="commucore-post-single">

    <div class="commucore-post-layout">

        <div class="commucore-post-main">

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

            <?php if (! empty($post['body'])) : ?>
                <div class="commucore-post-body">
                    <?php echo wp_kses_post($post['body']); ?>
                </div>
            <?php endif; ?>

        </div>

        <?php if (! empty($post['images'])) : ?>
        <aside class="commucore-post-sidebar">
            <div class="commucore-post-gallery">
                <?php foreach ($post['images'] as $image) :
                    $img_src = $image['url_large'] ?? $image['url_medium'] ?? $image['url_small'] ?? $image['url'] ?? null;

                    $gallery_srcset_parts = [];
                    if (! empty($image['url_small'])) {
                        $gallery_srcset_parts[] = $image['url_small'] . ' 150w';
                    }
                    if (! empty($image['url_medium'])) {
                        $gallery_srcset_parts[] = $image['url_medium'] . ' 768w';
                    }
                    if (! empty($image['url_large'])) {
                        $gallery_srcset_parts[] = $image['url_large'] . ' 2048w';
                    }
                    $gallery_srcset = ! empty($gallery_srcset_parts) ? implode(', ', $gallery_srcset_parts) : '';
                ?>
                    <?php if ($img_src) : ?>
                    <figure class="commucore-gallery-item">
                        <a href="<?php echo esc_url($image['url_large'] ?? $image['url']); ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            <img
                                src="<?php echo esc_url($img_src); ?>"
                                <?php if (! empty($gallery_srcset)) : ?>
                                srcset="<?php echo esc_attr($gallery_srcset); ?>"
                                sizes="(max-width: 768px) 100vw, 20vw"
                                <?php endif; ?>
                                alt="<?php echo esc_attr($image['caption'] ?? ''); ?>"
                                loading="lazy"
                            />
                        </a>
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
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </aside>
        <?php endif; ?>

    </div>

</article>
