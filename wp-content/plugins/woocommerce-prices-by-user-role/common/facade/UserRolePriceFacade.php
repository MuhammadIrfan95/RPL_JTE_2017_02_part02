<?php

class UserRolePriceFacade
{
    private static $_instance = null;
    const INDEX_PRODUCT_WITH_MINIMAL_PRICE = 0;

    public static function &getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end &getInstance
    
    public function __construct()
    {
         if (isset(self::$_instance)) {
            $message = 'Instance already defined ';
            $message .= 'use UserRolePriceFacade::getInstance';
            throw new Exception($message);
         }
    } // end __construct
    
    public function getRolePriceForWoocommercePriceSuffix(
        $product,
        $userRole,
        $engine,
        $engineFacade
    )
    {
        $productId = $this->_getVariationWithMinimalPrice(
            $product,
            $engineFacade
        );

        $minUserPrices = $this->_getUserRolePricesForProduct(
            $productId,
            $engine
        );

        if (!$minUserPrices) {
            $minUserPrices = array();
        }

        if ($this->_isSalePriceForUserRoleSet($minUserPrices, $userRole)) {
            return $this->_getSalePriceForUserRole($minUserPrices, $userRole);
        }
        
        return $this->_getRegularPriceForUserRole(
            $minUserPrices,
            $userRole
        );
    } // end getRolePriceForWoocommercePriceSuffix

    private function _getRegularPriceForUserRole($priceList, $userRole)
    {
        foreach ($priceList as $role => $price) {
            if ($role == $userRole) {
                return $price;
            }
        }

        return false;
    } // end _getRegularPriceForUserRole

    private function _getSalePriceForUserRole($priceList, $userRole)
    {
        foreach ($priceList['salePrice'] as $role => $price) {
            if ($role == $userRole) {
                return $price;
            }
        }

        return false;
    } // end _getSalePriceForUserRole

    private function _getUserRolePricesForProduct($productID, $engine)
    {
        return $engine->getMetaOptions(
            $productID,
            PRICE_BY_ROLE_PRICE_META_KEY
        );
    } // end _getUserRolePricesForProduct

    private function _getVariationWithMinimalPrice($product, $engineFacade)
    {
        $result = $engineFacade->getPricesFromVariationProduct($product);

        if (!empty($result)) {
            $result = array_keys($result, min($result));

            return $result[static::INDEX_PRODUCT_WITH_MINIMAL_PRICE];
        }

        return false;
    } // end _getVariationWithMinimalPrice

    private function _isSalePriceForUserRoleSet($priceList, $userRole)
    {
        return array_key_exists('salePrice', $priceList) &&
               !empty($priceList['salePrice'][$userRole]);
    } // end _isSalePriceForUserRoleSet
}