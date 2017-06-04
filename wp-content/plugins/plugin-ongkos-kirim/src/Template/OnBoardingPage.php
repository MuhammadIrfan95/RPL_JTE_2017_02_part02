<?php $redirect = admin_url('admin.php?page=wc-settings&tab=shipping&section=plugin_ongkos_kirim'); ?>
<style type="text/css">
	body{
	font-family: 'Open Sans', sans-serif;
	font-size: 16px;
	line-height: 1.5;
	color: #232323;
	background-color: #fff;
	}
</style>
<div id="wizard-box" class="center wrap">
	<div class="ok-row">
		<div class="col-ok-6">
			<h3 class="title">Terimakasih</h3>
			<p>telah membeli <strong>Plugin Ongkos Kirim</strong> !</p>
			<img src="<?php echo plugin_dir_url(__FILE__)?>../../assets/plugin_ongkos_kirim.png">
			<p class="btn-box">
			<a href="<?php echo $redirect;?>" class="btn btn-blue">Mulai Konfigurasi</a>

		</div>
		<div class="col-ok-6">
			<h3 class="title">Tutorial Plugin Ongkos Kirim</h3>
			<p>&nbsp;</p>
			<iframe css="display:block;margin:0px auto;max-height:300px" width="100%" height="300px" src="https://www.youtube.com/embed/209vbcNJHnw" frameborder="0" allowfullscreen=""></iframe>

		</div>
	</div>

	<div class="ok-row">
		<div class="col-ok-12">
			<ul class="row-bawah">
				<li>
					<div class="more-content">
						<p>Dapatkan lisensi Tonjoo *</p>
						<p>(untuk aktivasi Plugin)</p>
					</div>
					<p><a href="https://tonjoostudio.com/manage/ajax/directBuy?product_id=4399&cur=idr" class="btn btn-orange-welcome" target="_blank">Dapatkan Lisensi</a></p>
				</li>
				<li>
					<div class="more-content">
						<p>Lisensi Raja Ongkir</p>
						<p>Gunakan API Raja Ongkir untuk data ongkir yang 100% update.</p>
						<p><strong>Basic</strong> = data pengiriman sampai kabupaten.</p>
						<p><strong>Pro</strong> = data pengiriman sampai kecamatan.</p>
					</div>
					<p><a href="http://rajaongkir.com/akun/panel" class="btn btn-orange-welcome" target="_blank">Dapatkan API Key</a></p>
				</li>
				<li>
					<div class="more-content">
						<p>Ingin mengenal konfigurasi plugin ongkos kirim secara detail ?</p>
						<p>&nbsp</p>
					</div>
					<a href="http://pustaka.tonjoostudio.com/plugins/woo-ongkir-tutorial/" class="btn btn-green-welcome">Baca Petunjuk</a></p>						
				</li>
				<li>
					<div class="more-content">
						<p>Punya masalah dengan <strong>Plugin Ongkos Kirim</strong> ?</p>
						<p>atau ingin melaporkan data tidak akurat?</p>
					</div>
					<p><a href="https://forum.tonjoostudio.com" class="btn btn-purple-welcome">Forum Plugin Ongkos Kirim</a></p>
				</li>
			</ul>
			<p class="pok-notice">
				*) Diperlukan untuk aktivasi plugin dan mendapatkan data tarif ongkir.<br/>Data tarif diupdate semi-otomatis sehingga ada beberapa data tarif yang tidak akurat, pelaporan data ongkir tidak akurat dapat lapor <a href="https://airtable.com/shry2kKGHmFyLLyvR">disini</a>.
			</p>
		</div>
	</div>
</div>