<?php
/**
 * Plugin Name: Screets Chat X
 * Plugin URI: http://apps.screets.org/chat-x-night-bird/
 * Description: <strong>Night Bird.</strong> Chat with your visitors on your website.
 * Version: 2.2.8
 * Author: Screets Team
 * Author URI: http://www.screets.com
 * Requires at least: 4.3
 * Tested up to: 4.7.2
 *
 *
 * Text Domain: schat
 * Domain Path: /languages/
 *
 * @package Chat X
 * @author Screets
 *
 * COPYRIGHT © 2016 Screets d.o.o. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

// Some useful constants
define( 'SCX_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SCX_URL', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) );
define( 'SCX_EDITION', 'Night Bird' );
define( 'SCX_PX', $wpdb->prefix . 'scx_' ); // DB Table prefix
define( 'SCX_SLUG', 'screets-cx' ); // Using for updating as slug and in options as prefix

define( 'SCX_FIREBASE_VERSION', '3.2.0' );

require_once( SCX_PATH . '/core/library/titan-framework/titan-framework-embedder.php' );

class ChatX  {

	/**
	 * @var Object
	 */
	var $opts;

	/**
	 * @var Object
	 */
	var $__opts;

	/**
	 * @var object 	Plugin meta data
	 */
	var $meta;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Check that function get_plugin_data exists
		if ( !function_exists( 'get_plugin_data' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Get plugin meta
		$this->meta = get_plugin_data( __FILE__, false );

		// Define app meta constants
		define( 'SCX_NAME', $this->meta['Name'] );
		define( 'SCX_NAME_SHORT', str_replace( 'Screets ', '', SCX_NAME ) );
		define( 'SCX_VERSION', $this->meta['Version'] );
		define( 'SCX_PLUGIN_URL', $this->meta['PluginURI'] );

		// Include required files
		$this->includes();

		// Install the plugin
		register_activation_hook( __FILE__, array( 'ChatX_Install', 'install' ) );

		// Get options
		add_action( 'tf_done', array( &$this, 'get_opts' ) );

		// Actions
		add_action( 'init', array( &$this, 'init' ), 10 );
		
		// Loaded action
		do_action( 'scx_loaded' );

	}

	/**
	 * Get options
	 *
	 * @access public
	 * @return void
	 */
	function get_opts() {
		
		// Get options framework
		$this->opts = TitanFramework::getInstance( SCX_SLUG );

	}

	/**
	 * Translate fields.
	 *
	 * @access public
	 * @return void
	 */
	function translate() {

		// Register strings to Polylang
		if( function_exists( 'pll_register_string' ) ) {
			foreach( $this->__opts as $id => $opt ) {
				$string = $this->opts->getOption( $id );
				pll_register_string( $opt['name'], $string, SCX_NAME, $opt['multiline'] );
			}
		}

		// Register strings to WPML 3.2+
		if( has_action( 'wpml_register_single_string' ) ) {
			foreach( $this->__opts as $id => $opt ) {
				$string = $this->opts->getOption( $id );
				do_action( 'wpml_register_single_string', SCX_NAME, $id, $string );
			}
		}
		
	}

	/**
	 * Init the plugin when WordPress initializes
	 *
	 * @access public
	 * @return void
	 */
	function init() {

		// Before init action
		do_action( 'before_scx_init' );

		if( current_user_can( 'scx_answer_visitor' ) )
			define( 'SCX_OP', true ); // User is operator?
		else
			define( 'SCX_VISITOR', true ); // User is visitor?

		// Set up localization
		$this->load_plugin_textdomain();

		// Initialization action
		do_action( 'scx_init' );

	}

	/**
	 * Include required core files
	 *
	 * @access public
	 * @return void
	 */
	function includes() {

		// Include core files
		require SCX_PATH . '/core/install.php';
		require SCX_PATH . '/core/fn.shortcodes.php';
		require SCX_PATH . '/core/fn.firebase.php';
		require SCX_PATH . '/core/fn.post-types.php';
		require SCX_PATH . '/core/fn.common.php';
		require SCX_PATH . '/core/fn.users.php';
		require SCX_PATH . '/core/fn.security.php';
		require SCX_PATH . '/core/fn.options.php';

		// Spyc library
		if ( !class_exists( 'Spyc' ) )
			require SCX_PATH . '/core/library/Spyc.php';

		// Back-end includes
		if( is_admin() ) {
			require SCX_PATH . '/core/class.nav-menu.php';
			require SCX_PATH . '/back-end.php';

		// Front-end includes
		} else {
			require SCX_PATH . '/core/fn.skin.php';
			require SCX_PATH . '/front-end.php';
		}

		// AJAX includes
		if ( defined( 'DOING_AJAX' ) ) {
			$this->ajax_includes();
		}

	}

	/**
	 * Include AJAX files
	 *
	 * @access public
	 * @return void
	 */
	function ajax_includes() {

		// Include back-end files
		require_once SCX_PATH . '/core/library/Pushover.php';
		require SCX_PATH . '/core/fn.ajax.php';

	}


	/**
	 * Get Ajax URL
	 *
	 * @access public
	 * @return string
	 */
	function ajax_url() {

		return admin_url( 'admin-ajax.php', 'relative' );

	}

	

	/**
	 * Localization
	 *
	 * @access public
	 * @return void
	 */
	function load_plugin_textdomain() {

		$locale = apply_filters( 'plugin_locale', get_locale(), 'schat' );

		load_textdomain( 'schat', WP_LANG_DIR . '/screets-chat/schat-' . $locale . '.mo' );
		load_plugin_textdomain( 'schat', false, SCX_PATH . '/languages/' );
	}

	/**
	 * Get token
	 *
	 * @access public
	 * @return string token
	 */
	public function get_token() {

		// Load required libraries
		if( !class_exists( 'Services_FirebaseTokenGenerator' ) )
			require_once SCX_PATH . '/core/library/firebase/FirebaseToken.php';

		// Create token generator
		$generator = new Services_FirebaseTokenGenerator( trim( $this->opts->getOption('app-secret') ) );

		// Admin user
		$is_admin = ( current_user_can( 'manage_options' ) ) ? true : false;

		// Debugging?
		$debug = $this->opts->getOption('debug' );
		
		// Create token
		$token = $generator->createToken(

			array(
				'uid' => fn_scx_get_user_id(),
				// Is current user an chat operator?
				'is_op' => ( $is_admin || current_user_can( 'scx_answer_visitor' ) ) ? true : false
			),

			array(

				// Admin role disables all security rules for this client
				// This will provide the client with read and write access to your entire Firebase
				'admin' => ( $is_admin ) ? true : false,

				// Debugging
				'debug' => ( !empty( $debug ) ) ? true : false

			)

		);

		return $token;

	}

	/**
	 * Activate plugin
	 *
	 * @access public
	 * @return void
	 */
	public function activate() {
		
		// Register operator role
		add_role(
			'cx_op',
			'Chat X Operator',
			array(
				'read' => true, // True allows that capability
				'edit_posts' => true,
				'delete_posts' => false // Use false to explicitly deny
			)
		);

		// Get roles
		$admin_role = get_role( 'administrator' );
		$op_role = get_role( 'cx_op' );

		// Add chat capability to admin and operators
		$admin_role->add_cap( 'scx_answer_visitor' );
		$admin_role->add_cap( 'scx_see_logs' );
		$admin_role->add_cap( 'scx_manage_chat_options' );

		$op_role->add_cap( 'scx_answer_visitor' );
		$op_role->add_cap( 'scx_see_logs' );

	}

	/**
	 * Load common scripts
	 *
	 * @access public
	 * @return void
	 */
	function common_scripts() {

		// Twemoji JS
		wp_register_script(
			'twemoji',
			'//twemoji.maxcdn.com/twemoji.min.js',
			null,
			SCX_VERSION,
			true
		);
		wp_enqueue_script( 'twemoji' );

		// Polyfill JS
		wp_register_script(
			'scx-polyfill',
			SCX_URL . '/assets/js/scx.polyfill.js',
			null,
			SCX_VERSION,
			true
		);
		wp_enqueue_script( 'scx-polyfill' );

		// Firebase JS
		wp_register_script(
			'scx-firebase',
			SCX_URL . '/assets/js/scx.firebase.js',
			array( 'firebase', 'firebase-app', 'firebase-auth', 'firebase-db' ),
			SCX_VERSION,
			true
		);
		wp_enqueue_script( 'scx-firebase' );

		// Firebase JS
		wp_register_script(
			'firebase',
			'https://www.gstatic.com/firebasejs/' . SCX_FIREBASE_VERSION . '/firebase.js',
			null, SCX_FIREBASE_VERSION, true
		);
		wp_enqueue_script( 'firebase' );

		// Firebase App JS
		wp_register_script(
			'firebase-app',
			'https://www.gstatic.com/firebasejs/' . SCX_FIREBASE_VERSION . '/firebase-app.js',
			null, SCX_FIREBASE_VERSION, true
		);
		wp_enqueue_script( 'firebase-app' );

		// Firebase Auth JS
		wp_register_script(
			'firebase-auth',
			'https://www.gstatic.com/firebasejs/' . SCX_FIREBASE_VERSION . '/firebase-auth.js',
			null, SCX_FIREBASE_VERSION, true
		);
		wp_enqueue_script( 'firebase-auth' );

		// Firebase DB JS
		wp_register_script(
			'firebase-db',
			'https://www.gstatic.com/firebasejs/' . SCX_FIREBASE_VERSION . '/firebase-database.js',
			null, SCX_FIREBASE_VERSION, true
		);
		wp_enqueue_script( 'firebase-db' );

	}

}

