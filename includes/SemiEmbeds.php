<?php


namespace FAU\OEmbed;

use  FAU\OEmbed\API\OEmbed;

defined('ABSPATH') || exit;

class SemiEmbeds {

    protected $options;

    public function __construct() {
        $this->options = Options::getOptions();
	
	add_action('init', [$this, 'univis_jobs']);

	
    }


    /*-----------------------------------------------------------------------------------*/
    /*  FAU.TV
    /*-----------------------------------------------------------------------------------*/
    public function univis_jobs() {
        if ($this->options->fau_videoportal->active  == true) {           
	   wp_embed_register_handler('univis_jobs', '#https?://(www\.)?univis\.(uni\-erlangen|fau)\.de/form\?__s=2&dsc=anew/position_view&.*#i', [$this, 'wp_embed_handler_univis_jobs']); 
	    
	}
    }

    public function wp_embed_handler_univis_jobs($matches, $attr, $url, $rawattr) {

  //      $oembed_url = 'https://www.video.uni-erlangen.de/services/oembed/?url=' . $matches[0] . '&format=json';
  //      $video = json_decode(wp_remote_retrieve_body(wp_safe_remote_get($oembed_url)), true);       

	$protokoll = "https://";
        
         if (strpos($url, 'http://') !== false) {
            $url = str_replace('http://', $protokoll, $url);
         }
	 

	$embed = '<div class="fau-oembed univis-job oembed">';
	
	$request = wp_safe_remote_get($url );

	if ( is_wp_error( $request ) ) {
	    return false;
	}
    
		
	$pattern = '/charset=[\"|\\\']?([^\"\\\']+)[\"|\\\']?/i';
	$type = $request['headers']['content-type'];
	    
	$ischarset = preg_match( $pattern, $type, $matches );
	if ( $ischarset && ! empty( $matches[1] ) ) {
	    $fromcharset = $matches[1];
	} else {
	    $fromcharset = 'ISO-8859-1';
	}
	$body = wp_remote_retrieve_body( $request );
	$toencoding = get_bloginfo("charset");
	
	$body = mb_convert_encoding($body, $toencoding, $fromcharset); 

	
	$pattern = '/<\!\-\- Start of content \-\->(.*)<\/td>/si';
	
	$foundposcontent = preg_match( $pattern, $body, $conm );
	if ( $foundposcontent && ! empty( $conm[1] ) ) {
	    $poscontent = $conm[1];
	}
	if (!empty($poscontent)) {
	    // remove gumbel html
	   	    
	    $poscontent = preg_replace('/<hr noshade>[\n\r\s]*<H2 align=center>(.*)<\/H2>[\n\r\s]*<hr noshade>/is','<div class="univis-stellentyp">$1:</div> ',$poscontent);

	    $poscontent = preg_replace('/<hr noshade>/i','',$poscontent);
	    $poscontent = preg_replace('/<([a-z]+) class=[\"|\\\']?formattedtext[\"|\\\']?>/i','<$1>',$poscontent);
	    $poscontent = preg_replace('/<li>[\n\r\s]*<p>[\n\r\s]*(.*)[\n\r\s]*<\/p>[\n\r\s]*<\/li>/i','<li>$1</li>',$poscontent);
	    $poscontent = preg_replace('/<li>[\n\r\s]*<span>[\n\r\s]*(.*)[\n\r\s]*<\/span>[\n\r\s]*<\/li>/i','<li>$1</li>',$poscontent);
	    $poscontent = preg_replace('/<input type=[\"|\\\']?submit[\"|\\\']?.*$/si','',$poscontent);
	    $poscontent = preg_replace('/<input ([^>]*)>/i','',$poscontent);
	    $poscontent = preg_replace('/[\n\r]+/s','',$poscontent);
	    $poscontent = preg_replace('/ align=[\"|\\\']?center[\"|\\\']?>/i','>',$poscontent);
	    $poscontent = preg_replace('/<p>\s*<\/p>/i','',$poscontent);
	    $poscontent = preg_replace('/<dt>\s*<\/dt>/i','',$poscontent);
	   
	    $poscontent = preg_replace('/<i>(.*)<\/i>/si','<em>$1</em>',$poscontent);
	    $poscontent = preg_replace('/<DD>/','<dd>',$poscontent);
	    $poscontent = preg_replace('/<DL>/','<dl>',$poscontent);
	    $poscontent = preg_replace('/<DT>/','<dt>',$poscontent);
	    $poscontent = preg_replace('/<\/DD>/','</dd>',$poscontent);
	    $poscontent = preg_replace('/<\/DL>/','</dl>',$poscontent);
	    $poscontent = preg_replace('/<\/DT>/','</dt>',$poscontent);
	    $poscontent = preg_replace('/<br \/>/','<br>',$poscontent);
	    $poscontent = preg_replace('/<span>/','',$poscontent);
	    $poscontent = preg_replace('/<\/span>/','',$poscontent);
	    
	    // Insert alert on End of call
	     $poscontent = preg_replace('/<h4>([\w\sa-z]+):\s+([\-\w\.\d]+)<\/h4>/i','[notice-attention]$1: $2[/notice-attention]',$poscontent);
	    
	     
	     // Insert Notice-Shortcode für Notices :)
	//     $poscontent = preg_replace('/<b>Hinweise für Bewerber\/\-innen<\/b>(.*)$/s','[notice-hinweis title="Hinweise für Bewerberinnen und Bewerber"]$1[/notice-hinweis]',$poscontent);
	    
	     
	      $poscontent = preg_replace('/<b>(.*)<\/b>/si','<strong>$1</strong>',$poscontent);
	}
	$embed .= $poscontent;
	// $embed .= $univishtmlpage;
	$embed .= "</div>";
	
	
	wp_enqueue_style('fau-oembed-style');
	return apply_filters('univis_jobs', $embed, $matches, $attr, $url, $rawattr);

    }
    
    


}