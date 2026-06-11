<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

class CommuCore_Assets
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
    }

    public function enqueue_frontend(): void
    {
        wp_enqueue_style(
            'commucore',
            COMMUCORE_URL . 'assets/css/commucore.css',
            [],
            COMMUCORE_VERSION
        );
    }

    public function enqueue_admin(string $hook): void
    {
        if ($hook !== 'settings_page_commucore') {
            return;
        }

        wp_enqueue_style(
            'commucore-admin',
            COMMUCORE_URL . 'assets/css/commucore-admin.css',
            [],
            COMMUCORE_VERSION
        );
    }
}
