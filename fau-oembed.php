<?php

/**
 * Plugin Name:         FAU oEmbed
 * Plugin URI:          https://github.com/RRZE-Webteam/fau-oembed
 * GitHub Plugin URI:   https://github.com/RRZE-Webteam/fau-oembed
 * Description:         Automatische Einbindung der FAU-Karten, Videos von FAU.tv, YouTube-Videos ohne Cookies, sowie weitere oEmbed-Quellen der FAU.
 * Version:             3.0.0
 * Author:              RRZE-Webteam
 * Author URI:          https://blogs.fau.de/webworking/
 * License:             GNU General Public License v2
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:         /languages
 * Text Domain:         fau-oembed
 */





namespace FAU\OEmbed;


defined('ABSPATH') || exit;

const RRZE_PHP_VERSION = '7.1';
const RRZE_WP_VERSION = '5.1';

const RRZE_PLUGIN_FILE = __FILE__;

// Automatische Laden von Klassen.
spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $base_dir = __DIR__ . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Registriert die Plugin-Funktion, die bei Aktivierung des Plugins ausgeführt werden soll.
register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
// Registriert die Plugin-Funktion, die ausgeführt werden soll, wenn das Plugin deaktiviert wird.
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');
// Wird aufgerufen, sobald alle aktivierten Plugins geladen wurden.
add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * Einbindung der Sprachdateien.
 */
function load_textdomain() {
    load_plugin_textdomain('fau-oembed', false, sprintf('%s/languages/', dirname(plugin_basename(__FILE__))));
}

/**
 * Wird durchgeführt, nachdem das Plugin aktiviert wurde.
 */
function activation() {
    // Sprachdateien werden eingebunden.
    load_textdomain();

    // Überprüft die minimal erforderliche PHP- u. WP-Version.
    system_requirements();

}

/**
 * Wird durchgeführt, nachdem das Plugin deaktiviert wurde.
 */
function deactivation() {
    // Hier können die Funktionen hinzugefügt werden, die
    // bei der Deaktivierung des Plugins aufgerufen werden müssen.
    // Bspw. wp_clear_scheduled_hook, flush_rewrite_rules, etc.
    
    // TODO :))
}

/**
 * Überprüft die minimal erforderliche PHP- u. WP-Version.
 */
function system_requirements() {
    $error = '';

    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        /* Übersetzer: 1: aktuelle PHP-Version, 2: erforderliche PHP-Version */
        $error = sprintf(__('Your server is running PHP version %1$s. Please upgrade at least to PHP version %2$s.', 'fau-oembed'), PHP_VERSION, RRZE_PHP_VERSION);
    }

    if (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        /* Übersetzer: 1: aktuelle WP-Version, 2: erforderliche WP-Version */
        $error = sprintf(__('Your Wordpress version is %1$s. Please upgrade at least to Wordpress version %2$s.', 'fau-oembed'), $GLOBALS['wp_version'], RRZE_WP_VERSION);
    }

    // Wenn die Überprüfung fehlschlägt, dann wird das Plugin automatisch deaktiviert.
    if (!empty($error)) {
        deactivate_plugins(plugin_basename(__FILE__), false, true);
        wp_die($error);
    }
}

/**
 * Wird durchgeführt, nachdem das WP-Grundsystem hochgefahren
 * und alle Plugins eingebunden wurden.
 */
function loaded() {
    // Sprachdateien werden eingebunden.
    load_textdomain();

    // Hauptklasse (Main) wird instanziiert.
    new Main();
}

