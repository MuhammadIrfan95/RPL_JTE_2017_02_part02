<?php
namespace PluginOngkosKirim\Model;

class API_Nusantara {

	protected $license_key;
	protected $type;
	protected $base_url;
	protected $api_param;
	protected $default_args;

	function __construct($license_key) {
		global $wp_version;
		$this->base_url 	= 'https://pluginongkoskirim.com/cek-tarif-ongkir/api';
		$this->api_param 	= '?license='.$license_key.'&website='.$this->getWebUrl();
		$this->default_args = array(
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
			'blocking'    => true,
			'headers'     => array(),
			'cookies'     => array(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			'filename'    => null
		);
	}

	public function getWebUrl() {
		preg_match_all("#^.+?[^\/:](?=[?\/]|$)#", get_site_url(), $matches); 
		return $matches[0][0];
	}

	private function populate_output($content) {
		if (is_wp_error($content)) {
			return array(
				'status'	=> false,
				'data'		=> 'Please try again ( Error: '.$content->get_error_message().' )'
			);
		}
		if ($content['response']['code'] !== 200) {
			return array(
				'status'	=> false,
				'data'		=> 'Error code: '.$content['response']['code']
			);	
		}
		$body = json_decode($content['body']);
		if (@$body->error) {
			return array(
				'status'	=> false,
				'data'		=> isset($body->data) ? $body->data : ''
			);
		}
		return array(
			'status'	=> true,
			'data'		=> isset($body->data) ? $body->data : $body
		);
	}

	public function getCourier() {
		$content = wp_remote_get( $this->base_url.'/ekspedisi/'.$this->api_param, $this->default_args );
		return $this->populate_output($content);
	}

	public function getProvince() {
		$content = wp_remote_get( $this->base_url.'/provinsi/'.$this->api_param, $this->default_args );
		return $this->populate_output($content);
	}

	public function getCity($province_id) {
		$content = wp_remote_get( $this->base_url.'/provinsi/'.$province_id.'/dati_ii'.$this->api_param, $this->default_args );
		return $this->populate_output($content);
	}

	public function getSingleCity($city_id) {
		$content = wp_remote_get( $this->base_url.'/asal/'.$city_id.$this->api_param, $this->default_args );
		return $this->populate_output($content);
	}

	public function getAllCity($search = '') {
		$content = wp_remote_get( $this->base_url.'/asal'.$this->api_param.'&s='.$search, $this->default_args );
		return $this->populate_output($content);
	}

	public function getDistrict($city_id) {
		$content = wp_remote_get( $this->base_url.'/kabupaten/'.$city_id.'/dati_iii'.$this->api_param, $this->default_args );
		return $this->populate_output($content);
	}

	public function getCost($origin,$destination,$courier) {
		$cur = '';
		foreach ($courier as $key => $value) {
			$cur .= '&ekspedisi['.$key.']=' . urlencode($value);
		}
		$content = wp_remote_get( $this->base_url.'/tarif/'.$origin.'/tujuan/'.$destination.$this->api_param.'&jenis=kecamatan'.$cur, $this->default_args );
		return $this->populate_output($content);
	}

	public function currency() {
		$content = wp_remote_get( 'http://api.fixer.io/latest?base=IDR', $this->default_args );
		return $this->populate_output($content);
	}

}
