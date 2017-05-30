<?php

class FestiWooCommerceVariableProduct extends AbstractFestiWooCommerceProduct
{
    public function removeAddToCartButton()
    {
        $this->adapter->addActionListener(
            'woocommerce_after_single_variation',
            'removeVariableAddToCartLinkAction'
        );
    } // end removeAddToCartButton
    
    public function getProductId($product)
    {
        //FIX:
        return $this->wooFacade->getID($product);//$product->id;
    } // end getProductId
    
    private function _getFirstVariationChildId($product)
    {
        if (!$this->_hasVariatonsProducts($product)) {
            return false;
        }
        
        $variations = $product->children;
        
        return current($product->children);
    } // end _getFirstVariationChildId
    
    private function _hasVariatonsProducts($product)
    {
        return isset($product->children);
    } // end _hasVariatonsProducts
    
    public function isAvaliableToDispalySavings($product)
    {
        return true;
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
            
            $price = $this->adapter->getUserPrice($product, $display);

            $priceList[] = $price;
        }
        
        return $priceList;
    } //end getAllPriceOfChildren

    public function isAvaliableToDisplaySaleRange($product)
    {
        $children = $this->getChildren($product);
        
        if (!$children) {
            return true;
        }

        return $this->_isAllChildsPriceRegularOrSale($children);
    } // end isAvaliableToDisplaySaleRange
    
    private function _getChildsWithRolePrice($children)
    {
        $listOfProducts = $this->adapter->getListOfPruductsWithRolePrice();

        $childs = array();
        
        foreach ($children as $childrenId) {
            if (!in_array($childrenId, $listOfProducts)) {
                continue;
            }
            
            $childs[] = $childrenId;
        }
        
        return $childs;
    } // end _getChildsWithRolePrice
    
    private function _isAllChildsPriceRegularOrSale($children)
    {
        $childsWithRolePrice = $this->_getChildsWithRolePrice($children);
        
        return count($childsWithRolePrice) == false;
    } // end _isAllChildsPriceRegularOrSale
    
    public function getFormatedPriceForSaleRange($product, $userPrice)
    {
        return $userPrice;
    } // end getFormatedPriceForSaleRange
    
    public function getUserPrice($product, $display = false)
    {
        $prices = $this->getAllPriceOfChildren($product, $display);
        if (!$prices) {
            return false;
        }
        
        $prices = array_unique($prices);
        
        $this->doRemoveEmptyPrices($prices);

        if (!$this->_hasEqualPricesInChildProducts($prices)) {
            return false;
        }
        
        $price = current($prices);
        
        if (!$display) {
            return $price;
        }
        
        $taxDisplayMode = $this->getTaxDisplayMode();
        $quantity = $this->productMinimalQuantity;
        
        if ($this->isPriceIncludeTax()) {
            return $product->get_price_including_tax($quantity, $price);
        }

        return $this->wooFacade->getPriceExcludingTax($product, $quantity, $price);//$product->get_price_excluding_tax($quantity, $price);
    } // end getUserPrice
    
    public function getUserPrices($product, $display = false)
    {
        $prices = $this->getAllPriceOfChildren($product, $display);
        if (!$prices) {
            return false;
        }
        $prices = array_unique($prices);
        
        $this->doRemoveEmptyPrices($prices);

        return $prices;
    } // end getUserPrices
    
    private function _hasEqualPricesInChildProducts($prices)
    {
        return count($prices) == $this->minimalPricesCount;
    } // end _hasEqualPricesInChildProducts
    
    protected function doRemoveEmptyPrices(&$prices)
    {
        while (($key = array_search(false, $prices)) !== false) {
            unset($prices[$key]);
        } 
    } // end doRemoveEmptyPrices
    
    public function getRegularPrice($product, $display)
    {
 
        $price = $this->_getPrice($product, $display);
        
        if (!$display) {
            return $price;
        }
        
        $taxDisplayMode = $this->getTaxDisplayMode();
        $quantity = $this->productMinimalQuantity;
        
        if ($this->isPriceIncludeTax()) {
            return $product->get_price_including_tax($quantity, $price);
        }
        
        return $this->wooFacade->getPriceExcludingTax($product, $quantity, $price);//$product->get_price_excluding_tax($quantity, $price);
    } // end getRegularPrice
    
    private function _getPrice($product, $display)
    {
        if ($this->_isExistsMethodVariationPrices($product)) {
                
            $prices = $product->get_variation_prices($display);
            
            if ($this->_isExistsRegularPriceKeyInPrices($prices)) {
                return current($prices['regular_price']);
            }
        }
        
        return $product->get_variation_price('min', $display);
    } //end _getPrice
    
    private function _isExistsMethodVariationPrices($product)
    {
        return method_exists($product, 'get_variation_prices');
    }
    
    private function _isExistsRegularPriceKeyInPrices($prices)
    {
        return array_key_exists('regular_price', $prices);
    }
}
