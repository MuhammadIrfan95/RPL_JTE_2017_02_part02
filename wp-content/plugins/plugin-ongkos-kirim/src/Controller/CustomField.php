<?php
namespace PluginOngkosKirim\Controller;

class CustomField {
	protected $type;
	protected $model;

	function __construct() {
		global $nusantara_core;
		global $nusantara_helper;
		$this->model	= $nusantara_core;
		$this->helper 	= $nusantara_helper;
		$this->type 	= get_option('nusantara_raja_ongkir_type');
		
		if($this->helper->is_plugin_active()) {
			add_filter('woocommerce_checkout_fields', array($this,'pok_checkout_fields'));
			add_filter('woocommerce_billing_fields', array($this,'pok_billing_fields'));
			add_filter('woocommerce_shipping_fields', array($this,'pok_shipping_fields'));
			add_filter('woocommerce_get_country_locale', array($this, 'pok_country_locale'));
			add_action('wp_enqueue_scripts', array($this,'pok_load_checkout_scripts'));
			add_filter('woocommerce_my_account_my_address_formatted_address', array($this, 'pok_format_myaccount_address'), 10, 3);
		}
	}

	public function get_checkout_post_data($itemdata) {
		$postdata = explode('&',@$_POST['post_data']);
		$post_data_ret = '';
		foreach ($postdata as $value) {
			if (strpos($value,$itemdata) !== FALSE) {
		        $post_data_ret = $value;
		        $ar = explode('=',$post_data_ret);
		        $post_data_ret = $ar[1];
		        break;
			}
        }
		$post_data_ret = str_replace('+',' ',$post_data_ret);
		return $post_data_ret;
	}

