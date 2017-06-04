<?php

define("NUSANTARA_ONGKIR", 'plugin-ongkos-kirim');

global $nusantara_core;
global $nusantara_helper;
$nusantara_core = new \PluginOngkosKirim\Controller\Core();
$nusantara_helper = new \PluginOngkosKirim\Helpers\Helpers();
new \PluginOngkosKirim\Controller\Ajax();
new \PluginOngkosKirim\Controller\Admin();
new \PluginOngkosKirim\Controller\CustomField();

add_action('init', 'tjWooOngkirStartSession', 1);
function tjWooOngkirStartSession() {
    if(!session_id()) {
        session_start();
	}
	if(!isset($_SESSION['_nusantara_ongkir_session_country']))
		$_SESSION['_nusantara_ongkir_session_country'] = 'ID';
}

/*load language*/
add_action('plugins_loaded', 'nusantara_ongkir_plugin_init');
if(!function_exists('nusantara_ongkir_plugin_init')) {
	function nusantara_ongkir_plugin_init() {
	    load_plugin_textdomain(NUSANTARA_ONGKIR, false, dirname(plugin_basename(__FILE__)) . '/../languages');
	}
}

add_action('woocommerce_checkout_update_order_review','nusantara_delete_transient');
function nusantara_delete_transient() {
	global $wpdb,$options_conf,$woocommerce;
	
	WC()->session->set( 'shipping_for_package', array('package_hash'=>'') );

	$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '_transient%_wc_ship_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '_transient_timeout%_wc_ship_%'" );
}

add_action( 'wp_enqueue_scripts', 'nusantara_ongkir_css' );
function nusantara_ongkir_css() {
    wp_enqueue_style( 'woo_ongkir_css', plugins_url('/../assets/css/woo_ongkir.css',__FILE__) );
}

add_action( 'admin_enqueue_scripts', 'load_nusantara_jquery' );
function load_nusantara_jquery() {
    // jQuery
	wp_enqueue_script('jquery');

    wp_enqueue_style( 'woo_ongkir_css_admin', plugins_url('/../assets/css/woo_ongkir_admin.css',__FILE__) );    
    wp_enqueue_style( 'nunsantara_token-input.css', plugins_url('/../assets/css/token-input.css',__FILE__) );
    wp_enqueue_script( 'woo-ongkir-jquery.tokeninput.js', plugins_url('/../assets/js/jquery.tokeninput.js',__FILE__) );
}

