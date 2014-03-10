<?php
/**
 * Plugin Name: FAU-oEmbed
 * Description: Automatische Einbindung der FAU-Karten und des FAU Videoportals, Einbindung von YouTube-Videos ohne Cookies.
 * Version: 1.2
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

    const version = '1.2'; // Plugin-Version
    
    const option_name = '_fau_oembed';

    const textdomain = 'fau-oembed';
    
    const php_version = '5.3'; // Minimal erforderliche PHP-Version
    
    const wp_version = '3.8'; // Minimal erforderliche WordPress-Version
    
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
            'youtube_nocookie' => array(
                'active' => true,
                'width' => $width,
                'norel' => 1
            ),
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
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_plugin_styles'));
        
        add_filter( 'embed_defaults', array(__CLASS__, 'embed_defaults') );

        add_action('init', array(__CLASS__, 'fau_karte'));
        add_action('init', array(__CLASS__, 'fau_videoportal'));      
        add_action('init', array(__CLASS__, 'youtube_nocookie'));
        
        add_filter( 'pre_get_posts', array(__CLASS__, 'delete_oembed_caches' ) );
    }
    
    public static function add_options_page() {            
	self::$oembed_option_page = add_options_page(__('oEmbed', self::textdomain), __('oEmbed', self::textdomain), 'manage_options', 'options-oembed', array(__CLASS__, 'options_oembed'));   
        add_action( 'load-' . self::$oembed_option_page, array(__CLASS__, 'oembed_help_menu' ));
    }
    
    public static function oembed_help_menu() {
    
    $content_overview = array(
        '<p>' . __('WordPress bindet Videos, Bilder und andere Inhalte einiger Provider automatisch in Ihre Blog-Seiten ein, sobald Sie den Link auf die entsprechende Datei angeben. Unterstützt werden hierbei z.B. Daten von YouTube, Twitter und Flickr.', self::textdomain) . '</p>',
        '<p><strong>' . __('Standardwerte für eingebettete Objekte', self::textdomain) . '</strong></p>',
        '<p>' . __('Hier können Sie einstellen, in welcher Größe die Inhalte automatisch eingebunden werden. Bei Objekten, bei denen das Seitenverhältnis fest vorgegeben ist (z.B. Videos), wird hierbei auf die kleinste Größe beschränkt.', self::textdomain) . '</p>',
        '<p>' . __('Sofern Sie die automatische Einbindung von YouTube-Videos ohne Cookies aktiviert haben, werden diese in der hierbei festgelegten Größe angezeigt.', self::textdomain) . '</p>'
    );
    
    $content_faukarte = array(
        '<p><strong>' . __('Automatische Einbindung von FAU-Karten', self::textdomain) . '</strong></p>',
        '<p>' . __('Die Friedrich-Alexander-Universität Erlangen-Nürnberg bietet die Möglichkeit, Karten von Standorten universitärer Einrichtungen zu erstellen. Sofern Sie hier die automatische Einbindung von diesen FAU-Karten aktivieren, wird Ihnen statt eines Links die Karte in Ihrer Blog-Seite angezeigt.', self::textdomain) . '</p>',
        '<p>' . __('So erstellen Sie Ihre FAU-Karte:', self::textdomain) . '</p>',
        '<ol>',
        '<li>' . sprintf(__('Gehen Sie auf den %s.', self::textdomain), '<a href="http://karte.fau.de/generator" target="_blank">Kartengenerator der Friedrich-Alexander-Universität Erlangen-Nürnberg</a>') . '</li>',
        '<li>' . __('Geben Sie den Standort der FAU an, den Sie in Ihrer Karte anzeigen möchten.', self::textdomain) . '</li>',
        '<li>' . __('Klicken Sie auf <i>Abschicken</i>.', self::textdomain) . '</li>',
        '<li>' . __('Kopieren Sie den angezeigten direkten Link zum iFrame und geben Sie diesen auf Ihrer Blog-Seite an.', self::textdomain) . '</li>',
        '</ol>'
    );
    
    $content_fauvideo = array(
        '<p><strong>' . __('Automatische Einbindung des FAU Videoportals', self::textdomain) . '</strong></p>',
        '<p>' . __('Wenn Sie hier die automatische Einbindung des FAU Videoportals aktivieren, wird Ihnen statt des Links der Clip in Ihrer Blog-Seite angezeigt.', self::textdomain) . '</p>',
        '<p>' . __('So binden Sie einen Clip des Videoportals ein:', self::textdomain) . '</p>',
        '<ol>',
        '<li>' . sprintf(__('Gehen Sie auf das %s.', self::textdomain), '<a href="http://www.video.uni-erlangen.de/" target="_blank">Videoportal der Friedrich-Alexander-Universität Erlangen-Nürnberg</a>') . '</li>',
        '<li>' . __('Wählen Sie das Video aus, das Sie in Ihrem Blog anzeigen möchten.', self::textdomain) . '</li>',
        '<li>' . __('Kopieren Sie die Adresse des <i>Anschauen</i>-Links des Videos.', self::textdomain) . '</li>',
        '<li>' . __('Fügen Sie die kopierte Adresse auf Ihrer Blog-Seite ein.', self::textdomain) . '</li>',
        '</ol>'
    );
    
    $content_youtube_nocookie = array(
        '<p><strong>' . __('Automatische Einbindung von YouTube-Videos ohne Cookies', self::textdomain) . '</strong></p>',
        '<p>' . sprintf(__('Wenn Sie hier die automatische Einbindung von YouTube-Videos ohne Cookies aktivieren, wird Ihnen bei der Angabe eines Links zu einem Video von der Seite %s', self::textdomain), '<a href="http://www.youtube.de/" target="_blank">YouTube</a>') . '</p>',
        '<ol>',
        '<li>' . __('auf Ihrer Blog-Seite das YouTube-Video ohne die Verwendung von Cookies und', self::textdomain) . '</li>',
        '<li>' . __('zusätzlich noch der Link zu dem Video auf YouTube angezeigt.', self::textdomain) . '</li>',
        '</ol>',
        '<p>' . __('Dabei können Sie die Breite angeben, in der YouTube-Videos auf Ihrer Seite dargestellt werden.', self::textdomain) . '</p>',
        '<p>' . __('Wenn Sie die Option <i>Anzeige ähnlicher Videos ausblenden</i> aktivieren, werden Ihnen am Ende Ihres Videos keine ähnlichen Videos als Vorschau angezeigt.', self::textdomain) . '</p>',
    );
    
   $help_tab_overview = array(
        'id' => 'overview',
        'title' => __('Übersicht', self::textdomain),
        'content' => implode(PHP_EOL, $content_overview),
        );
    
    $help_tab_faukarte = array(
        'id' => 'faukarte',
        'title' => __('FAU-Karte', self::textdomain),
        'content' => implode(PHP_EOL, $content_faukarte),
        );

    $help_tab_fauvideo = array(
        'id' => 'fauvideo',
        'title' => __('FAU Videoportal', self::textdomain),
        'content' => implode(PHP_EOL, $content_fauvideo),
        );
    
    $help_tab_youtube_nocookie = array(
        'id' => 'youtube_nocookie',
        'title' => __('YouTube ohne Cookies', self::textdomain),
        'content' => implode(PHP_EOL, $content_youtube_nocookie),
    );

    $help_sidebar = __( '<p><strong>Für mehr Information:</strong></p><p><a href="http://blogs.fau.de/webworking">RRZE-Webworking</a></p><p><a href="https://github.com/RRZE-Webteam">RRZE-Webteam in Github</a></p>', self::textdomain);
    
    $screen = get_current_screen();

    if ( $screen->id != self::$oembed_option_page )  {
        return;
    }
    
    $screen->add_help_tab( $help_tab_overview );
    $screen->add_help_tab( $help_tab_faukarte );
    $screen->add_help_tab( $help_tab_fauvideo );
    $screen->add_help_tab( $help_tab_youtube_nocookie );
    
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
        
        add_settings_section('videoportal_section', __('Automatische Einbindung des FAU Videoportals', self::textdomain), '__return_false', 'oembed_options');
        add_settings_field('videoportal_active', __( 'Aktivieren', self::textdomain ), array(__CLASS__, 'videoportal_active'), 'oembed_options', 'videoportal_section');

        
        add_settings_section('youtube_nocookie_section', __('Automatische Einbindung von YouTube-Videos ohne Cookies', self::textdomain), '__return_false', 'oembed_options');
        add_settings_field('youtube_nocookie_active', __( 'Aktivieren', self::textdomain ), array(__CLASS__, 'youtube_nocookie_active'), 'oembed_options', 'youtube_nocookie_section');
        add_settings_field('youtube_nocookie_width', __( 'Breite', self::textdomain ), array(__CLASS__, 'youtube_nocookie_width'), 'oembed_options', 'youtube_nocookie_section');
        add_settings_field('youtube_nocookie_norel', __( 'Anzeige ähnlicher Videos ausblenden', self::textdomain ), array(__CLASS__, 'youtube_nocookie_norel'), 'oembed_options', 'youtube_nocookie_section');
    }
    
    public static function register_plugin_styles() {
        wp_register_style('fau-oembed', plugins_url('fau-oembed/css/fau-oembed.css'), array('dashicons'));
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
     * 
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
    
        
    public static function videoportal_active() {
        $options = self::get_options();
        ?>
            <input type='checkbox' name="<?php printf('%s[fau_videoportal]', self::option_name); ?>" <?php checked($options['fau_videoportal'], true); ?>>
                   
        <?php
    }
    
    
    public static function youtube_nocookie_active() {
        $options = self::get_options();
        ?>
            <input type='checkbox' name="<?php printf('%s[youtube_nocookie][active]', self::option_name); ?>" <?php checked($options['youtube_nocookie']['active'], true); ?>>
                   
        <?php
    }
       
     public static function youtube_nocookie_width() {
        $options = self::get_options();  
        
        ?>
            <input type='text' name="<?php printf('%s[youtube_nocookie][width]', self::option_name); ?>" value="<?php echo $options['youtube_nocookie']['width']; ?>"><p class="description"><?php _e('Zu empfehlen ist eine Breite von mindestens 350px.', self::textdomain); ?></p>
       <?php
    }
    
    public static function youtube_nocookie_norel() {
        $options = self::get_options();
        ?>
            <input type='checkbox' name="<?php printf('%s[youtube_nocookie][norel]', self::option_name); ?>" <?php checked($options['youtube_nocookie']['norel'], true); ?>><p class="description"><?php _e('Funktioniert nur, wenn die automatische Einbindung von YouTube-Videos aktiviert ist.', self::textdomain); ?></p>
                   
        <?php
    }

    public static function options_validate($input) {
        $defaults = self::default_options();
        $options = self::get_options();
        
        $input['embed_defaults']['width'] = (int) $input['embed_defaults']['width'];
        $input['embed_defaults']['height'] = (int) $input['embed_defaults']['height'];
        $input['embed_defaults']['width'] = !empty($input['embed_defaults']['width']) ? $input['embed_defaults']['width'] : $defaults['embed_defaults']['width'];
        $input['embed_defaults']['height'] = !empty($input['embed_defaults']['height']) ? $input['embed_defaults']['height'] : $defaults['embed_defaults']['height'];
        
        $input['faukarte']['active'] = isset($input['faukarte']['active']) ? true : false;
        $input['fau_videoportal'] = isset($input['fau_videoportal']) ? true : false;
        
        
        
        /* Modifizieren der Anzeige nur für FAU-Karten funktioniert noch nicht
         * 
        $input['faukarte']['width'] = (int) $input['faukarte']['width'];
        $input['faukarte']['height'] = (int) $input['faukarte']['height'];
        $input['faukarte']['width'] = !empty($input['faukarte']['width']) ? $input['faukarte']['width'] : $defaults['faukarte']['width'];
        $input['faukarte']['height'] = !empty($input['faukarte']['height']) ? $input['faukarte']['height'] : $defaults['faukarte']['height'];
        */
        
        $input['youtube_nocookie']['active'] = isset($input['youtube_nocookie']['active']) ? true : false;
        $input['youtube_nocookie']['norel'] = isset($input['youtube_nocookie']['norel']) ? 1 : 0;
        $input['youtube_nocookie']['width'] = (int) $input['youtube_nocookie']['width'];
        $input['youtube_nocookie']['width'] = !empty($input['youtube_nocookie']['width']) ? $input['youtube_nocookie']['width'] : $defaults['youtube_nocookie']['width'];
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
 * Versuch, die FAU-Karte nicht über add_provider sondern über register_handler einzubinden um die Größenangabe nur für die FAU-Karte zu beeinflussen
 * 
    public static function fau_karte() {
        $options = self::get_options();
        if ($options['faukarte']['active'] == true) {
            //wp_embed_register_handler('faukarte', '#http://karte\.fau\.de/api/v1/iframe/([a-z0-9 //\-_]+)#i', 'wp_embed_handler_faukarte');
            wp_embed_register_handler('faukarte', '#http://karte\.fau\.de/api/v1/iframe/address/findelgasse#i', 'wp_embed_handler_faukarte');
        }
    }
      
    public static function wp_embed_handler_faukarte( $matches, $attr, $url, $rawattr ) {
         $options = self::get_options();
          $embed = sprintf(
            '<div class="embed-faukarte"><iframe src="http://karte.fau.de/api/v1/iframe/%1$s" width="%2$spx" height="%3$spx" frameborder="0" scrolling="no" marginwidth="0" marginheight="0"></iframe></div>',
            esc_attr($matches[1]),
            $options['faukarte']['width'],
            $options['faukarte']['height']
          
        );
         
        return apply_filters( 'embed_faukarte', $embed, $matches, $attr, $url, $rawattr );
     } 
    
 * 
 */
    
    
    public static function fau_videoportal() {
        $options = self::get_options();
        if ($options['fau_videoportal'] == true) {      
            wp_oembed_add_provider('http://www.video.uni-erlangen.de/webplayer/id/*', 'http://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://www.video.fau.de/webplayer/id/*', 'http://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://video.fau.de/webplayer/id/*', 'http://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://www.fau-tv.de/webplayer/id/*', 'http://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://fau-tv.de/webplayer/id/*', 'http://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://www.fau.tv/webplayer/id/*', 'http://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://fau.tv/webplayer/id/*', 'http://www.video.uni-erlangen.de/services/oembed/?url=');
            //wp_oembed_add_provider('http://www.video.uni-erlangen.de/clip/id/*', 'http://www.dev.video.uni-erlangen.de/services/oembed/?url=');      //vom Videoportal noch nicht unterstützt
        }
    }
   
  
    
    public static function youtube_nocookie() { 
        $options = self::get_options();
        if ($options['youtube_nocookie']['active'] == true) {    
            wp_oembed_remove_provider( '#https?://(www\.)?youtube\.com/watch.*#i' ); 
            wp_oembed_remove_provider( 'http://youtu.be/*' ); 
       
            wp_embed_register_handler( 'ytnocookie', '#https?://www\.youtube\-nocookie\.com/embed/([a-z0-9\-_]+)#i', array(__CLASS__, 'wp_embed_handler_ytnocookie') );
            wp_embed_register_handler( 'ytnormal', '#https?://www\.youtube\.com/watch\?v=([a-z0-9\-_]+)#i', array(__CLASS__, 'wp_embed_handler_ytnocookie') );
            wp_embed_register_handler( 'ytnormal2', '#https?://www\.youtube\.com/watch\?feature=player_embedded&v=([a-z0-9\-_]+)#i', array(__CLASS__, 'wp_embed_handler_ytnocookie') );
            wp_embed_register_handler( 'yttube', '#http://youtu\.be/([a-z0-9\-_]+)#i', array(__CLASS__, 'wp_embed_handler_ytnocookie') );
        }
    }


    public static function wp_embed_handler_ytnocookie( $matches, $attr, $url, $rawattr ) {
        $options = self::get_options();
        
        wp_enqueue_style('fau-oembed');
      
        $relvideo = '';
        if ($options['youtube_nocookie']['norel']==1) {
            $relvideo = '?rel=0';
        }
        $height = $options['youtube_nocookie']['width']*36/64;
        $str = __('YouTube-Video', self::textdomain);
        $embed = sprintf(
            '<div class="embed-youtube"><iframe src="https://www.youtube-nocookie.com/embed/%1$s%3$s" width="%2$spx" height="%4$spx" frameborder="0" scrolling="no" marginwidth="0" marginheight="0"></iframe><p>%5$s: <a href="https://www.youtube.com/watch?v=%1$s">https://www.youtube.com/watch?v=%1$s</a></p></div>',
            esc_attr($matches[1]),
            $options['youtube_nocookie']['width'],
            $relvideo,
            $height,
            $str
        );
        return apply_filters( 'embed_ytnocookie', $embed, $matches, $attr, $url, $rawattr );
    }   

    public static function delete_oembed_caches( $query ) {
        
        if( !is_single() && !is_page() )
            return;
        
        $post = get_post();
        $post_ID = $post->ID;
        $content = $post->post_content;
        
        if( !preg_match_all( '|^\s*(https?://[^\s"]+)\s*$|im', $content ) )
                return;
        
        $post_metas = get_post_custom_keys( $post_ID );
        if ( empty($post_metas) )
            return;

        foreach( $post_metas as $post_meta_key ) {
            if ( '_oembed_' == substr( $post_meta_key, 0, 8 ) )
                delete_post_meta( $post_ID, $post_meta_key );
        }
        
    }
    
    
}
