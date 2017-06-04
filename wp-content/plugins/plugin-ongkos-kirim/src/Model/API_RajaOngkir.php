<?php
namespace PluginOngkosKirim\Model;

class API_RajaOngkir {

	protected $api_key;
	protected $type;

	function __construct($api_key, $type) {
		global $wp_version;
		$this->api_key = $api_key;
		$this->type = $type;
		if ($type == "pro") {
			$this->base_url 	= 'http://pro.rajaongkir.com/api';
		} else {
			$this->base_url 	= 'http://api.rajaongkir.com/'.$type;
		}
		$this->default_args = array(
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
			'blocking'    => true,
			'headers'     => array(
    			"key" => $this->api_key
  			),
			'cookies'     => array(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			'filename'    => null
		);
	}

	private function populate_output($content) {
		if (is_wp_error($content)) {
			return array(
				'status'	=> false,
				'data'		=> 'Please try again ( Error: '.$content->get_error_message().' )'
			);
		}
		$body = json_decode($content['body']);
		if (@$body->rajaongkir->status->code != 200) {
			return array(
				'status'	=> false,
				'data'		=> isset($body->rajaongkir->status->description) ? $body->rajaongkir->status->description : ''
			);
		}
		return array(
			'status'	=> true,
			'data'		=> $body->rajaongkir->results
		);
	}

	// public function getCourier() {
	// 	$content = wp_remote_get( $this->base_url.'/ekspedisi/', $this->default_args );
	// 	return $this->populate_output($content);
	// }

	public function getProvince() {
		$content = wp_remote_get( $this->base_url.'/province', $this->default_args );
		return $this->populate_output($content);
	}

	public function getCity($province_id) {
		$content = wp_remote_get( $this->base_url.'/city?province='.$province_id, $this->default_args );
		return $this->populate_output($content);
	}

	public function getSingleCity($city_id) {
		$content = wp_remote_get( $this->base_url.'/city?id='.$city_id, $this->default_args );
		return $this->populate_output($content);
	}

	public function getAllCity() {
		$content = wp_remote_get( $this->base_url.'/city', $this->default_args );
		return $this->populate_output($content);
	}

	public function getDistrict($city_id) {
		if ($this->type != "pro") {
			return array(
				'status'	=> false,
				'data'		=> "This method only for PRO license"
			);
		}
		$content = wp_remote_get( $this->base_url.'/subdistrict?city='.$city_id, $this->default_args );
		return $this->populate_output($content);
	}

	public function getAllCountry() {
		if ($this->type == "starter") {
			return array(
				'status'	=> false,
				'data'		=> "This method only for PRO/BASIC license"
			);
		}
		$content = wp_remote_get( $this->base_url.'/v2/internationalDestination', $this->default_args );
		return $this->populate_output($content);
	}

	public function getCost($origin,$destination,$weight,$courier) {
		// remove international only couriers
		if(($key = array_search('expedito', $courier)) !== false) {
			unset($courier[$key]);
		}
		$result = array();
		$params = array(
			'origin' 		=> $origin,
			'originType' 	=> 'city',
			'destination' 	=> $destination,
			'destinationType' => $this->type == "pro" ? "subdistrict" : "city",
			'weight' 		=> $weight,
		);
		if ($this->type == "pro" || $this->type == "basic") {
			$params['courier'] = implode(':',$courier);
			$this->default_args['headers']['Content-Type'] = "application/json";
			$this->default_args['body'] = json_encode($params);
			$content = wp_remote_post( $this->base_url.'/cost', $this->default_args );
			$response = $this->populate_output($content);
			if ($response['status'] && !empty($response['data'])) {
				$result = $response['data'];
			}
		} else {
			// terpaksa dibuat loop karena rajaongkir ngasih response Bad Request kalo pake multiple courier sekali request
			foreach ($courier as $key => $value) {
				$params['courier'] = $value;
				$this->default_args['headers']['Content-Type'] = "application/json";
				$this->default_args['body'] = json_encode($params);

				$content = wp_remote_post( $this->base_url.'/cost', $this->default_args );
				$response = $this->populate_output($content);
				if ($response['status'] && !empty($response['data'][0])) {
					$result[] = $response['data'][0];
				}
			}
		}
		return $result;
	}

	public function getCostInternational($origin,$destination,$weight,$courier) {
		$result = array();
		$courier = array_intersect($courier, array('pos', 'tiki', 'jne', 'slis', 'expedito'));
		foreach ($courier as $key => $value) {
			$params = array(
				'origin' 		=> $origin,
				'originType' 	=> 'city',
				'destination' 	=> $destination,
				'weight' 		=> $weight,
				'courier' 		=> $value,
			);
			$this->default_args['headers']['Content-Type'] = "application/json";
			$this->default_args['body'] = json_encode($params);

			$content = wp_remote_post( $this->base_url.'/v2/internationalCost', $this->default_args );
			$response = $this->populate_output($content);
			if ($response['status'] && !empty($response['data'][0])) {
				$result[] = $response['data'][0];
			}
		}
		return $result;
	}

	public function getKeyStatus($api_key, $type) {
		if ($type == "pro") {
			$base_url 	= 'http://pro.rajaongkir.com/api';
		} else {
			$base_url 	= 'http://api.rajaongkir.com/'.$type;
		}
		$args = $this->default_args;
		$args["headers"]["key"]	= $api_key;
		$content = wp_remote_get( $base_url.'/province', $args );
		if (!is_wp_error($content)) {
			$body = json_decode($content['body']);
			if (@$body->rajaongkir->status->code == 200) {
				return true;
			}
		}
		return false;
	}

}