function is_session_started() {
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

add_action('woocommerce_checkout_process', 'my_custom_checkout_field_process');
function my_custom_checkout_field_process() {
	$is_type = get_option('nusantara_raja_ongkir_type','starter');
	$vendor = get_option('nusantara_base_api');
	if($is_type == 'pro' || $vendor != 'rajaongkir') {
	    if ( !$_POST[ 'billing_district' ] && @$_POST[ 'billing_country' ] == 'ID' ) 
	        wc_add_notice( __( '<b>Billing district</b> is required', NUSANTARA_ONGKIR ), 'error' );

	    if(isset($_POST['ship_to_different_address']) && !empty($_POST['ship_to_different_address']) && @$_POST[ 'shipping_country' ] == 'ID') {
	    	if ( !$_POST[ 'shipping_district' ] )
		        wc_add_notice( __( '<b>Shipping district</b> is required', NUSANTARA_ONGKIR ), 'error' );
	    }
	}

}

// saving customer custom address
add_action( 'woocommerce_checkout_update_order_meta', 'pok_update_order_meta' );
function pok_update_order_meta( $order_id ) {
	global $woocommerce;
	$order = new WC_Order( $order_id );
	$user_id = version_compare( $woocommerce->version, '3.0', '>=' ) ? $order->get_user_id() : $order->user_id;
    if ( ! empty( $_POST['billing_state'] ) ) {
        update_user_meta($user_id, 'billing_state', sanitize_text_field( $_POST['billing_state']));
    }
    if ( ! empty( $_POST['billing_district'] ) ) {
        update_user_meta($user_id, 'billing_district', sanitize_text_field( $_POST['billing_district']));
    }
    if ( ! empty( $_POST['shipping_state'] ) ) {
        update_user_meta($user_id, 'shipping_state', sanitize_text_field( $_POST['shipping_state']));
    }
    if ( ! empty( $_POST['shipping_district'] ) ) {
        update_user_meta($user_id, 'shipping_district', sanitize_text_field( $_POST['shipping_district']));
    }
}

add_action('woocommerce_before_template_part','inject_table_class_before',10,4);
function inject_table_class_before( $template_name, $template_path, $located, $args  ) {
	if( $template_name == 'checkout/review-order.php' ) {
		if( !puk_check_table_class( $located ) ) {
			echo "<div class='woocommerce-checkout-review-order-table'>";			
			global $puk_table_class_after;
			$puk_table_class_after = true;			
		}
	}
}

function puk_check_table_class( $located ) {
	if( strpos( file_get_contents( $located) ,'woocommerce-checkout-review-order-table' ) !== false) 
		return true;
	return false;
}

add_action('woocommerce_after_template_part','inject_table_class_after',10,4);
function inject_table_class_after( $template_name, $template_path, $located, $args  ) {
	global $puk_table_class_after;

	if( $puk_table_class_after ) {
		echo "</div>";
		$puk_table_class_after = false;
	}
}

// essentially disable WooCommerce's shipping rates cache
// fixing ongkir error for WooCommerce 2.6.0
add_filter('woocommerce_checkout_update_order_review', 'clear_wc_shipping_rates_cache');
function clear_wc_shipping_rates_cache(){
 	$packages = WC()->cart->get_shipping_packages();
 	foreach ($packages as $key => $value) {
 		$shipping_session = "shipping_for_package_$key";
 		unset(WC()->session->{$shipping_session});
 	}
}

// detail ongkir address on email invoice
add_action( 'woocommerce_email_customer_details', 'pok_detail_on_invoice', 30, 3 );
function pok_detail_on_invoice( $order, $sent_to_admin = false, $plain_text = false ) {
	global $woocommerce;
	// billing details
	$order_id = version_compare( $woocommerce->version, '3.0', '>=' ) ? $order->get_id() : $order->id;
	if(! get_post_meta($order_id, 'meta_woo_ongkir')) {
		$billing_state = get_post_meta( $order_id, '_billing_state', true );
		$billing_city = get_post_meta( $order_id, '_billing_city', true );
		$billing_district = get_post_meta( $order_id, '_billing_district', true );
	} else {
		$meta_ongkir = get_post_meta($order_id, 'meta_woo_ongkir', true);
		$billing_state = isset($meta_ongkir['billing_state']) ? $meta_ongkir['billing_state'] : '';
		$billing_city = isset($meta_ongkir['billing_city']) ? $meta_ongkir['billing_city'] : '';
		$billing_district = isset($meta_ongkir['billing_district']) ? $meta_ongkir['billing_district'] : '';
	}

	// shipping detail
	if(! get_post_meta($order_id, 'meta_woo_ongkir')) {
		$shipping_state = get_post_meta( $order_id, '_shipping_state', true );
		$shipping_city = get_post_meta( $order_id, '_shipping_city', true );
		$shipping_district = get_post_meta( $order_id, '_shipping_district', true );
	} else {
		$meta_ongkir = get_post_meta($order_id, 'meta_woo_ongkir', true);
		$shipping_state = isset($meta_ongkir['shipping_state']) ? $meta_ongkir['shipping_state'] : '';
		$shipping_city = isset($meta_ongkir['shipping_city']) ? $meta_ongkir['shipping_city'] : '';
		$shipping_district = isset($meta_ongkir['shipping_district']) ? $meta_ongkir['shipping_district'] : '';
	}

	if(isset($billing_state) && $billing_state != ''):
	?>

	<table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">
		<tr>
			<td class="td" style="text-align:left; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" valign="top" width="50%">
				<h3><?php _e( 'Billing address details', NUSANTARA_ONGKIR ); ?></h3>
				
				<p class="text">
					<b><?php _e( 'District', NUSANTARA_ONGKIR ); ?>:</b><br>
					<?php echo $billing_district ?>
				</p>
				<p class="text">
					<b><?php _e( 'Town / City', NUSANTARA_ONGKIR ); ?>:</b><br>
					<?php echo $billing_city ?>
				</p>
				<p class="text">
					<b><?php _e( 'Province', NUSANTARA_ONGKIR ); ?>:</b><br>
					<?php echo $billing_state ?>
				</p>
			</td>
			<td class="td" style="text-align:left; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" valign="top" width="50%">
				<h3><?php _e( 'Shipping address details', NUSANTARA_ONGKIR ); ?></h3>
			
				<p class="text">
					<b><?php _e( 'District', NUSANTARA_ONGKIR ); ?>:</b><br>
					<?php echo (isset($shipping_district) && $shipping_district != '') ? $shipping_district : $billing_district; ?>
				</p>
				<p class="text">
					<b><?php _e( 'Town / City', NUSANTARA_ONGKIR ); ?>:</b><br>
					<?php echo (isset($shipping_city) && $shipping_city != '') ? $shipping_city : $billing_city; ?>
				</p>
				<p class="text">
					<b><?php _e( 'Province', NUSANTARA_ONGKIR ); ?>:</b><br>
					<?php echo (isset($shipping_state) && $shipping_state != '') ? $shipping_state : $billing_state; ?>
				</p>
			</td>			
		</tr>
	</table>

	<?php

	endif; // isset billing_state
}