<?php

class FestiWooCommerceGroupedProduct extends AbstractFestiWooCommerceProduct
{
    public function removeAddToCartButton()
    {
        $this->wpFacade->onRemoveAllActions('woocommerce_grouped_add_to_cart');
        
        $this->adapter->addActionListener(
            'woocommerce_grouped_add_to_cart',
            'removeGroupedAddToCartLinkAction'
        );
    } // end removeAddToCartButton
    
    public function getProductId($product)
    {
        return $this->wooFacade->getID($product);//$product->id;
    } // end getProductId
    
    public function isAvaliableToDispalySavings($product)
    {
        return $this->adapter->isProductPage();
    } // end isAvaliableToDispalySavings
    
    public function getMaxProductPice($product, $display)
    {
        $priceList = $this->getAllPriceOfChildren($product, $display);
        
        return ($priceList) ? max($priceList) : false;
    } // end getMaxProductPice
    
    public function getMinProductPice($product, $display)
    {
        $priceList = $this->getAllPriceOfChildren($product, $display);
        
        return ($priceList) ? min($priceList) : false;
    } // end getMinProductPice
    
    protected function getAllPriceOfChildren($product, $display)
    {
        $children = $this->getChildren($product);

        if (!$children) {
            return false;
        }
        
        $priceList = array();

        foreach ($children as $childrenId) {
            $product = $this->adapter->createProductInstance($childrenId);
            
            $price = $this->getUserPrice($product, $display);
            
            if (!$price) {
                continue;
            }

            $priceList[] = $price;
        }
        
        return $priceList;
    } //end getAllPriceOfChildren
    
    public function isAvaliableToDisplaySaleRange($product)
    {
        return true;
    } // end isAvaliableToDisplaySaleRange
}