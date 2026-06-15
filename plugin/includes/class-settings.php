<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

class CommuCore_Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_commucore_test_connection', [$this, 'ajax_test_connection']);
    }

    public function add_menu(): void
    {
        add_options_page(
            __('CommuCore Einstellungen', 'commucore'),
            'CommuCore',
            'manage_options',
            'commucore',
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        register_setting('commucore', COMMUCORE_OPTION_KEY, [
            'sanitize_callback' => [$this, 'sanitize'],
        ]);
    }

    public function sanitize(array $input): array
    {

        if (isset($_POST['commucore_flush_cache'])) {
            CommuCore_Api_Client::flush_cache();
            set_transient('commucore_flushed_notice', true, 60);
            return get_option(COMMUCORE_OPTION_KEY, []);
        }

        $clean = [];

        $clean['instance_url'] = isset($input['instance_url'])
            ? rtrim(esc_url_raw(trim($input['instance_url'])), '/')
            : '';

        $clean['api_key'] = isset($input['api_key'])
            ? sanitize_text_field(trim($input['api_key']))
            : '';

        $clean['locale'] = in_array($input['locale'] ?? '', ['de', 'en', 'hu'], true)
            ? $input['locale']
            : 'de';

        $clean['date_format'] = in_array($input['date_format'] ?? '', ['long', 'short', 'iso'], true)
            ? $input['date_format']
            : 'long';

        $clean['events_per_page'] = min(50, max(1, (int) ($input['events_per_page'] ?? 10)));
        $clean['posts_per_page']  = min(50, max(1, (int) ($input['posts_per_page'] ?? 10)));

        $clean['events_list_slug']   = sanitize_title($input['events_list_slug']   ?? 'veranstaltungen') ?: 'veranstaltungen';
        $clean['events_detail_slug'] = sanitize_title($input['events_detail_slug'] ?? 'veranstaltung')   ?: 'veranstaltung';

        $clean['posts_list_slug']   = sanitize_title($input['posts_list_slug']   ?? 'beitraege') ?: 'beitraege';
        $clean['posts_detail_slug'] = sanitize_title($input['posts_detail_slug'] ?? 'beitrag')   ?: 'beitrag';

        $old = get_option(COMMUCORE_OPTION_KEY, []);

        // Cache leeren wenn Verbindungsdaten sich ändern
        if (
            ($old['instance_url'] ?? '') !== $clean['instance_url'] ||
            ($old['api_key'] ?? '')      !== $clean['api_key']
        ) {
            CommuCore_Api_Client::flush_cache();
        }

        // Seiten umbenennen wenn Slugs sich ändern
        if (($old['events_list_slug'] ?? 'veranstaltungen') !== $clean['events_list_slug']) {
            $page_id = get_option('commucore_events_list_page_id', 0);
            if ($page_id && get_post($page_id)) {
                wp_update_post(['ID' => $page_id, 'post_name' => $clean['events_list_slug']]);
            }
            commucore_register_rewrite_rules();
            flush_rewrite_rules();
        }

        if (($old['events_detail_slug'] ?? 'veranstaltung') !== $clean['events_detail_slug']) {
            $page_id = get_option('commucore_events_detail_page_id', 0);
            if ($page_id && get_post($page_id)) {
                wp_update_post(['ID' => $page_id, 'post_name' => $clean['events_detail_slug']]);
            }
            commucore_register_rewrite_rules();
            flush_rewrite_rules();
        }

        if (($old['posts_list_slug'] ?? 'beitraege') !== $clean['posts_list_slug']) {
            $page_id = get_option('commucore_posts_list_page_id', 0);
            if ($page_id && get_post($page_id)) {
                wp_update_post(['ID' => $page_id, 'post_name' => $clean['posts_list_slug']]);
            }
            commucore_register_rewrite_rules();
            flush_rewrite_rules();
        }

        if (($old['posts_detail_slug'] ?? 'beitrag') !== $clean['posts_detail_slug']) {
            $page_id = get_option('commucore_posts_detail_page_id', 0);
            if ($page_id && get_post($page_id)) {
                wp_update_post(['ID' => $page_id, 'post_name' => $clean['posts_detail_slug']]);
            }
            commucore_register_rewrite_rules();
            flush_rewrite_rules();
        }

        return $clean;
    }

    public function ajax_test_connection(): void
    {
        check_ajax_referer('commucore_test', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'commucore')]);
        }

        $url = rtrim(sanitize_text_field($_POST['instance_url'] ?? ''), '/');
        $key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($url) || empty($key)) {
            wp_send_json_error(['message' => __('Bitte URL und API-Schlüssel eingeben.', 'commucore')]);
        }

        $client   = new CommuCore_Api_Client($url, $key);
        $response = $client->get('events', ['limit' => 1], false); // false = kein Cache

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Verbindung erfolgreich! CommuCore ist erreichbar.', 'commucore'),
        ]);
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $options = get_option(COMMUCORE_OPTION_KEY, []);
        $url     = $options['instance_url']   ?? '';
        $key     = $options['api_key']        ?? '';
        $locale  = $options['locale']         ?? 'de';
        $dfmt    = $options['date_format']    ?? 'long';
        $elimit       = $options['events_per_page']    ?? 10;
        $plimit       = $options['posts_per_page']     ?? 10;
        $list_slug    = $options['events_list_slug']   ?? 'veranstaltungen';
        $detail_slug  = $options['events_detail_slug'] ?? 'veranstaltung';
        $posts_list_slug   = $options['posts_list_slug']   ?? 'beitraege';
        $posts_detail_slug = $options['posts_detail_slug'] ?? 'beitrag';
        $has_permalinks    = (bool) get_option('permalink_structure');
        ?>
        <div class="wrap commucore-settings">

            <div class="commucore-header">
               <div style="display: flex; align-items: center; gap: 1rem;">
                   <aside>
                       <img src="<?php echo esc_url(COMMUCORE_URL); ?>assets/images/logo_commu-core.svg"
                            alt="CommuCore Logo"
                            style="width: 55px; height: 55px; object-fit: contain;"
                       >
                   </aside>
                   <div>
                       <h1>CommuCore</h1>
                       <p><?php esc_html_e('Verbinde deine WordPress-Seite mit CommuCore.', 'commucore'); ?></p>
                   </div>
               </div>

                <div style="display: flex; align-items: center; gap: 1rem;">
                    <?php
                    if (get_transient('commucore_flushed_notice')) {
                        delete_transient('commucore_flushed_notice');
                        add_settings_error('commucore', 'cache_flushed',
                                __('Cache wurde geleert. Daten werden beim nächsten Seitenaufruf neu geladen.', 'commucore'),
                                'success'
                        );
                    }
                    settings_errors('commucore');

                    ?>
                </div>
            </div>



            <form method="post" action="options.php">
                <?php settings_fields('commucore'); ?>

                <ul class="commucore-tab-nav" role="tablist">
                    <li role="presentation">
                        <button type="button" role="tab" class="active" data-tab="tab-einstellungen">
                            <?php esc_html_e('Einstellungen', 'commucore'); ?>
                        </button>
                    </li>
                    <li role="presentation">
                        <button type="button" role="tab" data-tab="tab-veranstaltungen">
                            <?php esc_html_e('Veranstaltungen', 'commucore'); ?>
                        </button>
                    </li>
                    <li role="presentation">
                        <button type="button" role="tab" data-tab="tab-artikel">
                            <?php esc_html_e('Artikel', 'commucore'); ?>
                        </button>
                    </li>
                </ul>

                <div id="tab-einstellungen" class="commucore-tab-content active">
                    <div class="commucore-card">
                        <h2><?php esc_html_e('Verbindung', 'commucore'); ?></h2>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="commucore_instance_url">
                                        <?php esc_html_e('Instanz-URL', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="url"
                                        id="commucore_instance_url"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[instance_url]"
                                        value="<?php echo esc_attr($url); ?>"
                                        class="regular-text"
                                        placeholder="https://mein-verein.commu-core.app"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Die URL deiner CommuCore-Instanz.', 'commucore'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="commucore_api_key">
                                        <?php esc_html_e('API-Schlüssel', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="password"
                                        id="commucore_api_key"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[api_key]"
                                        value="<?php echo esc_attr($key); ?>"
                                        class="regular-text"
                                        autocomplete="new-password"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Den API-Schlüssel findest du in CommuCore unter Profil → API-Schlüssel.', 'commucore'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="commucore_locale">
                                        <?php esc_html_e('Sprache', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <select id="commucore_locale" name="<?php echo COMMUCORE_OPTION_KEY; ?>[locale]">
                                        <option value="de" <?php selected($locale, 'de'); ?>><?php esc_html_e('Deutsch', 'commucore'); ?></option>
                                        <option value="en" <?php selected($locale, 'en'); ?>><?php esc_html_e('English', 'commucore'); ?></option>
                                        <option value="hu" <?php selected($locale, 'hu'); ?>><?php esc_html_e('Magyar', 'commucore'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <div class="commucore-test-row">
                            <button type="button" id="commucore-test-btn" class="button button-secondary">
                                <?php esc_html_e('Verbindung testen', 'commucore'); ?>
                            </button>
                            <span id="commucore-test-result" class="commucore-test-result" style="display:none;"></span>
                        </div>

                        <div class="commucore-test-row">
                            <span>
                                API Aufrufe werden <code>15 Minuten</code> im Cache gespeichert. Änderungen an der CommuCore werden entsprechend verzögert angezeit. Optional kann der <strong>Cache gelöscht</strong> werden.
                            </span>
                            <button type="submit" name="commucore_flush_cache" value="1" class="button button-secondary">
                                Cache leeren
                            </button>
                        </div>
                    </div>

                    <div class="commucore-card">
                        <h2><?php esc_html_e('Darstellung', 'commucore'); ?></h2>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="commucore_date_format">
                                        <?php esc_html_e('Datumsformat', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <select id="commucore_date_format" name="<?php echo COMMUCORE_OPTION_KEY; ?>[date_format]">
                                        <option value="long"  <?php selected($dfmt, 'long'); ?>><?php esc_html_e('15. Juni 2025', 'commucore'); ?></option>
                                        <option value="short" <?php selected($dfmt, 'short'); ?>><?php esc_html_e('15.06.2025', 'commucore'); ?></option>
                                        <option value="iso"   <?php selected($dfmt, 'iso'); ?>><?php esc_html_e('2025-06-15', 'commucore'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="commucore_events_per_page">
                                        <?php esc_html_e('Veranstaltungen pro Seite', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="number"
                                        id="commucore_events_per_page"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[events_per_page]"
                                        value="<?php echo esc_attr((string) $elimit); ?>"
                                        min="1"
                                        max="50"
                                        class="small-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="commucore_posts_per_page">
                                        <?php esc_html_e('Beiträge pro Seite', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="number"
                                        id="commucore_posts_per_page"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[posts_per_page]"
                                        value="<?php echo esc_attr((string) $plimit); ?>"
                                        min="1"
                                        max="50"
                                        class="small-text"
                                    />
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="commucore-card">
                        <h2><?php esc_html_e('Tutorial', 'commucore'); ?></h2>
                        <p>
                            <?php esc_html_e('Eine Schritt-für-Schritt-Anleitung findest du hier:', 'commucore'); ?>
                            <a href="https://commu-core.com/tutorials/wordpress-plugin" target="_blank" rel="noopener noreferrer">
                                commu-core.com/tutorials/wordpress-plugin
                            </a>
                        </p>
                    </div>
                </div>

                <div id="tab-veranstaltungen" class="commucore-tab-content">
                    <div class="commucore-card">
                        <h2><?php esc_html_e('Shortcodes für Veranstaltungen', 'commucore'); ?></h2>
                        <p><?php esc_html_e('Füge diese Shortcodes in beliebige Seiten oder Beiträge ein:', 'commucore'); ?></p>

                        <table class="widefat commucore-shortcode-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Shortcode', 'commucore'); ?></th>
                                    <th><?php esc_html_e('Beschreibung', 'commucore'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>[commucore_events]</code></td>
                                    <td><?php esc_html_e('Alle kommenden Veranstaltungen (Listenseite)', 'commucore'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[commucore_events upcoming="true" limit="5"]</code></td>
                                    <td><?php esc_html_e('Nächste 5 Veranstaltungen', 'commucore'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[commucore_event_single]</code></td>
                                    <td>
                                        <?php
                                        $event_url_example = $has_permalinks
                                            ? '/' . $detail_slug . '/42/'
                                            : '?page_id=' . (int) get_option('commucore_events_detail_page_id', 0) . '&amp;event_id=42';
                                        echo esc_html(sprintf(
                                            __('Einzelansicht — liest ID aus URL %s', 'commucore'),
                                            $event_url_example
                                        ));
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>[commucore_event_single id="42"]</code></td>
                                    <td><?php esc_html_e('Einzelansicht einer bestimmten Veranstaltung', 'commucore'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="commucore-card">
                        <h2><?php esc_html_e('URL-Slugs für Veranstaltungen', 'commucore'); ?></h2>

                        <?php if (! $has_permalinks) : ?>
                            <div class="commucore-notice commucore-notice-warning">
                                <p>
                                    <?php esc_html_e('Die Slug-basierten URLs (z. B. /veranstaltung/42/) funktionieren nur mit aktivierten Pretty Permalinks (Einstellungen → Permalinks → "Benutzerdefinierte Struktur" oder "Beitragsname").', 'commucore'); ?>
                                    <?php esc_html_e('Aktuell werden Standard-Permalinks verwendet. Die Seiten sind trotzdem über die unten stehenden ?page_id=-Links erreichbar.', 'commucore'); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="commucore_events_list_slug">
                                        <?php esc_html_e('Listen-Slug', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="commucore_events_list_slug"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[events_list_slug]"
                                        value="<?php echo esc_attr($list_slug); ?>"
                                        class="regular-text"
                                    />
                                    <p class="description">
                                        <?php
                                        $list_page_id = get_option('commucore_events_list_page_id', 0);
                                        if ($list_page_id && get_post($list_page_id)) {
                                            printf(
                                                '<a href="%s" target="_blank">%s</a>',
                                                esc_url(get_permalink($list_page_id)),
                                                esc_html(get_permalink($list_page_id))
                                            );
                                            if ($has_permalinks) {
                                                echo ' <span class="commucore-permalink-hint">(';
                                                printf(
                                                    esc_html__('Slug: /%s/', 'commucore'),
                                                    esc_html($list_slug)
                                                );
                                                echo ')</span>';
                                            }
                                        } else {
                                            esc_html_e('Seite wird bei Plugin-Aktivierung automatisch angelegt.', 'commucore');
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="commucore_events_detail_slug">
                                        <?php esc_html_e('Detail-Slug', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="commucore_events_detail_slug"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[events_detail_slug]"
                                        value="<?php echo esc_attr($detail_slug); ?>"
                                        class="regular-text"
                                    />
                                    <p class="description">
                                        <?php
                                        $detail_page_id = get_option('commucore_events_detail_page_id', 0);
                                        if ($detail_page_id && get_post($detail_page_id)) {
                                            $detail_url = $has_permalinks
                                                ? rtrim(get_permalink($detail_page_id), '/') . '/42/'
                                                : add_query_arg('event_id', '42', get_permalink($detail_page_id));
                                            printf(
                                                '<a href="%s" target="_blank">%s</a>',
                                                esc_url($detail_url),
                                                esc_html($detail_url)
                                            );
                                        } else {
                                            esc_html_e('Seite wird bei Plugin-Aktivierung automatisch angelegt.', 'commucore');
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div id="tab-artikel" class="commucore-tab-content">
                    <div class="commucore-card">
                        <h2><?php esc_html_e('Shortcodes für Artikel', 'commucore'); ?></h2>
                        <p><?php esc_html_e('Füge diese Shortcodes in beliebige Seiten oder Beiträge ein:', 'commucore'); ?></p>

                        <table class="widefat commucore-shortcode-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Shortcode', 'commucore'); ?></th>
                                    <th><?php esc_html_e('Beschreibung', 'commucore'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>[commucore_posts]</code></td>
                                    <td><?php esc_html_e('Alle Beiträge (Listenseite)', 'commucore'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[commucore_posts limit="3"]</code></td>
                                    <td><?php esc_html_e('Die letzten 3 Beiträge', 'commucore'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[commucore_posts id="7"]</code></td>
                                    <td><?php esc_html_e('Einen einzelnen Beitrag per ID', 'commucore'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[commucore_post_single]</code></td>
                                    <td>
                                        <?php
                                        $post_url_example = $has_permalinks
                                            ? '/' . $posts_detail_slug . '/42/'
                                            : '?page_id=' . (int) get_option('commucore_posts_detail_page_id', 0) . '&amp;post_id=42';
                                        echo esc_html(sprintf(
                                            __('Einzelansicht — liest ID aus URL %s', 'commucore'),
                                            $post_url_example
                                        ));
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>[commucore_post_single id="42"]</code></td>
                                    <td><?php esc_html_e('Einzelansicht eines bestimmten Beitrags', 'commucore'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="commucore-card">
                        <h2><?php esc_html_e('URL-Slugs für Artikel', 'commucore'); ?></h2>

                        <?php if (! $has_permalinks) : ?>
                            <div class="commucore-notice commucore-notice-warning">
                                <p>
                                    <?php esc_html_e('Die Slug-basierten URLs (z. B. /beitrag/42/) funktionieren nur mit aktivierten Pretty Permalinks (Einstellungen → Permalinks → "Benutzerdefinierte Struktur" oder "Beitragsname").', 'commucore'); ?>
                                    <?php esc_html_e('Aktuell werden Standard-Permalinks verwendet. Die Seiten sind trotzdem über die unten stehenden ?page_id=-Links erreichbar.', 'commucore'); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="commucore_posts_list_slug">
                                        <?php esc_html_e('Listen-Slug', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="commucore_posts_list_slug"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[posts_list_slug]"
                                        value="<?php echo esc_attr($posts_list_slug); ?>"
                                        class="regular-text"
                                    />
                                    <p class="description">
                                        <?php
                                        $posts_list_page_id = get_option('commucore_posts_list_page_id', 0);
                                        if ($posts_list_page_id && get_post($posts_list_page_id)) {
                                            printf(
                                                '<a href="%s" target="_blank">%s</a>',
                                                esc_url(get_permalink($posts_list_page_id)),
                                                esc_html(get_permalink($posts_list_page_id))
                                            );
                                            if ($has_permalinks) {
                                                echo ' <span class="commucore-permalink-hint">(';
                                                printf(
                                                    esc_html__('Slug: /%s/', 'commucore'),
                                                    esc_html($posts_list_slug)
                                                );
                                                echo ')</span>';
                                            }
                                        } else {
                                            esc_html_e('Seite wird bei Plugin-Aktivierung automatisch angelegt.', 'commucore');
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="commucore_posts_detail_slug">
                                        <?php esc_html_e('Detail-Slug', 'commucore'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="commucore_posts_detail_slug"
                                        name="<?php echo COMMUCORE_OPTION_KEY; ?>[posts_detail_slug]"
                                        value="<?php echo esc_attr($posts_detail_slug); ?>"
                                        class="regular-text"
                                    />
                                    <p class="description">
                                        <?php
                                        $posts_detail_page_id = get_option('commucore_posts_detail_page_id', 0);
                                        if ($posts_detail_page_id && get_post($posts_detail_page_id)) {
                                            $detail_url = $has_permalinks
                                                ? rtrim(get_permalink($posts_detail_page_id), '/') . '/42/'
                                                : add_query_arg('post_id', '42', get_permalink($posts_detail_page_id));
                                            printf(
                                                '<a href="%s" target="_blank">%s</a>',
                                                esc_url($detail_url),
                                                esc_html($detail_url)
                                            );
                                        } else {
                                            esc_html_e('Seite wird bei Plugin-Aktivierung automatisch angelegt.', 'commucore');
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button(__('Einstellungen speichern', 'commucore')); ?>
            </form>
        </div>

        <script>
        (function () {
            const btn    = document.getElementById('commucore-test-btn');
            const result = document.getElementById('commucore-test-result');

            if (btn) {
                btn.addEventListener('click', function () {
                    const url = document.getElementById('commucore_instance_url').value.trim();
                    const key = document.getElementById('commucore_api_key').value.trim();

                    result.style.display = 'inline-block';
                    result.className     = 'commucore-test-result commucore-testing';
                    result.textContent   = '<?php echo esc_js(__('Verbindung wird getestet …', 'commucore')); ?>';
                    btn.disabled         = true;

                    const data = new FormData();
                    data.append('action',       'commucore_test_connection');
                    data.append('nonce',        '<?php echo wp_create_nonce('commucore_test'); ?>');
                    data.append('instance_url', url);
                    data.append('api_key',      key);

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: data,
                    })
                    .then(r => r.json())
                    .then(response => {
                        result.className   = 'commucore-test-result ' + (response.success ? 'commucore-success' : 'commucore-error');
                        result.textContent = response.data.message;
                    })
                    .catch(() => {
                        result.className   = 'commucore-test-result commucore-error';
                        result.textContent = '<?php echo esc_js(__('Netzwerkfehler.', 'commucore')); ?>';
                    })
                    .finally(() => {
                        btn.disabled = false;
                    });
                });
            }

            const tabs = document.querySelectorAll('.commucore-tab-nav button');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (t) { t.classList.remove('active'); });
                    document.querySelectorAll('.commucore-tab-content').forEach(function (c) { c.classList.remove('active'); });
                    tab.classList.add('active');
                    var target = document.getElementById(tab.getAttribute('data-tab'));
                    if (target) { target.classList.add('active'); }
                });
            });
        }());
        </script>
        <?php
    }
}
