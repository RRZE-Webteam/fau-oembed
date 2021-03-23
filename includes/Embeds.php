<?php

namespace FAU\OEmbed;

use  FAU\OEmbed\API\OEmbed;

defined('ABSPATH') || exit;

class Embeds
{
    protected $options;

    protected $providers;
    
    protected $handlers;
    
    public function __construct()
    {
        $this->options = Options::getOptions();
        
        $this->providers = $this->providers();
        $this->handlers = $this->handlers();
        
        // Add Custom oEmbed Providers
        add_action('init', [$this, 'addProviders']);
        
        // Register Embed Handlers.
        // Sollte nur für Handlers verwendet werden, die oEmbed nicht unterstützen 
	// oder wo wir eine individuelle Anpassung machen.
        add_action('init', [$this, 'fau_karte']);
        add_action('init', [$this, 'fau_videoportal']);
	
	if (!Options::handled_by_Embed_Privacy('youtube')) {
	       add_action('init', [$this, 'youtube_nocookie']); 
	} 
	if (!Options::handled_by_Embed_Privacy('slideshare')) {
	     add_action('init', [$this, 'slideshare']);	
	}     
       
        add_action('init', [$this, 'brmediathek']);
    }

    protected function providers()
    {
        $providers = [
            // oEmbed Schnittstelle der Informatik 12
            // Ansprechpartner: Andreas Bininda <andreas.bininda@fau.de>
            'http://www12.informatik.uni-erlangen.de/people/bininda/*' => ['http://www12.informatik.uni-erlangen.de/people/bininda/test/', false],
            'https://www12.informatik.uni-erlangen.de/oembed-objekte/*' => ['https://www12.informatik.uni-erlangen.de/oembed/', false],
            // oEmbed Schnittstelle von FAU Mac Support
            // Ansprechpartner: Gregor Longariva <gregor.longariva@fau.de>
            'https://faumac.rrze.fau.de/oembed/*' => ['https://faumac.rrze.fau.de/oembed/', false],
            // oEmbed Schnittstelle für die EInbindung im Rahmen der 100 Jahre WISO
            // Ansprechpartner: Jalowski, Max <max.jalowski@fau.de>
            'https://100jahre.wi1projects.de' => ['https://100jahre.wi1projects.de/oembed', false]
        ];
        return apply_filters('fau_oembed_providers', $providers);
    }
    
    
    