// Init the plugin class
$GLOBALS['ChatX'] = new ChatX();

/**
 * Get current page URL.
 *
 * @since Live Chat X (3.0.0)
 * @return string URL
 */
function fn_lcx_get_current_url() {

	$page_URL = 'http';
	
	if( !empty( $_SERVER['HTTPS'] ) ) {
		if ( @$_SERVER['HTTPS'] == 'on' )
			$page_URL .= "s";
	}

	$page_URL .= '://';

	if ( @$_SERVER['SERVER_PORT'] != '80' )
		$page_URL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .$_SERVER['REQUEST_URI'];
	else
		$page_URL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	return $page_URL;

}

/**
 * Check new plugin updates from Screets server.
 */
if( is_admin() ) {
	global $pagenow;

	if( !empty( $pagenow ) ) {

		switch( $pagenow ) {
			case 'plugin-install.php':
			case 'update-core.php':
			case 'plugins.php':
			case 'admin-ajax.php':
			case 'update.php':

				require SCX_PATH . '/core/library/update-checker/plugin-update-checker.php';

				$checker = PucFactory::buildUpdateChecker(
					'http://support.screets.org/wp-updates/?action=get_metadata&slug=screets-cx',
					__FILE__,
					'screets-cx'
				);
				$checker->addQueryArgFilter( '_fn_lcx_filter_updates' );

			break;

		}

	}

	/**
	 * Filter updates with API key.
	 *
	 * @since Live Chat X (1.0.1)
	 * @return array Query arguments
	 */
	function _fn_lcx_filter_updates( $query_args ) {

		$api_key = get_option( 'screets_key', 'general' );

		// Include API key
		if ( !empty( $api_key ) ) {
			$query_args['key'] = $api_key;
			$query_args['domain'] = fn_lcx_get_current_url();
		}

		return $query_args;
	}
	
}