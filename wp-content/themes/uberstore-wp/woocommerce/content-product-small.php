<?php
/**
 * The template for displaying product content within loops.
 *
 * Override this template by copying it to yourtheme/woocommerce/content-product.php
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

global $product;

// Ensure visibility
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

?>
<div itemscope itemtype="<?php echo woocommerce_get_product_schema(); ?>" <?php post_class("post small-6 medium-3 columns"); ?>>
	<?php
		/**
		 * woocommerce_before_shop_loop_item hook.
		 *
		 * @hooked woocommerce_template_loop_product_link_open - 10
		 */
		do_action( 'woocommerce_before_shop_loop_item' );
	?>
	<figure<?php if( ot_get_option('product_hover') == 'fade'){ echo ' class="fade"'; }?>>
	
		<?php
			$image_html = "";
			
			if (thb_out_of_stock()) {
				echo '<span class="badge out-of-stock">' . __( 'Out of Stock', 'uberstore' ) . '</span>';
			} else if ( $product->is_on_sale() ) {
				echo apply_filters('woocommerce_sale_flash', '<span class="badge onsale">'.__( 'Sale', 'uberstore' ).'</span>', $post, $product);
			}

			if ( has_post_thumbnail() ) {
				$image_html = wp_get_attachment_image( get_post_thumbnail_id(), 'shop_catalog' );					
			}
		?>
		
		<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
			
			<?php
				$attachment_ids = $product->get_gallery_attachment_ids();
				
				$img_count = 0;
				
				if ($attachment_ids) {
					
					echo '<div class="product-image">'.$image_html.'</div>';	
					
					foreach ( $attachment_ids as $attachment_id ) {
						
						if ( get_post_meta( $attachment_id, '_woocommerce_exclude_image', true ) )
							continue;
						
						echo '<div class="product-image">'.wp_get_attachment_image( $attachment_id, 'shop_catalog' ).'</div>';	
						
						$img_count++;
						
						if ($img_count == 1) break;
			
					}
								
				} else {
				
					echo '<div class="product-image">'.$image_html.'</div>';					
					echo '<div class="product-image">'.$image_html.'</div>';
					
				}
			?>			
		</a>
	</figure>
	
	<div class="post-title">
		<?php
			$size = sizeof( get_the_terms( $post->ID, 'product_cat' ) );
			echo $product->get_categories( ', ', '<aside class="post_categories">' . _n( '', '', $size, 'uberstore' ) . ' ', '</aside>' );
		?>
		<h3>
			<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
		</h3>
		<?php
			/**
			 * woocommerce_after_shop_loop_item_title hook.
			 *
			 * @hooked woocommerce_template_loop_rating - 5
			 * @hooked woocommerce_template_loop_price - 10
			 */
			do_action( 'woocommerce_after_shop_loop_item_title' );
		?>
		<div class="shop-buttons">
			<?php 
				/**
				 * woocommerce_after_shop_loop_item hook.
				 *
				 * @hooked woocommerce_template_loop_product_link_close - 5
				 * @hooked woocommerce_template_loop_add_to_cart - 10
				 */
				do_action( 'woocommerce_after_shop_loop_item' ); 
			?>
		</div>
	</div>
</div><!-- end product -->