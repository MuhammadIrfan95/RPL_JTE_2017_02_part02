<?php
/*
Plugin Name: Rich Snippets WordPress Plugin by WP-Buddy
Plugin URI: http://wp-buddy.com/products/plugins/rich-snippets-wordpress-plugin/
Description: Allows you to create rich snippets to use in your posts, pages and custom post types.
Version: 1.6.1
Author: wp-buddy
Author URI: http://codecanyon.net/user/wpbuddy
License: CodeCanyon regular License
License URI: http://codecanyon.net/licenses
Text Domain: rich-snippets-wordpress-plugin
Domain Path: /assets/langs/
*/
/*  Copyright 2012-2014  WP-Buddy  (email : info@wp-buddy.com)
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'wpbrs_autoloader' ) ) {
	/**
	 * The autoloader class
	 *
	 * @param string $class_name
	 *
	 * @return bool
	 * @since 1.0
	 */
	function wpbrs_autoloader( $class_name ) {
		$file = trailingslashit( dirname( __FILE__ ) ) . 'classes/' . strtolower( $class_name ) . '.php';
		if ( is_file( $file ) ) {
			require_once( $file );

			return true;
		}

		return false;
	}
}

// registering the autoloader function
try {
	spl_autoload_register( 'wpbrs_autoloader', true );
} catch ( Exception $e ) {
	function __autoload( $class_name ) {
		wpbrs_autoloader( $class_name );
	}
}

if ( ! isset( $GLOBALS['wpb_rich_snippets'] ) ) {
	$GLOBALS['wpb_rich_snippets'] = new WPB_Rich_Snippets( __FILE__ );
}
