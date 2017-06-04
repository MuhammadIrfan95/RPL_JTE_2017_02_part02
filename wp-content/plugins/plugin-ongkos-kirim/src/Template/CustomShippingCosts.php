<table class="form-table">
	<tr>
		<th scope="row" class="titledesc">
			<label><?php echo __('Custom Courier Name',NUSANTARA_ONGKIR)?></label>
		</th>
		<td class="forminp">
			<fieldset>
				<input type="text" value="<?php echo $config['custom_courier'] ?>" name="custom_courier" style="width:350px" />
			</fieldset>
		</td>
		<td class="legend">
			<legend class="nusantara-legend-text"><span><?php echo __('Your custom courier name',NUSANTARA_ONGKIR)?></span></legend>
		</td>
	</tr>
	<tr>
		<th scope="row" class="titledesc">
			<label><?php echo __('Custom Shipping Cost Type',NUSANTARA_ONGKIR)?></label>
		</th>
		<td class="forminp">
			<fieldset>
				<select style="width:350px" name="custom_ongkir_type">
					<option value="append" <?php echo $config['custom_ongkir_type'] == 'append' ? 'selected' : ''; ?>>Append</option>
					<option value="replace" <?php echo $config['custom_ongkir_type'] == 'replace' ? 'selected' : ''; ?>>Replace</option>
				</select>
			</fieldset>
		</td>
		<td class="legend">
			<legend class="nusantara-legend-text"><span><?php echo __('Append: Add courier option for selected destination inside cost table. <br/>Replace: Courier option for selected destination only use custom courier.', NUSANTARA_ONGKIR)?></span></legend>
		</td>
	</tr>
</table>
<br>
<table class="wc_shipping widefat wp-list-table custom-costs">
	<thead>
		<tr>
			<th style="text-align:center" colspan="3"><?php _e("Destination", NUSANTARA_ONGKIR) ?></th>
			<th style="text-align:center"><?php _e("Courier", NUSANTARA_ONGKIR) ?></th>
			<th style="text-align:center"><?php _e("Service Name", NUSANTARA_ONGKIR) ?></th>
			<th style="text-align:center"><?php _e("Cost per Kg", NUSANTARA_ONGKIR) ?></th>
			<th style="text-align:center"></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if(!empty($config['manual_shipping'])) {
			foreach ($config['manual_shipping'] as $key => $value)  {
				?>
				<tr>
					<td>
						<input type="hidden" value="<?php echo $value['manualselectprovince']?>" name="manual[manualselectprovince][]">
						<input type="hidden" value="<?php echo $value['manualselectprovince_text']?>" name="manual[manualselectprovince_text][]">
						<?php echo $value['manualselectprovince_text']?></td>
					<td>
						<input type="hidden" value="<?php echo $value['manualselectcity']?>" name="manual[manualselectcity][]">
						<input type="hidden" value="<?php echo $value['manualselectcity_text']?>" name="manual[manualselectcity_text][]">
						<?php echo $value['manualselectcity_text']?></td>
					<td>
						<input type="hidden" value="<?php echo $value['manualselectdistrict']?>" name="manual[manualselectdistrict][]">
						<input type="hidden" value="<?php echo $value['manualselectdistrict_text']?>" name="manual[manualselectdistrict_text][]">
						<?php echo $value['manualselectdistrict_text']?></td>
					<td>
						<input type="hidden" value="<?php echo $value['ekspedisi']?>" name="manual[ekspedisi][]"><?php echo $value['ekspedisi'] == 'custom' ? $config['custom_courier'] : $value['ekspedisi'] ?></td>
					<td>
						<input type="hidden" value="<?php echo $value['nunsatara_jenis']?>" name="manual[nunsatara_jenis][]"><?php echo $value['nunsatara_jenis']?></td>
					<td>
						<input type="hidden" value="<?php echo $value['nusantara_tarif']?>" name="manual[nusantara_tarif][]"><?php echo $value['nusantara_tarif']?></td>
					<td style="text-align:center">
						<a class="remove-manual"><?php _e('Delete', NUSANTARA_ONGKIR) ?></a></td>
				</tr>
				<?php
			}
		}
		?>
		<tr id="input-field">
			<td>
				<select id="manualselectprovince" style="max-width: 150px;">
					<option value="*"><?php _e('All Province', NUSANTARA_ONGKIR) ?></option>
					<?php if (!empty($data['province'])) : ?>
						<?php foreach ($data['province'] as $p) : ?>
							<option value="<?php echo $p->id ?>"><?php echo $p->nama ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</td>
			<td>
				<select id="manualselectcity" style="max-width: 150px;">
					<option value="*"><?php _e('All City', NUSANTARA_ONGKIR) ?></option>
				</select>
			</td>
			<td>
				<select id="manualselectdistrict" style="max-width: 150px;">
					<option value="*"><?php _e('All District', NUSANTARA_ONGKIR) ?></option>
				</select>
			</td>
			<td>
				<select id="ekspedisi" style="max-width: 150px;">
					<option value="custom"><?php echo !empty($config['custom_courier']) ? $config['custom_courier'] : 'custom' ?></option>
					<?php if (!empty($config['courir_type'])) : ?>
						<?php foreach ($config['courir_type'] as $c) : ?>
							<option value="<?php echo $c ?>"><?php echo strtoupper($c) ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</td>
			<td>
				<input id="nunsatara_jenis" type="text">
			</td>
			<td>
				<input id="nusantara_tarif" type="number" min="0" value="0" style="max-width: 150px;">
			</td>
			<td>
				<button type="button" class="button button-primary" id="save-change"><?php _e('Add', NUSANTARA_ONGKIR) ?></button>
			</td>
		</tr>
	</tbody>
