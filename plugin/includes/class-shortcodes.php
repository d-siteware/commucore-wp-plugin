<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

class CommuCore_Shortcodes
{
    public function __construct()
    {
        add_shortcode('commucore_events',       [$this, 'render_events']);
        add_shortcode('commucore_event_single', [$this, 'render_event_single']);
        add_shortcode('commucore_posts',        [$this, 'render_posts']);
    }

    /**
     * [commucore_events]
     * [commucore_events upcoming="true" limit="5"]
     *
     * @param array<string, string>|string $atts
     */
    public function render_events($atts): string
    {
        $atts = shortcode_atts([
            'upcoming' => 'false',
            'limit'    => '',
        ], $atts, 'commucore_events');

        $options = get_option(COMMUCORE_OPTION_KEY, []);
        $client  = new CommuCore_Api_Client();

        $params = [
            'limit'    => (int) ($atts['limit'] ?: ($options['events_per_page'] ?? 10)),
            'upcoming' => $atts['upcoming'] === 'true' ? 'true' : 'false',
        ];

        $data = $client->get('events', $params);
        return $this->render_template('events-list', $data, $options);
    }

    /**
     * [commucore_event_single]
     * Liest event_id aus dem URL-Segment: /veranstaltung/42/
     *
     * @param array<string, string>|string $atts
     */
    public function render_event_single($atts): string
    {
        $atts = shortcode_atts([
            'id' => '',
        ], $atts, 'commucore_event_single');

        $options  = get_option(COMMUCORE_OPTION_KEY, []);
        $client   = new CommuCore_Api_Client();

        // ID aus Shortcode-Attribut oder URL-Segment
        $event_id = ! empty($atts['id'])
            ? absint($atts['id'])
            : absint(get_query_var('event_id', 0));

        if (! $event_id) {
            return $this->render_error(
                __('Keine Veranstaltung ausgewählt.', 'commucore')
            );
        }

        $data = $client->get('events/' . $event_id);
        return $this->render_template('event-single', $data, $options);
    }

    /**
     * [commucore_posts]
     * [commucore_posts limit="3"]
     * [commucore_posts id="7"]
     *
     * @param array<string, string>|string $atts
     */
    public function render_posts($atts): string
    {
        $atts = shortcode_atts([
            'id'    => '',
            'limit' => '',
        ], $atts, 'commucore_posts');

        $options = get_option(COMMUCORE_OPTION_KEY, []);
        $client  = new CommuCore_Api_Client();

        if (! empty($atts['id'])) {
            $data = $client->get('posts/' . absint($atts['id']));
            return $this->render_template('post-single', $data, $options);
        }

        $params = [
            'limit' => (int) ($atts['limit'] ?: ($options['posts_per_page'] ?? 10)),
        ];

        $data = $client->get('posts', $params);
        return $this->render_template('posts-list', $data, $options);
    }

    /**
     * Template laden — Theme kann überschreiben.
     *
     * @param array<string, mixed>|\WP_Error $data
     * @param array<string, mixed>           $options
     */
    private function render_template(string $template, $data, array $options): string
    {
        if (is_wp_error($data)) {
            return $this->render_error($data->get_error_message());
        }

        $theme_file = locate_template('commucore/' . $template . '.php');
        $file       = $theme_file ?: COMMUCORE_PATH . 'templates/' . $template . '.php';

        if (! file_exists($file)) {
            return $this->render_error(
                sprintf(__('Template nicht gefunden: %s', 'commucore'), $template)
            );
        }

        ob_start();
        include $file;
        return ob_get_clean() ?: '';
    }

    private function render_error(string $message): string
    {
        return sprintf(
            '<div class="commucore-error">%s</div>',
            esc_html($message)
        );
    }
}
