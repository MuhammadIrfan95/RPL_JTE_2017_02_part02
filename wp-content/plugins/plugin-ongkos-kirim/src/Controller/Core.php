<?php
namespace PluginOngkosKirim\Controller;

use \PluginOngkosKirim\Model\API as PluginOngkosKirimAPI;
use \PluginOngkosKirim\Helpers\Helpers as Helpers;

class Core {
	// protected $config;
	protected $api;
	protected $request_trans;
	protected $key_prefix;

	public function __construct() {
		$this->api = new PluginOngkosKirimAPI();
		$this->request_trans = get_option('nusantara_transient_request_interval', 72);
		$this->key_prefix = "pok_data_";
		$this->enable_cache = !NUSANTARA_ONGKIR_DEBUG; // for debugging purpose
	}

	private function isCacheExists($key) {
		if ($this->enable_cache) {
			$data = get_option($this->key_prefix.sanitize_title_for_query($key), false);
			if ($data && !empty($data)) {
				if (false !== get_transient($this->key_prefix.sanitize_title_for_query($key))) {
					return true;
				}
			} else {
				delete_transient($this->key_prefix.sanitize_title_for_query($key));
			}
		}
		return false;
	}

	private function cacheIt($key, $new_value = null, $expiration = null) {
		if (is_null($expiration)) $expiration = 60*60*$this->request_trans;
		if (!empty($new_value)) {
			if ($this->enable_cache) {
				update_option($this->key_prefix.sanitize_title_for_query($key), $new_value, 'no');
				set_transient($this->key_prefix.sanitize_title_for_query($key), true, $expiration); // we store data with option, so no need to set value on transient
			}
			$return = $new_value;
		} else {
			$return = get_option($this->key_prefix.sanitize_title_for_query($key), false);
		}
		return $return;
	}

	public function purgeCache($key_type = "") {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '{$this->key_prefix}{$key_type}%'" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '_transient_{$this->key_prefix}{$key_type}%'" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '_transient_timeout_{$this->key_prefix}{$key_type}%'" );
	}

