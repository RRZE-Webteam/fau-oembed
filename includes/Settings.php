<?php

namespace FAU\OEmbed;

use FAU\OEmbed\Main;

defined('ABSPATH') || exit;

class Settings {
    /**
     * Optionsname
     * @var string
     */
    protected $option_name;

    /**
     * Einstellungsoptionen
     * @var object
     */
    protected $options;

    /**
     * "Screen ID" der Einstellungsseite.
     * @var string
     */
    protected $admin_settings_page;

    /**
     * Settings-Klasse wird instanziiert.
     */
    public function __construct() {
        $this->option_name = Options::getOptionName();
        $this->options = Options::getOptions();

        add_action('admin_menu', [$this, 'admin_settings_page']);
        add_action('admin_init', [$this, 'admin_settings']);
    }

    /**
     * Füge eine Einstellungsseite in das Menü "Einstellungen" hinzu.
     */
    public function admin_settings_page()  {
        $this->admin_settings_page = add_options_page(__('FAU oEmbed', 'fau-oembed'), __('FAU oEmbed', 'fau-oembed'), 'manage_options', 'fau-oembed', [$this, 'settings_page']);
        add_action('load-' . $this->admin_settings_page, [$this, 'admin_help_menu']);
    }

    /**
     * Die Ausgabe der Einstellungsseite.
     */
    public function settings_page()
    {
        ?>
        <div class="wrap">
            <h2><?php echo __('Einstellungen &rsaquo; FAU oEmbed', 'fau-oembed'); ?></h2>
            <form method="post" action="options.php">
            <?php
            settings_fields('fau_oembed_options');
            do_settings_sections('fau_oembed_options');
            submit_button();
            ?>
            </form>
        </div>
        <?php
    }
    
    
    /**
     * Legt die Einstellungen der Einstellungsseite fest.
     */
    public function admin_settings()
    {
        register_setting('fau_oembed_options', $this->option_name, [$this, 'options_validate']);



        add_settings_section('embed_default_section', __('Standardwerte für eingebettete Objekte','fau-oembed'), '__return_false', 'fau_oembed_options');

        add_settings_field('embed_defaults_width', __('Breite','fau-oembed'), [$this, 'embed_defaults_width'], 'fau_oembed_options', 'embed_default_section');
        add_settings_field('embed_defaults_height', __('Höhe','fau-oembed'), [$this, 'embed_defaults_height'], 'fau_oembed_options', 'embed_default_section');

        add_settings_section('faukarte_section', __('Automatische Einbindung von FAU-Karten','fau-oembed'), '__return_false', 'fau_oembed_options');
        add_settings_field('faukarte_active', __('Aktivieren','fau-oembed'), [$this, 'faukarte_active'], 'fau_oembed_options', 'faukarte_section');

        add_settings_section('fau_videoportal_section', __('Automatische Einbindung des FAU Videoportals','fau-oembed'), '__return_false', 'fau_oembed_options');
        add_settings_field('fau_videoportal_active', __('Aktivieren','fau-oembed'), [$this, 'fau_videoportal_active'], 'fau_oembed_options', 'fau_videoportal_section');

	if (!Options::handled_by_Embed_Privacy('youtube')) {
	    add_settings_section('youtube_section', __('Automatische Einbindung von YouTube-Videos ohne Cookies','fau-oembed'), '__return_false', 'fau_oembed_options');
	    add_settings_field('youtube_active', __('Aktivieren','fau-oembed'), [$this, 'youtube_active'], 'fau_oembed_options', 'youtube_section');
	    add_settings_field('youtube_norel', __('Anzeige ähnlicher Videos ausblenden','fau-oembed'), [$this, 'youtube_norel'], 'fau_oembed_options', 'youtube_section');
	}
	if (!Options::handled_by_Embed_Privacy('slideshare')) {
	    add_settings_section('slideshare_section', __('Automatische Einbindung von Slideshare-Präsentationen','fau-oembed'), '__return_false', 'fau_oembed_options');
	    add_settings_field('slideshare_active', __('Aktivieren','fau-oembed'), [$this, 'slideshare_active'], 'fau_oembed_options', 'slideshare_section');
	}
	add_settings_section('brmediathek_section', __('Automatische Einbindung von Videos aus der BR-Mediathek','fau-oembed'), '__return_false', 'fau_oembed_options');
        add_settings_field('brmediathek_active', __('Aktivieren','fau-oembed'), [$this, 'brmediathek_active'], 'fau_oembed_options', 'brmediathek_section');
	
    }

    
    
    

