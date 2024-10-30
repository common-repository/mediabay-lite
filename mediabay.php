<?php
/**
 * Plugin Name: Mediabay Lite
 * Plugin URI:  https://codedraft.xyz/mediabay/
 * Description: Get organized with thousands of images. Organize media into folders.
 * Version:     1.6
 * Author:      codedrafty
 * Author URI:  https://codedraft.xyz/mediabay/
 * Text Domain: mediabay
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages/
 */


if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MEDIABAY__FILE__', __FILE__ );
define( 'MEDIABAY_FOLDER', 'mediabay_wpfolder' );
define( 'MEDIABAY_VERSION', '1.6' );
define( 'MEDIABAY_PATH', plugin_dir_path( MEDIABAY__FILE__ ) );
define( 'MEDIABAY_URL', plugins_url( '/', MEDIABAY__FILE__ ) );
define( 'MEDIABAY_ASSETS_URL', MEDIABAY_URL . 'assets/' );
define( 'MEDIABAY_TEXT_DOMAIN', 'mediabay' );
define( 'MEDIABAY_PLUGIN_BASE', plugin_basename( MEDIABAY__FILE__ ) );
define( 'MEDIABAY_PLUGIN_NAME', 'Mediabay Lite' );


function mediabay_plugins_loaded()
{
	// include main plugin file
	include_once ( MEDIABAY_PATH . 'inc/plugin.php' );
	load_plugin_textdomain(MEDIABAY_TEXT_DOMAIN, false, plugin_basename(__DIR__) . '/languages/');
}

add_action('plugins_loaded', 'mediabay_plugins_loaded');