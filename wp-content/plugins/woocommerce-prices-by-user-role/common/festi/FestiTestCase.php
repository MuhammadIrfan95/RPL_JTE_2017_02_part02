<?php

class FestiTestCase extends WP_UnitTestCase
{
    protected function setMainPage(WP_Post $page)
    {
        update_option('page_on_front', $page->ID);
        update_option('show_on_front', 'page');
    } // end setMainPage
    
    protected function doAction($name)
    {
        ob_start();
        
        do_action($name);
        
        return ob_get_clean();
    } // end doAction
    
    protected function createPage($options = array())
    {
        $options['post_type'] = 'page';
        
        return $this->createPost($options);
    } // end createPage
    
    protected function createPost(&$options)
    {
        if (empty($options['post_title'])) {
            $options['post_title'] = 'content_'.rand(1000, 100000);
        }
        
        if (empty($options['post_type'])) {
            $options['post_type'] = 'post';
        }
        
        return self::factory()->post->create_and_get($options);
    } // end createPost
    
    protected function createWooProduct()
    {
        $options = array(
            'post_type' => 'product'
        );
        
        $post = $this->createPost($options);
        
        $idPost = $post->ID;

        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        $regularPriceKey= WooCommerceProductValuesObject::REGULAR_PRICE_KEY;
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        wp_set_object_terms($idPost, 'simple', 'product_type');
        
        update_post_meta($idPost, '_visibility', 'visible');
        update_post_meta($idPost, '_stock_status', 'instock');
        update_post_meta($idPost, 'total_sales', '0');
        update_post_meta($idPost, '_downloadable', 'yes');
        update_post_meta($idPost, '_virtual', 'yes');
        update_post_meta($idPost, $regularPriceKey, '1');
        update_post_meta($idPost, $salePriceKey, '1');
        update_post_meta($idPost, '_purchase_note', '');
        update_post_meta($idPost, '_featured', 'no');
        update_post_meta($idPost, '_weight', '');
        update_post_meta($idPost, '_length', '');
        update_post_meta($idPost, '_width', '');
        update_post_meta($idPost, '_height', '');
        update_post_meta($idPost, '_sku', '');
        update_post_meta($idPost, '_product_attributes', array());
        update_post_meta($idPost, '_sale_price_dates_from', '');
        update_post_meta($idPost, '_sale_price_dates_to', '');
        update_post_meta($idPost, $priceKey, '1');
        update_post_meta($idPost, '_sold_individually', '');
        update_post_meta($idPost, '_manage_stock', 'no');
        update_post_meta($idPost, '_backorders', 'no');
        update_post_meta($idPost, '_stock', '');
        update_post_meta($idPost, '_download_limit', '');
        update_post_meta($idPost, '_download_expiry', '');
        update_post_meta($idPost, '_download_type', '');
        update_post_meta($idPost, '_product_image_gallery', '');
    
        return $idPost;
    } // end createWooProduct
    
    
    //$content = apply_filters('the_content', $page->post_content);
    //get_post_field('post_content', $page->ID);
    //echo $content;
    
}