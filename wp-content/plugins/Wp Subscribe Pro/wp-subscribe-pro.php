<?php
/*
Plugin Name: WP Subscribe Pro
Plugin URI: http://mythemeshop.com/plugins/wp-subscribe-pro/
Description: WP Subscribe is a simple but powerful subscription plugin which supports MailChimp, Aweber, Feedburner, GetResponse, MailerLite, BenchmarkEmail and Constant Contact.
Author: MyThemeShop
Version: 1.4.0
Author URI: http://mythemeshop.com/
*/ 
// Pemberitahuan kepada penjual apabila ada produk terbaru dari distributor

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define('WP_SUBSCRIBE_PRO_PLUGIN_VERSION', '1.4.0');
define('WP_SUBSCRIBE_PRO_PLUGIN_BASE', plugin_basename( __FILE__ ));

// Make it load WP Subscribe first
function wp_subscribe_load_plugin_first() {
	$this_plugin = 'wp-subscribe/wp-subscribe.php';
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_search($this_plugin, $active_plugins);
	if ($this_plugin_key) { // if it's 0 it's the first plugin already, no need to continue
		array_splice($active_plugins, $this_plugin_key, 1);
		array_unshift($active_plugins, $this_plugin);
		update_option('active_plugins', $active_plugins);
	}
}
add_action("activated_plugin", "wp_subscribe_load_plugin_first");

if (!class_exists('Mailchimp'))
    require_once dirname(__FILE__) . '/Mailchimp.php';

if (!class_exists('GetResponse'))
    require_once dirname(__FILE__) . '/getresponse.php';

if (!class_exists('BMEAPI'))
	require_once dirname(__FILE__) . '/BMEAPI.class.php';

if (!function_exists('wp_subscribe_register_widget')) {
    include_once dirname(__FILE__) . '/wp-subscribe-widget.php';
    include_once dirname(__FILE__) . '/functions.php';
	include_once dirname(__FILE__) . '/options.php';
} else {
    // add notice
    add_action( 'admin_notices', 'wp_subscribe_deactivate_plugin_notice' );

	function wp_subscribe_deactivate_plugin_notice() {
	    ?>
	    <div class="error">
	        <p><?php _e( 'Please deactivate WP Subscribe plugin first to use the Premium features!', 'wp-review' ); ?></p>
	    </div>
	    <?php
	}
}