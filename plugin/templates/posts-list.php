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
$date_format = $options['date_format'] ?? 'long';
$detail_slug = $options['posts_detail_slug'] ?? 'beitrag';

if (empty($posts)) {
    echo '<p class="commucore-empty">' . esc_html__('Keine Beiträge gefunden.', 'commucore') . '</p>';
    return;
}
?>

<div class="commucore-posts-list">
    <?php foreach ($posts as $post) :
        $thumb = $post['image_small'] ?? $post['image'] ?? null;
        $detail_url = commucore_detail_url($detail_slug, 'post_id', 'commucore_posts_detail_page_id', (int) $post['id']);
        $srcset_parts = [];
        if (! empty($post['image_small'])) {
            $srcset_parts[] = $post['image_small'] . ' 150w';
        }
        if (! empty($post['image'])) {
            $srcset_parts[] = $post['image'] . ' 300w';
        }
        $srcset = ! empty($srcset_parts) ? implode(', ', $srcset_parts) : '';
    ?>
        <article class="commucore-post-card">

            <?php if (! empty($thumb)) : ?>
                <div class="commucore-event-image">
                    <a href="<?php echo esc_url($detail_url); ?>">
                        <img src="<?php echo esc_url($thumb); ?>"
                             <?php if (! empty($srcset)) : ?>
                             srcset="<?php echo esc_attr($srcset); ?>"
                             <?php endif; ?>
                             sizes="150px"
                             alt="<?php echo esc_attr($post['title'] ?? ''); ?>"
                             class="commucore-post-thumb"
                             width="150"
                             loading="lazy" />
                    </a>
                </div>
            <?php endif; ?>

            <div class="commucore-post-body">

                <h3 class="commucore-post-title">
                    <a href="<?php echo esc_url($detail_url); ?>">
                        <?php echo esc_html($post['title'] ?? ''); ?>
                    </a>
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

                <a href="<?php echo esc_url($detail_url); ?>" class="commucore-event-more">
                    <?php esc_html_e('Mehr erfahren →', 'commucore'); ?>
                </a>

            </div>

        </article>
    <?php endforeach; ?>
</div>
