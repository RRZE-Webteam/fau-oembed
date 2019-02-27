<?php

 namespace FAU\OEmbed\API;

defined('ABSPATH') || exit;

/**
 * IP address.
 *
 * This class is immutable, i.e. once created it can't be changed. Methods that modify it
 * will always return a new instance.
 */
 abstract class OEmbed {

    /*
     * Gist by https://gist.github.com/RadGH, 
     *      https://gist.github.com/RadGH/64d74e429583c5ab390f1395971b2495 
     */
    public static function get_data( $url, $width = null, $height = null ) {
	if ( function_exists('_wp_oembed_get_object') ) {
		require_once( ABSPATH . WPINC . '/class-oembed.php' );
	}
	
	$args = array();
	if ( $width ) $args['width'] = $width;
	if ( $height ) $args['height'] = $height;
	
	// If height is not given, but the width is, use 1080p aspect ratio. And vice versa.
	if ( $width && !$height ) $args['height'] = $width * (1080/1920);
	if ( !$width && $height ) $args['width'] = $height * (1920/1080);
	
	$oembed = _wp_oembed_get_object();
	$provider = $oembed->get_provider( $url, $args );
	$data = $oembed->fetch( $provider, $url, $args );
	
	if ( $data ) {
		$data = (array) $data;
		if ( !isset($data['url']) ) $data['url'] = $url;
		if ( !isset($data['provider']) ) $data['provider'] = $provider;
		// Convert url to hostname, eg: "youtube" instead of "https://youtube.com/"
		$data['provider-name'] = pathinfo( str_replace( array('www.'), '', parse_url( $url, PHP_URL_HOST )), PATHINFO_FILENAME );
		return $data;
	}
	
	return false;
    }
    
    
    /**
     * Filters the given oEmbed HTML to make sure iframes have a title attribute.
     *
     * @since 5.2.0
     *
     * @param string $result The oEmbed HTML result.
     * @param object $data   A data object result from an oEmbed provider.
     * @param string $url    The URL of the content to be embedded.
     * @return string The filtered oEmbed result.
     */
    public static function filter_oembed_title_attribute( $result, $data, $url ) {
	    if ( false === $result || ! in_array( $data->type, array( 'rich', 'video' ) ) ) {
		    return $result;
	    }

	    $title = ! empty( $data->title ) ? $data->title : '';

	    $pattern        = '/title\=[\"|\\\']{1}([^\"\\\']*)[\"|\\\']{1}/i';
	    $has_title_attr = preg_match( $pattern, $result, $matches );

	    if ( $has_title_attr && ! empty( $matches[1] ) ) {
		    $title = $matches[1];
	    }

	    /**
	     * Filters the title attribute of the given oEmbed HTML.
	     *
	     * @since 5.2.0
	     *
	     * @param string $title The title attribute.
	     * @param string $result The oEmbed HTML result.
	     * @param object $data   A data object result from an oEmbed provider.
	     * @param string $url    The URL of the content to be embedded.
	     */
	    $title = apply_filters( 'oembed_title_attribute', $title, $result, $data, $url );

	    if ( '' === $title ) {
		    return $result;
	    }

	    if ( $has_title_attr ) {
		    $result = preg_replace( $pattern, 'title="' . esc_attr( $title ) . '"', $result );
	    } else {
		    return str_ireplace( '<iframe ', sprintf( '<iframe title="%s" ', esc_attr( $title ) ), $result );
	    }

	    return $result;
    }

    
 }