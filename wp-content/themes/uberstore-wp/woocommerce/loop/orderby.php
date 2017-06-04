<?php
/**
 * Show options for ordering
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<form class="woocommerce-ordering custom" method="get">
	<div class="select-wrapper">
		<select name="orderby" class="orderby">
			<?php $catalog_orderby_options = apply_filters( 'woocommerce_catalog_orderby', array(
						'menu_order' => __( 'Default', 'uberstore' ),
						'popularity' => __( 'Sort by terpopuler', 'uberstore'),
						'rating'     => __( 'Sort by rating', 'uberstore' ),
						'date'       => __( 'Sort by terbaru', 'uberstore'),
						'price'      => __( 'Sort by harga: termurah', 'uberstore' ),
						'price-desc' => __( 'Sort by harga: termahal', 'uberstore' )
					) );
			?>
			<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
		// Keep query string vars intact
		foreach ( $_GET as $key => $val ) {
			if ( 'orderby' === $key || 'submit' === $key ) {
				continue;
			}
			if ( is_array( $val ) ) {
				foreach( $val as $innerVal ) {
					echo '<input type="hidden" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $innerVal ) . '" />';
				}
			} else {
				echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" />';
			}
		}
	?>
</form>