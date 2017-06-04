=== Plugin Ongkos Kirim ===
Contributors: Todi.Adiatmo, eringga, haristonjoo, Alzea, gamaup
Tags: ongkos kirim, ongkir, jne, tiki, pos
Requires at least: 3.5
Tested up to: 4.7.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Ongkos Kirim Seluruh Indonesia dengan Mudah dan Cepat, Menggunakan Plugin Ongkos Kirim.

== Description ==
Plugin Ongkos Kirim adalah extension WooCommerce yang berfungsi untuk menghitung ongkos kirim seluruh Indonesia (JNE, POS, Tiki, dll). Dengan menggunakan extension ini, anda tidak perlu memasukan data ongkos kirim secara manual, karena plugin sudah ter-integrasi dengan data Rajaongkir.com secara real-time. Cukup aktifkan plugin, masukan API rajaongkir, dan Woocommerce anda siap mengirim ke seluruh Indonesia.


== Features ==

* Premium API Tarif
* Ongkos Kirim Real-Time Seluruh Indonesia
* Mendukung Hingga 18 Jasa Ekspedisi
* Menyatu Sepenuhnya Dengan Woo Commerce
* Fitur Ongkos Kirim Tambahan (Additional Markup Fee)
* Angka Unik Transaksi
* Ongkos Kirim Hingga Kecamatan
* Multiple Currency
* Multiple-Couriers Pada Halaman Checkout
* Mudah di Install & Digunakan
* Multi Bahasa
* Dukungan Teknis Profesional


== Installation ==

1. Beli lisensi yang anda butuhkan (Personal / Developer)
2. Download plugin dari halaman akun
3. Upload file woo-ongkir-premium.zip via wp-admin > Plugins > Add New
4. Aktifasi via wp-admin > Plugin.


== Changelog ==

= 2.0.10 =
* Fixed checkout fields order on Woocommerce 3.x
* Fixed shipping city sometimes not displayed on order details.
* Prevent "Illegal string offset" errors on checkout page
* Adding default field priority to order checkout fields, including filter hook.

= 2.0.9 =
* Major bugfix: Shipping weight not counted properly.

= 2.0.8 =
* Support Woocommerce 3.0
* Bugfix: Custom costs not displayed when courier filter is active
* Minor bugfix

= 2.0.7 =
* Add NCS & First Logistics (rajaongkir)
* New option to show long description on courier services (rajaongkir)
* New option to reset configuration
* Improve checkout AJAX actions
* Bugfix: store location is empty
* Bugfix: checkout with international destination not working properly
* Other minor bugfix

= 2.0.6 =
* Auto-filled checkout fields for returning user (can be enabled/disabled via admin)
* Fix addresses not displaying properly on My Account page
* Ability to edit addresses via My Account (including district)
* Fix JNE costs sometimes not displayed
* Set rule for Paketpos Biasa
* Fix POS costs calculation
* Minor Bug Fix

= 2.0.5 =
* Fix licensing issue

= 2.0.4 =
* Add handling for license get status from server
* Fixing some PHP 7 notices
* Minor bug fix

= 2.0.3 =
* Add ongkir detail address on customer invoice

= 2.0.2 =
* Fix error "Can't use function return value in write context"

= 2.0.1 =
* Fix bug perhitungan ongkir dengan satuan berat selain kg
* Minor bug fix

= 2.0.0 =
* Rebuild plugin from scratch
* Perbaikan sistem pengecekan lisensi
* Mendukung semua jasa pengiriman yang disediakan oleh API RajaOngkir. Daftar jasa pengiriman yang didukung dapat dicek di http://rajaongkir.com/dokumentasi#daftar-kurir
* Fitur ongkos kirim internasional (khusus RajaOngkir Pro)
* Fitur pembulatan berat pengiriman
* Peningkatan performa dengan sistem cache
* Fitur Flush Cache
* Fitur pengurangan ongkos kirim (Mark Down Fee)
* Penyempurnaan translasi Bahasa Indonesia
* Penyederhanaan & pembaruan halaman settings
* Update loading data pada halaman checkout
* Fix bug "District Required" pada pengiriman internasional

= 1.3.3 =
* Minor Bug Fix

= 1.3.2 =
* Minor Bug Fix

= 1.3.1 =
* Minor Bug Fix

= 1.3.0 =
* Fixing bugs store location tidak muncul di admin
* Fixing bugs ongkos kirim tidak keluar di WooCommerce 2.6

= 1.2.0 =
* Fix internasional flat tarif dengan WooCommerce 2.6.4

= 1.1.2 =
* Kompatible dengan WooCommerce 2.6.0

= 1.1.1 =
* Fixing bugs alamat billing dan shipping di admin order
* Kompatible dengan WooCommerce API v3

= 1.1.0 =
* Fixing bugs di custom ongkos kirim

= 1.0.9 =
* Mayor bugs fixing
* Notifikasi jika "Enable shipping" belum diaktifkan
* Minimal "Berat Pengiriman Default" adalah 0.125
* Custom / Free Ongkos Kirim

= 1.0.8 =
* Bahasa Indonesia
* API Plugin Ongkos Kirim
* Multiple Currency

= 1.0.7 =
* Fitur ongkos kirim hingga kecamatan (Raja Ongkir Pro)
* Integrasi lisensi Raja Ongkir Pro
* Coding core memakai PSR-4 (Minimum PHP version 5.3 +)
* Support pengiriman luar negeri
* Bug fix activation plugin
* Minor improvements

= 1.0.6 =
* Fitur Ongkos Kirim Tambahan (Additional Markup Fee)

= 1.0.5 =
* Mendukung nomor unik transaksi

= 1.0.4 =
* Mendukung multiple-courier

= 1.0 =
* Initial release