    protected function handlers()
    {
        $handlers = [
            'fau_kartendienst' => [
                // Content Security Policy (default-src)
                'allowed_domains' => ['karte.fau.de', 'karte.uni-erlangen.de'],
                // API
                'faukarte_api' => [
                    'regex' => '#https?://karte\.(uni\-erlangen|fau)\.de/api/v1/iframe/*#i',
                    'callback' => [$this, 'wp_embed_handler_faukartenapi']
                ],
                // Url (bspw. https://karte.fau.de/#17/49.59725/11.00835)
                'faukarte_link' => [
                    'regex' => '#https?://karte\.(uni\-erlangen|fau)\.de/\#([\d]+)/([\d\.]+)/([\d\.]+)#i',
                    'callback' => [$this, 'wp_embed_handler_faukarte']
                ]
            ],
            'fau_videoportal' => [
                // Content Security Policy (default-src)
                'allowed_domains' => ['*.video.fau.de', '*.video.uni-erlangen.de'],
                'fau_videoportal_webplayer' => [
                    'regex' => '#https?://(www\.)?video\.uni-erlangen\.de/webplayer/id/([\d]+)/?#i',
                    'callback' => [$this, 'wp_embed_handler_fautv']
                ],
                'fau_videoportal_clip' => [
                    'regex' => '#https?://(www\.)?video\.(uni\-erlangen|fau)\.de/clip/id/.*#i',
                    'callback' => [$this, 'wp_embed_handler_fautv']
                ],
                'fau_videoportal_collection' => [
                    'regex' => '#https?://(www\.)?video\.(uni\-erlangen|fau)\.de/collection/clip/\d+/.*#i',
                    'callback' => [$this, 'wp_embed_handler_fautv']
                ]
            ],
            'youtube' => [
                // Content Security Policy (default-src)
                'allowed_domains' => ['www.youtube-nocookie.com', 'www.youtube.com', 'youtu.be'],
                'youtube_nocookie' => [
                    'regex' => '#https?://www\.youtube\-nocookie\.com/embed/([a-z0-9\-_]+)#i',
                    'callback' => [$this, 'wp_embed_handler_ytnocookie']
                ],
                'youtube_normal' => [
                    'regex' => '#https?://www\.youtube\.com/watch\?v=([a-z0-9\-_]+)#i',
                    'callback' => [$this, 'wp_embed_handler_ytnocookie']
                ],
                'youtube_normal2' => [
                    'regex' => '#https?://www\.youtube\.com/watch\?feature=player_embedded&v=([a-z0-9\-_]+)#i',
                    'callback' => [$this, 'wp_embed_handler_ytnocookie']
                ],
                'youtube_tube' => [
                    'regex' => '#http://youtu\.be/([a-z0-9\-_]+)#i',
                    'callback' => [$this, 'wp_embed_handler_ytnocookie']
                ]
            ],
            'slideshare' => [
                // Content Security Policy (default-src)
                'allowed_domains' => ['*.slideshare.net'],
                'slideshare' => [
                    'regex' => '#https?://(.+?\.)?slideshare\.net/.*#i',
                    'callback' => [$this, 'wp_embed_handler_slidershare']
                ]
            ],
            'brmediathek' => [
                // Content Security Policy (default-src)
                'allowed_domains' => ['www.br.de'],
                // https://www.br.de/mediathek/video/abitur-und-dann-mit-profis-an-der-zukunft-basteln-av:584f8d2b3b46790011a26a9b
                'brmediathek' => [
                    'regex' => '#https?://www\.br\.de/mediathek/video/([a-z0-9\-_:]+)#i',
                    'callback' => [$this, 'wp_embed_handler_brmediathek']
                ]
            ]
        ];
        return apply_filters('fau_oembed_handlers', $handlers);
    }
    
    public function addProviders() {
        foreach ($this->providers as $k => $v) {
            wp_oembed_add_provider($k, $v[0], $v[1]);
        }
    }
    
    /**
     * [registerHandler description]
     * @param  string $handler [description]
     */
    protected function registerHandler(string $handler)  {
        if (empty($this->handlers[$handler])) {
            return;
        }

        foreach ($this->handlers[$handler] as $k => $v) {
            if ($k == 'allowed_domains') {
                continue;
            }
            wp_embed_register_handler($k, $v['regex'], $v['callback']);
        }
    }
    
    /*-----------------------------------------------------------------------------------*/
    /* FAU Karte
    /*-----------------------------------------------------------------------------------*/
    public function fau_karte()
    {
        if ($this->options->faukarte->active == true) {
            $this->registerHandler('fau_kartendienst');
        }
    }
    
    public function wp_embed_handler_faukartenapi($matches, $attr, $url, $rawattr)
    {
        $karte_api = $this->options->faukarte->apiurl;
        $karte_start = 'karte.fau.de/api/v1/iframe/';
        $protokoll = "https://";
        
        if (strpos($url, 'http://') !== false) {
            $url = str_replace('http://', $protokoll, $url);
        }

        
        if (strpos($url, $karte_start) === false) {
            if (strpos($url, $karte_api) === true) {
                $url = $protokoll . $karte_api . $url;
            }
        }
        $title = $this->options->faukarte->title;
        $width = $this->options->embed_defaults->width;
        $height = $this->options->embed_defaults->height;
	$notice = $this->options->faukarte->iframe_notice;
    
        $id = md5(uniqid('', true));
    
        $embed = '<div class="fau-oembed" id="'.$id.'">';
        $embed .= '<iframe title="'.$title.'" src="'.$url.'"';
        $embed .= ' class="fau-karte defaultwidth" width="'.$width.'" height="'.$height.'"';
	$embed .= ' seamless>';


	if (!empty($notice)) {
	     $embed .= '<p>'.$notice.' <a href="'.$url.'">'.$url.'</a></p>';
	}
	
        $embed .= '</iframe>';
        $embed .= '</div>';
	
        wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_faukarte', $embed, $matches, $attr, $url, $rawattr);
    }
    
