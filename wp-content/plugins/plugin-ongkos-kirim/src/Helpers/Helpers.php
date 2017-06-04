<?php
namespace PluginOngkosKirim\Helpers;

use \PluginOngkosKirim\Controller\Core as CoreOngkir;

class Helpers {
	protected $config;
	protected $key;
	protected $type;
	protected $model;
	public function __construct() {
		global $nusantara_core;
		$this->model = $nusantara_core;

		if($this->is_plugin_active()) {
			add_action( 'woocommerce_cart_calculate_fees', array($this,'woo_add_cart_fee' ));
			add_action( 'woocommerce_checkout_update_order_meta', array($this,'custom_checkout_field_update_order_meta'));
			add_filter( 'woocommerce_checkout_fields',array($this, 'custom_override_checkout_fields' ));
			add_filter( 'woocommerce_states', array($this,'custom_woocommerce_states' ));
			add_action( 'woocommerce_review_order_after_cart_contents', array($this,'ongkir_extra_fields' ));
		}
	}

	public function is_license_active() {
		$license = get_option('nusantara_ongkir_license_status');
		if (empty($license[0]) || @$license[0] == false) {
			return false;
		}
		return true;
	}

	public function is_rajaongkir_active() {
		$rajaongkir_key = get_option('nusantara_api_key_raja_ongkir', "");
		if (empty($rajaongkir_key)) {
			return false;
		}
		$rajaongkir_status = get_option('nusantara_rajaongkir_key_status', array(false,''));
		if ($rajaongkir_status[0] == false) {
			return false;
		}
		return true;
	}

	public function is_plugin_active() { // for front
		$status = get_option('woocommerce_plugin_ongkos_kirim_settings', array('enabled'=>'yes'));
		if ($status['enabled'] == 'no') {
			return false;
		}

		// plugin ongkos kirim license status
		if (!$this->is_license_active()) {
			return false;
		}

		// curl must active
		if (!function_exists('curl_version')) {
			return false;
		}
		
		// rajaongkir status
		if (get_option('nusantara_base_api') == 'rajaongkir') {
			if (!$this->is_rajaongkir_active()) {
				return false;
			}
		}

		// base city
		$base_city = get_option('nusantara_store_location', array());
		if (empty($base_city)) {
			return false;
		}

		return true;
	}

	public function is_admin_active() { // for admin
		// plugin ongkos kirim license status
		if (!$this->is_license_active()) {
			return false;
		}
		
		// rajaongkir status
		if (get_option('nusantara_base_api','rajaongkir') == 'rajaongkir') {
			if (!$this->is_rajaongkir_active()) {
				return false;
			}
		}

		return true;
	}

