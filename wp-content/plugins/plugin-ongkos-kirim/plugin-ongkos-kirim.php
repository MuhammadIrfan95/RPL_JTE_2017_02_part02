<?php

/**
 * @link              https://tonjoostudio.com/addons/woo-ongkir/
 * @since             1.0.0
 * @package           Plugin Ongkos Kirim
 *
 * @wordpress-plugin
 * Plugin Name:       Plugin Ongkos Kirim
 * Plugin URI:        https://tonjoostudio.com/addons/woo-ongkir/
 * Description: Support woocomerce calculation your shipping cost 
 * Version:           2.0.10
 * Author:            Tonjoostudio
 * Author URI:        https://tonjoostudio.com
 * License:           GPL
 */
if ( ! defined( 'ABSPATH' ) ) exit; 

define("NUSANTARA_ONGKIR_DEBUG", false);

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/tonjoo-plugin-updater/library-license-wordpress/tonjoo_license.php';
require __DIR__.'/src/bootstrap.php';

if(! function_exists('plugin_ongkos_kirim_init')):

function plugin_ongkos_kirim_init() {
	if(! class_exists('plugin_ongkos_kirim')):
	
	class Plugin_Ongkos_Kirim extends WC_Shipping_Method {
		protected $option;
		protected $config;
		protected $model;
		protected $request_trans;
		protected $key;
		protected $type;
		protected $lisensi;
		protected $is_active;

		public function __construct() {
			global $nusantara_core;
			global $nusantara_helper;
			$this->id 					= 'plugin_ongkos_kirim';
			$this->method_title 		= __('Plugin Ongkos Kirim');
			$this->method_description 	= __('Shipping Method for Indonesia Marketplace');
			$this->enabled 				= 'yes';
			$this->title 				= __('Plugin Ongkos Kirim');
			$this->model 				= $nusantara_core;
			$this->helper 				= $nusantara_helper;
			$this->option 				= get_option('woocommerce_plugin_ongkos_kirim_settings');
			
			if(get_option('nusantara_base_api') == 'nusantara') {
				$this->type = 'pro';
			} else {
				if(get_option('nusantara_raja_ongkir_type') === 'pro') {
					$this->type = 'pro';							
				} else {
					$this->type = 'default';														
				}
			}

			$this->is_active 			= $this->helper->is_license_active();
			$this->init();
		}
	
		public function init() {
			global $wpdb,$woocommerce;

			if(!isset($_SESSION['_nusantara_ongkir_session_country']))
				$_SESSION['_nusantara_ongkir_session_country'] = 'ID';

			$this->submit_action();					
			$this->init_form_fields(); 
			$this->init_settings();
			add_action('woocommerce_update_options_shipping_' . $this->id,array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_payment_gateways',array(&$this, 'process_admin_options'));
			add_action('woocommerce_before_checkout_form', array(&$this, 'checkout_debugger'));
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __('Enable/Disable',NUSANTARA_ONGKIR),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this shipping method', NUSANTARA_ONGKIR ),
					'default' => 'no',
				),
			);
		}

		public function load_config() {
			$this->config['base_server']				= get_option('nusantara_base_server', 'indonesia');
			$this->config['base_api']					= get_option('nusantara_base_api', 'rajaongkir');
			$this->config['ongkir_lisensi']				= get_option('nusantara_ongkir_lisensi', "");
			$this->config['ongkir_license_status']		= get_option('nusantara_ongkir_license_status', array(false,""));
			$this->config['api_key_raja_ongkir']		= get_option('nusantara_api_key_raja_ongkir', "");
			$this->config['raja_ongkir_type']			= get_option('nusantara_raja_ongkir_type', "starter");
			$this->config['rajaongkir_key_status']		= get_option('nusantara_rajaongkir_key_status', array(false,""));
			$this->config['courir_type']				= get_option('nusantara_courir_type', array());
			$this->config['store_location']				= get_option('nusantara_store_location');
			$this->config['show_advanced_options']		= get_option('nusantara_show_advanced_options', 'hide');
			$this->config['is_specific_courir_type']	= get_option('nusantara_is_specific_courir_type', 'no');
			$this->config['specific_courir_type']		= get_option('nusantara_specific_courir_type',array());
			$this->config['alternatif_specific_curier']	= get_option('nusantara_alternatif_specific_curier','no');
			$this->config['alternatif_courier_package']	= get_option('nusantara_alternatif_courier_package',array());
			$this->config['international_shipping']		= get_option('nusantara_international_shipping','no');
			$this->config['shipping_cost_by_kg']		= get_option('nusantara_shipping_cost_by_kg','no');
			$this->config['round_shipping_weight']		= get_option('nusantara_round_shipping_weight','auto');
			$this->config['round_shipping_weight_tolerance'] = get_option('nusantara_round_shipping_weight_tolerance',500);
			$this->config['with_unique_number']			= get_option('nusantara_with_unique_number','no');
			$this->config['unique_number']				= get_option('nusantara_unique_number',1);
			$this->config['with_ongkos_kirim_tambahan']	= get_option('nusantara_with_ongkos_kirim_tambahan', 'no');
			$this->config['ongkos_kirim_tambahan']		= get_option('nusantara_ongkos_kirim_tambahan', 0);
			$this->config['transient_request_interval']	= get_option('nusantara_transient_request_interval', 72);
			$this->config['default_berat_shipping']		= get_option('nusantara_default_berat_shipping', 1);
			$this->config['custom_courier']				= get_option('nusantara_custom_courier', "Custom");
			$this->config['custom_ongkir_type']			= get_option('nusantara_custom_ongkir_type', "append");
			$this->config['save_returned_user_information']	= get_option('nusantara_save_returned_user_information', "yes");
			$this->config['show_long_description']		= get_option('nusantara_show_long_description', "no");
			$this->config['manual_shipping']			= get_option('nusantara_manual_shipping', array());
		}

		public function submit_action() {
			if (wp_verify_nonce( @$_POST['_pok_do'], 'pok-change-setting' )) { // saving options
				$this->load_config();
				$old_value = $this->config;
				$new_value = $_POST;

				// fix first installation
				if (empty($new_value['courir_type'])) {
					$new_value['courir_type'] = $this->helper->get_default_couriers();
				}

				foreach ($old_value as $key => $value) {
					if (isset($new_value[$key])) {

						// if setting is switching base api
						if ($key == "base_api" && $this->helper->is_admin_active()) continue;
						if ($key == "raja_ongkir_type") continue;

						// store location
						if ($key == "store_location" && !is_array($new_value[$key])) {
							$store_location = explode(",", $new_value[$key]);
							$new_value[$key] = array($store_location[0]);
						}

						// purge cache if couriers and store location changed
						if ($key == "store_location" || $key == "courir_type") {
							if ($new_value[$key] != $this->config[$key]) {
								$this->model->purgeCache('cost');
							}
						}

						//sanitize
						if (!is_array($new_value[$key])) {
							$new_value[$key] = htmlspecialchars($new_value[$key]);
						}

						// update
						if ($new_value[$key] !== $old_value[$key]) {
							update_option('nusantara_'.$key, $new_value[$key], 'no');
						}
					}
				}

			} else if (wp_verify_nonce( @$_POST['_pok_do'], 'pok-update-custom-shipping-costs' )) { // custom shipping costs
				if(isset($_POST['manual'])) {
					$count_append = count($_POST['manual']['manualselectprovince']);
					$manual = array();
					$append_manual 	= array();
					for ($i=0; $i < $count_append; $i++) {
						$append_manual['manualselectprovince'] 		= htmlspecialchars($_POST['manual']['manualselectprovince'][$i]);
						$append_manual['manualselectprovince_text'] = htmlspecialchars($_POST['manual']['manualselectprovince_text'][$i]);
						$append_manual['manualselectcity'] 			= htmlspecialchars($_POST['manual']['manualselectcity'][$i]);
						$append_manual['manualselectcity_text'] 	= htmlspecialchars($_POST['manual']['manualselectcity_text'][$i]);
						$append_manual['manualselectdistrict'] 		= htmlspecialchars($_POST['manual']['manualselectdistrict'][$i]);
						$append_manual['manualselectdistrict_text'] = htmlspecialchars($_POST['manual']['manualselectdistrict_text'][$i]);
						$append_manual['ekspedisi'] 				= htmlspecialchars($_POST['manual']['ekspedisi'][$i]);
						$append_manual['nunsatara_jenis'] 			= htmlspecialchars($_POST['manual']['nunsatara_jenis'][$i]);
						$append_manual['nusantara_tarif'] 			= htmlspecialchars($_POST['manual']['nusantara_tarif'][$i]);
						$manual[] = $append_manual;
					}
					update_option('nusantara_manual_shipping', $manual, "no");
				} else {
					update_option('nusantara_manual_shipping', array(), "no");
				}
				update_option('nusantara_custom_courier', $_POST['custom_courier'], "no");
				update_option('nusantara_custom_ongkir_type', $_POST['custom_ongkir_type'], "no");

			} else if (wp_verify_nonce( @$_GET['_pok_do'], 'pok-change-base-api' )) { // switching base api
				if (@$_GET['base_api'] == "rajaongkir") {
					update_option('nusantara_base_api', 'nusantara', 'no');
					update_option('nusantara_courir_type', $this->helper->get_default_couriers(), 'no');
				} else if (@$_GET['base_api'] == "nusantara") {
					update_option('nusantara_base_api', 'rajaongkir', 'no');
					$type = get_option('nusantara_raja_ongkir_type', "starter");
					if ($type == "pro") {
						$couriers = $this->helper->get_default_couriers("rajaongkir_pro");
					} else if ($type == "basic") {
						$couriers = $this->helper->get_default_couriers("rajaongkir_basic");
					} else {
						$couriers = $this->helper->get_default_couriers();
					}
					update_option('nusantara_courir_type', $couriers, 'no');
					if (!$this->helper->is_rajaongkir_active()) {
						delete_option('nusantara_store_location');
					}
				}
				$store_location = get_option('nusantara_store_location');
				if ($store_location !== false && is_array($store_location) && !empty($store_location) && isset($store_location[0])) {
					if ($this->model->getSingleCity($store_location[0]) === false) {
						delete_option('nusantara_store_location');	
					}
				}
				// delete custom costs
				delete_option('nusantara_manual_shipping');
				// delete customer saved address data
				global $wpdb;
				$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key IN ('billing_state','billing_city','billing_district','shipping_state','shipping_city','shipping_district')");
				// purge cache
				$this->model->purgeCache();
				wp_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'));
				die;

			} else if (wp_verify_nonce( @$_GET['_pok_do'], 'pok-flush-cache' )) { // flush cache
				$this->model->purgeCache();
				wp_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'));
				die;
			} else if (wp_verify_nonce( @$_GET['_pok_do'], 'pok-reset' )) { // reset
				global $wpdb;
				$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'nusantara_%'");
				$this->model->purgeCache();
				wp_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'));
				die;
			}
		}

		public function admin_options() {
			$this->load_config();

			if(false ===  get_transient('nusantara_ongkir_license_status_check') && $this->is_active) {
				nusantara_plugin_license_check();
				set_transient('nusantara_ongkir_license_status_check', $this->is_active, 60 * 60 * 168); // 7 days
			}

			if (@$_GET['pok_tab'] == "custom_costs") {
				$active_tab = "custom_costs";
			} else if (@$_GET['pok_tab'] == "costs_debugger" && NUSANTARA_ONGKIR_DEBUG) {
				$active_tab = "costs_debugger";
			} else {
				$active_tab = "settings";
			}

			?>

			<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim') ?>" class="nav-tab <?php echo $active_tab == "settings" ? "nav-tab-active" : ""; ?>"><?php echo __('Settings')?></a>
				<?php if ($this->helper->is_admin_active()) : ?>
				<a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim&pok_tab=custom_costs') ?>" class="nav-tab <?php echo $active_tab == "custom_costs" ? "nav-tab-active" : ""; ?>"><?php echo __('Custom Shipping Costs', NUSANTARA_ONGKIR)?></a>
				<?php endif; ?>
				<?php if (NUSANTARA_ONGKIR_DEBUG): ?>
					<a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim&pok_tab=costs_debugger') ?>" class="nav-tab <?php echo $active_tab == "costs_debugger" ? "nav-tab-active" : ""; ?>">Ongkir Debugger</a>
				<?php endif; ?>
			</h2>

			<?php

			if (@$_GET['pok_tab'] == "custom_costs") {
				if (!$this->helper->is_admin_active()) {
					wp_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'));
				} else {
					$this->show_page_custom_costs();
				}
			} else if (@$_GET['pok_tab'] == "costs_debugger") {
				if (!NUSANTARA_ONGKIR_DEBUG) {
					wp_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'));
				} else {
					$this->show_page_costs_debugger();
				}
			} else {
				$this->show_page_setting();
			}
		}

		protected function show_page_setting() {
			global $nusantara_core;

			$config = $this->config;

			$data = array(
				'couriers' => array(),
				'courier_package' => array(),
				'all_city' => array()
			);

			if ($this->helper->is_admin_active()) {
				$data['couriers'] = $nusantara_core->getCourier();
				$data['courier_package'] = $nusantara_core->getCourierPackage();
				
				if ($config['base_api'] == 'rajaongkir' && $this->helper->is_rajaongkir_active()) {
					$data['all_city'] = $nusantara_core->getAllCity();
				} else {
					$data['all_city'] = array();
				}
			}

			?>
			<h2 class="plugin-title"><?php _e('Plugin Ongkos Kirim Settings',NUSANTARA_ONGKIR); ?></h2>
			<table class="form-table">
				<?php
				wp_nonce_field('pok-change-setting', '_pok_do');
				$this->generate_settings_html();
				require __DIR__.'/src/Template/SettingField.php';
				?>
			</table>
			<?php
		}

		protected function show_page_custom_costs() {
			global $nusantara_core;
			
			if (!$this->helper->is_license_active()) {
				wp_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'));
				die;
			}
			$config = $this->config;
			$data['couriers']			= $nusantara_core->getCourier();
			// $data['all_city']			= $nusantara_core->getAllCity();
			$data['province']			= $nusantara_core->getProvince();
			?>
			<h2 class="plugin-title"><?php _e('Custom Shipping Costs',NUSANTARA_ONGKIR); ?></h2>
			<div style="display:none;">
				<?php $this->generate_settings_html(); ?>
			</div>
			<?php
			wp_nonce_field('pok-update-custom-shipping-costs', '_pok_do');
			require __DIR__.'/src/Template/CustomShippingCosts.php';
		}

		protected function show_page_costs_debugger() {
			global $nusantara_core;
			$config = $this->config;
			$data['province'] = $nusantara_core->getProvince();
			if (wp_verify_nonce( @$_POST['_pok_do'], 'pok-costs-debugger' )) { // costs debugger
				require __DIR__.'/src/Helpers/Debugger.php';
				$args = array(
					'weight'	=> $_POST['costs_debugger_weight'],
					'state'		=> $_POST['costs_debugger_province'],
					'city'		=> $_POST['costs_debugger_city'],
					'district'	=> @$_POST['costs_debugger_district']
				);
				$config = $this->config;
				$debugger = new Debugger();
				$originResult = $debugger->getOriginalCost($args, $config);
				$formattedResult = $debugger->getFormattedCost($args, $config);
			}
			?>
			<h2 class="plugin-title">Ongkir Debugger</h2>
			<div style="display:none;">
				<?php $this->generate_settings_html(); ?>
			</div>
			<?php
			wp_nonce_field('pok-costs-debugger', '_pok_do');
			require __DIR__.'/src/Template/CostsDebugger.php';	
		}

		// protected function get_checkout_post_data($itemdata)
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

		public function calculate_shipping( $package = array() ) {
			global $woocommerce;
			global $wpdb;

			$weight = 0;
			$rates = array();
			$final_rates = array();
			$custom_shipping_type = get_option('nusantara_custom_ongkir_type','append');

			if (!$this->helper->is_plugin_active()) return false;

			if (empty($package)) return false;

			if(@$_SESSION['_nusantara_ongkir_session_country'] != 'ID') {
				if (get_option('nusantara_base_api') == 'nusantara' || get_option('nusantara_raja_ongkir_type', 'starter') == 'starter' || get_option('nusantara_international_shipping', 'yes') == 'no')
				return false;
			}
			$destination = $package['destination'];
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%wc_ship%'");

			$default_weight = get_option('nusantara_default_berat_shipping', 1);
			foreach ( $package['contents'] as $cnt ) {
				$product = $cnt['data'];
				$weight += (($product->has_weight() ? $product->get_weight() : $default_weight) * $cnt['quantity']);
			}
			$weight = $this->helper->weightConvert($weight);

			if ($destination['country'] == 'ID') {
				//get destination
				if ($this->type == 'pro') { // get district (not provided by WC by default)
					if(isset($_POST['post_data'])) { // checkout page
						if ($this->get_checkout_post_data('ship_to_different_address') === '1') {
							$district = $this->get_checkout_post_data('shipping_district');
						} else {
							$district = $this->get_checkout_post_data('billing_district');
						}
					} else { // order detail (after checkout)
						if (@$_POST['shipping_district']) {
							$district = htmlspecialchars(@$_POST['shipping_district']);
						} else {
							$district = htmlspecialchars(@$_POST['billing_district']);
						}
					}
					if (!empty($district)) {
						$destination['district'] = (int)$district;
						$destination_id = (int)$district;
					}
					$destination_type = "district";
				} else {
					$destination_id = (int)$destination['city'];
					$destination_type = "city";
				}
				// get costs
				if (isset($destination_type) && !empty($destination_id)) {
					$rates 			= $this->model->getCost($destination_id,$weight);
					$custom_costs 	= $this->model->getCustomCost($destination, $destination_type, $weight);
					if ($custom_shipping_type == 'replace' && !empty($custom_costs)) {
						$rates = $custom_costs;
					} else {
						if (!empty($custom_costs)) {
							$rates = array_merge($custom_costs, $rates);
						}
					}
				}

			} else if (get_option('nusantara_base_api') == 'rajaongkir' && get_option('nusantara_raja_ongkir_type', 'starter') != 'starter' && get_option('nusantara_international_shipping', 'yes') == 'yes' ) { // international shipping
				$country_name = WC()->countries->countries[ $destination['country'] ];
				$country_data = $this->model->getAllCountry();
				$destination_id = array_search($this->helper->rajaongkirCountryName($country_name), $country_data);
				if ($destination_id) {
					$rates = $this->model->getCostInternational($destination_id, $weight);
				}
			}

			if (!empty($rates)) {
				$this->load_config();
				foreach ($rates as $r) {
					$courier = explode('-', $r['class']);
					$courier = isset($courier[0]) ? strtolower(trim($courier[0])) : '';
					
					// additional cost
					$additional_cost = 0;
					if ($this->config['with_ongkos_kirim_tambahan'] == 'yes') {
						$additional_cost = intval($this->config['ongkos_kirim_tambahan']); //TODO: filter
					}

					$cost = $r['cost'] + $additional_cost;
					$cost = $this->model->currencyConvert($cost);

					// if filter courier active
					if ($this->config['is_specific_courir_type'] == "yes" && $this->config["base_api"] == "nusantara" && @$r['source'] != 'custom') {

						$class = trim(str_replace(array('-',' '), '', $r['class']));
						$allowed_class = array();
						$allowed_class_alt = array();
						foreach ($this->config['specific_courir_type'] as $s) {
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
							foreach ($this->config['alternatif_courier_package'] as $s) {
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
			} else {
				if (!isset($destination_id)) {
					$rate = array(
						'id' => $this->id ,
						'label' => __('Please fill in all shipping detail\'s field to display shipping cost',NUSANTARA_ONGKIR),
						'cost' => ''
					);
					$this->add_rate( $rate );
				}
			}

			if (!empty($final_rates)) {
				$i = 1;
				$final_rates = apply_filters('pok_shipping_costs', $final_rates, $destination);
				foreach ($final_rates as $f) {
					$rate = array(
						'id' => $this->id.'-'.$i,
						'label' => $f['label'],
						'cost' => $f['cost']
					);
					$this->add_rate( $rate );
					$i++;
				}
			}
		}

		public function checkout_debugger() {
			if (isset($_GET['pok_debug'])) {
				$this->load_config();
				if (@$_GET['license'] == $this->config['ongkir_lisensi']) {
					global $woocommerce;
					global $wp_version;
					echo "<h4>Versions</h4>";
					echo "<pre>";
					echo "Plugin Ongkir: 2.0.10<br/>";
					echo "Woocommerce: ".$woocommerce->version."<br/>";
					echo "Wordpress: ".$wp_version."<br/>";
					echo "PHP: ".phpversion()."<br/>";
					echo "</pre>";
					echo "<h4>Configs</h4>";
					echo "<pre>";
					print_r($this->config);
					echo "</pre>";
					echo "<h4>Status</h4>";
					echo "<pre>";
					echo "License: ".($this->helper->is_license_active() ? "active" : "not active")."<br/>";
					echo "Plugin: ".($this->helper->is_plugin_active() ? "active" : "not active")."<br/>";
					echo "Admin: ".($this->helper->is_admin_active() ? "active" : "not active")."<br/>";
					echo "Rajaongkir: ".($this->helper->is_rajaongkir_active() ? "active" : "not active")."<br/>";
					echo "</pre>";
				}
			}
		}
	}
	
	endif; // if(! class_exists('plugin_ongkos_kirim'))
}
add_action( 'woocommerce_shipping_init', 'plugin_ongkos_kirim_init' );


function add_tonjoo_shipping_method( $methods ) {
	$methods['plugin_ongkos_kirim'] = 'Plugin_Ongkos_Kirim';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_tonjoo_shipping_method' );

endif; // function_exists('plugin_ongkos_kirim_init')

register_activation_hook(__FILE__, 'nusantara_plugin_activate');
add_action('admin_init', 'nusantara_admin_init');

function nusantara_plugin_activate() {
	// redirection
	add_option('nusantara_ongkir_activation_redirect', true);
}

function nusantara_admin_init() {
	// register scheduling check
	if (! wp_next_scheduled ( 'nusantara_plugin_license_check_hook' )) {
		wp_schedule_event(time(), 'daily', 'nusantara_plugin_license_check_hook');
	}

	// redirect
	if (get_option('nusantara_ongkir_activation_redirect', false)) {
		delete_option('nusantara_ongkir_activation_redirect');
		if(!nusantara_plugin_license_check()) {
			exit( wp_redirect( admin_url( 'admin.php?page=plugin-ongkos-kirim' ) ) );
		} else {
			exit( wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim' ) ) );
		}
	}
}
add_action('nusantara_plugin_license_check_hook', 'nusantara_plugin_license_check');

function nusantara_plugin_license_check($debug = false, $override_license_key = false) {
	$license_key 	= get_option('nusantara_ongkir_lisensi', false);
	$license_status	= get_option('nusantara_ongkir_license_status', array(false,"License is not active"));

	if($license_key == false || empty($license_key)) {
		$license_status = array(false, "License is not active");
		pok_update_license_status($license_status, true);
		return $license_status[0];
	}

	$license_checker 	= new tonjoo_license();
	$args 				= array(
		'key'			=> $license_key,
		'plugin_name'	=> 'wooongkir-premium'
	);

	// if override license
	if($override_license_key) {
		$args['key'] = $override_license_key;
	}

	// hit server
	$server_response = $license_checker->getStatus($args);

	// for debuging purpose
	if($debug) {
		// status before hit server
		echo "<b>Before hit server status:</b><pre>";
		print_r($license_status);
		echo "</pre>";

		// server response
		echo "<b>Server response:</b><pre>";
		print_r($server_response);
		echo "</pre>";
	}

	// if not connect internet, return latest status
	if (!empty($server_response) && !empty($server_response['data']) && isset($server_response['data']->status)) {
		if ($server_response['data']->status === true) {
			$license_status = array(true,"License is active");
			set_transient('nusantara_ongkir_license_status_check', true, 60 * 60 * 168); //7 days
		} else if ($server_response['data']->status === false) {
			$license_status = array(false, "License is not active");
		}
	}
	else {
		// for debuging purpose
		if($debug) {
			echo '<b>Notices:</b>';
			echo '<pre>Keep using `Before hit server status` because lack of `$server_response["data"]->status`</pre>';
		}
	}

	pok_update_license_status($license_status);
	return $license_status;
}

// update license status based on false license counter
function pok_update_license_status($license_status, $force_update = false) {
	/**
	 * There are 2 kind of license status:
	 * - array(false,"License is not active")
	 * - array(true,"License is active")
	 */

	$max_false_counter = 5;
	$opt_counter_name = 'nusantara_ongkir_false_license_counter';
	$will_update_status = false;

	// get will_update_status
	if($license_status[0] == false) {
		$false_counter = get_option($opt_counter_name, 0);

		if($false_counter >= $max_false_counter) {
			$will_update_status = true;			
		}
		else {
			update_option($opt_counter_name, $false_counter + 1);
			$will_update_status = false;
		}
	}
	else {
		update_option($opt_counter_name, 0);
		$will_update_status = true;
	}

	// set based of will_update_status
	if($will_update_status || $force_update) {
		update_option('nusantara_ongkir_license_status', $license_status);
	}
}

// get global $nusantara_helper 
global $nusantara_helper;

/**
 * Init Tonjoo License
 */
$license = new tonjoo_license();
$license->getJsonLocal(array(
	'plugin_name' => 'wooongkir-premium',
	'file' => __FILE__,
	'key' => get_option('nusantara_ongkir_lisensi', false),
	'status' => array(
		'status' => $nusantara_helper->is_license_active()
	)
));

/**
 * Tonjoo TGM Teman Plugin
 */
require_once( __DIR__ . '/teman-plugin.php');
if($nusantara_helper->is_license_active()) {
	$tonjoo_recomended = array(
		'plugin-resi' => array(
			'name' => 'Plugin Resi',
			'url' => 'https://tonjoostudio.com/product/plugin-kirim-resi',
		)
	);

	// run the notifications
	new TemanPluginTonjoo('Plugin Ongkos Kirim', NUSANTARA_ONGKIR, $tonjoo_recomended);
}


// debugger
if(! function_exists('pre')):
function pre($val) {
	echo "<pre>";
	print_r($val);
	echo "</pre>";
}
endif;