</table>
<script>
	jQuery(function($) {

		$('#save-change').on('click',function(){

			if($('#manualselectprovince').val() == '') {
				alert('Please select the "Provinsi"');
				return;
			} else if($('#ekspedisi').val() == '') {
				alert('Please select the "Ekspedisi"');
				return;
			}

			var manualselectprovince = $('#manualselectprovince').find(":selected").val();
			var manualselectprovince_text = $('#manualselectprovince').find(":selected").text();
			var manualselectcity = $('#manualselectcity').find(":selected").val();
			var manualselectcity_text = $('#manualselectcity').find(":selected").text();
			var manualselectdistrict = $('#manualselectdistrict').find(":selected").val();
			var manualselectdistrict_text = $('#manualselectdistrict').find(":selected").text();
			var ekspedisi = $('#ekspedisi').find(":selected").val();
			if (ekspedisi == 'custom') {
				var ekspedisi_text = "<?php echo $config['custom_courier']; ?>";
			} else {
				var ekspedisi_text = $('#ekspedisi').find(":selected").text();;
			}		
			var nunsatara_jenis = $('#nunsatara_jenis').val() == '' ? '-' : $('#nunsatara_jenis').val();
			var nusantara_tarif = $('#nusantara_tarif').val() == '' ? '0' : $('#nusantara_tarif').val();
			var html = '<tr>';
				html += '<td><input name="manual[manualselectprovince][]" type="hidden" value="'+ manualselectprovince +'"><input name="manual[manualselectprovince_text][]" type="hidden" value="'+ manualselectprovince_text +'">'+manualselectprovince_text+'</td>';
				html += '<td><input name="manual[manualselectcity][]" type="hidden" value="'+ manualselectcity +'"><input name="manual[manualselectcity_text][]" type="hidden" value="'+ manualselectcity_text +'">'+manualselectcity_text+'</td>';
				html += '<td><input name="manual[manualselectdistrict][]" type="hidden" value="'+ manualselectdistrict +'"><input name="manual[manualselectdistrict_text][]" type="hidden" value="'+ manualselectdistrict_text +'">'+manualselectdistrict_text+'</td>';
				html += '<td><input name="manual[ekspedisi][]" type="hidden" value="'+ ekspedisi +'">'+ekspedisi_text+'</td>';
				html += '<td><input name="manual[nunsatara_jenis][]" type="hidden" value="'+ nunsatara_jenis +'">'+nunsatara_jenis+'</td>';
				html += '<td><input name="manual[nusantara_tarif][]" type="hidden" value="'+ nusantara_tarif +'">'+nusantara_tarif+'</td>';
				html += '<td style="text-align:center"><a class="remove-manual"><?php _e('Delete', NUSANTARA_ONGKIR) ?></a></td>';
				html += '</tr>';
			$('#input-field').before(html);
			$('.remove-manual').on('click',function(){
				$(this).closest('tr').remove();
			})
		});

		$('.remove-manual').on('click',function(){
			$(this).closest('tr').remove();
		});

		$('#manualselectprovince').on('change',function(){
			var province_id = $(this).val();
			var embuh = '';
			var action =  'pok_get_city_by_province_id';
			$('#manualselectcity, #manualselectdistrict').prop('disabled',true);
			$.ajax({
				url: "<?php echo admin_url('admin-ajax.php')?>",
				type: "POST",
				data: {action : action,province_id : province_id},
				dataType:'json',
				cache: false,
				async: false,
				success: function(arr){
				  	var selectList = '';
				  	var arrCity  = '<option value="*"><?php _e('All City', NUSANTARA_ONGKIR) ?></option>';
				  	$.each(arr, function(key,value) {
						var data = {};
						arrCity += '<option value='+ value.id + '>'+ value.nama +'</option>';
					});
					$('#manualselectcity, #manualselectdistrict').prop('disabled',false);
					$('#manualselectcity').html(arrCity);
					$('#manualselectdistrict').html('<option value="*"><?php _e('All District', NUSANTARA_ONGKIR) ?></option>');
					$('#manualselectcity').on('change',function(){
						var city_id = $(this).val();
						var action =  'pok_get_district_by_province_id';
						$('#manualselectdistrict').prop('disabled',true);
						$.ajax({
							url: "<?php echo admin_url('admin-ajax.php')?>",
							type: "POST",
							data: {action : action,city_id : city_id},
							dataType:'json',
							cache: false,
							async: false, 
							success: function(arr){
							  	var selectList = '';
							  	var arrDistrict = '<option value="*"><?php _e('All District', NUSANTARA_ONGKIR) ?></option>';
							  	$.each(arr, function(key,value) {
									var data = {};
									arrDistrict += '<option value='+ value.id + '>'+ value.nama +'</option>';
								});
								$('#manualselectdistrict').html(arrDistrict);
								$('#manualselectdistrict').prop('disabled',false);
						  	}
						});
					});
				},
				error: function(err) {
					console.log(err);
				}
			});
		});
	})
</script>