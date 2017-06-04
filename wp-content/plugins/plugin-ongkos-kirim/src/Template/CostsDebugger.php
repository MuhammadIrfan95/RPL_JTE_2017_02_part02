<table>
	<tr>
		<td>
			<label>Base API :</label>
		</td>
		<td>
			<strong>
				<?php echo ucfirst($config['base_api']) ?>
				<?php echo $config['base_api'] == 'rajaongkir' ? ucfirst($config['raja_ongkir_type']) : '' ?>
			</strong>
		</td>
	</tr>
	<tr>
		<td>
			<label>Kota Asal :</label>
		</td>
		<td>
			<strong style="font-size:0;">
				<?php
				$loc = 'Unknown';
				foreach ($config['store_location'] as $l) {
					if (!empty($l)) {
						$single_location = $nusantara_core->getSingleCity($l);
						$loc = $single_location->nama;
					}
				};
				?>
				<span style="font-size:14px;"><?php echo $loc; ?></span>
			</strong>
		</td>
	</tr>
	<tr>
		<td>
			<label>Berat (kg) :</label>
		</td>
		<td>
			<input type="number" step="0.1" name="costs_debugger_weight" value="<?php echo isset($args['weight']) ? $args['weight'] : 1 ?>">
		</td>
	</tr>
	<tr>
		<td>
			<label>Tujuan Provinsi :</label>
		</td>
		<td>
			<select name="costs_debugger_province" id="selectprovince">
				<option value="">Pilih Provinsi</option>
				<?php foreach ($data['province'] as $p) : ?>
					<option value="<?php echo $p->id ?>" <?php echo (isset($args) && isset($args['state']) && $args['state'] == $p->id) ? 'selected' : '' ?>><?php echo $p->nama ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label>Tujuan Kota/Kab :</label>
		</td>
		<td>
			<select name="costs_debugger_city" id="selectcity">
				<option value="">Pilih Kota</option>
			</select>
		</td>
	</tr>
	<?php if ($config['base_api'] == 'nusantara' || ($config['base_api'] == 'rajaongkir' && $config['raja_ongkir_type'] == 'pro')) : ?>
		<tr>
			<td>
				<label>Tujuan Kecamatan :</label>
			</td>
			<td>
				<select name="costs_debugger_district" id="selectdistrict">
					<option value="">Pilih Kecamatan</option>
				</select>
			</td>
		</tr>
	<?php endif ?>
	<tr>
		<td><br><button class="button button-primary">Get Response</button></td>
	</tr>
</table>
<?php if (isset($originResult)) : ?>
	<br>
	<table id="result">
		<tr>
			<td>
				<h3>Original Response</h3>
				<pre><?php print_r($originResult) ?></pre>		
			</td>
			<td>
				<h3>Filtered Result</h3>
				<pre><?php print_r($formattedResult) ?></pre>	
			</td>
		</tr>
	</table>
<?php endif; ?>

<style>
	tr td:first-child {
		padding-right: 20px;
	}
	pre {
		background-color: #fff;
		font-size: 9pt;
		line-height: 12px;
		padding: 10px;
	}
	#result td {
		vertical-align: top;
	}
	p.submit {
		display: none;
	}
	tr td strong br {
		display: none;
	}
</style>
<script>
jQuery(function($) {
	$('#selectprovince').on('change',function(){
		var province_id = $(this).val();
		var embuh = '';
		var action =  'pok_get_city_by_province_id';
		$('#selectcity, #selectdistrict').prop('disabled',true);
		$.ajax({
			url: "<?php echo admin_url('admin-ajax.php')?>",
			type: "POST",
			data: {action : action,province_id : province_id},
			dataType:'json',
			cache: false,
			async: false,
			success: function(arr){
			  	var selectList = '';
			  	var arrCity  = '<option value="">Pilih Kota</option>';
			  	$.each(arr, function(key,value) {
					var data = {};
					arrCity += '<option value='+ value.id + '>'+ value.nama +'</option>';
				});
				$('#selectcity, #selectdistrict').prop('disabled',false);
				$('#selectcity').html(arrCity);
				$('#selectdistrict').html('<option value="">Pilih Kecamatan</option>');
				$('#selectcity').on('change',function(){
					var city_id = $(this).val();
					var action =  'pok_get_district_by_province_id';
					$('#selectdistrict').prop('disabled',true);
					$.ajax({
						url: "<?php echo admin_url('admin-ajax.php')?>",
						type: "POST",
						data: {action : action,city_id : city_id},
						dataType:'json',
						cache: false,
						async: false, 
						success: function(arr){
						  	var selectList = '';
						  	var arrDistrict = '<option value="">Pilih Kecamatan</option>';
						  	$.each(arr, function(key,value) {
								var data = {};
								arrDistrict += '<option value='+ value.id + '>'+ value.nama +'</option>';
							});
							$('#selectdistrict').html(arrDistrict);
							$('#selectdistrict').prop('disabled',false);
					  	}
					});
				});
			},
			error: function(err) {
				console.log(err);
			}
		});
	});

	var set_value = <?php echo isset($args) ? json_encode($args) : json_encode(false); ?>;
	if (set_value) {
		var province_id = set_value['state'];
		var embuh = '';
		var action =  'pok_get_city_by_province_id';
		$('#selectcity, #selectdistrict').prop('disabled',true);
		$.ajax({
			url: "<?php echo admin_url('admin-ajax.php')?>",
			type: "POST",
			data: {action : action,province_id : province_id},
			dataType:'json',
			cache: false,
			async: false,
			success: function(arr){
			  	var selectList = '';
			  	var arrCity  = '<option value="">Pilih Kota</option>';
			  	$.each(arr, function(key,value) {
					var data = {};
					arrCity += '<option value='+ value.id + '>'+ value.nama +'</option>';
				});
				$('#selectcity, #selectdistrict').prop('disabled',false);
				$('#selectcity').html(arrCity);
				$('#selectdistrict').html('<option value="">Pilih Kecamatan</option>');
				if (set_value['city']) {
					$('#selectcity').val(set_value['city']);
					var city_id = set_value['city'];
					var action =  'pok_get_district_by_province_id';
					$('#selectdistrict').prop('disabled',true);
					$.ajax({
						url: "<?php echo admin_url('admin-ajax.php')?>",
						type: "POST",
						data: {action : action,city_id : city_id},
						dataType:'json',
						cache: false,
						async: false, 
						success: function(arr){
						  	var selectList = '';
						  	var arrDistrict = '<option value="">Pilih Kecamatan</option>';
						  	$.each(arr, function(key,value) {
								var data = {};
								arrDistrict += '<option value='+ value.id + '>'+ value.nama +'</option>';
							});
							$('#selectdistrict').html(arrDistrict).val(set_value['district']);
							$('#selectdistrict').prop('disabled',false);
					  	}
					});
				};
			},
			error: function(err) {
				console.log(err);
			}
		});
	}
});
</script>