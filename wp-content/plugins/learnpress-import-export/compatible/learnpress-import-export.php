<?php
/*
Plugin Name: LearnPress Export/Import Courses
Plugin URI: http://thimpress.com/learnpress
Description: Export and Import your courses with all lesson and quiz in easiest way
Author: ThimPress
Version: 0.9.2
Author URI: http://thimpress.com
Text Domain: learnpress-import-export
Tags: learnpress
*/
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !defined( 'LPR_EXPORT_IMPORT_FILE' ) ){
	define( 'LPR_EXPORT_IMPORT_FILE', __FILE__ );
	define( 'LPR_EXPORT_IMPORT_PATH', dirname( __FILE__ ) );
}
/**
 * Register Export/Import courses addon
 */
function learn_press_register_export_import() {
	require_once( LPR_EXPORT_IMPORT_PATH . '/init.php' );
}

add_action( 'learn_press_register_add_ons', 'learn_press_register_export_import' );
add_action( 'plugins_loaded', 'learnpress_import_export_translations' );
function learnpress_import_export_translations() {
	$textdomain    = 'learnpress_import_export';
	$locale        = apply_filters( "plugin_locale", get_locale(), $textdomain );
	$lang_dir      = dirname( __FILE__ ) . '/lang/';
	$mofile        = sprintf( '%s.mo', $locale );
	$mofile_local  = $lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
	if ( file_exists( $mofile_global ) ) {
		load_textdomain( $textdomain, $mofile_global );
	} else {
		load_textdomain( $textdomain, $mofile_local );
	}
}
