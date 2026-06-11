<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

class CommuCore_Api_Client
{
    private string $base_url;
    private string $api_key;
    private string $locale;

    public function __construct(
        string $base_url = '',
        string $api_key  = '',
        string $locale   = 'de'
    ) {
        if (empty($base_url) || empty($api_key)) {
            $options       = get_option(COMMUCORE_OPTION_KEY, []);
            $this->base_url = rtrim($options['instance_url'] ?? '', '/');
            $this->api_key  = $options['api_key'] ?? '';
            $this->locale   = $options['locale']  ?? 'de';
        } else {
            $this->base_url = rtrim($base_url, '/');
            $this->api_key  = $api_key;
            $this->locale   = $locale;
        }
    }

    /**
     * GET request gegen die CommuCore API.
     *
     * @param  string               $endpoint  z.B. 'events' oder 'events/42'
     * @param  array<string, mixed> $params    Query-Parameter
     * @param  bool                 $use_cache Transient-Cache verwenden
     * @return array<string, mixed>|\WP_Error
     */
    public function get(string $endpoint, array $params = [], bool $use_cache = true)
    {
        $params['locale'] = $this->locale;

        $url        = $this->base_url . '/api/public/v1/' . ltrim($endpoint, '/');
        $url        = add_query_arg($params, $url);
        $cache_key  = 'commucore_' . md5($url);

        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        if (empty($this->base_url) || empty($this->api_key)) {
            return new \WP_Error(
                'commucore_not_configured',
                __('CommuCore ist nicht konfiguriert. Bitte API-Schlüssel und URL eintragen.', 'commucore')
            );
        }

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'sslverify' => defined('COMMUCORE_SSL_VERIFY') ? COMMUCORE_SSL_VERIFY : true,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Accept'        => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code === 401) {
            return new \WP_Error(
                'commucore_unauthorized',
                __('Ungültiger API-Schlüssel.', 'commucore')
            );
        }

        if ($code === 403) {
            return new \WP_Error(
                'commucore_forbidden',
                __('Fehlende Berechtigung. Bitte prüfe den Scope des API-Schlüssels.', 'commucore')
            );
        }

        if ($code !== 200) {
            return new \WP_Error(
                'commucore_api_error',
                sprintf(__('API-Fehler: HTTP %d', 'commucore'), $code)
            );
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(
                'commucore_json_error',
                __('Ungültige API-Antwort.', 'commucore')
            );
        }

        if ($use_cache) {
            set_transient($cache_key, $data, 15 * MINUTE_IN_SECONDS);
        }

        return $data;
    }

    /**
     * Alle CommuCore-Transients löschen.
     */
    public static function flush_cache(): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_commucore_%',
                '_transient_timeout_commucore_%'
            )
        );
    }
}
