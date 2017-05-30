<?php

if (!interface_exists("IWooCommerceCart")) {
    require_once dirname(__FILE__).'/IWooCommerceCart.php';
}

class WooCommerceCartFacade implements IWooCommerceCart
{
    private static $_instance = null;
    
    public static function &getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end &getInstance
    
    public function getTotal()
    {
        $cart = $this->getCartInstance();
        return $cart->total;
    } // end getTotal
    
    public function getTotalExcludeTax()
    {
        $cart = $this->getCartInstance();
        $total = $cart->total - $cart->tax_total - $cart->shipping_tax_total;
        $total = ($total < 0) ? 0 : $total;

        return $total;
    } // end getTotalExcludeTax
    
    public function getTaxTotal()
    {
        $cart = $this->getCartInstance();
        return $cart->tax_total;
    } // end getTaxTotal
    
    public function getSubtotal()
    {
        $cart = $this->getCartInstance();
        return $cart->subtotal;
    } // end getSubtotal
    
    public function getSubtotalExcludeTax()
    {
        $cart = $this->getCartInstance();
        return $cart->subtotal_ex_tax;
    } // end getSubtotalExcludeTax
    
    public function getShippingTotal()
    {
        $cart = $this->getCartInstance();
        return $cart->shipping_total;
    } // end getShippingTotal
    
    public function getShippingTaxTotal()
    {
        $cart = $this->getCartInstance();
        return $cart->shipping_tax_total;
    } // end getShippingTaxTotal
    
    public function getProducts()
    {
        $cart = $this->getCartInstance();
        return $cart->cart_contents;
    } // end getProducts
    
    public function &getCartInstance()
    {
        $wooFacade = WooCommerceFacade::getInstance();
        $woocommerce = $wooFacade->getWooCommerceInstance();
        
        if (!isset($woocommerce->cart)) {
            throw new Exception("WooCommerce Cart instance not defined");
        }
        
        return $woocommerce->cart;
    } // end getCartInstance
    
    public function getTaxDisplayMode()
    {
        return get_option('woocommerce_tax_display_cart');
    } // end getTaxDisplayMode
    
    public function isPricesIncludeTax()
    {
        $taxDisplayMode = $this->getTaxDisplayMode();
        
        return $taxDisplayMode == 'incl';
    } // end isPricesIncludeTax
    
    public function isTaxInclusionOptionOn()
    {
        $cart = $this->getCartInstance();
        
        return $cart->prices_include_tax == 1;
    }
    
    public function getShippingCost($shippingMethods)
    {
        foreach (WC()->cart->get_shipping_packages() as $singlePackage) {

            $package = WC()->shipping->calculate_shipping_for_package(
                $singlePackage
            );

            // Only display the costs for the chosen shipping method
            foreach ($shippingMethods as $key) {
                if (isset($package['rates'][ $key ])) {
                    $shippingMethods['cost'] = $package['rates'][$key];
                }
            }
        }
        
        if (array_key_exists('cost', $shippingMethods)) {
            return $shippingMethods['cost']->cost;    
        }
        
        return false;
    }
    
    public function getCoupons()
    {
        $cart = $this->getCartInstance();   
        
        return $cart->coupons;
    }
    
    public function getTotalFullPrice()
    {
       $cart = $this->getCartInstance();
       
       return $cart->cart_contents_total;
    }
}