    public function wp_embed_handler_faukarte($matches, $attr, $url, $rawattr)
    {
        $karte_api = $this->options->faukarte->apiurl;
        $karte_start = 'karte.fau.de/#';
        $protokoll = "https://";
        
        if (strpos($url, 'http://') !== false) {
            $url = str_replace('http://', $protokoll, $url);
        }

        
        if (strpos($url, $karte_start) === false) {
            if (strpos($url, $karte_api) === false) {
                $url = $protokoll . $karte_api . $url;
            }
        }
    
        $title = $this->options->faukarte->title;
        $width = $this->options->embed_defaults->width;
        $height = $this->options->embed_defaults->height;
	$notice = $this->options->faukarte->iframe_notice;

        $id = md5(uniqid('', true));
    
        $embed = '<div class="fau-oembed" id="'.$id.'">';
        $embed .= '<iframe title="'.$title.'" src="'.$url.'"';
        $embed .= ' class="fau-karte defaultwidth" width="'.$width.'" height="'.$height.'"';
        $embed .= ' seamless>';
	if (!empty($notice)) {
	     $embed .= '<p>'.$notice.' <a href="'.$url.'">'.$url.'</a></p>';
	}
	
        $embed .= '</iframe>';
        $embed .= '</div>';

        wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_faukarte', $embed, $matches, $attr, $url, $rawattr);
    }
    
    /*-----------------------------------------------------------------------------------*/
    /*  FAU Videoportal
    /*-----------------------------------------------------------------------------------*/
    public function fau_videoportal()
    {
        if ($this->options->fau_videoportal->active  == true) {
            $this->registerHandler('fau_videoportal');
        }
    }

