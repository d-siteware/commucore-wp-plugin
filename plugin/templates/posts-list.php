<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Template: Beitragsliste
 *
 * Verfügbare Variablen:
 *   $data    — API-Response Array mit 'data' (Items) und 'meta' (Pagination)
 *   $options — Plugin-Einstellungen
 */

$posts       = $data['data'] ?? [];
$thumb_size  = $options['thumbnail_size'] ?? 'medium';
$date_format = $options['date_format']    ?? 'long';

if (empty($posts)) {
    echo '<p class="commucore-empty">' . esc_html__('Keine Beiträge gefunden.', 'commucore') . '</p>';
    return;
}
?>

<div class="commucore-posts-list">
    <?php foreach ($posts as $post) : ?>
        <article class="commucore-post-card">

            <div class="commucore-post-body">

                <?php if (! empty($post['label'])) : ?>
                    <span class="commucore-post-label">
                        <?php echo esc_html($post['label']); ?>
                    </span>
                <?php endif; ?>

                <h3 class="commucore-post-title">
                    <?php echo esc_html($post['title'] ?? ''); ?>
                </h3>

                <div class="commucore-post-meta">
                    <?php if (! empty($post['published_at'])) : ?>
                        <span class="commucore-post-date">
                            <?php echo esc_html(commucore_format_date($post['published_at'], $date_format)); ?>
                        </span>
                    <?php endif; ?>

                    <?php if (! empty($post['event']['title'])) : ?>
                        <span class="commucore-post-event">
                            <?php esc_html_e('zu:', 'commucore'); ?>
                            <?php echo esc_html($post['event']['title']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (! empty($post['excerpt'])) : ?>
                    <p class="commucore-post-excerpt">
                        <?php echo esc_html($post['excerpt']); ?>
                    </p>
                <?php endif; ?>

            </div>

        </article>
    <?php endforeach; ?>
</div>
