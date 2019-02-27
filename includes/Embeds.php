<?php

namespace FAU\OEmbed;

use  FAU\OEmbed\API\OEmbed;

defined('ABSPATH') || exit;

class Embeds {

    protected $options;

    public function __construct() {
        $this->options = Options::get_options();
	
	add_action('init', [$this, 'fau_karte']);
         add_action('init', [$this, 'fau_videoportal']);
         add_action('init', [$this, 'youtube_nocookie']);
         add_action('init', [$this, 'oembed_add_providers']);
	
	
    }


    /*-----------------------------------------------------------------------------------*/
    /* FAU Karte
    /*-----------------------------------------------------------------------------------*/  
    public function fau_karte() {
        if ($this->options->faukarte['active'] == true) {
            wp_oembed_add_provider('http://karte.fau.de/api/v1/iframe/*', 'https://karte.fau.de/api/v1/oembed/');
            wp_oembed_add_provider('https://karte.fau.de/api/v1/iframe/*', 'https://karte.fau.de/api/v1/oembed/');       
	    
	   wp_embed_register_handler('faukarte', '#https?://karte\.(uni\-erlangen|fau)\.de/\#([\d]+)/([\d\.]+)/([\d\.]+)#i', [$this, 'wp_embed_handler_faukarte']);  
	   // https://karte.fau.de/#17/49.59725/11.00835
        }
    }
    public function wp_embed_handler_faukarte($matches, $attr, $url, $rawattr) {
	$karte_api = $this->options->faukarte['apiurl'];
         $karte_start = 'karte.fau.de/#';
         $protokoll = "https://";
        
         if (strpos($url, 'http://') !== false) {
            $url = str_replace('http://', $protokoll, $url);
         }

        
         if (strpos($url, $karte_start) === false) {
             if (strpos($url, $karte_api) === false)
                $url = $protokoll . $karte_api . $url;
         }
	
	$title = $this->options->faukarte['title'];
	$id = uniqid();
	
	$embed = '<div class="fau-oembed" id="'.$id.'">';
	$embed .= '<iframe title="'.$title.'" src="'.$url.'"';
	$embed .= ' class="fau-karte defaultwidth"';
	$embed .= ' seamless></iframe>';
	$embed .= '</div>';

	wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_faukarte', $embed, $matches, $attr, $url, $rawattr);
    }
    /*-----------------------------------------------------------------------------------*/
    /*  FAU.TV
    /*-----------------------------------------------------------------------------------*/
    public function fau_videoportal() {
        if ($this->options->fau_videoportal['active']  == true) {           
	   wp_embed_register_handler('fautv', '#https?://(www\.)?video\.uni-erlangen\.de/webplayer/id/([\d]+)/?#i', [$this, 'wp_embed_handler_fautv']); 
	   wp_embed_register_handler('fautv2', '#https?://(www\.)?video\.(uni\-erlangen|fau)\.de/clip/id/.*#i', [$this, 'wp_embed_handler_fautv']); 
	   wp_embed_register_handler('fautv3', '#https?://(www\.)?video\.(uni\-erlangen|fau)\.de/collection/clip/\d+/.*#i', [$this, 'wp_embed_handler_fautv']); 
	    
	}
    }

    public function wp_embed_handler_fautv($matches, $attr, $url, $rawattr) {

        $oembed_url = 'https://www.video.uni-erlangen.de/services/oembed/?url=' . $matches[0] . '&format=json';
        $video = json_decode(wp_remote_retrieve_body(wp_safe_remote_get($oembed_url)), true);       

	
         if (!isset($video['file'])) {
            return '';
         }
	 
	$title = ! empty( $video['title'] ) ? $video['title'] : $this->options->embed_defaults['title'];
	$width = ! empty( $video['width'] ) ? $video['width'] : $this->options->embed_defaults['width'];
         $height = ! empty( $video['height'] ) ? $video['height'] : $this->options->embed_defaults['height'];	 
	$image = ! empty( $video['preview_image'] ) ? $video['preview_image'] : $this->options->fau_videoportal['defaultthumb'];
	 
         $file = $video['file'];

        
	$embed = '<div class="fau-oembed oembed fauvideo" itemscope itemtype="http://schema.org/VideoObject">';
	
	
	
	preg_match('/(\d+)$/',$url, $match);
	$id = $match[0]."-".uniqid();
	
	if ($this->options->fau_videoportal['display_title']) {
	    $embed .= '<h3 id="'.$id.'" itemprop="name">'.$title.'</h3>';
	} else {
	    $embed .= '<meta itemprop="name" content="'.$title.'">';
	}
	
        $embed .= '<meta itemprop="url" content="'.$file.'">';
        $embed .= '<meta itemprop="height" content="'.$video['height'].'">';
        $embed .= '<meta itemprop="width" content="'.$video['width'].'">';
        $embed .= '<meta itemprop="thumbnailUrl" content="'.$image.'">';
	if (! empty( $data['author_name'] )) {
	    $embed .= '<meta itemprop="author" content="'.$data['author_name'].'">';
	}
	if (! empty( $data['provider_name'] )) {
	    $embed .= '<meta itemprop="provider" content="'.$data['provider_name'].'">';
	}

	
         $embed .= '<div id="'.$id.'" class="fau-oembed-video">';	
         $embed .= '[video preload="none" width="' . $width . '" height="' . $height . '" src="' . $file . '" poster="' . $image . '"][/video]';
         $embed .= '</div>';
	if ($this->options->fau_videoportal['display_source']) {
	    $embed .= '<div class="caption">';
	    $embed .= __('Quelle:','fau-oembed').' ';
	    if (! empty( $data['author_name'] )) {
		$embed .= '<span class="author_name">'.$data['author_name'].'</span>, ';
	    }
	    $embed .= '<span class="url">'.$url.'</span>';
	    $embed .= '</div>';
	}
	$embed .= '</div>';
	
	wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_fautv', $embed, $matches, $attr, $url, $rawattr);
    }
    
