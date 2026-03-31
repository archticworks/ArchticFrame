<?php
/**
 * Plugin Name: ArchticFrame
 * Description: Assign archive posts to custom post types and load Archtic templates.
 * Version: 1.0.0
 * Author: archtic
 * Author URI: https://profiles.wordpress.org/archtic/
 * Text Domain: archticframe
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARCHTICFRAME_VERSION', '1.0.0' );
define( 'ARCHTICFRAME_PATH', plugin_dir_path( __FILE__ ) );
define( 'ARCHTICFRAME_URL', plugin_dir_url( __FILE__ ) );

require_once ARCHTICFRAME_PATH . 'includes/core.php';

function archticframe_init() {
	ArchticFrame_Core::init();
}
add_action( 'plugins_loaded', 'archticframe_init' );