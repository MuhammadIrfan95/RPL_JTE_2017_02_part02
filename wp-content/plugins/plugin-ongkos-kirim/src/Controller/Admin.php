<?php
namespace PluginOngkosKirim\Controller;

class Admin {

	public function __construct() {
		add_action( 'admin_menu', array($this,'pok_admin_menu') );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array($this,'meta_after_billing_address'), 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array($this,'meta_after_shipping_address'), 10, 1 );
		add_action( 'admin_notices', array($this,'pok_admin_notices') );
	}

	public function pok_admin_menu() {
		add_menu_page( 'Plugin Ogkos Kirim', 'Plugin Ongkos Kirim', 'manage_options', 'plugin-ongkos-kirim', array($this,'plugin_ongkos_kirim_onboarding'), 'dashicons-products', 6  );
	}

	public function plugin_ongkos_kirim_onboarding() {
		require __DIR__.'/../Template/OnBoardingPage.php';
	}

	public function meta_after_billing_address($order) {
		global $woocommerce;
		$order_id = version_compare( $woocommerce->version, '3.0', '>=' ) ? $order->get_id() : $order->id;
		if(! get_post_meta($order_id, 'meta_woo_ongkir')) {
			$state = get_post_meta( $order_id, '_billing_state', true );
			$city = get_post_meta( $order_id, '_billing_city', true );
			$district = get_post_meta( $order_id, '_billing_district', true );
		} else {
			$meta_ongkir = get_post_meta($order_id, 'meta_woo_ongkir', true);
			$state = isset($meta_ongkir['billing_state']) ? $meta_ongkir['billing_state'] : '';
			$city = isset($meta_ongkir['billing_city']) ? $meta_ongkir['billing_city'] : '';
			$district = isset($meta_ongkir['billing_district']) ? $meta_ongkir['billing_district'] : '';
		}

		// billing
		if($district != '') {
			echo '<h4>Ongkos Kirim Billing Detail</h4>';
		    echo '<p><strong>'.__('District', NUSANTARA_ONGKIR).':</strong><br>' . $district . '</p>';
		    if($city != '') {
			    echo '<p><strong>'.__('Town / City', NUSANTARA_ONGKIR).':</strong><br>' . $city . '</p>';
			}
		    if($state != '') {
		    	echo '<p><strong>'.__('Province', NUSANTARA_ONGKIR).':</strong><br>' . $state . '</p>';
		    }
		}
	}

	public function meta_after_shipping_address($order) {
		global $woocommerce;
		$order_id = version_compare( $woocommerce->version, '3.0', '>=' ) ? $order->get_id() : $order->id;
		if(! get_post_meta($order_id, 'meta_woo_ongkir')) {
			$state = get_post_meta( $order_id, '_shipping_state', true );
			$city = get_post_meta( $order_id, '_shipping_city', true );
			$district = get_post_meta( $order_id, '_shipping_district', true );
		} else {
			$meta_ongkir = get_post_meta($order_id, 'meta_woo_ongkir', true);
			$state = isset($meta_ongkir['shipping_state']) ? $meta_ongkir['shipping_state'] : '';
			$city = isset($meta_ongkir['shipping_city']) ? $meta_ongkir['shipping_city'] : '';
			$district = isset($meta_ongkir['shipping_district']) ? $meta_ongkir['shipping_district'] : '';
		}

		// shipping
		if($district != '') {
			echo '<h4>Ongkos Kirim Shipping Detail</h4>';
		    echo '<p><strong>'.__('District', NUSANTARA_ONGKIR).':</strong><br>' . $district . '</p>';
		    if($city != '') {
			    echo '<p><strong>'.__('Town / City', NUSANTARA_ONGKIR).':</strong><br>' . $city . '</p>';
			}
		    if($state != '') {
		    	echo '<p><strong>'.__('Province', NUSANTARA_ONGKIR).':</strong><br>' . $state . '</p>';
		    }
		}
	}

	// admin notices
	public function pok_admin_notices() {
		global $nusantara_helper;
		global $nusantara_core;
		$errors = array();
		$status = get_option('woocommerce_plugin_ongkos_kirim_settings', array('enabled'=>'no'));

		if (!$nusantara_helper->is_woocommerce_active()) {
			$errors[] = __('Woocommerce not active', NUSANTARA_ONGKIR);
		}
		
		if (!function_exists('curl_version')) {
			$errors[] = __('Plugin Ongkos Kirim needs active CURL', NUSANTARA_ONGKIR);
		}

		if ($status['enabled'] == 'yes') {
			if (! $nusantara_helper->is_license_active()) {
				if (get_option('nusantara_ongkir_license_status') !== false && get_option('nusantara_ongkir_lisensi') !== false) {
					$errors[] = __('License is not active.', NUSANTARA_ONGKIR);
				}
			} else {
				if (get_option('nusantara_base_api') == 'rajaongkir') {
					$rajaongkir_status = get_option('nusantara_rajaongkir_key_status', array(false, ""));
					if (!$rajaongkir_status[0]) {
						$errors[] = __('RajaOngkir API Key is not active.', NUSANTARA_ONGKIR);
					}
				}
				if ($nusantara_helper->is_admin_active()) {
					$store_location = get_option('nusantara_store_location',array());
					if (empty($store_location) || empty($store_location[0])) {
						$errors[] = __('Store Location is empty.', NUSANTARA_ONGKIR);
					}
					$courier = get_option('nusantara_courir_type', array());
					if (empty($courier)) {
						$errors[] = __('Selected Couriers is empty.', NUSANTARA_ONGKIR);
					}
				}
			}
		}
		if (!empty($errors)) {
			?>
			<div class="notice notice-error">
				<p><?php _e('<strong>Plugin Ongkos Kirim</strong> is disabled due to the following errors:', NUSANTARA_ONGKIR) ?></p>
				<?php foreach ($errors as $e) : ?>
					<p style="margin:0;">- <?php echo $e; ?></p>
				<?php endforeach; ?>
				<p style="margin-top: 0;"></p>
			</div>
			<?php
		}
	}
}