<?php
/**
 * Plugin Name: FAU-oEmbed
 * Description: oEmbed-Funktionen.
 * Version: 1.0
 * Author: RRZE-Webteam
 * Author URI: https://github.com/RRZE-Webteam/
 * License: GPLv2 or later
 */

/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

register_activation_hook(__FILE__, array('FAU_oEmbed', 'activation_hook'));

add_action('plugins_loaded', array( 'FAU_oEmbed', 'init'));

class FAU_oEmbed {

    const version = '1.0'; // Plugin-Version
    
    const option_name = '_fau_oembed';

    const textdomain = 'fau-oembed';
    
    const php_version = '5.3'; // Minimal erforderliche PHP-Version
    
    const wp_version = '3.7'; // Minimal erforderliche WordPress-Version
    
    private static $oembed_option_page = null;

    public static function activation_hook() {
        $error = '';

        if ( version_compare(PHP_VERSION, self::php_version, '<')) {
            $error = sprintf(__( 'Ihre PHP-Version %s ist veraltet. Bitte aktualisieren Sie mindestens auf die PHP-Version %s.', self::textdomain), PHP_VERSION, self::php_version);
        }

        if ( version_compare($GLOBALS['wp_version'], self::wp_version, '<' ) ) {
            $error = sprintf(__( 'Ihre Wordpress-Version %s ist veraltet. Bitte aktualisieren Sie mindestens auf die Wordpress-Version %s.', self::textdomain), $GLOBALS['wp_version'], self::wp_version);
        }

        if( ! empty($error)) {
            deactivate_plugins( plugin_basename(__FILE__), false, true);
            wp_die($error);
        }
    }

    private static function default_options() {
        if ( ! empty( $GLOBALS['content_width'] ) )
            $width = (int) $GLOBALS['content_width'];

        if ( empty( $width ) )
            $width = 500;

        $height = min( ceil( $width * 1.5 ), 1000 );
     
        $options = array(
            'embed_defaults' => array(
                'width' => $width,
                'height' => $height
            ),
            'faukarte' => array(
                'active' => true,
                'width' => $width,
                'height' => $height
            ),
            'fau_videoportal' => true,
            'youtube_nocookie' => true
        );
        
        return $options;
    }
    
    private static function get_options() {
        $defaults = self::default_options();

        $options = (array) get_option(self::option_name);
        
        $options = wp_parse_args($options, $defaults);
        
        $options = array_intersect_key($options, $defaults);

        return $options;
    }
    
    public static function init() {
        load_plugin_textdomain( self::textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        
        add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
        add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) );
        
        add_filter( 'embed_defaults', array(__CLASS__, 'embed_defaults') );


