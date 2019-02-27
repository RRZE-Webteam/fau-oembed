<?php

namespace FAU\OEmbed;

defined('ABSPATH') || exit;

class Shortcode {
    
    
    protected $options;
    
    public function __construct() {
	$this->options = Options::get_options();
	add_shortcode('faukarte', array($this, 'shortcode_faukarte'));
    }
   
    
    public function shortcode_faukarte($atts) {
        //  http://karte.fau.de/#14/49.4332/11.0977 wird zu http://karte.fau.de/api/v1/iframe/zoom/14/center/49.4332,11.0977
	
	$title = ! empty( $video['title'] ) ? $video['title'] : $this->options->embed_defaults['title'];
	
        $default = array(
            'url' => 'https://karte.fau.de/api/v1/iframe/',
            'width' => '',
            'height' => '',
            'zoom' => '',
	    'title' => '',
        );
        
        $atts = shortcode_atts($default, $atts);
        extract($atts);
        
	$title = ! empty( sanitize_title($atts['title']) ) ? sanitize_title($atts['title']) : $this->options->faukarte['title'];
	$width = ! empty( intval($atts['width'])) ? intval($atts['width']) : $this->options->faukarte['width'];
	$height = ! empty( intval($atts['height'])) ? intval($atts['height']) : $this->options->faukarte['height'];
	
	
        if (is_feed()) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return sprintf('<a href="%1$s">%1$s</a>', $url);
            } else {
                return '';
            }
        }
        
        $karte_api = 'karte.fau.de/api/v1/iframe/';
        $karte_start = 'karte.fau.de/#';
        $protokoll = "https://";
        
        if (strpos($url, 'http://') !== false) {
            $url = str_replace('http://', $protokoll, $url);
        }
        
        if (strpos($url, $karte_start) === false) {
            if (strpos($url, $karte_api) === false)
                $url = $protokoll . $karte_api . $url;
            if ($zoom)
                $url = $url . "/zoom/" . $zoom;
        }
        wp_enqueue_style('fau-oembed-style');
	
	$output = '<div class="fau-oembed oembed">';
	
	$output .= '<iframe title="'.$title.'" src="'.$url.'"';
	
	if (!empty( $atts['width'] )) {
	    $output .= ' class="fau-karte" width="'.$width.'" height="'.$height.'"';
	} else {
	    $output .= ' class="fau-karte defaultwidth"';
	}
	
	$output .= ' seamless></iframe>';
	$output .= '</div>';
        
	return $output;
    }
    
    
}
