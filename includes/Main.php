<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FAU\OEmbed;

use FAU\OEmbed\Settings;

defined('ABSPATH') || exit;

class Main
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        // add_filter('embed_oembed_html', [$this, 'fau_oembed_wrap_oembed_div'], 10, 3);
        new Settings();
        // Settings of Plugin
        new Embeds();
        // New Embdes and Changes to StandardEmbdes
        new Shortcode();
        // Shortcodes
        new SemiEmbeds();
        // Embdes, that feel and work like embeds for the author, but dont use oEmbed-API
    }

    /*-----------------------------------------------------------------------------------*/
    /* Enqueue der globale Skripte.
    /*-----------------------------------------------------------------------------------*/
    public function register_scripts()
    {
        wp_register_style(
            'fau-oembed-style',
            plugins_url('assets/css/fau-oembed.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );        
    }

    /*-----------------------------------------------------------------------------------*/
    /* Surround embeddings with div class
    /*-----------------------------------------------------------------------------------*/
    public function fau_oembed_wrap_oembed_div($html, $url, $attr)
    {
        return '<div class="oembed">' . $html . '</div>';
    }

    /*
     * Only for debugging, remove on stable
     */
    public  function var_dump_ret($mixed = null)
    {
        ob_start();
        var_dump($mixed);
        $content = ob_get_contents();
        ob_end_clean();
        return "<pre>" . $content . "</pre>";
    }
}