        add_action('init', array(__CLASS__, 'fau_karte'));
        /*
        * Automatische Einbindung des FAU Videoportals und von YouTube-Nocookie-Videos funktioniert noch nicht
        * 
        add_action('init', array(__CLASS__, 'fau_videoportal'));
        add_action('init', array(__CLASS__, 'youtube_nocookie'));  
         * 
         */     
    }
    
    public static function add_options_page() {
            
	self::$oembed_option_page = add_options_page(__('oEmbed', self::textdomain), __('oEmbed', self::textdomain), 'manage_options', 'options-oembed', array(__CLASS__, 'options_oembed'));   
        add_action( 'load-' . self::$oembed_option_page, array(__CLASS__, 'oembed_help_menu' ));
    }
    
   public static function oembed_help_menu() {
    
    $content = array(
        '<p>' . __('WordPress bindet Videos, Bilder und andere Inhalte einiger Provider automatisch in Ihre Blog-Seiten ein, sobald Sie den Link auf die entsprechende Datei angeben. Unterstützt werden hierbei z.B. Daten von YouTube, Twitter und Flickr.', self::textdomain) . '</p>',
        '<p><strong>' . __('Standardwerte für eingebettete Objekte', self::textdomain) . '</strong></p>',
        '<p>' . __('Hier können Sie einstellen, in welcher Größe die Inhalt automatisch eingebunden werden.', self::textdomain) . '</p>',
        '<p><strong>' . __('Automatische Einbindung von FAU-Karten', self::textdomain) . '</strong></p>',
        '<p>' . __('Die Friedrich-Alexander-Universität Erlangen-Nürnberg bietet die Möglichkeit, Karten von Standorten universitärer Einrichtungen zu erstellen. Sofern Sie hier die automatische Einbindung von diesen FAU-Karten aktivieren, wird Ihnen statt eines Links die Karte in Ihrer Blog-Seite angezeigt.', self::textdomain) . '</p>',
        '<p>' . __('So erstellen Sie Ihre FAU-Karte:', self::textdomain) . '</p>',
        '<ol>',
        '<li>' . sprintf(__('Gehen Sie auf den %s.', self::textdomain), '<a href="http://karte.fau.de/generator" target="_blank">Kartengenerator der Friedrich-Alexander-Universität Erlangen-Nürnberg</a>') . '</li>',
        '<li>' . __('Geben Sie den Standort der FAU an, den Sie in Ihrer Karte anzeigen möchten.', self::textdomain),
        '<li>' . __('Klicken Sie auf Abschicken.', self::textdomain),
        '<li>' . __('Kopieren Sie den angezeigten direkten Link zum iFrame und geben Sie diesen auf Ihrer Blog-Seite an.', self::textdomain) . '</li>',
        
       
       
        '</ol>'
    );
    
    $help_tab = array(
        'id' => 'fau_oembed',
        'title' => __('oEmbed', self::textdomain),
        'content' => implode(PHP_EOL, $content),
        );
    
    
    $help_sidebar = __( '<p><strong>Für mehr Information:</strong></p><p><a href="http://blogs.fau.de/webworking">RRZE-Webworking</a></p><p><a href="https://github.com/RRZE-Webteam">RRZE-Webteam in Github</a></p>', self::textdomain);
    
    $screen = get_current_screen();

    if ( $screen->id != self::$oembed_option_page )  {
        return;
    }
    
    $screen->add_help_tab( $help_tab );

    $screen->set_help_sidebar( $help_sidebar );
    
   }
        
        
    public static function options_oembed() {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php echo esc_html(__('Einstellungen &rsaquo; oEmbed', self::textdomain)); ?></h2>
            
            <form method="post" action="options.php">
                <?php 
                settings_fields('oembed_options');
                do_settings_sections('oembed_options');
                submit_button();
                ?>
            </form>            
        </div>
        <?php   
    }
    
    public static function admin_init() {        

        register_setting('oembed_options', self::option_name, array(__CLASS__, 'options_validate'));
        
        add_settings_section('embed_default_section', __('Standardwerte für eingebettete Objekte', self::textdomain), '__return_false', 'oembed_options');
        
        add_settings_field('embed_defaults_width', __( 'Breite', self::textdomain ), array(__CLASS__, 'embed_defaults_width'), 'oembed_options', 'embed_default_section');
        add_settings_field('embed_defaults_height', __( 'Höhe', self::textdomain ), array(__CLASS__, 'embed_defaults_height'), 'oembed_options', 'embed_default_section');
        
        add_settings_section('faukarte_section', __('Automatische Einbindung von FAU-Karten', self::textdomain), '__return_false', 'oembed_options');
        add_settings_field('faukarte_active', __( 'Aktivieren', self::textdomain ), array(__CLASS__, 'faukarte_active'), 'oembed_options', 'faukarte_section');
        //add_settings_field('faukarte_width', __( 'Breite', self::textdomain ), array(__CLASS__, 'faukarte_width'), 'oembed_options', 'faukarte_section');
        //add_settings_field('faukarte_height', __( 'Höhe', self::textdomain ), array(__CLASS__, 'faukarte_height'), 'oembed_options', 'faukarte_section');
    }
    
    public static function embed_defaults_width() {
        $options = self::get_options();    
        ?>
            <input type='text' name="<?php printf('%s[embed_defaults][width]', self::option_name); ?>" value="<?php echo $options['embed_defaults']['width']; ?>">
       <?php
    }
    
    public static function embed_defaults_height() {
        $options = self::get_options();    
        ?>
            <input type='text' name="<?php printf('%s[embed_defaults][height]', self::option_name); ?>" value="<?php echo $options['embed_defaults']['height']; ?>">
       <?php
    }
    
    public static function faukarte_active() {
        $options = self::get_options();
        ?>
            <input type='checkbox' name="<?php printf('%s[faukarte][active]', self::option_name); ?>" <?php checked($options['faukarte']['active'], true); ?>>
                   
        <?php
    }
       /* Modifizieren der Anzeige nur für FAU-Karten funktioniert nicht    
     public static function faukarte_width() {
        $options = self::get_options();  
        
        ?>
            <input type='text' name="<?php printf('%s[faukarte][width]', self::option_name); ?>" value="<?php echo $options['faukarte']['width']; ?>">
       <?php
    }
    
    public static function faukarte_height() {
        $options = self::get_options();    
        ?>
            <input type='text' name="<?php printf('%s[faukarte][height]', self::option_name); ?>" value="<?php echo $options['faukarte']['height']; ?>">
       <?php
    }
   
    */
    
    
    public static function options_validate($input) {
        $defaults = self::default_options();
        $options = self::get_options();
        
        $input['embed_defaults']['width'] = (int) $input['embed_defaults']['width'];
        $input['embed_defaults']['height'] = (int) $input['embed_defaults']['height'];
        $input['embed_defaults']['width'] = !empty($input['embed_defaults']['width']) ? $input['embed_defaults']['width'] : $defaults['embed_defaults']['width'];
        $input['embed_defaults']['height'] = !empty($input['embed_defaults']['height']) ? $input['embed_defaults']['height'] : $defaults['embed_defaults']['height'];
        
        $input['faukarte']['active'] = isset($input['faukarte']['active']) ? true : false;
        
        /* Modifizieren der Anzeige nur für FAU-Karten funktioniert nicht
         * 
        $input['faukarte']['width'] = (int) $input['faukarte']['width'];
        $input['faukarte']['height'] = (int) $input['faukarte']['height'];
        $input['faukarte']['width'] = !empty($input['faukarte']['width']) ? $input['faukarte']['width'] : $defaults['faukarte']['width'];
        $input['faukarte']['height'] = !empty($input['faukarte']['height']) ? $input['faukarte']['height'] : $defaults['faukarte']['height'];
        */
        return $input;
    }
    
    public static function embed_defaults($defaults) {     
        $options = self::get_options();

        $defaults['width'] = $options['embed_defaults']['width'];
        $defaults['height'] = $options['embed_defaults']['height'];
        
        return $defaults;
    }
    
    public static function fau_karte() {
        $options = self::get_options();
        if ($options['faukarte']['active'] == true) {
            wp_oembed_add_provider( 'http://karte.fau.de/api/v1/iframe/*', 'http://karte.fau.de/api/v1/oembed?url=' );
        } 
        
    }