    public function wp_embed_handler_fautv($matches, $attr, $url, $rawattr)
    {
        $oembed_url = 'https://www.video.uni-erlangen.de/services/oembed/?url=' . $matches[0] . '&format=json';
        $video = json_decode(wp_remote_retrieve_body(wp_safe_remote_get($oembed_url)), true);

    
        if (!isset($video['file'])) {
            return '';
        }
     
        $title = ! empty($video['title']) ? $video['title'] : $this->options->embed_defaults->title;
        $width = isset($video['width']) && absint($video['width']) ? absint($video['width']) : $this->options->embed_defaults->width;
        $height = isset($video['height']) && absint($video['height']) ? absint($video['height']) : $this->options->embed_defaults->height;
        $image = ! empty($video['preview_image']) ? $video['preview_image'] : $this->options->fau_videoportal->defaultthumb;
        $desc = ! empty(sanitize_text_field($video['description'])) ? sanitize_text_field($video['description']) : $this->options->fau_videoportal->description;
     
        $file = $video['file'];

        
        $embed = '<div class="fau-oembed oembed fauvideo" itemscope itemtype="http://schema.org/VideoObject">';
    
    
    
        preg_match('/(\d+)$/', $url, $match);
        $id = md5(uniqid($match[0], true));
    
        if ($this->options->fau_videoportal->display_title) {
            $embed .= '<h3 id="'.$id.'" itemprop="name">'.$title.'</h3>';
        } else {
            $embed .= '<meta itemprop="name" content="'.$title.'">';
        }
    
        $embed .= '<meta itemprop="contentUrl" content="'.$file.'">';
        $embed .= '<meta itemprop="url" content="'.$url.'">';
        $embed .= '<meta itemprop="height" content="'.$video['height'].'">';
        $embed .= '<meta itemprop="width" content="'.$video['width'].'">';
        $embed .= '<meta itemprop="thumbnailUrl" content="'.$image.'">';
        $embed .= '<meta itemprop="description" content="'.$desc.'">';
    
    
        if (! empty($data['author_name'])) {
            $embed .= '<meta itemprop="author" content="'.$data['author_name'].'">';
        }
        if (! empty($data['provider_name'])) {
            $embed .= '<meta itemprop="provider" content="'.$data['provider_name'].'">';
        }
        if (! empty($data['upload_date'])) {
            $embed .= '<meta itemprop="uploadDate" content="'.$data['upload_date'].'">';
        }
    
        $embed .= '<div id="'.$id.'" class="fau-oembed-video">';
        $embed .= '[video preload="none" width="' . $width . '" height="' . $height . '" src="' . $file . '" poster="' . $image . '"][/video]';
        $embed .= '</div>';
        if ($this->options->fau_videoportal->display_source) {
            $embed .= '<div class="caption">';
            $embed .= __('Quelle:', 'fau-oembed').' ';
            if (! empty($data['author_name'])) {
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
    public function youtube_nocookie()
    {
        if ($this->options->youtube->active == true) {
            wp_oembed_remove_provider('#https?://(www\.)?youtube\.com/watch.*#i');
            wp_oembed_remove_provider('http://youtu.be/*');
            $this->registerHandler('youtube');
        }
    }

    public function wp_embed_handler_ytnocookie($matches, $attr, $url, $rawattr)
    {
        if (is_feed()) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return sprintf('<a href="%1$s">%1$s</a>', $url);
            } else {
                return '';
            }
        }
       
        $relvideo = '';
        if ($this->options->youtube->norel == 1) {
            $relvideo = '?rel=0';
        }
       
        $data = OEmbed::get_data($url);
        $title = ! empty($data['title']) ? $data['title'] : $this->options->embed_defaults->title;
        $width = isset($data['width']) && absint($data['width']) ? absint($data['width']) : $this->options->embed_defaults->width;
        $height = isset($data['height']) && absint($data['height']) ? absint($data['height']) : $this->options->embed_defaults->height;
         
        $embed = '<div class="fau-oembed oembed" itemscope itemtype="http://schema.org/VideoObject">';
    
        $id = md5(uniqid($matches[1], true));
        
        if ($this->options->youtube->display_title) {
            $embed .= '<h3 id="'.$id.'" itemprop="name">'.$title.'</h3>';
        } else {
            $embed .= '<meta itemprop="name" content="'.$title.'">';
        }
        $embed .= '<meta itemprop="url" content="'.$url.'">';
        $embed .= '<meta itemprop="contentUrl" content="'.$url.'">';
        if (! empty($data['thumbnail_url'])) {
            $embed .= '<meta itemprop="thumbnail" content="'.$data['thumbnail_url'].'">';
        }
    

        if (! empty($data['description'])) {
            $embed .= '<meta itemprop="description" content="'.$data['description'].'">';
        } else {
            $desc = '';
        
            if (! empty($data['provider_name'])) {
                $desc .= $data['provider_name'].' '.__('Video', 'fau-oembed');
            }
            if (! empty($data['author_name'])) {
                $desc .= ' '.__('von', 'fau-oembed').' '.$data['author_name'];
            }
            $embed .= '<meta itemprop="description" content="'.$desc.'">';
        }
        if (! empty($data['width'])) {
            $embed .= '<meta itemprop="width" content="'.$data['width'].'">';
        }
        if (! empty($data['height'])) {
            $embed .= '<meta itemprop="height" content="'.$data['height'].'">';
        }
        if (! empty($data['author_name'])) {
            $embed .= '<meta itemprop="author" content="'.$data['author_name'].'">';
        }
        if (! empty($data['provider_name'])) {
            $embed .= '<meta itemprop="provider" content="'.$data['provider_name'].'">';
        }
    
        $usedefaultwidth = '';
        if (empty($data['width'])) {
            $usedefaultwidth = ' defaultwidth';
        }
    
        if ($this->options->youtube->display_title) {
            $embed .= '<iframe class="youtube'.$usedefaultwidth.'" aria-labelledby="'.$id.'"';
        } else {
            $embed .= '<iframe class="youtube'.$usedefaultwidth.'" title="'.$title.'"';
        }
    
        $embed .= ' src="https://www.youtube-nocookie.com/embed/'.esc_attr($matches[1]).$relvideo.'" width="'.$width.'" height="'.$height.'">';
	$notice = $this->options->youtube->iframe_notice;
	if (!empty($notice)) {
	     $embed .= '<p>'.$notice.' <a href="'.$url.'">'.$url.'</a></p>';
	}
	$embed .= '</iframe>';
        if ($this->options->youtube->display_source) {
            $embed .= '<div class="caption">';
            $embed .= __('Quelle:', 'fau-oembed').' ';
            if (! empty($data['author_name'])) {
                $embed .= '<span class="author_name">'.$data['author_name'].'</span>, ';
            }
            $embed .= '<a href="'.$url.'">'.$url.'</a>';
            $embed .= '</div>';
        }
        $embed .= '</div>';
    
        wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_ytnocookie', $embed, $matches, $attr, $url, $rawattr);
    }
        
    /*-----------------------------------------------------------------------------------*/
    /*  Slideshare
    /*-----------------------------------------------------------------------------------*/
    public function slideshare()
    {
        if ($this->options->slideshare->active == true) {
            wp_oembed_remove_provider('#https?://(.+?\.)?slideshare\.net/.*#i');
            $this->registerHandler('slideshare');
        }
    }

    public function wp_embed_handler_slidershare($matches, $attr, $url, $rawattr)
    {
        if (is_feed()) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return sprintf('<a href="%1$s">%1$s</a>', $url);
            } else {
                return '';
            }
        }

