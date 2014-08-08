<?php
/*
Plugin Name: Windows Phone Store Box
Plugin URI:  http://wordpress.org/plugins/windows-phone-store-box
Description: Displaying windowsphone.com store app information in a box inside post or page.
Version:     0.1
Author:      fahmiadib
Author URI:  http://fahmiadib.wordpress.com
License:     GPLv2 or later
*/

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ )
	die( 'Access denied.' );

define( 'WPSBOX_NAME',                 'Windows Phone Store Box' );
define( 'WPSBOX_REQUIRED_PHP_VERSION', '5.2' );
define( 'WPSBOX_REQUIRED_WP_VERSION',  '3.5' );

/**
 * Checks if the system requirements are met
 * @return bool True if system requirements are met, false if not
 */
function wpsbox_requirements_met() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, WPSBOX_REQUIRED_PHP_VERSION, '<' ) ) {
		return false;
	}

	if ( version_compare( $wp_version, WPSBOX_REQUIRED_WP_VERSION, '<' ) ) {
		return false;
	}

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 */
function wpsbox_requirements_error() {
	global $wp_version;

	require_once( dirname( __FILE__ ) . '/views/requirements-error.php' );
}

/**
 * Loads all the files that make up WPSBOX
 */
function wpsbox_include_files() {
	require_once( dirname( __FILE__ ) . '/classes/wpsbox.php' );
	require_once( dirname( __FILE__ ) . '/classes/wpsbox-settings.php' );
	require_once( dirname( __FILE__ ) . '/classes/wpsbox-shortcode.php' );
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the plugin requirements are met. 
 * Otherwise older PHP installations could crash when trying to parse it.
 */
if ( wpsbox_requirements_met() ) {
	wpsbox_include_files();

	if ( class_exists( 'WPSBox' ) ) {
		$GLOBALS['wpsbox'] = WPSBox::get_instance();

		register_activation_hook(   __FILE__, array( $GLOBALS['wpsbox'], 'activate' ) );
		register_deactivation_hook( __FILE__, array( $GLOBALS['wpsbox'], 'deactivate' ) );
	}
} else {
	add_action( 'admin_notices', 'wpsbox_requirements_error' );
}