	public function pok_checkout_fields( $fields ) {
		global $nusantaracommerce;
		if(@$_SESSION['_nusantara_ongkir_session_country'] != 'ID') {
			return $fields;	
		}

		$r_province = $this->model->getProvince();
		$r_prov 	= array();
		$r_prov[] 	= __('Select Province', NUSANTARA_ONGKIR);
		
		if (!empty($r_province)) {
			foreach($r_province AS $prov) {
				$r_prov[$prov->id] = $prov->nama;
			}
		} else {
			wc_add_notice( __( 'Failed to load data. Please refresh the page.', NUSANTARA_ONGKIR ), 'error' );
		}
	
		$base_location_ids 			= get_option('nusantara_store_location',array());

		if(empty($base_location_ids)) {
	        wc_add_notice( __( 'Please fill the origin city in plugin ongkos kirim configuration page', NUSANTARA_ONGKIR ), 'error' );
		}

		$field_order = apply_filters('pok_fields_order', array(
			'first_name'	=> 10,
			'last_name'		=> 20,
			'country'		=> 30,
			'state'			=> 40,
			'city'			=> 50,
			'district'		=> 60,
			'address'		=> 70,
			'postcode'		=> 80,
			'phone'			=> 90,
			'email'			=> 100
		));

		$billing_first_name_tmp 	= isset($fields['billing']['billing_first_name']) ? $fields['billing']['billing_first_name'] : array() ;
		$billing_last_name_tmp 		= isset($fields['billing']['billing_last_name']) ? $fields['billing']['billing_last_name'] : array() ;
		$shipping_first_name_tmp 	= isset($fields['shipping']['shipping_first_name']) ? $fields['shipping']['shipping_first_name'] : array() ;
		$shipping_last_name_tmp 	= isset($fields['shipping']['shipping_last_name']) ? $fields['shipping']['shipping_last_name'] : array() ;
		$billing_state_tmp 			= isset($fields['billing']['billing_state_s']) ? $fields['billing']['billing_state_s'] : array() ;
		$shipping_state_tmp 		= isset($fields['shipping']['shipping_state_s']) ? $fields['shipping']['shipping_state_s'] : array() ;
		$billing_address_1_tmp 		= isset($fields['billing']['billing_address_1']) ? $fields['billing']['billing_address_1'] : array() ;
		$shipping_address_1_tmp 	= isset($fields['shipping']['shipping_address_1']) ? $fields['shipping']['shipping_address_1'] : array() ;
		$billing_city_tmp 			= isset($fields['billing']['billing_city']) ? $fields['billing']['billing_city'] : array() ;
		$shipping_city_tmp 			= isset($fields['shipping']['shipping_city']) ? $fields['shipping']['shipping_city'] : array() ;
		$billing_address_2_tmp 		= isset($fields['billing']['billing_address_2']) ? $fields['billing']['billing_address_2'] : array() ;
		$shipping_address_2_tmp 	= isset($fields['shipping']['shipping_address_2']) ? $fields['shipping']['shipping_address_2'] : array() ;
		$billing_postcode_tmp 		= isset($fields['billing']['billing_postcode']) ? $fields['billing']['billing_postcode'] : array() ;
		$shipping_postcode_tmp 		= isset($fields['shipping']['shipping_postcode']) ? $fields['shipping']['shipping_postcode'] : array() ;
		$billing_phone_tmp 			= isset($fields['billing']['billing_phone']) ? $fields['billing']['billing_phone'] : array() ;
		$billing_email_tmp 			= isset($fields['billing']['billing_email']) ? $fields['billing']['billing_email'] : array() ;
		$shipping_country_tmp 		= isset($fields['shipping']['shipping_country']) ? $fields['shipping']['shipping_country'] : array() ;
		$billing_country_tmp 		= isset($fields['billing']['billing_country']) ? $fields['billing']['billing_country'] : array() ;
		$shipping_vendor_tmp 		= isset($fields['shipping']['shipping_vendor']) ? $fields['shipping']['shipping_vendor'] : array() ;
		$billing_vendor_tmp 		= isset($fields['billing']['billing_vendor']) ? $fields['billing']['billing_vendor'] : array() ;
		$order_base_city_tmp 		= isset($fields['order']['order_base_city']) ? $fields['order']['order_base_city'] : array() ;
		$billing_district_tmp 		= isset($fields['billing']['billing_district']) ? $fields['billing']['billing_district'] : array() ;
		$shipping_district_tmp 		= isset($fields['shipping']['shipping_district']) ? $fields['shipping']['shipping_district'] : array() ;

		unset($fields['billing']);
		unset($fields['shipping']);

		$fields['billing']['billing_first_name'] 				= $billing_first_name_tmp;
		$fields['billing']['billing_first_name']['label']		= __('First Name', NUSANTARA_ONGKIR);
		$fields['billing']['billing_last_name'] 				= $billing_last_name_tmp;
		$fields['billing']['billing_last_name']['label']		= __('Last Name', NUSANTARA_ONGKIR);
		$fields['billing']['billing_last_name']['priority']		= $field_order['first_name'];

		$fields['shipping']['shipping_first_name'] 				= $shipping_first_name_tmp;
		$fields['shipping']['shipping_first_name']['label']		= __('First Name', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_last_name'] 				= $shipping_last_name_tmp;
		$fields['shipping']['shipping_last_name']['label']		= __('Last Name', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_last_name']['priority']	= $field_order['last_name'];

		$fields['billing']['billing_address_1'] 				= $billing_address_1_tmp;
		$fields['billing']['billing_address_1']['label'] 		= __('Address', NUSANTARA_ONGKIR);
		$fields['billing']['billing_address_1']['placeholder'] 	= '';
		$fields['billing']['billing_address_1']['priority'] 	= $field_order['address'];

		$fields['shipping']['shipping_address_1'] 				= $shipping_address_1_tmp;
		$fields['shipping']['shipping_address_1']['label'] 		= __('Address', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_address_1']['priority'] 	= $field_order['address'];

		$fields['billing']['billing_postcode'] 					= $billing_postcode_tmp;
		$fields['billing']['billing_postcode']['label'] 		= __('Postcode / ZIP', NUSANTARA_ONGKIR);
		$fields['billing']['billing_postcode']['required'] 		= false;
		$fields['billing']['billing_postcode']['priority'] 		= $field_order['postcode'];

		$fields['shipping']['shipping_postcode'] 				= $shipping_postcode_tmp;
		$fields['shipping']['shipping_postcode']['label'] 		= __('Postcode / ZIP', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_postcode']['required'] 	= false;
		$fields['shipping']['shipping_postcode']['priority'] 	= $field_order['postcode'];

		$fields['billing']['billing_email'] 					= $billing_email_tmp;
		$fields['billing']['billing_email']['label'] 			= __('Email Address', NUSANTARA_ONGKIR);
		$fields['billing']['billing_email']['priority'] 		= $field_order['email'];
		$fields['shipping']['shipping_email'] 					= $billing_email_tmp;
		$fields['shipping']['shipping_email']['label'] 			= __('Email Address', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_email']['priority'] 		= $field_order['email'];

		$fields['billing']['billing_phone'] 					= $billing_phone_tmp;
		$fields['billing']['billing_phone']['label']			= __('Phone', NUSANTARA_ONGKIR);
		$fields['billing']['billing_phone']['priority']			= $field_order['phone'];
		$fields['shipping']['shipping_phone'] 					= $billing_phone_tmp;
		$fields['shipping']['shipping_phone']['label']			= __('Phone', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_phone']['priority']		= $field_order['phone'];

		$fields['billing']['billing_country'] 					= $billing_country_tmp;
		$fields['billing']['billing_country']['label']			= __('Country', NUSANTARA_ONGKIR);
		$fields['billing']['billing_country']['priority']		= $field_order['country'];
		$fields['shipping']['shipping_country']					= $shipping_country_tmp;
		$fields['shipping']['shipping_country']['label']		= __('Country', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_country']['priority']		= $field_order['country'];
		
		$fields['billing']['billing_state'] 					= $billing_state_tmp;
		$fields['billing']['billing_state']['label'] 			= __('Province', NUSANTARA_ONGKIR);
		$fields['billing']['billing_state']['placeholder']		= __('Select Province', NUSANTARA_ONGKIR);
		$fields['billing']['billing_state']['options'] 			= $r_prov;
		$fields['billing']['billing_state']['type'] 			= 'select';
		$fields['billing']['billing_state']['class'] 			= isset($fields['billing']['billing_state']['class']) ? array_merge($fields['billing']['billing_state']['class'],array('validate-required')) : array('validate-required');
		$fields['billing']['billing_state']['priority']			= $field_order['state'];

		$fields['shipping']['shipping_state'] 					= $shipping_state_tmp;
		$fields['shipping']['shipping_state']['label'] 			= __('Province', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_state']['placeholder']	= __('Select Province', NUSANTARA_ONGKIR);
		$fields['shipping']['shipping_state']['type'] 			= 'select';
		$fields['shipping']['shipping_state']['options'] 		= $r_prov;
		$fields['shipping']['shipping_state']['class'] 			= isset($fields['shipping']['shipping_state']['class']) ? array_merge($fields['shipping']['shipping_state']['class'],array('validate-required')) : array('validate-required');
		$fields['shipping']['shipping_state']['priority']		= $field_order['state'];
		
		if($this->type == 'pro' || get_option('nusantara_base_api') == 'nusantara' ) {
			$fields['billing']['billing_city'] 						= $billing_city_tmp;
			$fields['billing']['billing_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['billing']['billing_city']['placeholder']		= __('Select City', NUSANTARA_ONGKIR);
			$fields['billing']['billing_city']['type'] 				= 'select';
			$fields['billing']['billing_city']['required'] 			= true;
			$fields['billing']['billing_city']['options'] 			= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['billing']['billing_city']['class'] 			= is_array($fields['billing']['billing_city']['class']) ? array_merge($fields['billing']['billing_city']['class'],array('validate-required')) : array('validate-required');
			$fields['billing']['billing_city']['priority'] 			= $field_order['city'];

			$fields['shipping']['shipping_city'] 					= $shipping_city_tmp;
			$fields['shipping']['shipping_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['shipping']['shipping_city']['placeholder'] 	= __('Select City', NUSANTARA_ONGKIR);
			$fields['shipping']['shipping_city']['type'] 			= 'select';
			$fields['shipping']['shipping_city']['required'] 		= true;
			$fields['shipping']['shipping_city']['options'] 		= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['shipping']['shipping_city']['class'] 			= isset($fields['shipping']['shipping_city']['class']) ? array_merge($fields['shipping']['shipping_city']['class'],array('validate-required')) : array('validate-required');
			$fields['shipping']['shipping_city']['priority']		= $field_order['city'];

			$fields['billing']['billing_district'] 					= $billing_district_tmp;
			$fields['billing']['billing_district']['label'] 		= __('District', NUSANTARA_ONGKIR);
			$fields['billing']['billing_district']['placeholder']	= __('Select District', NUSANTARA_ONGKIR);
			$fields['billing']['billing_district']['type'] 			= 'select';
			$fields['billing']['billing_district']['required'] 		= true;
			$fields['billing']['billing_district']['options'] 		= array(''=>__('Select District', NUSANTARA_ONGKIR));
			$fields['billing']['billing_district']['class'] 		= isset($fields['billing']['billing_district']['class']) ? array_merge($fields['billing']['billing_district']['class'],array('validate-required')) : array('validate-required');
			$fields['billing']['billing_district']['priority'] 		= $field_order['district'];

			$fields['shipping']['shipping_district'] 				= $shipping_district_tmp;
			$fields['shipping']['shipping_district']['label'] 		= __('District', NUSANTARA_ONGKIR);
			$fields['shipping']['shipping_district']['placeholder'] = __('Select District', NUSANTARA_ONGKIR);
			$fields['shipping']['shipping_district']['type'] 		= 'select';
			$fields['shipping']['shipping_district']['required'] 	= true;
			$fields['shipping']['shipping_district']['options'] 	= array(''=>__('Select District', NUSANTARA_ONGKIR));
			$fields['shipping']['shipping_district']['class'] 		= isset($fields['shipping']['shipping_district']['class']) ? array_merge($fields['shipping']['shipping_district']['class'],array('validate-required')) : array('validate-required');
			$fields['shipping']['shipping_district']['priority']	= $field_order['district'];
		} else {
			$fields['billing']['billing_city'] 						= $billing_city_tmp;
			$fields['billing']['billing_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['billing']['billing_city']['placeholder']		= __('Select City', NUSANTARA_ONGKIR);
			$fields['billing']['billing_city']['type'] 				= 'select';
			$fields['billing']['billing_city']['options'] 			= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['billing']['billing_district']['class'] 		= isset($fields['billing']['billing_district']['class']) ? array_merge($fields['billing']['billing_district']['class'],array('validate-required')) : array('validate-required');
			$fields['billing']['billing_city']['priority'] 			= $field_order['city'];

			$fields['shipping']['shipping_city'] 					= $shipping_city_tmp;
			$fields['shipping']['shipping_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['shipping']['shipping_city']['placeholder'] 	= __('Select City', NUSANTARA_ONGKIR);
			$fields['shipping']['shipping_city']['type'] 			= 'select';
			$fields['shipping']['shipping_city']['options'] 		= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['shipping']['shipping_district']['class'] 		= isset($fields['shipping']['shipping_district']['class']) ? array_merge($fields['shipping']['shipping_district']['class'],array('validate-required')) : array('validate-required');
			$fields['shipping']['shipping_city']['priority'] 		= $field_order['city'];
		}

		// multi store location is deprecated
		// $fields['order']['order_base_city'] 					= $order_base_city_tmp;
		// $fields['order']['order_base_city']['label'] 			= __('Store Location');
		// $fields['order']['order_base_city']['placeholder'] 		= __('Select City');
		// $fields['order']['order_base_city']['type'] 			= 'select';
		// $fields['order']['order_base_city']['options'] 			= $r_cit;
		// $fields['order']['order_base_city']['class'] 			= array('update_totals_on_change');

		return $fields;
	}

	public function pok_billing_fields( $fields ) {
		if (!is_account_page()) return $fields;

		if(@$_SESSION['_nusantara_ongkir_session_country'] != 'ID') {
			return $fields;	
		}

		$r_province = $this->model->getProvince();
		$r_prov 	= array();
		$r_prov[] 	= __('Select Province', NUSANTARA_ONGKIR);
		
		if (!empty($r_province)) {
			foreach($r_province AS $prov) {
				$r_prov[$prov->id] = $prov->nama;
			}
		} else {
			wc_add_notice( __( 'Failed to load data. Please refresh the page.', NUSANTARA_ONGKIR ), 'error' );
		}

		$billing_first_name_tmp 	= isset($fields['billing_first_name']) ? $fields['billing_first_name'] : array() ;
		$billing_last_name_tmp 		= isset($fields['billing_last_name']) ? $fields['billing_last_name'] : array() ;
		$billing_state_tmp 			= isset($fields['billing_state_s']) ? $fields['billing_state_s'] : array() ;
		$billing_address_1_tmp 		= isset($fields['billing_address_1']) ? $fields['billing_address_1'] : array() ;
		$billing_city_tmp 			= isset($fields['billing_city']) ? $fields['billing_city'] : array() ;
		$billing_address_2_tmp 		= isset($fields['billing_address_2']) ? $fields['billing_address_2'] : array() ;
		$billing_postcode_tmp 		= isset($fields['billing_postcode']) ? $fields['billing_postcode'] : array() ;
		$billing_phone_tmp 			= isset($fields['billing_phone']) ? $fields['billing_phone'] : array() ;
		$billing_email_tmp 			= isset($fields['billing_email']) ? $fields['billing_email'] : array() ;
		$billing_country_tmp 		= isset($fields['billing_country']) ? $fields['billing_country'] : array() ;
		$billing_vendor_tmp 		= isset($fields['billing_vendor']) ? $fields['billing_vendor'] : array() ;
		$billing_district_tmp 		= isset($fields['billing_district']) ? $fields['billing_district'] : array() ;

		$fields = array();

		$fields['billing_first_name'] 				= $billing_first_name_tmp;
		$fields['billing_first_name']['label']		= __('First Name', NUSANTARA_ONGKIR);
		$fields['billing_last_name'] 				= $billing_last_name_tmp;
		$fields['billing_last_name']['label']		= __('Last Name', NUSANTARA_ONGKIR);

		$fields['billing_address_1'] 				= $billing_address_1_tmp;
		$fields['billing_address_1']['label'] 		= __('Address', NUSANTARA_ONGKIR);

		$fields['billing_postcode'] 				= $billing_postcode_tmp;
		$fields['billing_postcode']['label'] 		= __('Postcode / ZIP', NUSANTARA_ONGKIR);
		$fields['billing_postcode']['required'] 	= false;
		$fields['billing_postcode']['class'] 		= array();

		$fields['billing_email'] 					= $billing_email_tmp;
		$fields['billing_email']['label'] 			= __('Email Address', NUSANTARA_ONGKIR);

		$fields['billing_phone'] 					= $billing_phone_tmp;
		$fields['billing_phone']['label']			= __('Phone', NUSANTARA_ONGKIR);

		$fields['billing_country'] 					= $billing_country_tmp;
		$fields['billing_country']['label']			= __('Country', NUSANTARA_ONGKIR);
		$fields['billing_country']['class'] 		= array();
		$fields['billing_country']['priority'] 		= 120;
		
		$fields['billing_state'] 					= $billing_state_tmp;
		$fields['billing_state']['label'] 			= __('Province', NUSANTARA_ONGKIR);
		$fields['billing_state']['placeholder']		= __('Select Province', NUSANTARA_ONGKIR);
		$fields['billing_state']['options'] 		= $r_prov;
		$fields['billing_state']['type'] 			= 'select';
		$fields['billing_state']['class'] 			= array();
		$fields['billing_state']['priority'] 			= 130;
		
		if($this->type == 'pro' || get_option('nusantara_base_api') == 'nusantara' ) {
			$fields['billing_city'] 					= $billing_city_tmp;
			$fields['billing_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['billing_city']['placeholder']		= __('Select City', NUSANTARA_ONGKIR);
			$fields['billing_city']['type'] 			= 'select';
			$fields['billing_city']['required'] 		= true;
			$fields['billing_city']['options'] 			= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['billing_city']['class'] 			= array();
			$fields['billing_city']['priority']			= 140;

			$fields['billing_district'] 				= $billing_district_tmp;
			$fields['billing_district']['label'] 		= __('District', NUSANTARA_ONGKIR);
			$fields['billing_district']['placeholder']	= __('Select District', NUSANTARA_ONGKIR);
			$fields['billing_district']['type'] 		= 'select';
			$fields['billing_district']['required'] 	= true;
			$fields['billing_district']['options'] 		= array(''=>__('Select District', NUSANTARA_ONGKIR));
			$fields['billing_district']['class'] 		= array();
			$fields['billing_district']['priority'] 	= 150;
		} else {
			$fields['billing_city'] 					= $billing_city_tmp;
			$fields['billing_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['billing_city']['placeholder']		= __('Select City', NUSANTARA_ONGKIR);
			$fields['billing_city']['type'] 			= 'select';
			$fields['billing_city']['options'] 			= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['billing_city']['class'] 			= array();
			$fields['billing_city']['priority'] 		= 140;
		}

		return $fields;
	}

	public function pok_shipping_fields( $fields ) {
		if (!is_account_page()) return $fields;

		if(@$_SESSION['_nusantara_ongkir_session_country'] != 'ID') {
			return $fields;	
		}

		$r_province = $this->model->getProvince();
		$r_prov 	= array();
		$r_prov[] 	= __('Select Province', NUSANTARA_ONGKIR);
		
		if (!empty($r_province)) {
			foreach($r_province AS $prov) {
				$r_prov[$prov->id] = $prov->nama;
			}
		} else {
			wc_add_notice( __( 'Failed to load data. Please refresh the page.', NUSANTARA_ONGKIR ), 'error' );
		}

		$shipping_first_name_tmp 	= isset($fields['shipping_first_name']) ? $fields['shipping_first_name'] : array();
		$shipping_last_name_tmp 	= isset($fields['shipping_last_name']) ? $fields['shipping_last_name'] : array();
		$shipping_state_tmp 		= isset($fields['shipping_state_s']) ? $fields['shipping_state_s'] : array();
		$shipping_address_1_tmp 	= isset($fields['shipping_address_1']) ? $fields['shipping_address_1'] : array();
		$shipping_city_tmp 			= isset($fields['shipping_city']) ? $fields['shipping_city'] : array();
		$shipping_address_2_tmp 	= isset($fields['shipping_address_2']) ? $fields['shipping_address_2'] : array();
		$shipping_postcode_tmp 		= isset($fields['shipping_postcode']) ? $fields['shipping_postcode'] : array();
		$shipping_phone_tmp 		= isset($fields['shipping_phone']) ? $fields['shipping_phone'] : array();
		$shipping_email_tmp 		= isset($fields['shipping_email']) ? $fields['shipping_email'] : array();
		$shipping_country_tmp 		= isset($fields['shipping_country']) ? $fields['shipping_country'] : array();
		$shipping_vendor_tmp 		= isset($fields['shipping_vendor']) ? $fields['shipping_vendor'] : array();
		$shipping_district_tmp 		= isset($fields['shipping_district']) ? $fields['shipping_district'] : array();

		$fields = array();

		$fields['shipping_first_name'] 				= $shipping_first_name_tmp;
		$fields['shipping_first_name']['label']		= __('First Name', NUSANTARA_ONGKIR);
		$fields['shipping_last_name'] 				= $shipping_last_name_tmp;
		$fields['shipping_last_name']['label']		= __('Last Name', NUSANTARA_ONGKIR);

		$fields['shipping_address_1'] 				= $shipping_address_1_tmp;
		$fields['shipping_address_1']['label'] 		= __('Address', NUSANTARA_ONGKIR);

		$fields['shipping_postcode'] 				= $shipping_postcode_tmp;
		$fields['shipping_postcode']['label'] 		= __('Postcode / ZIP', NUSANTARA_ONGKIR);
		$fields['shipping_postcode']['required'] 	= false;
		$fields['shipping_postcode']['class'] 		= array();

		$fields['shipping_email'] 					= $shipping_email_tmp;
		$fields['shipping_email']['label'] 			= __('Email Address', NUSANTARA_ONGKIR);

		$fields['shipping_phone'] 					= $shipping_phone_tmp;
		$fields['shipping_phone']['label']			= __('Phone', NUSANTARA_ONGKIR);

		$fields['shipping_country'] 				= $shipping_country_tmp;
		$fields['shipping_country']['label']		= __('Country', NUSANTARA_ONGKIR);
		$fields['shipping_country']['class'] 		= array();
		$fields['shipping_country']['priority']		= 120;
		
		$fields['shipping_state'] 					= $shipping_state_tmp;
		$fields['shipping_state']['label'] 			= __('Province', NUSANTARA_ONGKIR);
		$fields['shipping_state']['placeholder']	= __('Select Province', NUSANTARA_ONGKIR);
		$fields['shipping_state']['options'] 		= $r_prov;
		$fields['shipping_state']['type'] 			= 'select';
		$fields['shipping_state']['class'] 			= array();
		$fields['shipping_state']['priority'] 		= 130;
		
		if($this->type == 'pro' || get_option('nusantara_base_api') == 'nusantara' ) {
			$fields['shipping_city'] 					= $shipping_city_tmp;
			$fields['shipping_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['shipping_city']['placeholder']		= __('Select City', NUSANTARA_ONGKIR);
			$fields['shipping_city']['type'] 			= 'select';
			$fields['shipping_city']['required'] 		= true;
			$fields['shipping_city']['options'] 		= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['shipping_city']['class'] 			= array();
			$fields['shipping_city']['priority'] 		= 140;

			$fields['shipping_district'] 				= $shipping_district_tmp;
			$fields['shipping_district']['label'] 		= __('District', NUSANTARA_ONGKIR);
			$fields['shipping_district']['placeholder']	= __('Select District', NUSANTARA_ONGKIR);
			$fields['shipping_district']['type'] 		= 'select';
			$fields['shipping_district']['required'] 	= true;
			$fields['shipping_district']['options'] 	= array(''=>__('Select District', NUSANTARA_ONGKIR));
			$fields['shipping_district']['class'] 		= array();
			$fields['shipping_district']['priority'] 	= 150;
		} else {
			$fields['shipping_city'] 					= $shipping_city_tmp;
			$fields['shipping_city']['label'] 			= __('City', NUSANTARA_ONGKIR);
			$fields['shipping_city']['placeholder']		= __('Select City', NUSANTARA_ONGKIR);
			$fields['shipping_city']['type'] 			= 'select';
			$fields['shipping_city']['options'] 		= array(''=>__('Select City', NUSANTARA_ONGKIR));
			$fields['shipping_city']['class'] 			= array();
			$fields['shipping_city']['priority'] 		= 140;
		}

		return $fields;
	}

	public function pok_country_locale($fields) {
		$fields['ID']['state']['label'] = __('Province', NUSANTARA_ONGKIR);
		$fields['ID']['postcode']['label'] = __('Postcode / ZIP', NUSANTARA_ONGKIR);
		$fields['ID']['city']['label'] = __('Town / City', NUSANTARA_ONGKIR);
		return $fields;
	}

	public function pok_load_checkout_scripts() {
		if (in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ))) {
			if (is_checkout() || is_account_page()) {
				$country = isset($_SESSION['_nusantara_ongkir_session_country']) ? $_SESSION['_nusantara_ongkir_session_country'] : 'ID';
				wp_enqueue_script('pok_checkout',plugins_url('../../assets/js/pok_checkout.min.js',__FILE__), array('jquery'), null, true);
				$localize = array(
					'ajaxurl'       		=> admin_url('admin-ajax.php'),
					'nextNonce'     		=> wp_create_nonce('myajax-next-nonce'),
					'labelFailedCity'		=> __('Failed to load city list. Try again?', NUSANTARA_ONGKIR),
					'labelFailedDistrict'	=> __('Failed to load district list. Try again?', NUSANTARA_ONGKIR),
					'labelSelectCity'		=> __('Select City', NUSANTARA_ONGKIR),
					'labelLoadingCity'		=> __('Loading city options...', NUSANTARA_ONGKIR),
					'labelSelectDistrict'	=> __('Select District', NUSANTARA_ONGKIR),
					'labelLoadingDistrict'	=> __('Loading district options...', NUSANTARA_ONGKIR),
					'enableDistrict'		=> false,
					'billing_country'		=> $country,
					'shipping_country'		=> $country,
					'loadReturningUserData' => is_account_page() ? "yes" : get_option('nusantara_save_returned_user_information', "yes"),
					'billing_state'			=> 0,
					'shipping_state'		=> 0,
					'billing_city'			=> 0,
					'shipping_city'			=> 0,
					'billing_district'		=> 0,
					'shipping_district'		=> 0,
				);
				// check if district is displayed
				if (get_option('nusantara_base_api') == 'nusantara' || (get_option('nusantara_base_api') == 'rajaongkir' && $this->type == 'pro')) {
					$localize['enableDistrict'] = true;
				}
				//get returning user data
				if (is_user_logged_in()) {
					$billing_state = get_user_meta(get_current_user_id(), 'billing_state', true);
					if ($billing_state != "") $localize['billing_state'] = $billing_state;
					$shipping_state = get_user_meta(get_current_user_id(), 'shipping_state', true);
					if ($shipping_state != "") $localize['shipping_state'] = $shipping_state;
					$billing_city = get_user_meta(get_current_user_id(), 'billing_city', true);
					if ($billing_city != "") $localize['billing_city'] = $billing_city;
					$shipping_city = get_user_meta(get_current_user_id(), 'shipping_city', true);
					if ($shipping_city != "") $localize['shipping_city'] = $shipping_city;
					$billing_district = get_user_meta(get_current_user_id(), 'billing_district', true);
					if ($billing_district != "") $localize['billing_district'] = $billing_district;
					$shipping_district = get_user_meta(get_current_user_id(), 'shipping_district', true);
					if ($shipping_district != "") $localize['shipping_district'] = $shipping_district;
				}
				wp_localize_script( 'pok_checkout', 'pok_checkout_data', $localize);
			}
		}
	}

	public function pok_format_myaccount_address($address, $customer_id, $name) {
		global $woocommerce;
		//fix country name
		$country = get_user_meta( $customer_id, $name . '_country', true );
		if ($country != "") {
			$address['country'] = WC()->countries->countries[ $country ];
		}
		//fix province name
		$state = get_user_meta( $customer_id, $name . '_state', true );
		if ((int)$state) {
			$provinces = $this->model->getProvince();
			foreach($provinces AS $prov) {
				if ($prov->id == $state) {
					$address['state'] = $prov->nama;
				}
			}
		}
		//fix city name
		$city = get_user_meta( $customer_id, $name . '_city', true );
		$address['district'] = "";
		if ((int)$city) {
			$city_data = $this->model->getSingleCity($city);
			if ($city_data) {
				$address['city'] = $city_data->nama;
				$district = get_user_meta( $customer_id, $name . '_district', true );
				//fix district name
				if ((int)$district) {
					$districts = $this->model->getDistrict($city);
					foreach($districts AS $d) {
						if ($d->id == $district) {
							$address['district'] = $d->nama;
						}
					}
				}
			}
		}
		return $address;
	}

}