	public function deleteCache($key) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '".$this->key_prefix.sanitize_title_for_query($key)."'" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '_transient_".$this->key_prefix.sanitize_title_for_query($key)."'" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE '_transient_timeout_".$this->key_prefix.sanitize_title_for_query($key)."'" );
	}

	public function getCourier() {
		if (!$this->isCacheExists('courier')) {
			$result = $this->api->getCourier();
		}
		return $this->cacheIt('courier', @$result, 60*60*24*7);
	}

	public function getCourierPackage() {
		if (!$this->isCacheExists('courier_package')) {
			$result = $this->api->getCourierPackage();
		}
		return $this->cacheIt('courier_package', @$result, 60*60*24*7);
	}

	public function getProvince() {
		if (!$this->isCacheExists('province')) {
			$result = $this->api->getProvince();
		}
		return $this->cacheIt('province', @$result, 60*60*24*7);
	}

	public function getCity($province_id = 0) {
		if (!$this->isCacheExists('city_'.$province_id)) {
			$result = $this->api->getCity($province_id);
		}
		return $this->cacheIt('city_'.$province_id, @$result, 60*60*24*7);
	}

	public function getSingleCity($city_id = 0) {
		if (!$this->isCacheExists('city_single_'.$city_id)) {
			$result = $this->api->getSingleCity($city_id);
		}
		return $this->cacheIt('city_single_'.$city_id, @$result, 60*60*24*7);
	}

	public function getAllCity() {
		if (!$this->isCacheExists('all_city')) {
			$result = $this->api->getAllCity();
		}
		return $this->cacheIt('all_city', @$result, 60*60*24*7);
	}

	public function searchCity($search = "") {
		if (!$this->isCacheExists('search_city_'.$search)) {
			$result = $this->api->searchCity($search);
		}
		return $this->cacheIt('search_city_'.$search, @$result, 60*60*24*7);
	}

	public function getDistrict($city_id = 0) {
		if (!$this->isCacheExists('district_'.$city_id)) {
			$result = $this->api->getDistrict($city_id);
		}
		return $this->cacheIt('district_'.$city_id, @$result, 60*60*24*7);
	}

	public function getAllCountry() {
		if (!$this->isCacheExists('country')) {
			$result = $this->api->getAllCountry();
		}
		return $this->cacheIt('country', @$result, 60*60*24*7);
	}

	public function getCost($destination = 0, $weight) {
		$courier = get_option('nusantara_courir_type',array());
		$origin = get_option('nusantara_store_location',array());
		if ($weight < 1) $weight = 1;
		if (get_option('nusantara_base_api','nusantara') == 'nusantara') $weight = Helpers::roundWeight($weight); // pembulatan
		$weight = $weight*1000;
		if ($origin === false || empty($origin) || empty($origin[0])) return false;
		if (!$this->isCacheExists('cost_'.$destination.'_'.$weight)) {
			$result = $this->api->getCost($origin[0], $destination, $weight, $courier);
		}
		return $this->cacheIt('cost_'.$destination.'_'.$weight, @$result);
	}

	public function getCostInternational($destination, $weight) {
		$courier = get_option('nusantara_courir_type',array());
		$origin = get_option('nusantara_store_location',array());
		$weight = $weight*1000;
		if ($origin === false || empty($origin) || empty($origin[0])) return false;
		if (!$this->isCacheExists('cost_international_'.$destination.'_'.$weight)) {
			$result = $this->api->getCostInternational($origin[0], $destination, $weight, $courier);
		}
		return $this->cacheIt('cost_international_'.$destination.'_'.$weight, @$result);
	}

	public function getCustomCost($destination, $destination_type, $weight) {
		$data = get_option('nusantara_manual_shipping', array());
		$custom_courier = get_option('nusantara_custom_courier', 'Custom');
		$weight = Helpers::roundWeight($weight);
		$costs = array();
		if (!empty($data)) {
			foreach ($data as $d) {
				$found = false;
				if ($d['manualselectprovince'] == '*') {
					$found = true;
				} else if ($d['manualselectprovince'] == $destination['state']) {
					if ($d['manualselectcity'] == '*') {
						$found = true;
					} else if ($d['manualselectcity'] == $destination['city']) {
						if ($destination_type == 'city') {
							$found = true;
						} else if ($destination_type == 'district') {
							if ($d['manualselectdistrict'] == '*' || $d['manualselectdistrict'] == $destination['district']) {
								$found = true;
							}
						}
					}
				}
				if ($found) {
					$courier = $d['ekspedisi'] == 'custom' ? $custom_courier : $d['ekspedisi'];
					if ((empty($d['nunsatara_jenis']) || $d['nunsatara_jenis'] == '-') && ($courier == "")) {
						$class = __("Custom Shipping", NUSANTARA_ONGKIR);
					} else {
						if (empty($d['nunsatara_jenis']) || $d['nunsatara_jenis'] == '-') {
							$class = strtoupper($courier);
						} else if ($courier == "") {
							$class = $d['nunsatara_jenis'];
						} else {
							$class = strtoupper($courier)." - ".$d['nunsatara_jenis'];
						}
					}
					$costs[] = array(
						"class"			=> $class,
						"courier"		=> strtolower($courier),
						"description"	=> 'custom',
						"cost"			=> $d['nusantara_tarif'] * $weight,
						"time"			=> "-",
						"source"		=> 'custom'
					);
				}
			}
		}
		return $costs;
	}

	public function getCurrencyRates() {
		if (!$this->isCacheExists('currency')) {
			$result = $this->api->currency();
		}
		return $this->cacheIt('currency', @$result);
	}

	public function currencyConvert($price = 0, $symbol = '') {
		$rates = $this->getCurrencyRates();
		if (empty($symbol)) $symbol = get_option('woocommerce_currency');
		if($symbol != 'IDR' && !empty($rates["data"]) && !empty($rates["data"]->rates)) {
			$conv = (array) $rates["data"]->rates;
			return $price * (float)$conv[$symbol];
		}
		return $price;
	}

	public function registerLicense($license_key) {
		$license_checker 	= new \tonjoo_license();
		$args 				= array(
			'key'			=> $license_key,
			'plugin_name'	=> 'wooongkir-premium'
		);
		$server_response = $license_checker->RegisterKey($args);
		return $server_response;
	}

	public function unregisterLicense() {
		$license_key = get_option('nusantara_ongkir_lisensi');
		$license_checker 	= new \tonjoo_license();
		$args 				= array(
			'key'			=> $license_key,
			'plugin_name'	=> 'wooongkir-premium'
		);
		$server_response = $license_checker->unRegisterKey($args);
		return $server_response;
	}

	public function getLicenseStatus($license_key) {
		$license_checker 	= new \tonjoo_license();
		$args 				= array(
			'key'			=> $license_key,
			'plugin_name'	=> 'wooongkir-premium'
		);
		$server_response = $license_checker->getStatus($args);
		return $server_response;
	}

	public function getRajaongkirStatus($api_key, $api_type) {
		return $this->api->getRajaongkirStatus($api_key, $api_type);
	}
}