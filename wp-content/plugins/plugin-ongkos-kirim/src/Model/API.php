<?php namespace PluginOngkosKirim\Model;

use \PluginOngkosKirim\Model\API_Nusantara as API_Nusantara;
use \PluginOngkosKirim\Model\API_RajaOngkir as API_RajaOngkir;
use \PluginOngkosKirim\Helpers\Helpers as Helpers;

class API {

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

	public function getCourier() {
		if($this->vendor == 'rajaongkir' && $this->rajaongkir_type == 'pro') {
			return array(
				'jne'		=> 'JNE',
				'pos'		=> 'POS',
				'tiki'		=> 'TIKI',
				'pcp'		=> 'PCP',
				'rpx'		=> 'RPX',
				'esl'		=> 'Eka Sari Lorena',
				'pandu'		=> 'PANDU',
				'wahana'	=> 'WAHANA',
				'sicepat'	=> 'SICEPAT',
				'jnt'		=> 'J&T',
				'pahala'	=> 'PAHALA',
				'cahaya'	=> 'CAHAYA',
				'sap'		=> 'SAP',
				'jet'		=> 'JET',
				'indah'		=> 'INDAH',
				'dse'		=> '21 Express',
				'slis'		=> 'Solusi Express',
				'expedito'	=> 'Expedito',
				'first'		=> 'First Logistics',
				'ncs'		=> 'NCS',
				'star'		=> 'Star Cargo'
			);
		} else if($this->vendor == 'rajaongkir' && $this->rajaongkir_type == 'basic') {
			return array(
				'jne'	=> 'JNE',
				'pos'	=> 'POS',
				'tiki'	=> 'TIKI',
				'pcp'	=> 'PCP',
				'rpx'	=> 'RPX',
				'esl'	=> 'ESL'
			);
		} else {
			return array(
				'jne'	=> 'JNE',
				'pos'	=> 'POS',
				'tiki'	=> 'TIKI'
			);
		}
	}

	public function getCourierPackage() {
		$result = array();
		$courier = $this->nusantara->getCourier();
		if ($courier["status"]) {
			foreach ($courier["data"] as $c) {
				if (!empty($c->data)) {
					foreach ($c->data as $t) {
						$result[] = strtoupper($c->nama)." ".$t;
					}
				}
			}
		}
		return $result;
	}

	public function getProvince() {
		if ($this->vendor == "nusantara") {
			$result = $this->nusantara->getProvince();
			if ($result["status"]) {
				return $result["data"];
			}
		} else {
			if (is_null($this->rajaongkir)) return false;
			$result = $this->rajaongkir->getProvince();
			if ($result["status"] && !empty($result["data"])) {
				foreach ($result["data"] as $d) {
					$d->id 		= $d->province_id;
					$d->nama 	= $d->province;
				};
				return $result["data"];
			}
		}
		return false;
	}

	public function getCity($province_id) {
		if ($this->vendor == "nusantara") {
			$result = $this->nusantara->getCity($province_id);
			if ($result["status"]) {
				foreach ($result["data"] as $d) {
					$d->type = $d->jenis;
					if ($d->type == "Kota") {
						$d->nama = "Kota ".$d->nama;
					}
				};
				return $result["data"];
			}
		} else {
			if (is_null($this->rajaongkir)) return false;
			$result = $this->rajaongkir->getCity($province_id);
			if ($result["status"] && !empty($result["data"])) {
				foreach ($result["data"] as $d) {
					$d->id 		= $d->city_id;
					$d->nama 	= $d->city_name;
					if ($d->type == "Kota") {
						$d->nama = "Kota ".$d->nama;
					}
				};
				return $result["data"];
			}
		}
		return false;
	}

	public function getSingleCity($city_id) {
		if ($this->vendor == "nusantara") {
			$result = $this->nusantara->getSingleCity($city_id);
			if ($result["status"]) {
				return $result["data"][0];
			}
		} else {
			if (is_null($this->rajaongkir)) return false;
			$result = $this->rajaongkir->getSingleCity($city_id);
			if ($result["status"] && !empty($result["data"])) {
				$result["data"]->id 	= $result["data"]->city_id;
				$result["data"]->nama 	= $result["data"]->city_name;
				$result["data"]->type 	= $result["data"]->type == "Kabupaten" ? "Kab." : $result["data"]->type;
				$result["data"]->provinsi = $result["data"]->province;
				return $result["data"];
			}
		}
		return false;
	}

	public function getAllCity() {
		if ($this->vendor != "rajaongkir" || is_null($this->rajaongkir)) return false;
		// this method only for rajaongkir

		$result = $this->rajaongkir->getAllCity();
		if ($result["status"]) {
			return $result["data"];
		}
		return false;
	}

	public function searchCity($search = "") {
		if (strlen($search) < 3) return array();
		$result = $this->nusantara->getAllCity($search);
		if ($result["status"]) {
			foreach ($result["data"] as $d) {
				$d->type = $d->jenis;
			};
			return $result["data"];
		}
		return false;
	}

