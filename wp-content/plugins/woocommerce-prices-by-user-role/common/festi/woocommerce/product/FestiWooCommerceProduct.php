<?php
if (!class_exists("AbstractFestiWooCommerceProduct")) {
    require_once dirname(__FILE__).'/AbstractFestiWooCommerceProduct.php';
}

class FestiWooCommerceProduct
{
    const FILTER_GET_PRICE_HTML_PRIORITY = 200;
    
    private $_engine;
    private $_wooFacade;
    private $_types = array(
        'simple',
        'variable',
        'grouped',
        'variation',
        'addons',
        'bundle',
        'external',
        'composite',
        'subscription',
        'variable-subscription',
        'subscription_variation',
        'yith_bundle'
    );
    private $_instances = array();
    
    public function __construct($engine)
    {
        $this->_engine = $engine;
        $this->_wooFacade = WooCommerceFacade::getInstance();
        $this->_prepareInstances();
        $this->onInit();
    } // end __construct
    
    public function doFormatProductTypeName($typeName)
    {
        $delimeter = $this->getDelimeterPosition($typeName);
        
        $length = strlen($typeName);
        $firstPart = substr($typeName, 0, $delimeter);
        $secondPart = substr($typeName, $delimeter + 1, $length);
        
        return $firstPart.ucfirst($secondPart);
    }
    
    public function isTypeNameComposedOfTwoWords($typeName)
    {
        return strpos($typeName, '-') !== false 
            || strpos($typeName, '_') !== false;
    }

    public function getDelimeterPosition($typeName)
    {
        $delimeters = array('-', '_');
        
        foreach ($delimeters as $delimeter) {
            $position = strpos($typeName, $delimeter);
            
            if (!$position === false) {
                return $position;
            }
        }
    }
    
    private function _prepareInstances()
    {
        foreach ($this->_types as $type) {
            $originalTypeName = $type;
            
            if ($this->isTypeNameComposedOfTwoWords($type)) {
                $type = $this->doFormatProductTypeName($type);
            }
            
            $className = 'FestiWooCommerce'.ucfirst($type).'Product';
            
            $this->_onInitInstance($className);
            
            $this->_instances[$originalTypeName] = new $className($this);
        }
    } // end _prepareInstances
    
    private function _onInitInstance($className)
    {
        $fileName = $className.'.php';
        $filePath = dirname(__FILE__).'/'.$fileName;
        
        if (!file_exists($filePath)) {
            throw new Exception("The ".$fileName." not found!");
        }
        
        require_once $filePath;
        
        if (!class_exists($className)) {
            $message = "The class ".$className." is not exists in ".$filePath;
            throw new Exception($message);
        }
    } // end _onInitInstance
    
    protected function onInit()
    {
        foreach ($this->_instances as $instance) {
            $instance->onInit();
        }
    } // end onInit
    
    public function getInstance($productType)
    {
        if (!array_key_exists($productType, $this->_instances)) {
            throw new Exception('Not found instance with type '.$productType);
        }
        
        return $this->_instances[$productType];
    } // end getInstance
    
