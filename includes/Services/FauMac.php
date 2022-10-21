<?php

namespace FAU\OEmbed\Services;

defined('ABSPATH') || exit;

class FauMac
{
    private static $instance;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        add_action('init', [$this, 'registerHandler']);
    }

    public function __clone()
    {
        trigger_error('Cloning is not allowed.', E_USER_ERROR);
    }

    public function __wakeup()
    {
        trigger_error('Unserialize is forbidden.', E_USER_ERROR);
    }

    /**
     * Register an embed handler for the service.
     *
     * @return void
     */
    public function registerHandler()
    {
        wp_embed_register_handler(
            'https://faumac.rrze.fau.de/oembed/software',
            '#https://faumac.rrze.fau.de/oembed/software/?#i',
            [$this, 'software']
        );
    }

    /**
     * Software handler callback.
     *
     * @param array $matches
     * @param array $attr
     * @param string $url
     * @param array $rawattr
     * @return string
     */
    public function software($matches, $attr, $url, $rawattr)
    {
        $embed = '';
        $url = $matches[0] ?? '';
        $url = sprintf('%1$s?url=%1$s', $url);
        $response = wp_remote_get(sprintf('%1$s?url=%1$s', $url));
        if (is_array($response) && !is_wp_error($response)) {
            $body = json_decode($response['body'], true);
            $embed = $body['html'] ?? '';
        }
        return apply_filters('embed_faumac_software', $embed, $matches, $attr, $url, $rawattr);
    }
}
