<?php

class WooVersionThree implements IWooVersion
{
    private $_hooks = array(
        'woocommerce_get_price' => 'woocommerce_product_get_price'
    );
    
    public function getType($product)
    {
        return $product->get_type();
    }
    
    public function getVariationID($product)
    {
        return $product->get_id();
    }
    
    public function isPostParent($product)
    {
        return $product->get_parent_data();
    }
    
    public function getPostParent($product)
    {
        return $product->get_parent_data();
    }
    
    public function _isChildProduct($product)
    {
        return (bool) $product->get_parent_id(); //$product->has_child();
    }
    
    public function getParentID($product)
    {
        return $product->get_parent_id();
    }
    
    public function getID($product)
    {
        return $product->get_id();
    }
    
    public function getPriceExcludingTax($product, $args)
    {
        return wc_get_price_excluding_tax($product, $args);
    }
    
    public function getPriceIncludingTax($product, $args)
    {
        return wc_get_price_including_tax($product, $args);
    }
    
    public function getHook($hookName)
    {
        if (!array_key_exists($hookName, $this->_hooks)) {
            return $hookName;    
        }
        
        return $this->_hooks[$hookName];
    }
    
    public function getPricesFromVariationProduct($product)
    {
        $prices = array();
        $productIDs = $product->get_children();

        foreach ($productIDs as $productID) {
            $product = wc_get_product($productID);
            $prices[$productID] = $product->get_price();
        }
        
        return $prices;
    }
}