        $data = OEmbed::get_data($url);
        if (! $data) {
            return $url;
        }
        $title = !empty($data['title']) ? $data['title'] : $this->options->embed_defaults->title;
        $width = isset($data['width']) && absint($data['width']) ? absint($data['width']) : $this->options->embed_defaults->width;
        $height = isset($data['height']) && absint($data['height']) ? absint($data['height']) : $this->options->embed_defaults->height;
     
        $embed = '<div class="fau-oembed oembed" itemscope itemtype="http://schema.org/PresentationDigitalDocument">';
        $id = md5(uniqid('', true));
        
        if ($this->options->slideshare->display_title) {
            $embed .= '<h3 id="'.$id.'" itemprop="name">'.$title.'</h3>';
        } else {
            $embed .= '<meta itemprop="name" content="'.$title.'">';
        }
        $embed .= '<meta itemprop="url" content="'.$url.'">';
        if (! empty($data['thumbnail_url'])) {
            $embed .= '<meta itemprop="thumbnail" content="'.$data['thumbnail_url'].'">';
        }
    

        if (! empty($data['description'])) {
            $embed .= '<meta itemprop="description" content="'.$data['description'].'">';
        } else {
            $desc = '';
        
            if (! empty($data['provider_name'])) {
                $desc .= $data['provider_name'].' '.__('Folien', 'fau-oembed');
            }
            if (! empty($data['author_name'])) {
                $desc .= ' '.__('von', 'fau-oembed').' '.$data['author_name'];
            }
            $embed .= '<meta itemprop="description" content="'.$desc.'">';
        }
    
