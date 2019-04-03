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
    $width = $this->sanitizeCSSWidth($atts['width'], $this->options->embed_defaults['width']);
    $height = $this->sanitizeCSSHeight($atts['height'], $this->options->embed_defaults['height']);
	
	
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
	    $output .= ' class="fau-karte"';
	} else {
	    $output .= ' class="fau-karte defaultwidth"';
	}
	
	$output .= ' width="'.$width.'" height="'.$height.'" seamless></iframe>';
	$output .= '</div>';
        
	return $output;
    }
    
    /**
     * Sanitizes CSS width string
     * @param  string $value   Width att from shortcode
     * @param  string $default Default width
     * @return string          Sanitized width
     */
    protected function sanitizeCSSWidth($value, $default)
    {
        return $this->sanitizeCSSHeight($value, $default);
    }

    /**
     * Sanitizes CSS height string
     * @param  string $value   Height att from shortcode
     * @param  string $default Default height
     * @return string          Sanitized height
     */
    protected function sanitizeCSSHeight($value, $default)
    {
        $defaultValue = '300';
        $defaultUnit = 'px';
        $pattern = '/^(\d+)(px|%)?$/';

        $default = preg_replace('/\s+/', '', $default);
        preg_match($pattern, $default, $match);
        if (empty($match)) {
            $default = $defaultValue . $defaultUnit;
        } elseif (! isset($match[2])) {
            $default = absint($match[1]) . $defaultUnit;
        } else {
            $default = absint($match[1]) . $match[2];
        }

        $value = preg_replace('/\s+/', '', $value);
        preg_match($pattern, $value, $match);
        if (empty($match)) {
            $value = $default;
        } elseif (! isset($match[2])) {
            $value = absint($match[1]) . $defaultUnit;
        } else {
            $value = absint($match[1]) . $match[2];
        }

        return $value;
    }
    
}
