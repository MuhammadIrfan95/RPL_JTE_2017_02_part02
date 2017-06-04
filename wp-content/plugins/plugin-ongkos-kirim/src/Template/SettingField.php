<tr>
	<th scope="row" class="titledesc">
		<label for="woocommerce_wc_tonjoo_shipping_base_server"><?php echo __('Base Server',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="indonesia" name="base_server" value="indonesia" <?php echo $config['base_server'] == 'indonesia' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="indonesia">
					<span>Indonesia</span>
				</label>
				<input type="radio" id="international" name="base_server"  value="international" <?php echo $config['base_server'] == 'international' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="international">
					<span>International</span>
				</label>
			</p>
		</fieldset>	
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text">
			<span>
				<?php echo __('Location of your web server/hoster',NUSANTARA_ONGKIR)?>
			</span>
		</legend>
	</td>
</tr>
<tr>
	<th scope="row" class="titledesc">
		<label for="woocommerce_wc_tonjoo_shipping_nusantara_ongkir_lisensi"><?php echo __('License Plugin Ongkos Kirim',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<input type="hidden" name="ongkir_lisensi" value="<?php echo $config['ongkir_lisensi'] ?>">
			<input type="password" id="tj_woo_onkir_license_input" style="max-width:350px;width:100%;" value="<?php echo $config['ongkir_lisensi'] ?>"/>
			<?php if($config['ongkir_license_status'][0] === true) : ?>
				<p class="license-response">
					<span class="active"><?php echo $config['ongkir_license_status'][1] ?></span>
					<button type="button" class="button" id="deactive-license">Deactive License</button>
				</p>
			<?php else : ?>
				<p class="license-response">
					<span><?php echo $config['ongkir_license_status'][1]; ?></span>
					<button type="button" class="button-primary" id="activate-license">Activate</button>
				</p>
			<?php endif; ?>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text">
			<span>
				<?php echo __('Plugin Ongkos Kirim License. <br>Check your license here <a target="_blank" href="https://tonjoostudio.com/manage/plugin/">https://tonjoostudio.com/manage/plugin/</a>',NUSANTARA_ONGKIR)?>
			</span>
		</legend>
	</td>
</tr>
<tr>
	<th scope="row" class="titledesc">
		<label for="woocommerce_wc_tonjoo_shipping_base_api"><?php echo __('API Ongkos Kirim',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="cb-nusantara" name="base_api" value="nusantara" <?php echo $config['base_api'] == 'nusantara' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-nusantara">
					<span>Plugin Ongkos Kirim</span>
				</label>
				<input type="radio" id="cb-rajaongkir" name="base_api"  value="rajaongkir" <?php echo $config['base_api'] == 'rajaongkir' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-rajaongkir">
					<span>Raja Ongkir</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text">
			<span>
				<?php echo __('Use our default premium API, or Rajaongkir API',NUSANTARA_ONGKIR)?>
			</span>
		</legend>
	</td>
</tr>

<tr class="api-key-raja-ongkir <?php echo $config['base_api'] == "rajaongkir" ? "show" : "" ?>">
	<th scope="row" class="titledesc">
		<label for="woocommerce_wc_tonjoo_shipping_base_shipping"><?php echo __('Type API Raja Ongkir',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="cb-starter" name="raja_ongkir_type" value="starter" <?php echo $config['raja_ongkir_type'] == 'starter' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-starter">
					<span>Starter</span>
				</label>
				<input type="radio" id="cb-basic" name="raja_ongkir_type" value="basic" <?php echo $config['raja_ongkir_type'] == 'basic' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-basic">
					<span>Basic</span>
				</label>
				<input type="radio" id="cb-pro" name="raja_ongkir_type" value="pro" <?php echo $config['raja_ongkir_type'] == 'pro' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-pro">
					<span>Pro</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text">
			<span>
				<?php echo __('Type API Raja Ongkir',NUSANTARA_ONGKIR)?>
			</span>
		</legend>
	</td>
</tr>
<tr valign="top" class="api-key-raja-ongkir <?php echo $config['base_api'] == "rajaongkir" ? "show" : "" ?>">
	<th scope="row" class="titledesc">
		<label for="woocommerce_wc_tonjoo_shipping_nusantara_api_key_raja_ongkir"><?php echo __('API Key Raja Ongkir',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<input type="text" id="rajaongkir_api_key" value="<?php echo $config['api_key_raja_ongkir']?>" style="width:350px" />
			<?php if($config['rajaongkir_key_status'][0] === true) : ?>
				<p class="api-key-response">
					<span class="active"><?php echo $config['rajaongkir_key_status'][1] ?></span>
					<button type="button" class="button" id="set-api-key">Change API Key</button>
				</p>
			<?php else : ?>
				<p class="api-key-response">
					<span><?php echo $config['rajaongkir_key_status'][1]; ?></span>
					<button type="button" class="button button-primary" id="set-api-key">Set API Key</button>
				</p>
			<?php endif; ?>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text">
			<span>
				<?php echo __('API Key Raja Ongkir. <br>Check your API key here <a target="_blank" href="http://rajaongkir.com/akun/panel">http://rajaongkir.com/akun/panel</a>',NUSANTARA_ONGKIR)?>
			</span>
		</legend>
	</td>
</tr>

<?php //var_dump($this->helper->is_admin_active());die; ?>
<?php if ( $this->helper->is_admin_active() ): ?>

<tr>
	<th scope="row" class="titledesc">
		<label for="woocommerce_wc_tonjoo_shipping_base_shipping"><?php echo __('Select Couriers', NUSANTARA_ONGKIR);?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text"><span><?php echo __('Courier Type');?></span></legend>
			<select id="courir_type" multiple="multiple" class="multiselect chosen_select" name="courir_type[]" >
				<?php
				foreach ($data['couriers'] as $key => $value) {
					if (in_array($key, array('expedito'))) {
						$value = $value." (*)";
					}
					?>
					<option value="<?php echo $key;?>" <?php echo in_array($key, $config['courir_type']) ? 'selected="selected"' : ''?>><?php echo $value;?></option>
					<?php
				}
				?>
			</select>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo ($config['base_api'] == "rajaongkir" && $config['raja_ongkir_type'] != 'starter') ? __('Courier Type.<br>(*) = International shipping only.',NUSANTARA_ONGKIR) : __('Courier Type.',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr valign="top"  id="store-first">
	<th scope="row" class="titledesc">
		<label for="woocommerce_wc_tonjoo_shipping_base_shipping"><?php echo __('Store Location',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp" id="table-storelocation">
		<fieldset style="width:350px">
			<legend class="screen-reader-text"><span><?php echo __('Location of your store. You can insert multiple location')?></span></legend>
			<?php if ($config['base_api'] == 'nusantara') : ?>
				<input id="storelocation" name="store_location" size="30">
			<?php else: ?>
				<select id="store_location" class="chosen_select" name="store_location[]" >
					<option value=""><?php _e('Select Store Location', NUSANTARA_ONGKIR) ?></option>
					<?php if (!empty($data['all_city'])) : ?>
						<?php foreach ($data['all_city'] as $c) : ?>
							<option value="<?php echo $c->city_id ?>" <?php echo is_array($config['store_location']) && in_array($c->city_id, $config['store_location']) ? 'selected' : ''; ?>><?php echo $c->type != "Kabupaten" ? $c->type." " : ""; echo $c->city_name ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			<?php endif; ?>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Store Location',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced-options-toggle">
	<td colspan="3">
		<div class="toggle">
			<input type="radio" id="cb-show-advanced-options" name="show_advanced_options" value="show" <?php echo $config['show_advanced_options'] == 'show' ? 'checked="checked"' : ''; ?> style="display:none" />
			<label for="cb-show-advanced-options">
				<h3><?php _e('Show Advanced Options', NUSANTARA_ONGKIR) ?> <span class="dashicons dashicons-arrow-down-alt2"></span></h3>
			</label>
			<input type="radio" id="cb-hide-advanced-options" name="show_advanced_options" value="hide" <?php echo $config['show_advanced_options'] == 'hide' ? 'checked="checked"' : ''; ?> style="display:none" />
			<label for="cb-hide-advanced-options">
				<h3><?php _e('Hide Advanced Options', NUSANTARA_ONGKIR) ?> <span class="dashicons dashicons-arrow-up-alt2"></span></h3>
			</label>
		</div>
	</td>
</tr>

<?php if ($config['base_api'] == "rajaongkir" && $config['raja_ongkir_type'] != 'starter') : ?>
<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Enable International Shipping',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="cb-international-shipping-yes" name="international_shipping" value="yes" <?php echo $config['international_shipping'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-international-shipping-yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="cb-international-shipping-no" name="international_shipping" value="no" <?php echo $config['international_shipping'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-international-shipping-no">
					<span>No</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Show international shipping costs',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>
<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Show Long Description on Checkout',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="cb-show_long_description-yes" name="show_long_description" value="yes" <?php echo $config['show_long_description'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-show_long_description-yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="cb-show_long_description-no" name="show_long_description" value="no" <?php echo $config['show_long_description'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-show_long_description-no">
					<span>No</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Show long description for each courier service. Example: <strong>JNE - REG</strong> becomes <strong>JNE - REG (Layanan Reguler)</strong>',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>
<?php endif; ?>

<?php if ($config['base_api'] == "nusantara") : ?>
<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Filter Courier Package Type',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="cb-specific-courier-yes" name="is_specific_courir_type" value="yes" <?php echo $config['is_specific_courir_type'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-specific-courier-yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="cb-specific-courier-no" name="is_specific_courir_type" value="no" <?php echo $config['is_specific_courir_type'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-specific-courier-no">
					<span>No</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Specific Courier Package Type',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced toggle1">
	<th scope="row" class="titledesc">
		<label for="specific_courir_type"><?php echo __('Filter Couriers Type');?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text"><span><?php echo __('You can filter certain courier package type, based on their services');?></span></legend>
			<select id="specific_courir_type" multiple="multiple" class="multiselect chosen_select" name="specific_courir_type[]" >
				<?php 
				foreach ($data['courier_package'] as $p) {
					if( in_array($p, $config['specific_courir_type'])) {
						$selected = 'selected="selected"';
					} else {
						$selected = '';					
					}
					?>
					<option value="<?php echo $p;?>" <?php echo $selected;?>><?php echo $p;?></option>
					<?php
				}
				?>
			</select>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Select Specific Courier Type', NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced toggle1">
	<th scope="row" class="titledesc">
		<label><?php echo __('Secondary Courier',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="cb-specific-alternative-courier-yes" name="alternatif_specific_curier" value="yes" <?php echo $config['alternatif_specific_curier'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-specific-alternative-courier-yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="cb-specific-alternative-courier-no" name="alternatif_specific_curier" value="no" <?php echo $config['alternatif_specific_curier'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="cb-specific-alternative-courier-no">
					<span>No</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('If the prefered courier is not found, secondary courier will be use',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced toggle1 toggle2">
	<th scope="row" class="titledesc">
		<label for="alternatif_courier_package"><?php echo __('Courier', NUSANTARA_ONGKIR);?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text"><span><?php echo __('You can filter certain courier package type, based on their services');?></span></legend>
			<select id="alternatif_courier_package" multiple="multiple" class="multiselect chosen_select" name="alternatif_courier_package[]" >
				<?php 
				foreach ($data['courier_package'] as $p) {
					if( in_array($p, $config['alternatif_courier_package'])) {
						$selected = 'selected="selected"';
					} else {
						$selected = '';					
					}
					?>
					<option value="<?php echo $p;?>" <?php echo $selected;?>><?php echo $p;?></option>
					<?php
				}
				?>
			</select>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Courier',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label for="round_shipping_weight"><?php echo __('Round Shipping Weight', NUSANTARA_ONGKIR);?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<select id="round_shipping_weight" name="round_shipping_weight" style="width:350px;">
				<option value="auto" <?php echo $config['round_shipping_weight'] == 'auto' ? 'selected' : '' ?>><?php _e('Auto', NUSANTARA_ONGKIR) ?></option>
				<option value="ceil" <?php echo $config['round_shipping_weight'] == 'ceil' ? 'selected' : '' ?>><?php _e('Ceil', NUSANTARA_ONGKIR) ?></option>
				<option value="floor" <?php echo $config['round_shipping_weight'] == 'floor' ? 'selected' : '' ?>><?php _e('Floor', NUSANTARA_ONGKIR) ?></option>
			</select>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('How shipping weight will be rounded',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced toggle5">
	<th scope="row" class="titledesc">
		<label for="round_shipping_weight_tolerance"><?php echo __('Shipping Weight Rounding Limit (gram)', NUSANTARA_ONGKIR);?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<input id="round_shipping_weight_tolerance" name="round_shipping_weight_tolerance" type="number" value="<?php echo $config['round_shipping_weight_tolerance'] ?>" min="0" max="1000" style="width:350px;">
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('If shipping weight is less equal to the limit, it will rounding down.<br>Otherwise, it will be rounding up.',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>
<?php endif; ?>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Show Shipping Weight',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="shipping_cost_by_kg_yes" name="shipping_cost_by_kg" value="yes" <?php echo $config['shipping_cost_by_kg'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="shipping_cost_by_kg_yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="shipping_cost_by_kg_no" name="shipping_cost_by_kg" value="no" <?php echo $config['shipping_cost_by_kg'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="shipping_cost_by_kg_no">
					<span>No</span>
				</label>
			</p>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Show shipping weight on checkout page',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Transaction With Unique Number',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="unique_number_yes" name="with_unique_number" value="yes" <?php echo $config['with_unique_number'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="unique_number_yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="unique_number_no" name="with_unique_number" value="no" <?php echo $config['with_unique_number'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="unique_number_no">
					<span>No</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Unique number is to easily differ one order from another',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced toggle3">
	<th scope="row" class="titledesc">
		<label><?php echo __('Transaction Unique Number Length',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<select name="unique_number" style="width:350px">
				<?php 
				$angka_unique = array(1,2,3,4,5);
				foreach ($angka_unique as $a) {
					?>
					<option <?php echo $config['unique_number'] == $a ? "selected" : ""; ?> value="<?php echo $a ?>"><?php echo $a ?></option>
					<?php
				}
				?>
			</select>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Length of your Unique Number Transaction',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Enable Additional Shipping Cost',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="markup_fee_yes" name="with_ongkos_kirim_tambahan" value="yes" <?php echo $config['with_ongkos_kirim_tambahan'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="markup_fee_yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="markup_fee_no" name="with_ongkos_kirim_tambahan" value="no" <?php echo $config['with_ongkos_kirim_tambahan'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="markup_fee_no">
					<span>No</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('You can mark-up/mark-down your shipping cost based on your need.',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced toggle4">
	<th scope="row" class="titledesc">
		<label><?php echo __('Additional Shipping Cost',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<input type="number" value="<?php echo $config['ongkos_kirim_tambahan'] ?>" name="ongkos_kirim_tambahan" style="width:350px" />
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Additional/Reduction Shipping Price here. (insert negative value for reduction)',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Transient Request Interval (Hour)',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<select style="width:350px" name="transient_request_interval" >
				<?php for ($i=6; $i <= 72; $i+=6) { ?>
					<option <?php echo $config['transient_request_interval'] == $i ? "selected" : ""; ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
				<?php } ?>
			</select>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Transient Request Interval is a feature that keep your shipping cost data as a stored cache. This feature will significally increase your website speed',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Default Shipping Weight',NUSANTARA_ONGKIR)?> (<?php echo get_option('woocommerce_weight_unit')?>)</label>
	</th>
	<td class="forminp">
		<fieldset>
			<select style="width:350px" name="default_berat_shipping" >
				<?php 
				global $nusantara_helper;
				for ($i=1000; $i >= 125; $i-=125) {
					$weight = $i / 1000;
					$weight = $weight/$nusantara_helper->weightConvert(1);
					?>
					<option <?php echo $config['default_berat_shipping'] == $weight ? "selected" : ""; ?> value="<?php echo $weight; ?>"><?php echo $weight; ?></option>
					<?php
				}
				?>
			</select>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Default Shipping Weight',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Save Returned User Information',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<p class="field switch">
				<input type="radio" id="save_returned_user_information_yes" name="save_returned_user_information" value="yes" <?php echo $config['save_returned_user_information'] == 'yes' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="save_returned_user_information_yes">
					<span>Yes</span>
				</label>
				<input type="radio" id="save_returned_user_information_no" name="save_returned_user_information" value="no" <?php echo $config['save_returned_user_information'] == 'no' ? 'checked="checked"' : ''; ?> style="display:none" />
				<label for="save_returned_user_information_no">
					<span>No</span>
				</label>
			</p>	
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Auto-fill checkout field with saved address if customer is a returning user.',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Flush Cache',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'), 'pok-flush-cache', '_pok_do') ?>" class="button button-primary" onclick="return confirm('<?php _e('Are you sure?', NUSANTARA_ONGKIR) ?>')" style="background-color: #a00; text-shadow: none; border-color: #800; box-shadow:0 2px 0 #800">Flush Cache</a>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Delete all cached data', NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>
<tr class="advanced">
	<th scope="row" class="titledesc">
		<label><?php echo __('Reset Configuration',NUSANTARA_ONGKIR)?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'), 'pok-reset', '_pok_do') ?>" class="button button-primary" onclick="return confirm('<?php _e('Are you sure? By doing this, you need to reactivate plugin license.', NUSANTARA_ONGKIR) ?>')" style="background-color: #a00; text-shadow: none; border-color: #800; box-shadow:0 2px 0 #800">Reset</a>
		</fieldset>
	</td>
	<td class="legend">
		<legend class="nusantara-legend-text"><span><?php echo __('Delete all saved configuration just like fresh install.',NUSANTARA_ONGKIR)?></span></legend>
	</td>
</tr>

<?php if(NUSANTARA_ONGKIR_DEBUG): ?>
<span id="pok-debug-hit-server">
	<tr class="advanced">
		<th scope="row" class="titledesc">
			<label><?php echo __('Check License Status',NUSANTARA_ONGKIR)?></label>
		</th>
		<td class="forminp">
			<fieldset>
				<a href="javascript:;" id="pok-hit-server" class="button button-primary">Hit Server</a>
			</fieldset>
		</td>
		<td class="legend">
		<legend class="nusantara-legend-text"><span>This will demonstrate the cron using key directly from the license key field</span></legend>
	</td>
	</tr>
	<tr class="advanced">
		<td class="titledesc" id="pok-hit-server-output" colspan="3"></td>
	</tr>
</span>
<?php endif ?>

<?php
endif;
$loc = array();
if(!empty($config['store_location']) && is_array($config['store_location']) && $this->helper->is_admin_active()) {
	foreach ($config['store_location'] as $l) {
		if (!empty($l)) {
			$single_location = $nusantara_core->getSingleCity($l);
			if ($single_location) {
				$loc[] = array('id'=>$l,'name'=>$single_location->nama);
			}
		}
	};	
}
?>

<script>
	jQuery(function($) {
		var base_api = '<?php echo $config['base_api']; ?>';
		if (base_api == "rajaongkir") {
			$(".api-key-raja-ongkir").addClass('show');
		} else {
			$(".api-key-raja-ongkir").removeClass('show');
		}
		// $('input[name="base_api"]').on('change', function() {
		// 	var value = $(this).val();
		// 	if (value == "rajaongkir") {
		// 		$(".api-key-raja-ongkir").addClass('show');2ec902f2ad951cc745285f7dabb4444f
		// 	} else {
		// 		$(".api-key-raja-ongkir").removeClass('show');3B0KGI4FON2ZLUTJEQ5AYCVA16SMP7
		// 	}
		// });

		$('input[name="raja_ongkir_type"]').on('click', function() {
			var value = $(this).val();
			if (value != "<?php echo $config["raja_ongkir_type"]; ?>" && "<?php echo $config['rajaongkir_key_status'][0] ?>" === "1") {
				window.alert('<?php _e('You are switching RajaOngkir API Type. Consider to click "Change API Key" button again to make sure this change happen.', NUSANTARA_ONGKIR) ?>');
				$('#set-api-key').addClass('button-primary');
			} else {
				if (<?php echo json_encode(get_option('nusantara_api_key_raja_ongkir') !== false) ?>) {
					$('#set-api-key').removeClass('button-primary');
				}
			}
		});

		$('input[name="base_api"]').on('click', function() {
			var value = $(this).val();
			var is_admin_active = <?php echo json_encode($this->helper->is_admin_active()); ?>;
			if (is_admin_active) {
				if (value != "<?php echo $config["base_api"]; ?>") {
					var confirm = window.confirm('<?php _e('Are you sure? Switching base API will delete all cached data and custom shipping costs. \nAlso, you might need to re-set your store location.', NUSANTARA_ONGKIR) ?>');
					if (confirm) {
						var target_url = "<?php echo wp_nonce_url(admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim&base_api='.$config["base_api"]), 'pok-change-base-api', '_pok_do'); ?>";
						window.location = target_url.replace( /&amp;/g, '&');
					} else {
						return false;
					}
				}
			} else {
				if (value == 'rajaongkir') {
					$('.api-key-raja-ongkir').addClass('show');
				} else {
					$('.api-key-raja-ongkir').removeClass('show');
				}
			}
		});

		// license activation
		$('#activate-license').on('click', function() {
			var license_key = $('#tj_woo_onkir_license_input').val();
			if (license_key == "") {
				$('.license-response span').text('<?php _e("License key is empty", NUSANTARA_ONGKIR) ?>');
				return;
			}
			$('.license-response span').addClass('loading').text('<?php _e("checking server...", NUSANTARA_ONGKIR) ?>');
			$(this).prop('disabled',true);
			$.ajax({
				url: "<?php echo admin_url('admin-ajax.php')?>",
				type: "POST",
				data: {
					action: "pok_activate_license",
					license_key: license_key
				},
				context: this,
				success: function(res) {
					if (res == "success") {
						location.reload(true);
					} else if (res == "") {
						$('.license-response span').removeClass('loading').text('<?php _e("can not connect server", NUSANTARA_ONGKIR) ?>');
						$(this).prop('disabled',false);
					} else {
						$('.license-response span').removeClass('loading').text(res);
						$(this).prop('disabled',false);
					}
				},
				error: function(err) {
					$('.license-response span').removeClass('loading').text('<?php _e("can not connect server", NUSANTARA_ONGKIR) ?>');
					$(this).prop('disabled',false);
				}
			});
		});
		$('#deactive-license').on('click', function() {
			$('.license-response span').removeClass('active').addClass('loading').text('<?php _e("checking server...", NUSANTARA_ONGKIR) ?>');
			$(this).prop('disabled',true);
			$.ajax({
				url: "<?php echo admin_url('admin-ajax.php')?>",
				type: "POST",
				data: {
					action: "pok_deactive_license"
				},
				context: this,
				success: function(res) {
					if (res == "success") {
						location.reload(true);
					} else if (res == "") {
						$('.license-response span').removeClass('loading').text('<?php _e("can not connect server", NUSANTARA_ONGKIR) ?>');
						$(this).prop('disabled',false);
					} else {
						$('.license-response span').removeClass('loading').text(res);
						$(this).prop('disabled',false);
					}
				},
				error: function(err) {
					$('.license-response span').removeClass('loading').text('<?php _e("can not connect server", NUSANTARA_ONGKIR) ?>');
					$(this).prop('disabled',false);
				}
			});
		});

		// rajaongkir api key activation
		$('#set-api-key').on('click', function() {
			var api_type = $('input[name="raja_ongkir_type"]:checked').val();
			var api_key = $('#rajaongkir_api_key').val();
			if (api_key == "") {
				$('.api-key-response span').text('<?php _e("API key is empty", NUSANTARA_ONGKIR) ?>');
				return;
			}
			$('.api-key-response span').removeClass('active').addClass('loading').text('<?php _e("checking server...", NUSANTARA_ONGKIR) ?>');
			$(this).prop('disabled',true);
			$.ajax({
				url: "<?php echo admin_url('admin-ajax.php')?>",
				type: "POST",
				data: {
					action: "pok_set_rajaongkir_api_key",
					api_key: api_key,
					api_type: api_type
				},
				context: this,
				success: function(res) {
					if (res == "success") {
						location.reload(true);
					} else if (res == "") {
						$('.api-key-response span').removeClass('loading').text('<?php _e("can not connect server", NUSANTARA_ONGKIR) ?>');
						$(this).prop('disabled',false);
					} else {
						$('.api-key-response span').removeClass('loading').text(res);
						$(this).prop('disabled',false);
					}
				},
				error: function(err, err1) {
					$('.api-key-response span').removeClass('loading').text('<?php _e("can not connect server", NUSANTARA_ONGKIR) ?>');
					$(this).prop('disabled',false);
				}
			});
		});

		// hit server
		$('#pok-hit-server').on('click', function() {
			var license_key = $('#tj_woo_onkir_license_input').val();
			if (license_key == "") {
				$('#pok-hit-server-output').text('<?php _e("License key is empty", NUSANTARA_ONGKIR) ?>');
				return;
			}
			$('#pok-hit-server-output').addClass('loading').text('<?php _e("checking server...", NUSANTARA_ONGKIR) ?>');
			$(this).prop('disabled',true);
			$.ajax({
				url: "<?php echo admin_url('admin-ajax.php')?>",
				type: "POST",
				data: {
					action: "pok_hit_server",
					license_key: license_key
				},
				context: this,
				success: function(res) {
					$('#pok-hit-server-output').html(res);
				},
				error: function(err) {
					$('#pok-hit-server-output').text('<?php _e("can not connect server", NUSANTARA_ONGKIR) ?>');
					$(this).prop('disabled',false);
				}
			});
		});

		var storeLocAwal = <?php echo json_encode($loc);?>;
		$("#storelocation").tokenInput([], {
		    placeholder: 'Select Base Location Shipping',
			minChars: 3,
		    prePopulate: storeLocAwal,
		    tokenLimit: 1
		});

		$('#token-input-storelocation').keyup(function(){
			var search = $(this).val();
			var embuh = '';
			var action =  'pok_search_city';
			if(search.length >= 3) {
				var dataStore = $.ajax({
				  url: "<?php echo admin_url('admin-ajax.php')?>",
				  type: "POST",
				  data: {action : action,search : search},
				  dataType:'json',
				  cache: false,
				  async: false, 
				  success: function(arr){
				  	var selectList = '';
				  	var arr1 = [];
  					var opt = [];
				  	$.each(arr, function(key,value) {
						var data = {};

				        data.name = value.nama;
				        data.id = value.id;
				  		arr1.push(data);

					});

					embuh = arr1;
				  }	
				});
				
				$("#storelocation").tokenInput(embuh);
				$('#table-storelocation .token-input-list:last').remove();
			}
		});

		var show_advanced = '<?php echo $config['show_advanced_options']; ?>';
		if (show_advanced == "show") {
			$("tr.advanced").addClass('show');
		} else {
			$("tr.advanced").removeClass('show');
		}
		$('input[name="show_advanced_options"]').on('change', function() {
			var show_advanced = $(this).val();
			if (show_advanced == "show") {
			$("tr.advanced").addClass('show');
			} else {
				$("tr.advanced").removeClass('show');
			}
		});

		var show_toggle1 = '<?php echo $config['is_specific_courir_type']; ?>';
		if (show_toggle1 == "yes") {
			$("tr.advanced.toggle1:not(.toggle2)").addClass('toggle-show');
		} else {
			$("tr.advanced.toggle1").removeClass('toggle-show');
		}
		$('input[name="is_specific_courir_type"]').on('change', function() {
			var show_toggle1 = $(this).val();
			if (show_toggle1 == "yes") {
				$("tr.advanced.toggle1:not(.toggle2)").addClass('toggle-show');
			} else {
				$("tr.advanced.toggle1").removeClass('toggle-show');
			}
		});

		var show_toggle2 = '<?php echo $config['alternatif_specific_curier']; ?>';
		if (show_toggle2 == "yes" && show_toggle1 == "yes") {
			$("tr.advanced.toggle1.toggle2").addClass('toggle-show');
		} else {
			$("tr.advanced.toggle1.toggle2").removeClass('toggle-show');
		}
		$('input[name="alternatif_specific_curier"]').on('change', function() {
			var show_toggle2 = $(this).val();
			if (show_toggle2 == "yes") {
				$("tr.advanced.toggle1.toggle2").addClass('toggle-show');
			} else {
				$("tr.advanced.toggle1.toggle2").removeClass('toggle-show');
			}
		});

		var show_toggle3 = '<?php echo $config['with_unique_number']; ?>';
		if (show_toggle3 == "yes") {
			$("tr.advanced.toggle3").addClass('toggle-show');
		} else {
			$("tr.advanced.toggle3").removeClass('toggle-show');
		}
		$('input[name="with_unique_number"]').on('change', function() {
			var show_toggle3 = $(this).val();
			if (show_toggle3 == "yes") {
				$("tr.advanced.toggle3").addClass('toggle-show');
			} else {
				$("tr.advanced.toggle3").removeClass('toggle-show');
			}
		});

		var show_toggle4 = '<?php echo $config['with_ongkos_kirim_tambahan']; ?>';
		if (show_toggle4 == "yes") {
			$("tr.advanced.toggle4").addClass('toggle-show');
		} else {
			$("tr.advanced.toggle4").removeClass('toggle-show');
		}
		$('input[name="with_ongkos_kirim_tambahan"]').on('change', function() {
			var show_toggle4 = $(this).val();
			if (show_toggle4 == "yes") {
				$("tr.advanced.toggle4").addClass('toggle-show');
			} else {
				$("tr.advanced.toggle4").removeClass('toggle-show');
			}
		});

		var show_toggle5 = '<?php echo $config['round_shipping_weight']; ?>';
		if (show_toggle5 == "auto") {
			$("tr.advanced.toggle5").addClass('toggle-show');
		} else {
			$("tr.advanced.toggle5").removeClass('toggle-show');
		}
		$('#round_shipping_weight').on('change', function() {
			var show_toggle5 = $(this).val();
			if (show_toggle5 == "auto") {
				$("tr.advanced.toggle5").addClass('toggle-show');
			} else {
				$("tr.advanced.toggle5").removeClass('toggle-show');
			}
		});

	});
</script>