/*
 * Automatische Einbindung des FAU Videoportals und von YouTube-Nocookie-Videos funktioniert noch nicht
 * 
 * 
 *    
    public static function fau_videoportal() {
        wp_oembed_add_provider('http://www.video.uni-erlangen.de/webplayer/id/*', 'http://www.dev.video.uni-erlangen.de/services/oembed/?url=');
    }
            
    public static function youtube_nocookie() { 
        // Filter fuer YouTube Embed mit nocookie:     
        wp_oembed_remove_provider( '#https?://(www\.)?youtube\.com/watch.*#i' ); 
        wp_embed_register_handler( 'ytnocookie', '#https?://www\.youtube\-nocookie\.com/embed/([a-z0-9\-_]+)#i', array(__CLASS__, 'wp_embed_handler_ytnocookie') );
        wp_embed_register_handler( 'ytnormal', '#https?://www\.youtube\.com/watch\?v=([a-z0-9\-_]+)#i', array(__CLASS__, 'wp_embed_handler_ytnocookie') );
        wp_embed_register_handler( 'ytnormal2', '#https?://www\.youtube\.com/watch\?feature=player_embedded&v=([a-z0-9\-_]+)#i', array(__CLASS__, 'wp_embed_handler_ytnocookie') );
    }


    public static function wp_embed_handler_ytnocookie( $matches, $attr, $url, $rawattr ) {
        $defaultoptions = array(
            'yt-norel' => 0,
            'yt-content-width' => '400',
            'yt-content-height' => '350 '
        );   
        $relvideo = '';
        if ($defaultoptions['yt-norel']==1) {
            $relvideo = '?rel=0';
        }
        $embed = sprintf(                               
                '<div class="embed-youtube"><p><img src="%1$s/images/social-media/youtube-24x24.png" width="24" height="24" alt="">YouTube-Video: <a href="https://www.youtube.com/watch?v=%2$s">https://www.youtube.com/watch?v=%2… src="https://www.youtube-nocookie.com/embed/%2$s%5$s" width="%3$spx" height="%4$spx" frameborder="0" scrolling="no" marginwidth="0" marginheight="0"></iframe></div>',
                get_template_directory_uri(),
                esc_attr($matches[1]),
                $defaultoptions['yt-content-width'],
                $defaultoptions['yt-content-height'],
                $relvideo

                );

        return apply_filters( 'embed_ytnocookie', $embed, $matches, $attr, $url, $rawattr );
    }   
   * 
   * 
   */
    
}
