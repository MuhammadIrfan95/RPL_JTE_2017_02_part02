<?php

/*
 * Need to compability with plugin Product Bundles
 * @link http://www.woothemes.com/products/product-bundles/
 */

class FestiWooCommerceBundleProduct extends FestiWooCommerceSimpleProduct
{
    public function isAvaliableToDisplaySaleRange($product)
    {
        return !$this->_hasRolePriceForCurrentUser($product);
    } // end isAvaliableToDisplaySaleRange
    
    private function _hasRolePriceForCurrentUser($product)
    {
        $listOfProducts = $this->adapter->getListOfPruductsWithRolePrice();
        $productId = $this->getProductId($product);
        return in_array($productId, $listOfProducts);
    } // end _hasRolePriceForCurrentUser
    
    public function getFormatedPriceForSaleRange($product, $userPrice)
    {
        return wc_price($userPrice);
    } // end getFormatedPriceForSaleRange
}