    public function addActionListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        $this->_engine->addActionListener(
            $hook,
            $method,
            $priority,
            $acceptedArgs
        );
    } // end addActionListener
    
    public function addFilterListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        $this->_engine->addFilterListener(
            $hook,
            $method,
            $priority,
            $acceptedArgs
        );
    } // end addFilterListener
    
    public function removeAllLoopAddToCartLinks()
    {
        $this->addFilterListener(
            'woocommerce_loop_add_to_cart_link',
            'onRemoveAllAddToCartButtonFilter',
            10,
            2
        );
    } // end removeAllLoopAddToCartLinks
    
    public function removeLoopAddToCartLinksInSomeProducts()
    {
        $this->addFilterListener(
            'woocommerce_loop_add_to_cart_link',
            'onRemoveAddToCartButtonInSomeProductsFilter',
            10,
            2
        );
    } // end removeLoopAddToCartLinksInSomeProducts
    
    public function removeAddToCartButton($type = false)
    {
        if ($type) {
            $this->_instances[$type]->removeAddToCartButton();
            return true;
        }
        
        foreach ($this->_instances as $instance) {
            $instance->removeAddToCartButton();
        }
    } // end removeAddToCartButton
    
    public function replaceAllPriceToText()
    { 
        $this->addFilterListener(
            'woocommerce_get_price_html',
            'onReplaceAllPriceToTextInAllProductFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onReplaceAllPriceToTextInAllProductFilter',
            10,
            2
        );
    } // end replaceAllPriceToText
    
    public function replaceAllPriceToTextInSomeProduct()
    {
        $this->addFilterListener(
            'woocommerce_get_price_html',
            'onReplaceAllPriceToTextInSomeProductFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onReplaceAllPriceToTextInSomeProductFilter',
            10,
            2
        );
    } // end replaceAllPriceToTextInSomeProduct
    
    public function fetchContentInsteadOfPrices()
    {
        $vars = array(
            'text' => $this->textInsteadPrices
        );
        
        return $this->fetch('custom_text.phtml', $vars);
    } // end fetchContentInsteadOfPrices
    
    public function onFilterPriceByRolePrice()
    {
        //FIXME:
        $hookName = $this->_wooFacade->getHook('woocommerce_get_price');
        $this->addFilterListener(
            $hookName,
            'onDisplayPriceByRolePriceFilter',
            10,
            2
        );
        $hookName = $this->_wooFacade->getHook(
            'woocommerce_product_variation_get_price'
        );
        
        $this->addFilterListener(
            $hookName,
            'onDisplayPriceByRolePriceFilter',
            10,
            2
        );
        
       
        
    } // end onFilterPriceByRolePrice
    
    public function onFilterPriceByDiscountOrMarkup()
    {
        //FIXME:
        
        $hookName = $this->_wooFacade->getHook('woocommerce_get_price');
        $this->addFilterListener(
            $hookName, //woocommerce_get_price
            'onDisplayPriceByDiscountOrMarkupFilter',
            10,
            2
        );
        
        $hookName = $this->_wooFacade->getHook('woocommerce_product_variation_get_price');
        $this->addFilterListener(
            $hookName, //woocommerce_get_price
            'onDisplayPriceByDiscountOrMarkupFilter',
            10,
            2
        );

    } // end onFilterPriceByDiscountOrMarkup
    
    public function getRolePrice($product)
    {
        if (!$product) {
            return false;
        }
        $productId = $this->getProductId($product);
        $type = $this->_wooFacade->getType($product);
        
        if (!$productId) {
            throw new Exception('Undefined productId Product type is '.$type);
        }

        return $this->_engine->getRolePrice($productId);
    } // end getRolePrice
    
    public function getRoleSalePrice($product)
    {
        if (!$product) {
            return false;
        }
        $idProduct = $this->getProductId($product);
        
        if (!$idProduct) {
            $type = $this->_wooFacade->getType($product);
            throw new Exception('Undefined productId Product type is '.$type);
        }
    
        if (!method_exists($this->_engine, 'getRoleSalePrice')) {
            throw new Exception('Undefined method getRoleSalePrice');
        }
        
        return $this->_engine->getRoleSalePrice($idProduct);
    } // end getRoleSalePrice
    
    public function getProductId($product)
    {
        if (!$product) {
            return false;
        }
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        if (!$this->_hasProductInstanceWithType($type)) {
            throw new Exception(
                "Plugin do not support product type ".$type
            );
        }

        $productId = $this->_instances[$type]->getProductId($product);
        
        return $productId;
    } // end getProductId
    
    private function _hasProductInstanceWithType($type)
    {
        return in_array($type, $this->_types);
    } // end _hasProductInstanceWithType
    
    public function onDisplayCustomerSavings()
    {
        $this->addFilterListener(
            'woocommerce_get_price_html',
            'onDisplayCustomerSavingsFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onDisplayCustomerSavingsFilter',
            10,
            2
        );
    } // end onDisplayCustomerSavings
    
    public function isAvaliableProductTypeToDispalySavings($product)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        return $this->_instances[$type]->isAvaliableToDispalySavings($product);
    } // end isAvaliableProductTypeToDispalySavings
    
    public function isProductPage()
    {
        return $this->_engine->isProductPage();
    } // end isProductPage
    
    public function getMaxProductPice($product, $display)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        $price = $this->_instances[$type]->getMaxProductPice(
            $product,
            $display
        );
        
        return $price;
    } // end getMaxProductPice
    
    public function getMinProductPice($product, $display)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        $price = $this->_instances[$type]->getMinProductPice(
            $product,
            $display
        );
        
        return $price;
    } // end getMinProductPice
    
    public function createProductInstance($productId)
    {
        return $this->_engine->createProductInstance($productId);
    } // end createProductInstance
    
    public function getPriceRange($product)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        return $this->_instances[$type]->getPriceRange($product);
    } // end getPriceRange
    
    public function isWoocommerceMultiLanguageActive()
    {
        return $this->_engine->isWoocommerceMultiLanguageActive();
    } // end isWoocommerceMultiLanguageActive
    
    public function getPostMeta($postId, $key, $single = true)
    {
        return $this->_engine->getPostMeta($postId, $key, $single);
    } // end getPostMeta
    
    public function isAvaliableToDisplaySaleRange($product)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        $result = $this->_instances[$type]->isAvaliableToDisplaySaleRange(
            $product
        );
        
        return $result;
    } // end isAvaliableToDisplaySaleRange
    
    public function getListOfPruductsWithRolePrice()
    {
        return $this->_engine->getListOfPruductsWithRolePrice();
    } // end getListOfPruductsWithRolePrice
    
    public function getRegularPrice($product, $display = false)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        return $this->_instances[$type]->getRegularPrice($product, $display);
    } // end getRegularPrice
    
    public function getUserPrice($product, $display = false)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        return $this->_instances[$type]->getUserPrice($product, $display);
    } // end getUserPrice
    
    public function getUserPrices($product, $display = false)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        return $this->_instances[$type]->getUserPrices($product, $display);
    } // end getUserPrices
    
    public function getFormatedPriceForSaleRange($product, $userPrice)
    {
        $type = $this->_wooFacade->getType($product);
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }
        
        $range = $this->_instances[$type]->getFormatedPriceForSaleRange(
            $product,
            $userPrice
        );
            
        return $range;
    } // end getUserPrice
    
    public function fetch($template, $vars = array())
    {
        return $this->_engine->fetch($template, $vars);
    } // end fetch
    
    public function getPriceSuffix($product, $price = '')
    {
        $type =  $this->_wooFacade->getType($product);//$product->get_type();
        
        if (!$type) {
            throw new Exception("Not defined woocommerce product type");
        }

        return $this->_instances[$type]->getPriceSuffix($product, $price);
    } // end getPriceSuffix
}