        if (! empty($data['author_name'])) {
            $embed .= '<meta itemprop="author" content="'.$data['author_name'].'">';
        }
        if (! empty($data['provider_name'])) {
            $embed .= '<meta itemprop="provider" content="'.$data['provider_name'].'">';
        }
    
    
        if ($this->options->slideshare->display_title) {
            $embed .= '<iframe class="slideshare defaultwidth" aria-labelledby="'.$id.'"';
        } else {
            $embed .= '<iframe class="slideshare defaultwidth" title="'.$title.'"';
        }
    
    
        $frameurl = '';
        $pattern        = '/src\=[\"|\\\']{1}([^\"\\\']*)[\"|\\\']{1}/i';
        $isframeurl = preg_match($pattern, $data['html'], $matches);

        if ($isframeurl && ! empty($matches[1])) {
            $frameurl = $matches[1];
        }

        $embed .= ' src="'.$frameurl.'" width="'.$width.'" height="'.$height.'">';
	
	$notice = $this->options->slideshare->iframe_notice;
	if (!empty($notice)) {
	     $embed .= '<p>'.$notice.' <a href="'.$url.'">'.$url.'</a></p>';
	}
	$embed .= '</iframe>';
	
        if ($this->options->slideshare->display_source) {
            $embed .= '<div class="caption">';
       
        
            if ($this->options->slideshare->display_title) {
                if (! empty($data['author_name'])) {
                    $embed .= '<span class="author_name">'.$data['author_name'].'</span>';
                }
                $embed .= ' '.__('auf', 'fau-oembed').' <a href="'.$url.'">'.$data['provider_name'].'</a>';
            } else {
                $embed .= '<a href="'.$url.'">'.$title.'</a>';
                if (! empty($data['author_name'])) {
                    $embed .= __('von', 'fau-oembed').' <span class="author_name">'.$data['author_name'].'</span>, ';
                }
            }
        
            $embed .= '</div>';
        }
        $embed .= '</div>';
    
    
    
        wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_ytnocookie', $embed, $matches, $attr, $url, $rawattr);
    }
    
    /*-----------------------------------------------------------------------------------*/
    /*  BR Mediathek
    /*-----------------------------------------------------------------------------------*/
    public function brmediathek()
    {
        if ($this->options->brmediathek->active == true) {
            wp_oembed_remove_provider('#https?://(www\.)?br\.de/*#i');
            $this->registerHandler('brmediathek');
        }
    }

    public function wp_embed_handler_brmediathek($matches, $attr, $url, $rawattr)
    {
        if (is_feed()) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return sprintf('<a href="%1$s">%1$s</a>', $url);
            } else {
                return '';
            }
        }
        
        $id = md5(uniqid('', true));
        
        $relvideo = '';
        if ($this->options->brmediathek->norel) {
            $relvideo = '&rel=0';
        }
        
        $width = isset($attr['width']) && absint($attr['width']) ? absint($attr['width']) : $this->options->embed_defaults->width;
        $height = isset($attr['height']) && absint($attr['height']) ? absint($attr['height']) : $this->options->embed_defaults->height;
       
        $embed = '<div class="fau-oembed oembed">';
        $embed .= '<div class="elastic-video">';
        $embed .= '<iframe class="brmediathek defaultwidth" frameborder="0" allow="autoplay; fullscreen" allowfullscreen aria-labelledby="' . $id . '"';
        $embed .= ' src="https://www.br.de/mediathek/embed/' . esc_attr($matches[1]) . '?autoplay=false&muted=false' . $relvideo . '" width="' . $width.'" height="' . $height . '">';
	
	$notice = $this->options->brmediathek->iframe_notice;
	if (!empty($notice)) {
	     $embed .= '<p>'.$notice.' <a href="'.$url.'">'.$url.'</a></p>';
	}
	$embed .= '</iframe>';
	
        $embed .= '</div>';
        $embed .= '</div>';

        wp_enqueue_style('fau-oembed-style');
        return apply_filters('embed_brmediathek', $embed, $matches, $attr, $url, $rawattr);
    }
}
