<?php

namespace FAU\OEmbed;

defined('ABSPATH') || exit;

class Settings
{
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
    public function __construct()
    {
        $this->option_name = Options::getOptionName();
        $this->options = Options::getOptions();

        add_action('admin_menu', [$this, 'admin_settings_page']);
        add_action('admin_init', [$this, 'admin_settings']);
    }

    /**
     * Füge eine Einstellungsseite in das Menü "Einstellungen" hinzu.
     */
    public function admin_settings_page()
    {
        $this->admin_settings_page = add_options_page(__('FAU oEmbed', 'fau-oembed'), __('FAU oEmbed', 'fau-oembed'), 'manage_options', 'fau-oembed', [$this, 'settings_page']);
    }

    /**
     * Die Ausgabe der Einstellungsseite.
     */
    public function settings_page()
    {
    ?>
        <div class="wrap">
            <h2><?php echo __('FAU oEmbed Settings', 'fau-oembed'); ?></h2>
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

        add_settings_section('embed_default_section', __('Default values for embedded objects', 'fau-oembed'), '__return_false', 'fau_oembed_options');

        add_settings_field('embed_defaults_width', __('Width', 'fau-oembed'), [$this, 'embed_defaults_width'], 'fau_oembed_options', 'embed_default_section');
        add_settings_field('embed_defaults_height', __('Height', 'fau-oembed'), [$this, 'embed_defaults_height'], 'fau_oembed_options', 'embed_default_section');

        add_settings_section('faukarte_section', __('Automatic integration of FAU Maps', 'fau-oembed'), '__return_false', 'fau_oembed_options');
        add_settings_field('faukarte_active', __('Activate', 'fau-oembed'), [$this, 'faukarte_active'], 'fau_oembed_options', 'faukarte_section');

        add_settings_section('fau_videoportal_section', __('Automatic integration of the FAU Video Portal', 'fau-oembed'), '__return_false', 'fau_oembed_options');
        add_settings_field('fau_videoportal_active', __('', 'fau-oembed'), [$this, 'fau_videoportal_active'], 'fau_oembed_options', 'fau_videoportal_section');

        if (!Options::handled_by_Embed_Privacy('youtube')) {
            add_settings_section('youtube_section', __('Automatic integration of YouTube videos without cookies', 'fau-oembed'), '__return_false', 'fau_oembed_options');
            add_settings_field('youtube_active', __('Activate', 'fau-oembed'), [$this, 'youtube_active'], 'fau_oembed_options', 'youtube_section');
            add_settings_field('youtube_norel', __('Hide display of related videos', 'fau-oembed'), [$this, 'youtube_norel'], 'fau_oembed_options', 'youtube_section');
        }
        if (!Options::handled_by_Embed_Privacy('slideshare')) {
            add_settings_section('slideshare_section', __('Automatic integration of Slideshare presentations', 'fau-oembed'), '__return_false', 'fau_oembed_options');
            add_settings_field('slideshare_active', __('Activate', 'fau-oembed'), [$this, 'slideshare_active'], 'fau_oembed_options', 'slideshare_section');
        }
        add_settings_section('brmediathek_section', __('Automatic integration of videos from the BR media library', 'fau-oembed'), '__return_false', 'fau_oembed_options');
        add_settings_field('brmediathek_active', __('Activate', 'fau-oembed'), [$this, 'brmediathek_active'], 'fau_oembed_options', 'brmediathek_section');
    }

    public function embed_defaults_width()
    {
    ?>
        <input type='text' name="<?php printf('%s[embed_defaults][width]', $this->option_name); ?>" value="<?php echo $this->options->embed_defaults->width; ?>">
    <?php
    }

    public function embed_defaults_height()
    {
    ?>
        <input type='text' name="<?php printf('%s[embed_defaults][height]', $this->option_name); ?>" value="<?php echo $this->options->embed_defaults->height; ?>">
    <?php
    }

    public function faukarte_active()
    {
    ?>
        <input type='checkbox' name="<?php printf('%s[faukarte][active]', $this->option_name); ?>" <?php checked($this->options->faukarte->active, true); ?>>
    <?php
    }

    public function fau_videoportal_active()
    {
    ?>
        <input type='checkbox' name="<?php printf('%s[fau_videoportal][active]', $this->option_name); ?>" <?php checked($this->options->fau_videoportal->active, true); ?>>
    <?php
    }

    public function youtube_active()
    {
    ?>
        <input type='checkbox' name="<?php printf('%s[youtube][active]', $this->option_name); ?>" <?php checked($this->options->youtube->active, true); ?>>
    <?php
    }


    public function youtube_norel()
    {
    ?>
        <input type='checkbox' name="<?php printf('%s[youtube][norel]', $this->option_name); ?>" <?php checked($this->options->youtube->norel, true); ?>>
        <span class="description"><?php _e('Only works if automatic embedding of YouTube videos is activated.', 'fau-oembed'); ?></span>
    <?php
    }

    public function slideshare_active()
    {
    ?>
        <input type='checkbox' name="<?php printf('%s[slideshare][active]', $this->option_name); ?>" <?php checked($this->options->slideshare->active, true); ?>>
    <?php
    }
    public function brmediathek_active()
    {
    ?>
        <input type='checkbox' name="<?php printf('%s[brmediathek][active]', $this->option_name); ?>" <?php checked($this->options->brmediathek->active, true); ?>>
    <?php
    }

    /**
     * Validiert die Eingabe der Einstellungsseite.
     * @param array $input
     * @return array
     */
    public function options_validate($input)
    {
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
}
