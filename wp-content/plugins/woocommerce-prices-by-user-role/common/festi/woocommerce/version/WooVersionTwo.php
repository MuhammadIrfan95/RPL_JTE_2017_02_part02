<?php

class WooVersionTwo implements IWooVersion
{
    public function getType($product)
    {
        return $product->product_type;
    }
    
    public function getVariationID($product)
    {
        return $product->variation_id;
    }
    
    public function isPostParent($product)
    {
        return isset($product->post->post_parent) 
               && $product->post->post_parent != false;
    }
    
    public function _isChildProduct($product)
    {
        return isset($product->post->post_parent) 
               && $product->post->post_parent != false;
    }
    
    public function getPostParent($product)
    {
        return $product->post->post_parent;
    }
    
    public function getParentID($product)
    {
        return $product->post->post_parent;
    }
    
    public function getID($product)
    {
        return $product->id;
    }
    
    public function getPriceExcludingTax($product, $args)
    {
        return $product->get_price_excluding_tax($args['qty'], $args['price']);
    }
    
    public function getPriceIncludingTax($product, $args)
    {
        return $product->get_price_including_tax($args['qty'], $args['price']);
    }
    
    public function getHook($hook)
    {
        return $hook;
    }
    
    public function getPricesFromVariationProduct($product)
    {
        $prices = array();
        $productIDs = $product->get_children();

        foreach ($productIDs as $productID) {
            $product = $product->get_child($productID);
            $prices[$productID] = $product->get_price();
        }
        
        return $prices;
    }
}
