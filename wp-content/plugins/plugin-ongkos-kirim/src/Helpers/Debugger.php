<?php

use \PluginOngkosKirim\Model\API_Nusantara as API_Nusantara;
use \PluginOngkosKirim\Model\API_RajaOngkir as API_RajaOngkir;

class Debugger {

	protected $api;
	protected $nusantara;
	protected $rajaongkir;
	protected $vendor;
	protected $rajaongkir_type;

	function __construct() {
		$this->vendor 			= get_option('nusantara_base_api', 'nusantara');
		$this->rajaongkir_type	= get_option('nusantara_raja_ongkir_type', 'starter');
		$rajaongkir_api_key		= get_option('nusantara_api_key_raja_ongkir', '');
		$license_key 			= get_option('nusantara_ongkir_lisensi', '');
		$license_status			= get_option('nusantara_ongkir_license_status', array(false,""));
		$rajaongkir_status		= get_option('nusantara_rajaongkir_key_status', array(false,""));
		if ($license_status[0]) {
			$this->nusantara = new API_Nusantara($license_key);
		}
		if ($rajaongkir_status[0]) {
			$this->rajaongkir = new API_RajaOngkir($rajaongkir_api_key, $this->rajaongkir_type);
		}
	}

	public function getOriginalCost($args, $config) {
		$origin = $config['store_location'];
		if ($origin === false || empty($origin) || empty($origin[0])) return "Origin Not Set";

		if($config['base_api'] == 'nusantara') {
			$response = $this->nusantara->getCost($origin[0], $args['district'], $config['courir_type']);
		} else {
			if ($config['raja_ongkir_type'] == 'pro') {
				$response = $this->rajaongkir->getCost($origin[0], $args['district'], $args['weight'], $config['courir_type']);
			} else {
				$response = $this->rajaongkir->getCost($origin[0], $args['city'], $args['weight'], $config['courir_type']);
			}
		}
		return $response;
	}

	public function getFormattedCost($args, $config) {
		global $nusantara_core;
		if($config['base_api'] == 'nusantara') {
			$type 				= 'pro';
		} else {
			if($config['raja_ongkir_type'] === 'pro') {
				$type 				= 'pro';							
			} else {
				$type 				= 'default';														
			}
		}
		if ($type == 'pro') {
			$destination_id = $args['district'];
			$destination_type = "district";
		} else {
			$destination_id = $args['city'];
			$destination_type = "city";
		}
		$custom_shipping_type = get_option('nusantara_custom_ongkir_type','append');
		$rates = $nusantara_core->getCost($destination_id, $args['weight']);
		$custom_costs 	= $nusantara_core->getCustomCost($args, $destination_type, $args['weight']);
		if ($custom_shipping_type == 'replace' && !empty($custom_costs)) {
			$rates = $custom_costs;
		} else {
			if (!empty($custom_costs)) {
				$rates = array_merge($custom_costs, $rates);
			}
		}

		if (!empty($rates)) {
			foreach ($rates as $r) {
				$courier = explode('-', $r['class']);
				$courier = isset($courier[0]) ? strtolower(trim($courier[0])) : '';
				
				// additional cost
				$additional_cost = 0;
				if ($config['with_ongkos_kirim_tambahan'] == 'yes') {
					$additional_cost = intval($config['ongkos_kirim_tambahan']); //TODO: filter
				}

				$cost = $r['cost'] + $additional_cost;
				$cost = $nusantara_core->currencyConvert($cost);

				// if filter courier active
				if ($config['is_specific_courir_type'] == "yes" && $config["base_api"] == "nusantara" && @$r['source'] != 'custom') {
					$class = trim(str_replace(array('-',' '), '', $r['class']));
					$allowed_class = array();
					$allowed_class_alt = array();
					foreach ($config['specific_courir_type'] as $s) {
						$allowed_class[] = strtolower(str_replace(array('-',' '), '', $s));
					}
					if (in_array(strtolower($class), $allowed_class)) {
						$final_rates[] = array(
							'courier'	=> $r['courier'],
							'label' 	=> ''.$r['class'],
							'cost' 		=> $cost,
							'source'	=> isset($r['source']) ? $r['source'] : 'api'
						);
					} else { // alternative courier filter
						foreach ($config['alternatif_courier_package'] as $s) {
							$allowed_class_alt[] = strtolower(str_replace(array('-',' '), '', $s));
						}
						if (in_array(strtolower($class), $allowed_class_alt)) {
							$final_rates[] = array(
								'courier'	=> $r['courier'],
								'label' 	=> $r['class'],
								'cost' 		=> $cost,
								'source'	=> isset($r['source']) ? $r['source'] : 'api'
							);
						}
					}
				} else {
					$final_rates[] = array(
						'courier'	=> $r['courier'],
						'label' 	=> $r['class'],
						'cost' 		=> $cost,
						'source'	=> isset($r['source']) ? $r['source'] : 'api'
					);
				}
			}
		}
		return $final_rates;
	}
}