	public function is_woocommerce_active() {
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ));
	}

	public function get_default_couriers($type = "") {
		if ($type == "rajaongkir_pro") {
			return array('jne','pos','tiki','pcp','rpx','esl','pandu','wahana','sicepat','jnt','pahala','cahaya','sap','jet','indah','dse','expedito','slis','first','ncs');
		} else if ($type == "rajaongkir_basic") {
			return array('jne','pos','tiki','pcp','rpx','esl');
		} else {
			return array('jne','pos','tiki');
		}
	}

	public function woo_add_cart_fee() {
		global $woocommerce;
	  	if(get_option('nusantara_with_unique_number') != 'yes')
	  		return false;
	  	$woocommerce->cart->add_fee( __('Unique numbers', NUSANTARA_ONGKIR), $this->random_number(get_option('nusantara_unique_number')) );	
	}

	public function random_number($panjang) {
	   	$karakter = '123456789';
	   	$string = '';
	   	for($i = 0; $i < $panjang; $i++) {
		   	$pos = rand(0, strlen($karakter)-1);
		   	$string .= $karakter{$pos};
	   	}
		if(isset($_SESSION['nusantara_unique_digit'])) {
			$string = $_SESSION['nusantara_unique_digit'];
		} else {
			$_SESSION['nusantara_unique_digit'] = $string;			
		}
	   	return $string;
	}

	public function custom_checkout_field_update_order_meta( $order_id ) {
		if(@$_SESSION['_nusantara_ongkir_session_country'] != 'ID') {
			return false;	
		}

	    $city_billing_id = 0;
	    $city_shipping_id = 0;
	    $meta_ongkir = array();

		// get array state / province
		$r_province = $this->model->getProvince();		
	    $r_prov = array();	    
	    foreach($r_province AS $prov) {
	        $r_prov[$prov->id] = $prov->nama;
	    }

	    // save billing state
		if (isset($_POST['billing_state']) && ! empty($_POST['billing_state'])) {
        	update_post_meta($order_id, '_billing_state', intval($_POST['billing_state']));
        	$meta_ongkir['billing_state'] = $r_prov[intval($_POST['billing_state'])];
    	}

    	// save shipping state
		if (isset($_POST['shipping_state']) && ! empty($_POST['shipping_state'])) {
        	update_post_meta($order_id, '_shipping_state', intval($_POST['shipping_state']));
        	$meta_ongkir['shipping_state'] = $r_prov[intval($_POST['shipping_state'])];
    	}

		// save billing city
		if (isset($_POST['billing_city']) && ! empty($_POST['billing_city'])) {
			$city_billing_id = intval($_POST['billing_city']);
			$city = $this->model->getSingleCity($city_billing_id);
			if (!empty($city)) {
	        	update_post_meta($order_id, '_billing_city', $city->nama);
	        	$meta_ongkir['billing_city'] = $city->nama;
	        } else { // when city data not provided by API
	        	if ($_POST['billing_state']) {
	        		$cities = $this->model->getCity(intval($_POST['billing_state']));
	        		$r_cities = array();	    
				    foreach($cities AS $c) {
				        $r_cities[$c->id] = $c->nama;
				    }
				    if ($r_cities[$city_billing_id]) {
				    	update_post_meta($order_id, '_billing_city', $r_cities[$city_billing_id]);
	        			$meta_ongkir['billing_city'] = $r_cities[$city_billing_id];
				    }
	        	}
	        }
    	}

    	// save shipping city
    	if (isset($_POST['shipping_city']) && ! empty($_POST['shipping_city'])) {
			$city_shipping_id = intval($_POST['shipping_city']);
			$city = $this->model->getSingleCity($city_shipping_id);
			if (!empty($city)) {
	        	update_post_meta($order_id, '_shipping_city', $city->nama);
	        	$meta_ongkir['shipping_city'] = $city->nama;
	        } else { // when city data not provided by API
	        	if ($_POST['shipping_state']) {
	        		$cities = $this->model->getCity(intval($_POST['shipping_state']));
	        		$r_cities = array();	    
				    foreach($cities AS $c) {
				        $r_cities[$c->id] = $c->nama;
				    }
				    if ($r_cities[$city_shipping_id]) {
				    	update_post_meta($order_id, '_shipping_city', $r_cities[$city_shipping_id]);
	        			$meta_ongkir['shipping_city'] = $r_cities[$city_shipping_id];
				    }
	        	}
	        }
    	}

		// save billing district
		if (isset($_POST['billing_district']) && ! empty($_POST['billing_district'])) {
			$coba = $this->model->getDistrict($city_billing_id);
		    $district = '';
		    foreach ($coba as $key => $value) {
		    	if($value->id == intval($_POST['billing_district'])) {
		    		$district = $value->nama;
		    		break;
		    	}
		    }
        	$meta_ongkir['billing_district'] = $district;
    	}
    	
    	// save shipping district
    	if (isset($_POST['shipping_district']) && ! empty($_POST['shipping_district'])) {
    		$coba = $this->model->getDistrict($city_shipping_id);
		    $district = '';

		    foreach ($coba as $key => $value) {
		    	if($value->id == intval($_POST['shipping_district'])) {
		    		$district = $value->nama;
		    		break;
		    	}
		    }
        	$meta_ongkir['shipping_district'] = $district;
    	}

    	// no shipping address
    	$shipping_adress = isset($_POST['ship_to_different_address']) ? $_POST['ship_to_different_address'] : 0;
    	if($shipping_adress != 1) {
    		update_post_meta($order_id, '_shipping_city', $meta_ongkir['billing_city']);
    		update_post_meta($order_id, '_shipping_state', intval($_POST['billing_state']));
    	}

    	update_post_meta($order_id, 'meta_woo_ongkir', $meta_ongkir);
	}

	public function custom_override_checkout_fields( $fields) {
	    unset($fields['shipping']['shipping_state']);
	    unset($fields['billing']['billing_state']);
	    return $fields;
	}
	
	public function custom_woocommerce_states( $states ) {
		global $nusantara_core;
		$provinces 	= $nusantara_core->getProvince();
		if(@$provinces == '')
			return $states;

		$r_prov 	= array();
		$r_prov[] 	= __('Select Province', NUSANTARA_ONGKIR);
		foreach($provinces AS $prov) {
			$r_prov[$prov->id] = $prov->nama;
		}
	  	$states['ID'] = $r_prov;
	 
	  	return $states;
	}

	public function ongkir_extra_fields() {
		if (get_option('nusantara_shipping_cost_by_kg') !== "yes") return false;

		global $woocommerce;

		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
	        $weight = 0;
	        $default_weight = get_option('nusantara_default_berat_shipping', 1);
		    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
		        $product = $values['data'];
		        $weight += (($product->has_weight() ? $product->get_weight() : $default_weight) * $values['quantity']);
		    }
		}
		?>

	    <tr>
			<td class="product-name">
			<?php echo __('Total Shipping Weight', NUSANTARA_ONGKIR)?>
			</td>
			<td class="product-total">
			<?php 
			$weight = $this->weightConvert($weight);
			if (get_option('nusantara_base_api') == 'rajaongkir') {
				echo $weight;
			} else {
				echo $this->roundWeight($weight) < 1 ? 1 : $this->roundWeight($weight);
			}
			?>
			Kg
			</td>
		</tr>
		<?php
	}

	public static function roundWeight($weight) {
		$method = get_option('nusantara_round_shipping_weight', 'auto');
		if ($method == 'ceil') {
			return ceil($weight);
		} else if ($method == 'floor') {
			return floor($weight);
		} else {
			$tolerance = get_option('nusantara_round_shipping_weight_tolerance', 500)/1000;
			$fraction = fmod($weight, 1);
			if ($fraction <= $tolerance) {
				return floor($weight);
			} else {
				return ceil($weight);
			}
		}
	}

	public static function weightConvert($weight,$unit = 'kg') {
		$wooWeightUnit = strtolower($current_unit = get_option('woocommerce_weight_unit'));
		$unit = strtolower($unit);
		if ($wooWeightUnit !== $unit) {
			switch ($wooWeightUnit) {
				case 'g':
					$weight *= 0.001;
					break;
				case 'lbs':
					$weight *= 0.4535;
					break;
				case 'oz';
					$weight *= 0.0283495;
					break;
			}
		}
		return $weight;
	}

	public function rajaongkirCountryName($country) {
		//TODO: for future development, consider using Rajaongkir's countries data rather than Woocommerce's.
		$countries = array(
			'China'			=> 'China (people_s rep)',
			'Iran'			=> 'Iran (Islamic rep)',
			'Korea'			=> 'Korea (rep)',
			'Laos'			=> 'Laos People_s Dem Rep',
			'United States (US)' => 'United States of America',
			'Hong Kong'		=> 'Hongkong'
			// ...
		);
		if (!empty($countries[$country])) return $countries[$country];
		return $country;
	}
}
