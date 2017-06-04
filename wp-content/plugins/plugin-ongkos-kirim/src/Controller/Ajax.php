<?php
namespace PluginOngkosKirim\Controller;

use \PluginOngkosKirim\Controller\Core as NusantaraCore;

class Ajax {
	
	protected $model;

	public function __construct() {
		global $nusantara_core;
		$this->model = $nusantara_core;

		/*ajax get District*/
		add_action('wp_ajax_pok_get_city_by_province_id',array($this,'pok_get_city_by_province_id'));
		add_action('wp_ajax_nopriv_pok_get_city_by_province_id',array($this,'pok_get_city_by_province_id'));

		add_action('wp_ajax_pok_get_district_by_province_id',array($this,'pok_get_district_by_province_id'));
		add_action('wp_ajax_nopriv_pok_get_district_by_province_id',array($this,'pok_get_district_by_province_id'));

		add_action('wp_ajax_get_store_location_city',array($this,'get_store_location_city'));
		add_action('wp_ajax_nopriv_get_store_location_city',array($this,'get_store_location_city'));

		add_action('wp_ajax_get_courier_type',array($this,'get_courier_type'));
		add_action('wp_ajax_nopriv_get_courier_type',array($this,'get_courier_type'));

		add_action('wp_ajax_get_list_city',array($this,'get_list_city'));
		add_action('wp_ajax_nopriv_get_list_city',array($this,'get_list_city'));

		add_action('wp_ajax_get_list_district',array($this,'get_list_district'));
		add_action('wp_ajax_nopriv_get_list_district',array($this,'get_list_district'));

		add_action('wp_ajax_pok_change_country',array($this,'change_country'));
		add_action('wp_ajax_nopriv_pok_change_country',array($this,'change_country'));

		add_action('wp_ajax_pok_search_city',array($this,'search_city'));
		add_action('wp_ajax_pok_activate_license',array($this,'activate_license'));
		add_action('wp_ajax_pok_deactive_license',array($this,'deactive_license'));
		add_action('wp_ajax_pok_set_rajaongkir_api_key',array($this,'set_rajaongkir_api_key'));
		add_action('wp_ajax_pok_hit_server',array($this,'hit_server'));
	}

	public function activate_license() {
		global $nusantara_core;
		$license = $_POST['license_key'];
		$nusantara_core->registerLicense($license);
		$check = $nusantara_core->getLicenseStatus($license);
		if (!empty($check) && !empty($check['data'])) {
			if (isset($check['data']->status) && $check['data']->status === true) {
				$license_status = array(true,"License is active");
				set_transient('nusantara_ongkir_license_status_check', true, 60 * 60 * 168);
				pok_update_license_status($license_status);
				update_option('nusantara_ongkir_lisensi', $license);
				if (get_option('nusantara_store_location') === false) {
					update_option('nusantara_store_location', array(155));
				}
				echo "success";
			} else {
				echo $check['data']->message;
			}
		} else {
			echo __("Server didn't respond", NUSANTARA_ONGKIR);
		}
		die();
	}

	public function deactive_license() {
		global $nusantara_core;
		$license = get_option('nusantara_ongkir_lisensi',"");
		$check = $nusantara_core->unregisterLicense();
		if (!empty($check) && !empty($check['data'])) {
			$data = json_decode($check['data']);
			if (@$data->status === true) {
				$license_status = array(false,"License is not active");
				pok_update_license_status($license_status, true);
				delete_option('nusantara_ongkir_lisensi');
				echo "success";
			} else {
				echo $data->message;
			}
		} else {
			echo "Server didn't respond";
		}
		die();
	}

	public function hit_server() {
		global $nusantara_core;

		$license = $_POST['license_key'];
		
		$license_status = nusantara_plugin_license_check(true, $license);
		$false_counter = get_option('nusantara_ongkir_false_license_counter', 0);
		$final_status	= get_option('nusantara_ongkir_license_status', array(false,"License is not active"));

		// response status
		echo "<b>Response status:</b><pre>";
		print_r($license_status);
		echo "</pre>";

		// final status
		echo "<b>Final status:</b><pre>";
		print_r($final_status);
		echo "</pre>";

		// false count
		echo "<b>False counter:</b> " . $false_counter;

		die();
	}

	public function set_rajaongkir_api_key() {
		global $nusantara_core;
		global $nusantara_helper;
		$api_key = $_POST['api_key'];
		$api_type = $_POST['api_type'];
		$check = $nusantara_core->getRajaongkirStatus($api_key, $api_type);
		if (@$check === true) {
			$nusantara_core->deleteCache('courier');
			update_option("nusantara_api_key_raja_ongkir", $api_key, "no");
			update_option("nusantara_raja_ongkir_type", $api_type, "no");
			update_option("nusantara_rajaongkir_key_status", array(true, "API Key Active"), "no");
			if ($api_type == "pro") {
				$couriers = $nusantara_helper->get_default_couriers("rajaongkir_pro");
			} else if ($api_type == "basic") {
				$couriers = $nusantara_helper->get_default_couriers("rajaongkir_basic");
			} else {
				$couriers = $nusantara_helper->get_default_couriers();
			}
			$nusantara_core->purgeCache('cost');
			update_option('nusantara_base_api', 'rajaongkir', 'no');
			update_option('nusantara_courir_type', $couriers, 'no');
			echo "success";
		} else {
			if ($api_key == "") {
				_e("API key is empty", NUSANTARA_ONGKIR);
			} else if (!in_array($api_type,array("starter","basic","pro"))) {
				_e("API type is not valid", NUSANTARA_ONGKIR);
			} else if (@$check !== false) {
				echo $check;
			} else {
				_e("API Key is not valid", NUSANTARA_ONGKIR);
			}
		}
		die;
	}

	public function search_city() {
		$search = htmlspecialchars($_POST['search']);
		$return = $this->model->searchCity($search);
		echo json_encode($return);
		exit();
	}

	public function change_country() {
		$old_value = isset($_SESSION['_nusantara_ongkir_session_country']) ? $_SESSION['_nusantara_ongkir_session_country'] : '';
		$new_value = isset($_POST['country']) ? $_POST['country'] : '';
		if ($old_value != $new_value) {
			if ($old_value == 'ID' || $new_value == 'ID') {
				echo "reload";
			}
		}
		$_SESSION['_nusantara_ongkir_session_country'] = $new_value;
		die();
	}

	public function get_list_district() {
		$city_id 	= htmlspecialchars($_POST['city_id']);
		$city 		= $this->model->getDistrict($city_id);
		$r_city 	= array();
		
		if(is_array($city)): foreach ($city as $key => $value) {
			$r_city[$value->id] = $value->nama;
		} endif;
		
		echo json_encode($r_city);
		wp_die();
	}

	public function get_list_city() {		
		$province_id = htmlspecialchars($_POST['province_id']);
		$city = $this->model->getCity($province_id);
		$r_city = array();
		
		if(is_array($city)): foreach ($city as $key => $value) {
			$r_city[$value->id] = $value->nama;
		} endif;

		echo json_encode($r_city);
		wp_die();
	}

	public function pok_get_city_by_province_id() {
		$province_id = htmlspecialchars($_POST['province_id']);
		$res = $this->model->getCity($province_id);
		echo json_encode($res);
		wp_die();
	}

	public function pok_get_district_by_province_id() {
		$city_id = htmlspecialchars($_POST['city_id']);
		$res = $this->model->getDistrict($city_id);
		echo json_encode($res);
		wp_die();
	}
	
}