    /*-----------------------------------------------------------------------------------*/
    /*  YouTube
    /*-----------------------------------------------------------------------------------*/    
    public function youtube_nocookie() {
        if ($this->options->youtube['active'] == true) {
            wp_oembed_remove_provider('#https?://(www\.)?youtube\.com/watch.*#i');
            wp_oembed_remove_provider('http://youtu.be/*');
            
            wp_embed_register_handler('ytnocookie', '#https?://www\.youtube\-nocookie\.com/embed/([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
            wp_embed_register_handler('ytnormal', '#https?://www\.youtube\.com/watch\?v=([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
            wp_embed_register_handler('ytnormal2', '#https?://www\.youtube\.com/watch\?feature=player_embedded&v=([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
            wp_embed_register_handler('yttube', '#http://youtu\.be/([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
        }
    }

    public function wp_embed_handler_ytnocookie($matches, $attr, $url, $rawattr) {
        if (is_feed()) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return sprintf('<a href="%1$s">%1$s</a>', $url);
            } else {
                return '';
            }
        }
       
        $relvideo = '';
        if ($this->options->youtube['norel'] == 1) {
            $relvideo = '?rel=0';
        }
       
	$data = OEmbed::get_data($url);
	$title = ! empty( $data['title'] ) ? $data['title'] : $this->options->embed_defaults['title'];
	$width = ! empty( $data['width'] ) ? $data['width'] : $this->options->embed_defaults['width'];
	$height = ! empty( $data['height'] ) ?  $data['height'] : $this->options->embed_defaults['height'];
	
	 
	$embed = '<div class="fau-oembed oembed" itemscope itemtype="http://schema.org/VideoObject">';
	 
	$id = $matches[1]."-".uniqid();
	// we use a uniq id here, cause of the case, that the same video could be 
	// displayed more as one time in the same website. this would then make an error,
	// cause id's habe to be uniq.
	if ($this->options->youtube['display_title']) {
	    $embed .= '<h3 id="'.$id.'" itemprop="name">'.$title.'</h3>';
	} else {
	    $embed .= '<meta itemprop="name" content="'.$title.'">';
	}
	$embed .= '<meta itemprop="url" content="'.$url.'">';
	if (! empty( $data['thumbnail_url'] )) {
	    $embed .= '<meta itemprop="thumbnail" content="'.$data['thumbnail_url'].'">';
	}
	if (! empty( $data['width'] )) {
	    $embed .= '<meta itemprop="width" content="'.$data['width'].'">';
	}
	if (! empty( $data['height'] )) {
	    $embed .= '<meta itemprop="height" content="'.$data['height'].'">';
	}
	if (! empty( $data['author_name'] )) {
	    $embed .= '<meta itemprop="author" content="'.$data['author_name'].'">';
	}
	if (! empty( $data['provider_name'] )) {
	    $embed .= '<meta itemprop="provider" content="'.$data['provider_name'].'">';
	}
	if ($this->options->youtube['display_title']) {
	    $embed .= '<iframe class="youtube defaultwidth"  aria-labelledby="'.$id.'"';
	} else {
	    $embed .= '<iframe class="youtube defaultwidth" title="'.$title.'"';
	}
	
	$embed .= ' src="https://www.youtube-nocookie.com/embed/'.esc_attr($matches[1]).$relvideo.'" width="'.$width.'" height="'.$height.'"></iframe>';
	if ($this->options->youtube['display_source']) {
	    $embed .= '<div class="caption">';
	    $embed .= __('Quelle:','fau-oembed').' ';
	    if (! empty( $data['author_name'] )) {
		$embed .= '<span class="author_name">'.$data['author_name'].'</span>, ';
	    }
	    $embed .= '<span class="url">'.$url.'</span>';
	    $embed .= '</div>';
	}
	$embed .= '</div>';
	
	
	
	wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_ytnocookie', $embed, $matches, $attr, $url, $rawattr);
    }

    /*-----------------------------------------------------------------------------------*/
    /*  Other Provider
    /*-----------------------------------------------------------------------------------*/   
    
    public function oembed_add_providers() {
	
	// oEmbed Schnittstelle der Informatik 12
	// Ansprechpartner: Andreas Bininda <andreas.bininda@fau.de>
        wp_oembed_add_provider('http://www12.informatik.uni-erlangen.de/people/bininda/*', 'http://www12.informatik.uni-erlangen.de/people/bininda/test/');
        wp_oembed_add_provider('https://www12.informatik.uni-erlangen.de/oembed-objekte/*', 'https://www12.informatik.uni-erlangen.de/oembed/');
        
	
	// oEmbed Schnittstelle von FAU Mac Support
	// Ansprechpartner: Gregor Longariva <gregor.longariva@fau.de>
        wp_oembed_add_provider('https://faumac.rrze.fau.de/oembed/*', 'https://faumac.rrze.fau.de/oembed/');
    }
   




}