    public function embed_defaults_width() {
        ?>
        <input type='text' name="<?php printf('%s[embed_defaults][width]', $this->option_name); ?>" value="<?php echo $this->options->embed_defaults->width; ?>">
        <?php
    }

    public function embed_defaults_height() {
        ?>
        <input type='text' name="<?php printf('%s[embed_defaults][height]', $this->option_name); ?>" value="<?php echo $this->options->embed_defaults->height; ?>">
        <?php
    }

    public function faukarte_active() {
        ?>
        <input type='checkbox' name="<?php printf('%s[faukarte][active]', $this->option_name); ?>" <?php checked($this->options->faukarte->active, true); ?>>                   
        <?php
    }

    public function fau_videoportal_active() {
        ?>
        <input type='checkbox' name="<?php printf('%s[fau_videoportal][active]', $this->option_name); ?>" <?php checked($this->options->fau_videoportal->active, true); ?>>
        <?php
    }

    public function youtube_active() {
        ?>
        <input type='checkbox' name="<?php printf('%s[youtube][active]', $this->option_name); ?>" <?php checked($this->options->youtube->active, true); ?>>
        <?php
    }


    public function youtube_norel() {
        ?>
        <input type='checkbox' name="<?php printf('%s[youtube][norel]',$this->option_name); ?>"<?php checked($this->options->youtube->norel, true); ?>>
        <p class="description"><?php _e('Funktioniert nur, wenn die automatische Einbindung von YouTube-Videos aktiviert ist.','fau-oembed'); ?></p>
        <?php
    }

      public function slideshare_active() {
        ?>
        <input type='checkbox' name="<?php printf('%s[slideshare][active]', $this->option_name); ?>" <?php checked($this->options->slideshare->active, true); ?>>
        <?php
    }
      public function brmediathek_active() {
        ?>
        <input type='checkbox' name="<?php printf('%s[brmediathek][active]', $this->option_name); ?>" <?php checked($this->options->brmediathek->active, true); ?>>
        <?php
    }
  
    /**
     * Validiert die Eingabe der Einstellungsseite.
     * @param array $input
     * @return array
     */
 public function options_validate($input) {

        $input['embed_defaults']['width'] = (int) $input['embed_defaults']['width'];
        $input['embed_defaults']['height'] = (int) $input['embed_defaults']['height'];
        $input['embed_defaults']['width'] = !empty($input['embed_defaults']['width']) ? $input['embed_defaults']['width'] : $this->options->embed_defaults->width;
        $input['embed_defaults']['height'] = !empty($input['embed_defaults']['height']) ? $input['embed_defaults']['height'] : $this->options->embed_defaults->height;

        $input['faukarte']['active'] = isset($input['faukarte']['active']) ? true : false;
        $input['fau_videoportal']['active'] = isset($input['fau_videoportal']['active']) ? true : false;

        $input['youtube']['active'] = isset($input['youtube']['active']) ? true : false;
        $input['youtube']['norel'] = isset($input['youtube']['norel']) ? 1 : 0;
	
	$input['slideshare']['active'] = isset($input['slideshare']['active']) ? true : false;

	$input['brmediathek']['active'] = isset($input['brmediathek']['active']) ? true : false;
	
	
	
        return $input;
    }
   
    /**
     * Erstellt die Kontexthilfe der Einstellungsseite.
     * @return void
     */
    public function admin_help_menu() {
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

        $content_youtube = array(
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

        $help_tab_youtube = array(
            'id' => 'youtube',
            'title' => __('YouTube ohne Cookies','fau-oembed'),
            'content' => implode(PHP_EOL, $content_youtube),
        );

        $help_sidebar = __('<p><strong>Für mehr Information:</strong></p><p><a href="http://blogs.fau.de/webworking">RRZE-Webworking</a></p><p><a href="https://github.com/RRZE-Webteam">RRZE-Webteam in Github</a></p>','fau-oembed');

        $screen = get_current_screen();

        if ($screen->id !=  $this->admin_settings_page) {
            return;
        }

        $screen->add_help_tab($help_tab_overview);
        $screen->add_help_tab($help_tab_faukarte);
        $screen->add_help_tab($help_tab_fauvideo);
        $screen->add_help_tab($help_tab_youtube);

        $screen->set_help_sidebar($help_sidebar);
    }
}
