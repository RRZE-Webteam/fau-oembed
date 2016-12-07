<?php
/*
  Plugin Name: FAU-oEmbed
  Plugin URI: https://github.com/RRZE-Webteam/fau-oembed
  Description: Automatische Einbindung der FAU-Karten und des FAU Videoportals, Einbindung von YouTube-Videos ohne Cookies.
  Version: 2.1.6
  Author: RRZE-Webteam
  Author URI: https://github.com/RRZE-Webteam/
  License: GPLv2 or later
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




add_action('plugins_loaded', array('FAU_oEmbed', 'instance'));

register_activation_hook(__FILE__, array('FAU_oEmbed', 'activation'));

class FAU_oEmbed {

    const version = '2.1.6'; // Plugin-Version
    const option_name = '_fau_oembed';
    const textdomain = 'fau-oembed';
    const php_version = '5.3'; // Minimal erforderliche PHP-Version
    const wp_version = '3.9'; // Minimal erforderliche WordPress-Version

    protected static $instance = null;

    public static function instance() {

        if (null == self::$instance) {
            self::$instance = new self;
            self::$instance->init();
        }

        return self::$instance;
    }

    public function __construct() {
        add_action('init', function() {

            wp_embed_register_handler(
                    'fautv', '#https://www\.video\.uni-erlangen\.de/webplayer/id/([\d]+)/?#i', array($this, 'wp_embed_handler_fautv')
            );
        });

        add_action('admin_enqueue_scripts', array($this, 'fau_oembed_enqueue_admin_script'));
        add_action('wp_enqueue_scripts', array($this, 'fau_oembed_enqueue_style'));
    }
 
    private $oembed_option_page = null;
    private $videoportal = array();

    public static function activation() {
        self::version_compare();
    }

    function fau_oembed_enqueue_admin_script() {

        if (isset($_GET['page'])) {


            if ($_GET['page'] == 'options-oembed') {
                wp_enqueue_media();
                wp_enqueue_script('fau-oembed-script', plugin_dir_url(__FILE__) . 'js/main.js');
                wp_localize_script('fau-oembed-script', 'oembed_default_place_holder_img_url', plugin_dir_url(__FILE__) . '/img/default-thumbnail.jpg');
            }
        }
    }

    function fau_oembed_enqueue_style(){
        
        
        wp_enqueue_style('fau-oembed-style', plugin_dir_url(__FILE__) . 'css/style.css');
        
    }
    private static function version_compare() {
        $error = '';

        if (version_compare(PHP_VERSION, self::php_version, '<')) {
            $error = sprintf(__('Ihre PHP-Version %s ist veraltet. Bitte aktualisieren Sie mindestens auf die PHP-Version %s.','fau-oembed'), PHP_VERSION, self::php_version);
        }

        if (version_compare($GLOBALS['wp_version'], self::wp_version, '<')) {
            $error = sprintf(__('Ihre Wordpress-Version %s ist veraltet. Bitte aktualisieren Sie mindestens auf die Wordpress-Version %s.','fau-oembed'), $GLOBALS['wp_version'], self::wp_version);
        }

        if (!empty($error)) {
            deactivate_plugins(plugin_basename(__FILE__), false, true);
            wp_die($error);
        }
    }

    private function default_options() {
        if (!empty($GLOBALS['content_width']))
            $width = (int) $GLOBALS['content_width'];

        if (empty($width))
            $width = 500;

        $height = min(ceil($width * 1.5), 1000);

        $options = array(
            'embed_defaults' => array(
                'width' => $width,
                'height' => $height,
                'place_holder' => plugin_dir_path(__DIR__) . '/images/placeholder.png',
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

    public function wp_embed_handler_fautv($matches, $attr, $url, $rawattr) {

        $options = $this->get_options();

        $html = file_get_contents('https://www.video.uni-erlangen.de/services/oembed/?url=' . $matches[0]);

        preg_match('/https(.*)m4v/iU', htmlspecialchars($html), $mactch);

        $embed = '<div id="fau-oembed-video" style="width:'.$options['embed_defaults']['width'].'px;height:'.$options['embed_defaults']['width'].'px"> [video preload="none" width="' . $options['embed_defaults']['width'] . '" height="' . $options['embed_defaults']['height'] . '" src="' . $mactch[0] . '" poster="' . wp_get_attachment_url($options['embed_defaults']['place_holder']) . '"][/video]</div>';


        return apply_filters('embed_fautv', $embed, $matches, $attr, $url, $rawattr);
    }

    private function get_options() {
        $defaults = $this->default_options();

        $options = (array) get_option(self::option_name);

        $options = wp_parse_args($options, $defaults);

        $options = array_intersect_key($options, $defaults);

        return $options;
    }

    private function init() {
        load_plugin_textdomain(self::textdomain, false, dirname(plugin_basename(__FILE__)) . '/languages');

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_options_page'));

        add_filter('embed_defaults', array($this, 'embed_defaults'));

        add_action('init', array($this, 'fau_karte'));
        add_action('init', array($this, 'fau_videoportal'));
        add_action('init', array($this, 'youtube_nocookie'));

        //add_filter('oembed_fetch_url', array($this, 'oembed_url_filter'), 10, 3);
        //add_filter('embed_oembed_html', array($this, 'oembed_html_filter'), 10, 4);

        add_shortcode('faukarte', array($this, 'shortcode_faukarte'));
    }

    public function add_options_page() {
        $this->oembed_option_page = add_options_page(__('oEmbed','fau-oembed'), __('oEmbed','fau-oembed'), 'manage_options', 'options-oembed', array($this, 'options_oembed'));
        add_action('load-' . $this->oembed_option_page, array($this, 'oembed_help_menu'));
    }

    public function oembed_help_menu() {

        $content_overview = array(
            '<p>' . __('WordPress bindet Videos, Bilder und andere Inhalte einiger Provider automatisch in Ihre Blog-Seiten ein, sobald Sie den Link auf die entsprechende Datei angeben. Unterstützt werden hierbei z.B. Daten von YouTube, Twitter und Flickr.','fau-oembed') . '</p>',
            '<p><strong>' . __('Standardwerte für eingebettete Objekte','fau-oembed') . '</strong></p>',
            '<p>' . __('Hier können Sie einstellen, in welcher Größe die Inhalte automatisch eingebunden werden. Bei Objekten, bei denen das Seitenverhältnis fest vorgegeben ist (z.B. Videos), wird hierbei auf die kleinste Größe beschränkt.','fau-oembed') . '</p>',
            '<p>' . __('Sofern Sie die automatische Einbindung von YouTube-Videos ohne Cookies aktiviert haben, werden diese in der hierbei festgelegten Größe angezeigt.','fau-oembed') . '</p>'
        );

        $content_faukarte = array(
            '<p><strong>' . __('Automatische Einbindung von FAU-Karten','fau-oembed') . '</strong></p>',
            '<p>' . __('Die Friedrich-Alexander-Universität Erlangen-Nürnberg bietet die Möglichkeit, Karten von Standorten universitärer Einrichtungen zu erstellen. Sofern Sie hier die automatische Einbindung von diesen FAU-Karten aktivieren, wird Ihnen statt eines Links die Karte in Ihrer Blog-Seite angezeigt.','fau-oembed') . '</p>',
            '<p>' . __('So erstellen Sie Ihre FAU-Karte:','fau-oembed') . '</p>',
            '<ol>',
            '<li>' . sprintf(__('Gehen Sie auf den %s.','fau-oembed'), '<a href="http://karte.fau.de/generator" target="_blank">Kartengenerator der Friedrich-Alexander-Universität Erlangen-Nürnberg</a>') . '</li>',
            '<li>' . __('Geben Sie den Standort der FAU an, den Sie in Ihrer Karte anzeigen möchten.','fau-oembed') . '</li>',
            '<li>' . __('Klicken Sie auf <i>Abschicken</i>.','fau-oembed') . '</li>',
            '<li>' . __('Kopieren Sie den angezeigten direkten Link zum iFrame und geben Sie diesen auf Ihrer Blog-Seite an.','fau-oembed') . '</li>',
            '</ol>',
            '<p><strong>' . __('Einbindung von FAU-Karten über Shortcode','fau-oembed') . '</strong></p>',
            '<p>' . __('Alternativ kann eine Karte von http://karte.fau.de auch über den Shortcode [faukarte] mit folgenden Parametern eingebunden werden:','fau-oembed') . '</p>',
            '<ol>',
            '<li>' . sprintf(__('url: Adresse des anzuzeigenden Kartenausschnitts, ohne vorangestelltes %1$s. Hier können Sie auch direkt die URL des gewählten Kartenausschnittes ohne vorangestelltes %2$s verwenden.','fau-oembed'), 'https://karte.fau.de/api/v1/iframe/', 'https://karte.fau.de/') . '</li>',
            '<li>' . __('width: Breite des anzuzeigenden Kartenausschnitts (auch %-Angaben sind erlaubt).','fau-oembed') . '</li>',
            '<li>' . __('height: Höhe des anzuzeigenden Kartenausschnitts (auch %-Angaben sind erlaubt).','fau-oembed') . '</li>',
            '<li>' . __('zoom: Zoomfaktor für den anzuzeigenden Kartenausschnitt (Wert zwischen 1 und 19, je größer der Wert desto größer die Darstellung).','fau-oembed') . '</li>',
            '<li>' . __('Beispiel: [faukarte url="address/martensstraße 1" width="100%" height="100px" zoom="12"]','fau-oembed') . '</li>',
            '</ol>',
        );

        $content_fauvideo = array(
            '<p><strong>' . __('Automatische Einbindung des FAU Videoportals','fau-oembed') . '</strong></p>',
            '<p>' . __('Wenn Sie hier die automatische Einbindung des FAU Videoportals aktivieren, wird Ihnen statt des Links der Clip in Ihrer Blog-Seite angezeigt.','fau-oembed') . '</p>',
            '<p>' . __('So binden Sie einen Clip des Videoportals ein:','fau-oembed') . '</p>',
            '<ol>',
            '<li>' . sprintf(__('Gehen Sie auf das %s.','fau-oembed'), '<a href="http://www.video.uni-erlangen.de/" target="_blank">Videoportal der Friedrich-Alexander-Universität Erlangen-Nürnberg</a>') . '</li>',
            '<li>' . __('Wählen Sie das Video aus, das Sie in Ihrem Blog anzeigen möchten.','fau-oembed') . '</li>',
            '<li>' . __('Kopieren Sie die Adresse des <i>Anschauen</i>-Links des Videos.','fau-oembed') . '</li>',
            '<li>' . __('Fügen Sie die kopierte Adresse auf Ihrer Seite ein.','fau-oembed') . '</li>',
            '</ol>'
        );

        $content_youtube_nocookie = array(
            '<p><strong>' . __('Automatische Einbindung von YouTube-Videos ohne Cookies','fau-oembed') . '</strong></p>',
            '<p>' . sprintf(__('Wenn Sie hier die automatische Einbindung von YouTube-Videos ohne Cookies aktivieren, wird Ihnen bei der Angabe eines Links zu einem Video von der Seite %s','fau-oembed'), '<a href="http://www.youtube.de/" target="_blank">YouTube</a>') . '</p>',
            '<ol>',
            '<li>' . __('auf Ihrer Blog-Seite das YouTube-Video ohne die Verwendung von Cookies und','fau-oembed') . '</li>',
            '<li>' . __('zusätzlich noch der Link zu dem Video auf YouTube angezeigt.','fau-oembed') . '</li>',
            '</ol>',
            '<p>' . __('Dabei können Sie die Breite angeben, in der YouTube-Videos auf Ihrer Seite dargestellt werden.','fau-oembed') . '</p>',
            '<p>' . __('Wenn Sie die Option <i>Anzeige ähnlicher Videos ausblenden</i> aktivieren, werden Ihnen am Ende Ihres Videos keine ähnlichen Videos als Vorschau angezeigt.','fau-oembed') . '</p>',
        );

        $help_tab_overview = array(
            'id' => 'overview',
            'title' => __('Übersicht','fau-oembed'),
            'content' => implode(PHP_EOL, $content_overview),
        );

        $help_tab_faukarte = array(
            'id' => 'faukarte',
            'title' => __('FAU-Karte','fau-oembed'),
            'content' => implode(PHP_EOL, $content_faukarte),
        );

        $help_tab_fauvideo = array(
            'id' => 'fauvideo',
            'title' => __('FAU Videoportal','fau-oembed'),
            'content' => implode(PHP_EOL, $content_fauvideo),
        );

        $help_tab_youtube_nocookie = array(
            'id' => 'youtube_nocookie',
            'title' => __('YouTube ohne Cookies','fau-oembed'),
            'content' => implode(PHP_EOL, $content_youtube_nocookie),
        );

        $help_sidebar = __('<p><strong>Für mehr Information:</strong></p><p><a href="http://blogs.fau.de/webworking">RRZE-Webworking</a></p><p><a href="https://github.com/RRZE-Webteam">RRZE-Webteam in Github</a></p>','fau-oembed');

        $screen = get_current_screen();

        if ($screen->id != $this->oembed_option_page) {
            return;
        }

        $screen->add_help_tab($help_tab_overview);
        $screen->add_help_tab($help_tab_faukarte);
        $screen->add_help_tab($help_tab_fauvideo);
        $screen->add_help_tab($help_tab_youtube_nocookie);

        $screen->set_help_sidebar($help_sidebar);
    }

    public function options_oembed() {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php echo esc_html(__('Einstellungen &rsaquo; oEmbed','fau-oembed')); ?></h2>

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

    public function admin_init() {

        register_setting('oembed_options', self::option_name, array($this, 'options_validate'));

        add_settings_section('embed_default_section', __('Standardwerte für eingebettete Objekte','fau-oembed'), '__return_false', 'oembed_options');

        add_settings_field('embed_defaults_width', __('Breite','fau-oembed'), array($this, 'embed_defaults_width'), 'oembed_options', 'embed_default_section');
        add_settings_field('embed_defaults_height', __('Höhe','fau-oembed'), array($this, 'embed_defaults_height'), 'oembed_options', 'embed_default_section');
        add_settings_field('embed_defaults_place_holder', __('Place Holder','fau-oembed'), array($this, 'embed_defaults_place_hoder'), 'oembed_options', 'embed_default_section');



        add_settings_section('faukarte_section', __('Automatische Einbindung von FAU-Karten','fau-oembed'), '__return_false', 'oembed_options');
        add_settings_field('faukarte_active', __('Aktivieren','fau-oembed'), array($this, 'faukarte_active'), 'oembed_options', 'faukarte_section');
        //add_settings_field('faukarte_width', __( 'Breite','fau-oembed' ), array($this, 'faukarte_width'), 'oembed_options', 'faukarte_section');
        //add_settings_field('faukarte_height', __( 'Höhe','fau-oembed' ), array($this, 'faukarte_height'), 'oembed_options', 'faukarte_section');

        add_settings_section('videoportal_section', __('Automatische Einbindung des FAU Videoportals','fau-oembed'), '__return_false', 'oembed_options');
        add_settings_field('videoportal_active', __('Aktivieren','fau-oembed'), array($this, 'videoportal_active'), 'oembed_options', 'videoportal_section');


        add_settings_section('youtube_nocookie_section', __('Automatische Einbindung von YouTube-Videos ohne Cookies','fau-oembed'), '__return_false', 'oembed_options');
        add_settings_field('youtube_nocookie_active', __('Aktivieren','fau-oembed'), array($this, 'youtube_nocookie_active'), 'oembed_options', 'youtube_nocookie_section');
        add_settings_field('youtube_nocookie_width', __('Breite','fau-oembed'), array($this, 'youtube_nocookie_width'), 'oembed_options', 'youtube_nocookie_section');
        add_settings_field('youtube_nocookie_norel', __('Anzeige ähnlicher Videos ausblenden','fau-oembed'), array($this, 'youtube_nocookie_norel'), 'oembed_options', 'youtube_nocookie_section');
    }

    public function embed_defaults_width() {
        $options = $this->get_options();
        ?>
        <input type='text' name="<?php printf('%s[embed_defaults][width]', self::option_name); ?>" value="<?php echo $options['embed_defaults']['width']; ?>">
        <?php
    }

    public function embed_defaults_height() {
        $options = $this->get_options();
        ?>
        <input type='text' name="<?php printf('%s[embed_defaults][height]', self::option_name); ?>" value="<?php echo $options['embed_defaults']['height']; ?>">
        <?php
    }

    public function embed_defaults_place_hoder() {

        $options = $this->get_options();

        if (!empty($options['embed_defaults']['place_holder'])) {

            $remove_class = '';
            $place_holder_image_url = wp_get_attachment_url($options['embed_defaults']['place_holder']);
        } else {
            $remove_class = 'hidden';
            $place_holder_image_url = plugin_dir_url(__FILE__) . '/img/default-thumbnail.jpg';
        }
        ?>




        <input class="regular-text" type="hidden" id="fau-embed-place-holder" name="<?php printf('%s[embed_defaults][place_holder]', self::option_name); ?>"  value="<?php echo $options['embed_defaults']['place_holder']; ?>"/>




        <img height="128" width="128" id="fau-embed-place-holder-img-preview"  src="<?php echo $place_holder_image_url; ?>" />

        <br/>
        <button id="fau-embed-add-place-holder-img" type="button" class="button button-large" value="" > <?php echo __('Upload',self::textdomain); ?> </button>
        <button  id="fau-embed-place-holder-delete-img" type="button" class="button button-large <?php echo $remove_class; ?>" > <?php echo __('Remove',self::textdomain); ?> </button> 




        <?php
    }

    public function faukarte_active() {
        $options = $this->get_options();
        ?>
        <input type='checkbox' name="<?php printf('%s[faukarte][active]', self::option_name); ?>" <?php checked($options['faukarte']['active'], true); ?>>                   
        <?php
    }

    /* Modifizieren der Anzeige nur für FAU-Karten funktioniert nicht    
     * 
      public function faukarte_width() {
      $options = $this->get_options();
      ?>
      <input type='text' name="<?php printf('%s[faukarte][width]', self::option_name); ?>" value="<?php echo $options['faukarte']['width']; ?>">
      <?php
      }

      public function faukarte_height() {
      $options = $this->get_options();
      ?>
      <input type='text' name="<?php printf('%s[faukarte][height]', self::option_name); ?>" value="<?php echo $options['faukarte']['height']; ?>">
      <?php
      }
     * 
     */

    public function videoportal_active() {
        $options = $this->get_options();
        ?>
        <input type='checkbox' name="<?php printf('%s[fau_videoportal]', self::option_name); ?>" <?php checked($options['fau_videoportal'], true); ?>>

        <?php
    }

    public function youtube_nocookie_active() {
        $options = $this->get_options();
        ?>
        <input type='checkbox' name="<?php printf('%s[youtube_nocookie][active]', self::option_name); ?>" <?php checked($options['youtube_nocookie']['active'], true); ?>>

        <?php
    }

    public function youtube_nocookie_width() {
        $options = $this->get_options();
        ?>
        <input type='text' name="<?php printf('%s[youtube_nocookie][width]', self::option_name); ?>" value="<?php echo $options['youtube_nocookie']['width']; ?>"><p class="description"><?php _e('Zu empfehlen ist eine Breite von mindestens 350px.','fau-oembed'); ?></p>
        <?php
    }

    public function youtube_nocookie_norel() {
        $options = $this->get_options();
        ?>
        <input type='checkbox' name="<?php printf('%s[youtube_nocookie][norel]', self::option_name); ?>" <?php checked($options['youtube_nocookie']['norel'], true); ?>><p class="description"><?php _e('Funktioniert nur, wenn die automatische Einbindung von YouTube-Videos aktiviert ist.','fau-oembed'); ?></p>

        <?php
    }

    public function options_validate($input) {
        $defaults = $this->default_options();
        $options = $this->get_options();

        $input['embed_defaults']['width'] = (int) $input['embed_defaults']['width'];
        $input['embed_defaults']['height'] = (int) $input['embed_defaults']['height'];
        $input['embed_defaults']['width'] = !empty($input['embed_defaults']['width']) ? $input['embed_defaults']['width'] : $defaults['embed_defaults']['width'];
        $input['embed_defaults']['height'] = !empty($input['embed_defaults']['height']) ? $input['embed_defaults']['height'] : $defaults['embed_defaults']['height'];

        $input['faukarte']['active'] = isset($input['faukarte']['active']) ? true : false;
        $input['fau_videoportal'] = isset($input['fau_videoportal']) ? true : false;

        /*
         * Modifizieren der Anzeige nur für FAU-Karten funktioniert noch nicht
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

    public function embed_defaults($defaults) {
        $options = $this->get_options();

        $defaults['width'] = $options['embed_defaults']['width'];
        $defaults['height'] = $options['embed_defaults']['height'];

        return $defaults;
    }

    public function fau_karte() {
        $options = $this->get_options();

        wp_oembed_remove_provider('http://karte.fau.de/api/v1/iframe/*');
        wp_oembed_remove_provider('https://karte.fau.de/api/v1/iframe/*');
        if ($options['faukarte']['active'] == true) {

            wp_oembed_add_provider('http://karte.fau.de/api/v1/iframe/*', 'https://karte.fau.de/api/v1/oembed?url=');
            wp_oembed_add_provider('https://karte.fau.de/api/v1/iframe/*', 'https://karte.fau.de/api/v1/oembed?url=');
            //$this->faukarte_oembed_add_provider('http://karte.fau.de/*', 'https://karte.fau.de/api/v1/oembed?url=');
            //$this->faukarte_oembed_add_provider('https://karte.fau.de/*', 'https://karte.fau.de/api/v1/oembed?url=');            
        }
    }

    public function shortcode_faukarte($atts) {
        //http://karte.fau.de/#14/49.4332/11.0977 wird zu http://karte.fau.de/api/v1/iframe/zoom/14/center/49.4332,11.0977
        $default = array(
            'url' => 'https://karte.fau.de/api/v1/iframe/',
            'width' => '720',
            'height' => '400',
            'zoom' => ''
        );
        $atts = shortcode_atts($default, $atts);
        extract($atts);
        $karte_api = 'karte.fau.de/api/v1/iframe/';
        $karte_start = 'karte.fau.de/#';
        $protokoll = "https://";
        if (strpos($url, 'http://') !== false)
            $url = str_replace('http://', $protokoll, $url);
        if (strpos($url, $karte_start) === false) {
            if (strpos($url, $karte_api) === false)
                $url = $protokoll . $karte_api . $url;
            if ($zoom)
                $url = $url . "/zoom/" . $zoom;
        }
        $output = sprintf('<iframe src="%1$s" width="%2$s" height="%3$s" seamless style="border: 0; padding: 0; margin: 0; overflow: hidden;"></iframe>', $url, $width, $height);
        return $output;
    }

    public function fau_videoportal() {
        $options = $this->get_options();


        if ($options['fau_videoportal'] == true) {
            wp_oembed_add_provider('http://www.video.uni-erlangen.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('https://www.video.uni-erlangen.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://www.video.fau.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('https://www.video.fau.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://video.fau.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('https://video.fau.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://www.fau-tv.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('https://www.fau-tv.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://fau-tv.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('https://fau-tv.de/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://www.fau.tv/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('https://www.fau.tv/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('http://fau.tv/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            wp_oembed_add_provider('https://fau.tv/webplayer/id/*', 'https://www.video.uni-erlangen.de/services/oembed/?url=');
            //wp_oembed_add_provider('http://www.video.uni-erlangen.de/clip/id/*', 'http://www.dev.video.uni-erlangen.de/services/oembed/?url=');      //vom Videoportal noch nicht unterstützt
        }
    }

    public function youtube_nocookie() {
        $options = $this->get_options();
        if ($options['youtube_nocookie']['active'] == true) {
            wp_oembed_remove_provider('#https?://(www\.)?youtube\.com/watch.*#i');
            wp_oembed_remove_provider('http://youtu.be/*');

            wp_embed_register_handler('ytnocookie', '#https?://www\.youtube\-nocookie\.com/embed/([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
            wp_embed_register_handler('ytnormal', '#https?://www\.youtube\.com/watch\?v=([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
            wp_embed_register_handler('ytnormal2', '#https?://www\.youtube\.com/watch\?feature=player_embedded&v=([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
            wp_embed_register_handler('yttube', '#http://youtu\.be/([a-z0-9\-_]+)#i', array($this, 'wp_embed_handler_ytnocookie'));
        }
    }

    public function wp_embed_handler_ytnocookie($matches, $attr, $url, $rawattr) {
        $options = $this->get_options();

        $relvideo = '';
        if ($options['youtube_nocookie']['norel'] == 1) {
            $relvideo = '?rel=0';
        }

        $height = $options['youtube_nocookie']['width'] * 36 / 64;

        $embed = sprintf('<div  class="oembed"><iframe src="https://www.youtube-nocookie.com/embed/%1$s%3$s" width="%2$spx" height="%4$spx" frameborder="0" scrolling="no" marginwidth="0" marginheight="0"></iframe></div>', esc_attr($matches[1]), $options['youtube_nocookie']['width'], $relvideo, $height);

        return apply_filters('embed_ytnocookie', $embed, $matches, $attr, $url, $rawattr);
    }

    public function oembed_url_filter($provider, $url, $args) {
        $host = parse_url($provider, PHP_URL_HOST);
        if ($host == 'www.video.uni-erlangen.de') {
            $this->videoportal[] = array($url => $provider);
        }
        return $provider;
    }

    public function oembed_html_filter($html, $url, $args, $post_id) {
        $provider = null;
        foreach ($this->videoportal as $val) {
            if (isset($val[$url])) {
                $provider = $val[$url];
            }
        }
        if ($provider) {
            $video = $this->fetch($provider, $url, $args);
            if (isset($video->html)) {
                $html = $video->html;
            }
        }
        return $html;
    }

    private function fetch($provider, $url, $args = '') {
        $args = wp_parse_args($args, wp_embed_defaults());

        $provider = add_query_arg('maxwidth', (int) $args['width'], $provider);
        $provider = add_query_arg('maxheight', (int) $args['height'], $provider);
        $provider = add_query_arg('url', urlencode($url), $provider);

        $result = $this->fetch_with_format($provider);

        if (is_wp_error($result) && 'not-implemented' == $result->get_error_code()) {
            return false;
        }

        return ( $result && !is_wp_error($result) ) ? $result : false;
    }

    private function fetch_with_format($provider_url_with_args) {
        $provider_url_with_args = add_query_arg('format', 'json', $provider_url_with_args);
        $response = wp_safe_remote_get($provider_url_with_args);

        if (501 == wp_remote_retrieve_response_code($response)) {
            return new WP_Error('not-implemented');
        }

        if (!$body = wp_remote_retrieve_body($response)) {
            return false;
        }

        return $this->parse_json($body);
    }

    private function parse_json($response_body) {
        return ( ( $data = json_decode(trim($response_body)) ) && is_object($data) ) ? $data : false;
    }

}
