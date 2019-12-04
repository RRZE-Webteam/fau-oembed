<?php

namespace FAU\OEmbed;

defined('ABSPATH') || exit;

class Options
{
    /**
     * Optionsname
     * @var string
     */
    protected static $optionName = 'fau_oembed';

    /**
     * Default options
     * @return array
     */
    protected static function defaultOptions() : array
    {
        if (!empty($GLOBALS['content_width'])) {
            $width = (int) $GLOBALS['content_width'];
        }

        if (empty($width)) {
            $width = 970;
        }

        $height = absint($width * 36 / 64);

        $options = [
            'embed_defaults' => [
                'width'	=> $width,
                'height' => $height,
                'title' => __('Embedding', 'fau-oembed')
            ],
            'faukarte' => [
                'active' => true,
                'apiurl' => 'karte.fau.de/api/v1/iframe/',
                'title'	=> __('FAU Karte', 'fau-oembed')
            ],
            'fau_videoportal' => [
                'active' => true,
                'defaultthumb' => plugin_dir_url(dirname(__FILE__)) . 'assets/images/fau-800x400.png',
                'display_title'	=> false,
                'display_source' => false,
                'description' => __('Keine Beschreibung verfügbar.', 'fau-oembed')
            ],
            'youtube' => [
                'active' => true,
                'norel' => 1,
                'display_title' => false,
                'display_source' => false,
                'description' => __('Keine Beschreibung verfügbar.', 'fau-oembed')
            ],
            'slideshare' => [
                'active' => true,
                'display_title' => true,
                'display_source' => true,
                'description' => __('Keine Beschreibung verfügbar.', 'fau-oembed')
            ],
            'brmediathek' => [
                'active' => true,
                'norel' => true,
                'display_title' => false,
                'display_source' => false,
                'description' => __('Keine Beschreibung verfügbar.', 'fau-oembed')
            ]
        ];

        return $options;
    }


    /**
     * Returns the options.
     * @param  boolean $network [description]
     * @return object           [description]
     */
    public static function getOptions()
    {
        $options = (array) get_option(self::$optionName);
        return self::parseOptions($options);
    }

    /**
     * Returns the name of the option.
     * @return string
     */
    public static function getOptionName()
    {
        return self::$optionName;
    }
    
    /**
     * Returns parsed options
     * @param  array $options [description]
     * @return object          [description]
     */
    protected static function parseOptions(array $options) : object
    {
        $defaults = self::defaultOptions();
        $options = wp_parse_args($options, $defaults);
        $options = (object) array_intersect_key($options, $defaults);
        foreach ($defaults as $key => $value) {
            if (is_array($value)) {
                $options->$key = wp_parse_args($options->$key, $value);
                $options->$key = (object) array_intersect_key($options->$key, $value);
            }
        }
        return $options;
    }    
}
