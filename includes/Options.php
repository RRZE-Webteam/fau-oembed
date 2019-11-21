<?php

namespace FAU\OEmbed;

defined('ABSPATH') || exit;

class Options {
    /**
     * Optionsname
     * @var string
     */
    protected static $option_name = 'fau_oembed';

    /**
     * Standard Einstellungen werden definiert
     * @return array
     */
    protected static function default_options() {
	if (!empty($GLOBALS['content_width'])) {
            $width = (int) $GLOBALS['content_width'];
        }

        if (empty($width)) {
            $width = 970;
        }

        $height = absint($width * 36 / 64);

        $options = [

            'embed_defaults' => array(
		'width'	    => $width,
		'height'	    => $height,
		'title'	    => __('Embedding','fau-oembed')
            ),
            'faukarte' => array(
		'active'	    => true,
		'apiurl'    => 'karte.fau.de/api/v1/iframe/',
		'title'	    => __('FAU Karte','fau-oembed')
            ),
            'fau_videoportal' => array(
		'active'	    => true,
		'defaultthumb' => plugin_dir_url(dirname(__FILE__)) . 'assets/images/fau-800x400.png',
		'display_title'	 => false,
		'display_source'	=> false,
		'description'	=> __('Keine Beschreibung verfügbar.','fau-oembed')
	    ),
            'youtube' => array(
		'active' => true,
		'norel' => 1,
		'display_title'	    => false,
		'display_source'	=> false,
		'description'	=> __('Keine Beschreibung verfügbar.','fau-oembed')
            ),
	    'slideshare' => array(
		'active' => true,
		'display_title'	    => true,
		'display_source'	=> true,
		'description'	=> __('Keine Beschreibung verfügbar.','fau-oembed')
            ),
	    'brmediathek' => array(
		'active' => true,
		'display_title'	    => false,
		'display_source'	=> false,
		'description'	=> __('Keine Beschreibung verfügbar.','fau-oembed')
            ),
	  
	    
	];

        return $options;       
    }


    /**
     * Gibt die Einstellungen zurück.
     * @return object
     */
    public static function get_options() {
        $defaults = self::default_options();

        $options = (array) get_option(self::$option_name);
        $options = wp_parse_args($options, $defaults);
        $options = array_intersect_key($options, $defaults);

        return (object) $options;
    }

    /**
     * Gibt den Namen der Option zurück.
     * @return string
     */
    public static function get_option_name()  {
        return self::$option_name;
    }
    
    
    
    

}