	public function getDistrict($city_id) {
		if ($this->vendor == "nusantara") {
			$result = $this->nusantara->getDistrict($city_id);
			if ($result["status"]) {
				return $result["data"];
			}
		} else {
			if ($this->rajaongkir_type != 'pro') return false;
			$result = $this->rajaongkir->getDistrict($city_id);
			if ($result["status"] && !empty($result["data"])) {
				foreach ($result["data"] as $d) {
					$d->id 		= $d->subdistrict_id;
					$d->nama 	= $d->subdistrict_name;
				};
				return $result["data"];
			}
		}
		return false;
	}

	public function getAllCountry() {
		if ($this->vendor == "rajaongkir" || $this->rajaongkir_type != "starter") {
			$result = $this->rajaongkir->getAllCountry();
			if ($result['status']) {
				$res = array();
				foreach ($result["data"] as $d) {
					// TODO: need filter to sanitize country name (exp: United States (US) -> United States of America)
					$res[(int)$d->country_id] = $d->country_name;
				}
				return $res;
			}
		}
		return false;
	}

	public function getCost($origin, $destination, $weight, $courier) {
		if ($this->vendor == "rajaongkir") {
			if (is_null($this->rajaongkir)) return array();
			$long_desc = get_option('nusantara_show_long_description', "no");
			$result = $this->rajaongkir->getCost($origin, $destination, $weight, $courier);
			if (!empty($result)) {
				$costs = array();
				foreach ($result as $c) {
					if (is_array($c->costs) && !empty($c->costs)) {
						foreach ($c->costs as $t) {
							if ($t->cost[0]->value > 0) {
								$costs[] = array(
									"class"			=> strtoupper($c->code)." - ".$t->service.($long_desc === "yes" && !empty($t->description) && $t->service != $t->description ? " (".$t->description.")" : ""),
									"courier"		=> strtolower($c->code),
									"description"	=> $t->description,
									"cost"			=> $t->cost[0]->value,
									"time"			=> $t->cost[0]->etd
								);
							}
						}
					}
				}
				return $costs;
			}
		} else {
			$result = $this->nusantara->getCost($origin, $destination, $courier);
			if ($result["status"]) {
				$costs = array();
				foreach ($result["data"] as $c) {
					if (!empty($c->tarif)) {
						foreach ($c->tarif as $t) {
							if ($t->namaLayanan == 'JTR') { // take out JTR from result
								continue;
							} else if ($t->namaLayanan == 'Paketpos Biasa' && $weight <= 2000) { // PaketPos Biasa only if weight>2kg
								continue;
							} else 
							if (strtolower($c->nama) == "pos") {
								if ($weight > 3000) {
									$cost = ( $t->tarif * 2 ) + ( $t->tarif_1_kg * ( Helpers::roundWeight($weight/1000) - 3 ) );
								} else {
									$cost = ($t->tarif*2)/3 * Helpers::roundWeight($weight/1000);	
								}
							} else {
								$cost = $t->tarif * Helpers::roundWeight($weight/1000);
							}
							$costs[] = array(
								"class"			=> $c->nama." - ".$t->namaLayanan,
								"courier"		=> strtolower($c->nama),
								"description"	=> $t->jenis,
								"cost"			=> $cost,
								// "time"			=> str_replace(" Hari", "", $t->etd),
								// "tarif_1_kg"	=> isset($t->tarif_1_kg) ? $t->tarif_1_kg : 0,
								// "tarif"			=> $t->tarif
							);
						}
					}
				}
				return $costs;
			}
		}
	}

	public function getCostInternational($origin, $destination, $weight, $courier) {
		if ($this->vendor == "rajaongkir" && !is_null($this->rajaongkir)) {
			global $nusantara_core;
			$result = $this->rajaongkir->getCostInternational($origin, $destination, $weight, $courier);
			if (!empty($result)) {
				$costs = array();
				foreach ($result as $c) {
					if (is_array($c->costs) && !empty($c->costs)) {
						foreach ($c->costs as $t) {
							if ($t->currency != "IDR") {
								$rates = $nusantara_core->getCurrencyRates();
								if (!isset($rates["data"]->rates) || empty($rates["data"]->rates->{$t->currency})) {
									continue;
								} else {
									$t->cost = $t->cost / $rates["data"]->rates->{$t->currency};
								}
							}
							$costs[] = array(
								"class"			=> strtoupper($c->code)." - ".$t->service,
								"courier"		=> strtolower($c->code),
								"description"	=> '',
								"cost"			=> $t->cost,
								"time"			=> !empty($t->etd) ? $t->etd : ''
							);
						}
					}
				}
				return $costs;
			}
		}
	}

	public function getRajaongkirStatus($api_key, $type) {
		if ($type == "pro") {
			$base_url 	= 'http://pro.rajaongkir.com/api';
		} else {
			$base_url 	= 'http://api.rajaongkir.com/'.$type;
		}
		$args = array(
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
			'blocking'    => true,
			'headers'     => array(
    			"key" => $api_key
  			),
			'cookies'     => array(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			'filename'    => null
		);
		$content = wp_remote_get( $base_url.'/province', $args );
		if (!is_wp_error($content)) {
			$body = json_decode($content['body']);
			if (@$body->rajaongkir->status->code == 200) {
				return true;
			}
		} else {
			return __("can not connect server", NUSANTARA_ONGKIR);
		}
		return false;
	}

	public function currency() {
		return $this->nusantara->currency();
	}

}