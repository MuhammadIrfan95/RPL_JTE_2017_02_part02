<?php
if (!class_exists("FestiWooProductAdpter")) {
    $path = '/common/festi/woocommerce/product/FestiWooCommerceProduct.php';
    require_once dirname(__FILE__).$path;
}

if (!class_exists("WooCommerceCartFacade")) {
    $path = '/common/festi/woocommerce/WooCommerceCartFacade.php';
    require_once dirname(__FILE__).$path;
}

if (!class_exists("WooUserRolePricesFrontendHookManager")) {
    $path = '/common/frontend/WooUserRolePricesFrontendHookManager.php';
    require_once __DIR__ . $path;
}

if (!class_exists("UserRoleEmptyPriceHelper")) {
    require_once __DIR__ . '/common/helpers/UserRoleEmptyPriceHelper.php';
}

class WooUserRolePricesFrontendFestiPlugin extends WooUserRolePricesFestiPlugin
{
    const TYPE_PRODUCT_SIMPLE = 'simple';
    
    const TYPE_PRICE_USER = 'user';
    const TYPE_PRICE_REGULAR = 'regular';
    
    private $_emptyPriceHelper;
    
    protected $settings = array();
    protected $userRole;
    protected $products;
    protected $eachProductId = 0;
    protected $removeLoopList = array();
    protected $textInsteadPrices;
    protected $mainProductOnPage = 0;
    private $_listOfProductsWithRolePrice = array();
    
    protected $subscriptionTax;
    protected $subscriptionCount;
    protected $subscribeProduct;
    protected $subscriptionFee;
    protected $subscriptionPrice;
    protected $mainTotals;
    protected $_facade;

    protected function onInit()
    {
        if (!$this->_isSesionStarted()) {
            session_start();
        }

        if ($this->_isMaxExecutionTimeLowerThanConstant()) {
            set_time_limit(WooUserRolePricesFestiPlugin::MAX_EXECUTION_TIME);
        }

        $this->addActionListener('woocommerce_init', 'onInitFiltersAction');

        $this->addActionListener('wp', 'onHiddenAndRemoveAction');

        $this->addActionListener('wp_print_styles', 'onInitCssAction');
        $this->addActionListener('wp_enqueue_scripts', 'onInitJsAction');

        $this->addFilterListener(
            'woocommerce_get_variation_prices_hash',
            'onAppendDataToVariationPriceHashGeneratorFilter',
            10,
            3
        );
        
        $this->_onInitApi();
       
        $this->_facade = WooCommerceFacade::getInstance();
        $this->_emptyPriceHelper = new UserRoleEmptyPriceHelper($this);
    } // end onInit
        
    private function _onInitApi()
    {
        $apiFacade = new WooUserRolePricesApiFacade($this);
        $apiFacade->init();
    } // end _onInitApi

    protected function getSettings()
    {
        if (!$this->settings) {
            $settings = $this->getOptions('settings');
            $this->settings = $settings;
        }

        if (!$this->settings) {
            throw new Exception('The settings can not be empty.');
        }

        return $this->settings;
    } // end getSettings
    
    public function onAppendDataToVariationPriceHashGeneratorFilter(
        $productData, $product, $display
    )
    {
        $roles = $this->getAllUserRoles();
        
        $value = PRICE_BY_ROLE_HASH_GENERATOR_VALUE_FOR_UNREGISTRED_USER;
        $data = (!$roles) ? array($value) : $roles;

        $productData[PRICE_BY_ROLE_HASH_GENERATOR_KEY] = $data;
        
        return $productData;
    } // end onAppendDataToVariationPriceHashGeneratorFilter
    
    protected function getProductsInstances()
    {
        return new FestiWooCommerceProduct($this);
    } // end getProductsInstances
    
    public function onGetTextInsteadOfEmptyPrice()
    {
        return $this->_emptyPriceHelper->onGetTextInsteadOfEmptyPrice();
    } // end onGetTextInsteadOfEmptyPrice
    
    public function onInitFiltersAction()
    {        
        $this->userRole = $this->getUserRole();
        
        $this->products = $this->getProductsInstances();
        
        $this->addActionListener('wp', 'onInitMainProductIdAction');
        
        if ($this->hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            $this->onFilterPriceByDiscountOrMarkup();   
        } else {
            $this->onFilterPriceByRolePrice();
        }

        $this->onDisplayCustomerSavings();

        $this->onFilterPriceRanges();
    } // end onInitFiltersAction
    
    public function onHiddenAndRemoveAction()
    {
        $this->onHideAddToCartButton();
        
        $this->onHidePrice();
        
        $this->_emptyPriceHelper->onHideEmptyPrice();
    } // end onHiddenAndRemoveAction
    
    public function onInitMainProductIdAction()
    {
        $this->getMainProductId();
    } // end onInitMainProductIdAction
    
    protected function onFilterPriceRanges()
    {
        $hookManager = new WooUserRolePricesFrontendHookManager($this);
        
        $hookManager->onInit();
    } // end onFilterPriceRanges
    
    public function onShowVariationPriceForCustomerSavings($isShow)
    {
        if ($this->_isShowCustomerSavingsOnProductPage()) {
            return true;
        }
        
        return $isShow;
    } // end onShowVariationPriceForCustomerSavings
    
    private function _isShowCustomerSavingsOnProductPage()
    {
        $settings = $this->getSettings();
        
        return !empty($settings) &&
               array_key_exists('showCustomerSavings', $settings) && 
               in_array('product', $settings['showCustomerSavings']);
    } // end _isShowCustomerSavingsOnProductPage

    public function onRemovePriceForUnregisteredUsers($price, $product)
    {
        if ($this->_hasReadMoreButtonText($price)) {
            return $price;
        }
        
        if (!$this->_isAvailablePriceInAllProductsForUnregisteredUsers()) {
             $price = null;
        }
        
        return $price;
    }
    
    private function _hasReadMoreButtonText($price)
    {
        return $price === $this->_facade->getEmptyPriceSymbol();
    } // end _hasReadMoreButtonText
    
    public function onSalePriceCheck($isSale, $product)
    {
        if ($this->_hasSalePriceByUnregisteredUser($product)) {
            return true;    
        }
        
        if ($this->_hasSalePriceForUserRole($product)) {
            return $this->_isRoleSalePriceLowerThenRolePrice($product);
        }

        if ($this->_hasRolePriceBySimpleProduct($product)) {
            return false;
        }
        
        if ($this->hasRoleSalePriceByVariableProduct($product)) {
            return true;
        }
        
        return $isSale;
    }
    
    private function _hasSalePriceByUnregisteredUser($product)
    {
        if (!$this->_isSimpleTypeProduct($product)) {
            return false;
        }
        
        if ($this->_isRegisteredUser()) {
            return false;
        }
        
        return (bool) $product->get_sale_price();
    }
    
    public function onHideSelectorSaleForProduct($content, $post, $product)
    {
        if ($this->hasRoleSalePriceByVariableProduct($product)) {
            return $content;
        }
        
        if ($this->_hasRolePriceByVariableProduct($product)) {
            return false;
        }
        
        if ($this->_hasRolePriceBySimpleProduct($product)) {
            return false;
        }
        
        return $content;
    }
    
    private function _hasRolePriceBySimpleProduct($product)
    {
        if (!$this->_isSimpleTypeProduct($product)) {
            return false;
        }
        $idProduct = $this->_facade->getID($product);
        $prices = $this->getProductPrices($idProduct); //$product->id

        if ($this->_hasSalePriceByUserRole($prices)) {
            return false;
        }
        
        if ($this->_hasPriceByUserRole($prices)) {
            return true;
        }
       
        return false;
    }
    
    private function _hasSalePriceByUserRole($prices)
    {
        return $this->userRole
               && array_key_exists('salePrice', $prices)
               && array_key_exists($this->userRole, $prices['salePrice'])
               && $prices['salePrice'][$this->userRole];
    }
    
    
    private function _isSimpleTypeProduct($product)
    {
        //$product->product_type == static::TYPE_PRODUCT_SIMPLE;
        return $this->_facade->getType($product) == static::TYPE_PRODUCT_SIMPLE;
    }
    
    private function _hasRolePriceByVariableProduct($product)
    {
        if (!$this->isVariableTypeProduct($product)) {
            return false;
        }
        $productsIDs = $product->get_children();
        $flag = false;
        
        if ($productsIDs) {
            foreach ($productsIDs as $id) {
                $prices = $this->getProductPrices($id);
                
                if ($this->_hasPriceByUserRole($prices)) {
                    $flag = true;
                    break;
                }
            }
        }
        
        return $flag;
    }
    
    private function _hasPriceByUserRole($prices)
    {
        return $this->userRole
               && array_key_exists($this->userRole, $prices)
               && $prices[$this->userRole];
    }

    public function onPriceFilterWidgetResults($products, $min, $max)
    {
        if (!$this->userRole) {
            return $products;
        }
        
        $rolePrices = $this->getRolePricesForWidgetFilter();
        
        $productIDs = array();
        foreach ($rolePrices as $productId => $price) {
            if ($this->_isRolePriceBetweenMinMax($price, $min, $max)) {
                $productIDs[] = $productId;
            }
        }
        
        $products = $this->_facade->getProductsByIDsForWidgetFilter(
            $productIDs
        );
       
        $products = $this->_getPrepareProductsByFilter($products);
            
        return $products;
    }
    
    private function _isRolePriceBetweenMinMax($rolePrice, $min, $max)
    {
        return $rolePrice && $rolePrice <= $max && $rolePrice >= $min;
    }
    
    private function _getPrepareProductsByFilter($products)
    {
        foreach ($products as $key => $product) {
            $products[$key] = (object) $product;
        }
        return $products;
    }
    
    public function onPriceFilterWidgetMaxAmount($max)
    {
        if ($this->userRole) {
            $resultPrices = $this->getRolePricesForWidgetFilter();
            
            if ($resultPrices) {
                return max($resultPrices);
            }            
        }     
        return $max;
    }
    
    public function onPriceFilterWidgetMinAmount($min)
    {
        if ($this->userRole) {
            $resultPrices = $this->getRolePricesForWidgetFilter();
            if ($resultPrices) {
                return min($resultPrices);
            }
        }    
        return $min;
    }
   
    public function getRolePricesForWidgetFilter()
    {
        $productsIDs = $this->_facade->getProductsIDsForRangeWidgetFilter();
        
        $rolePrices = array();
        
        foreach ($productsIDs as $id) {

            $product = $this->createProductInstance($id);

            if (!$this->hasProductID($product)) {
                continue;
            }
            
            $rolePrices[$id] = $this->products->getUserPrice($product);
        }
        
        $rolePrices = $this->_getPrepareRolePrices($rolePrices);
        
        return $rolePrices;
    }
    
    public function hasProductID($product)
    {
        return $this->_facade->getID($product);//!empty($product->id);
    }
    
    private function _getPrepareRolePrices($rolePrices)
    {
        
        foreach ($rolePrices as $key => $item) {
            if (!$item) {
                unset($rolePrices[$key]);    
            }
        }
        
        return $rolePrices;
    }
    
    public function onHideProductByUserRole($query)
    {
        $hideProducts = $this->getOptions(PRICE_BY_ROLE_HIDDEN_PRODUCT_OPTIONS);

        if (!$hideProducts) {
            $hideProducts = array();
        }
        
        if ($this->_hasHideProductByUserRole($hideProducts)) {
            $idProduct = $hideProducts[$this->userRole];
            $query->set('post__not_in', $idProduct);
        }
    }
    
    private function _hasHideProductByUserRole($hideProducts)
    {
        return !is_admin()
               && $this->userRole
               && array_key_exists($this->userRole, $hideProducts);
    }
    
    public function onProductPriceOnlyRegisteredUsers($price)
    {
       if (!$this->_hasAvailableRoleToViewPricesInAllProducts()) {
           $price = $this->textInsteadPrices;
       }
       
       return $price;
    } // end onProductPriceOnlyRegisteredUsers
    
    public function onSalePriceToNewPriceTemplateFilter(
        $price, $sale, $newPrice, $product
    )
    {
        if (!$this->_isRegisteredUser()) {
            return $price;
        }
        $idProduct = $this->_facade->getID($product);
        $prices = $this->getProductPrices($idProduct); //$product->id
        
        if (!$this->_hasPriceByUserRole($prices)) {
            return $price;
        }
        
        $product = $this->getProductNewInstance($product);
        
        if (!$this->products->isAvaliableToDisplaySaleRange($product)) {
        
            $price = $this->products->getFormatedPriceForSaleRange(
                $product,
                $newPrice
            );
            
            $price = $this->getFormattedPrice($price);
        }
        
        return $price;
    } // end onSalePriceToNewPriceTemplateFilter
        
    private function _fetchRolePriceRangeByVariableProdcuct($prices)
    {
        if (!$prices) {
            return false;
        }
        
        $minPrice = $this->getFormattedPrice(min($prices));
        $maxPrice = $this->getFormattedPrice(max($prices));
        
        return $this->fetchProductPriceRange($minPrice, $maxPrice);
    }
    
    public function onProductPriceRangeFilter($price, $product)
    {
        if ($this->_hasNewPriceForVariableProduct($product)) {
            return $price;
        }
        
        if ($this->hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            return $this->_fetchProductPriceRangeFilter($price, $product);
        }
        
        $type = PRICE_BY_ROLE_TYPE_PRODUCT_REGULAR_PRICE;
        $regularPrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            $type
        ); 
        
        $type = PRICE_BY_ROLE_TYPE_PRODUCT_SALE_PRICE;
        $salePrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            $type
        );

        if (empty($salePrices)) {
            return $this->_fetchRegularPriceRangeForVariableProduct(
                $product,
                $regularPrices
            );
        }
        
        return $this->_fetchSalePriceRangeForVariableProduct(
            $product,
            $regularPrices,
            $salePrices
        );
    } // end onProductPriceRangeFilter

    private function _fetchRegularPriceRangeForVariableProduct(
        $product,
        $prices
    )
    {
        
        $price = $this->_fetchRolePriceRangeByVariableProdcuct(
            $prices
        );

        $vars = array(
            'regularPrice' => $price.$this->_facade->getPriceSuffix($product),
        );

        return $this->fetch('price_role_with_sale_variable.phtml', $vars);
    } // end _fetchRegularPriceRangeForVariableProduct

    private function _fetchSalePriceRangeForVariableProduct(
        $product,
        $regularPrices,
        $salePrices
    )
    {
        $regularPrice = $this->_fetchRolePriceRangeByVariableProdcuct(
            $regularPrices
        );

        $salePrice = $this->_fetchRolePriceRangeByVariableProdcuct(
            $salePrices
        );
        
        $suffix = $this->_facade->getPriceSuffix($product);

        $vars = array(
            'regularPrice' => $regularPrice,
            'salePrice'    => $salePrice.$suffix
        );

        return $this->fetch('price_role_with_sale_variable.phtml', $vars);
    } // end _fetchSalePriceRangeForVariableProduct
    
    private function _fetchProductPriceRangeFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);
            
        $priceRangeType = PRICE_BY_ROLE_MIN_PRICE_RANGE_TYPE;
        
        $from = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            true
        );
        
        $priceRangeType = PRICE_BY_ROLE_MAX_PRICE_RANGE_TYPE;
        $to = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            true
        );
        
        if (!$from && !$to) {
            return $price;
        }
        
        $from = $this->getFormattedPrice($from);
        $to = $this->getFormattedPrice($to);
        
        $displayPrice = $this->fetchProductPriceRange($from, $to);

        $price = $displayPrice.$this->_facade->getPriceSuffix($product);
        
        return $price;
    }
    
    private function _hasNewPriceForVariableProduct($product)
    {
        return !$this->_hasRolePriceByVariableProduct($product) 
               && !$this->hasDiscountOrMarkUpForUserRoleInGeneralOptions();
    }
    
    private function _hasNewPriceForRangeProduct($product)
    {
        $productsIDs = $product->get_children();
        $flag = false;
        
        if ($productsIDs) {
            foreach ($productsIDs as $id) {
                $product = $this->createProductInstance($id);
                $hasNewPrice = $this->products->getUserPrice($product);
                if ($hasNewPrice) {
                    $flag = true;
                    break;
                }
            }
        }
        return $flag;
    }
    
    public function isVariableTypeProduct($product)
    {
        return $this->_facade->getType($product) == 'variable';
    }
    
    protected function fetchProductPriceRange($from, $to)
    {
        if ($from == $to) {
            $template = '%1$s';
        } else {
            $template = '%1$s&ndash;%2$s';
        }
        
        $content = _x($template, 'Price range: from-to', 'woocommerce');
        
        $content = sprintf($content, $from, $to);
        
        return $content;
    } // end fetchProductPriceRange
    
    protected function getMainProductId()
    {
        if ($this->mainProductOnPage) {
            return $this->mainProductOnPage;
        }
        
        if (!$this->isProductPage()) {
            return false;
        }
        
        $this->mainProductOnPage = get_the_ID();
        
        return $this->mainProductOnPage;
    } //end getMainProductId
    
    protected function onDisplayCustomerSavings()
    {
        if ($this->_isMarkupEnabledOrDiscountFromRolePrice()) {
            return false;
        }
        
        $this->products->onDisplayCustomerSavings();
        
        $this->mainTotals = true;
        
        $this->addFilterListener(
            'woocommerce_cart_totals_order_total_html',
            'onDisplayCustomerTotalSavingsFilter',
            10,
            2
        );
        
        $this->addFilterListener(
            'wcs_cart_totals_order_total_html',
            'onDisplayCustomerTotalSavingsFilter',
            10,
            2
        );
    } // end onDisplayCustomerSavings 
    
    private function _isMarkupEnabledOrDiscountFromRolePrice()
    {
        return !$this->_isDiscountTypeEnabled()
               && $this->_isRolePriceDiscountTypeEnabled();
    } // end _isMarkupEnabledOrDiscountFromRolePrice
    
    public function isSubcribePluginProducts($product)
    {
        $types = array(
            'subscription_variation',
            'subscription',
            'variable-subscription'
        );
        
        $type = $this->_facade->getType($product);
        
        return in_array($type, $types);
    }
    
    public function getSubscriptionProductsCount($product)
    {
        return $product['quantity'];
    }
    
    public function getFee($subscription, $coupons = false)
    {
        $fee = false;
        
        
        if (!$this->isFeeExist($subscription)) {
            return $fee;
        }
        
        $fee = $subscription->subscription_sign_up_fee;
        
        if ($this->isTaxExist()) {
            $discountCoupon = $this->getCouponsDiscount($coupons);
            if ($discountCoupon) {
               $feeCupon = $fee - $fee * $discountCoupon / 100;
               $fee = $feeCupon + $feeCupon * $this->subscriptionTax / 100; 
            } else {
                $feeTax = $fee * $this->subscriptionTax / 100; 
                $fee = $fee + $feeTax;
            }
           
        }

        $fee = $fee * $this->subscriptionCount;
        
        return $fee;
    }
    
    public function getCouponsDiscount($coupons)
    {
        $discounts = array();
        
        foreach ($coupons as $key => $item) {
            if ($item->discount_type == 'sign_up_fee_percent') {
                $discounts[] = $item->coupon_amount; 
            }
        }
        
        return count($discounts) > 0 ? max($discounts) : false;
    }
    
    public function isTaxExist()
    {
        return $this->taxPersent > 0;
    }
    
    public function getUserTotalWithSubscription($total)
    {       
        $product = $this->subscribeProduct;
        
        if (!$this->mainTotals) {
            $userPrice = $this->getUserPriceForSubscriptions($product);
            $userPrice = $userPrice * $this->subscriptionCount;
            $userPrice += $this->getShippingTotalWithTax();
            $userPrice = $userPrice * $this->subscriptionTax / 100 + $userPrice;
            
            return $userPrice;
        }
        
        if ($this->subscriptionFee) {
            $total = $total - $this->subscriptionFee;
        }
        
        return $total;
    }
    
    public function getRegularPriceForSubscription($subscribeProduct) 
    {
        $id = $this->getProductID($subscribeProduct);
        $product = $this->createProductInstance($id);
        
        $regularPrice = $this->products->getRegularPrice($product, true);

        return $regularPrice;
    }
    
    public function getUserPriceForSubscriptions($subscribeProduct)
    {
        $id = $this->getProductID($subscribeProduct);
        $product = $this->createProductInstance($id);
        
        $userPrice = $this->products->getUserPrice($product, true);
        
        return $userPrice;
    }
    
    public function getSubscriptionPriceWithTaxAndFee($product)
    {
        $fee = $this->subscriptionFee;
        
        $price = $product['data']->subscription_price;
        
        $priceTax = ($price / 100) * $this->subscriptionTax;
        
        return $fee + $price + $priceTax;
    }
    
    public function isOnlySubscriptionInCart($cart)
    {
        $products = $cart->getProducts();
        
        return count($products) == 1;
    }
    
    public function getTotalRetailWithoutSubscription($cart)
    {
        $products = $cart->getProducts();
        $total = 0;
        
        foreach ($products as $product) {
            if ($this->isSubcribePluginProducts($product['data'])) {
                continue;
            }

            $price = $product['data']->price * $product['quantity'];
            $taxPersent = $this->getSubscriptionTaxPersent($product);
            $tax = $price / 100 * $taxPersent;
            $total += $price + $tax;
        }
        
        return $total;
    }
    
    public function getProductID($product)
    {
        if ($this->isSubscribeVariableProduct($product)) {
            $idProduct = $this->_facade->getVariationID($product);
        } else {
            $idProduct = $this->_facade->getID($product);
        }
        
        return $idProduct;
    }
    
    public function isSubscribeVariableProduct($product) 
    {
        return (bool) $this->_facade->getVariationID($product);
    }
    
    public function getShippingCost($cart)
    {
        $shippingMethods = WC()->session->get(
            'chosen_shipping_methods',
            array()
        );

        $shippingCost = $cart->getShippingCost($shippingMethods);
        
        return $shippingCost;
    }
    
    public function getTotalRetailWithSubscription($total, $cart)
    {
         
        $product = $this->subscribeProduct;

        $shippingCost = $this->getShippingCost($cart);
        
        if (!$this->mainTotals) {
            
            $regularPrice = $this->getRegularPriceForSubscription($product);
            $regularPrice += $shippingCost;
            
            $priceWidthTax = $regularPrice * $this->subscriptionTax / 100;
            $priceWidthTax += $regularPrice;
            
            $regularPrice = $priceWidthTax * $this->subscriptionCount;
            return $regularPrice; 
        }
        
        $isTrial = $this->isTrialSubscription($product);
        
        if ($isTrial) {
            $price = $this->subscriptionPrice * $this->subscriptionCount;
            $total = $total - $price;
        }
        
        if ($this->isOnlySubscriptionInCart($cart) || !$isTrial) {
            return $total;
        }
        
        $total = $this->getTotalRetailWithoutSubscription($cart);
        
        $total += $shippingCost;
        return $total;
    }
    
    public function setSubscriptionProductOption($cart)
    {
        $products = $cart->getProducts();
        $product = $products[$this->subscriptionKey];
        
        $this->subscribeProduct = $product['data'];
        $this->subscriptionCount = $this->getSubscriptionProductsCount(
            $product
        );
       
        $this->subscriptionTax = $this->getSubscriptionTaxPersent($product);
        
        $coupons = $cart->getCoupons();
        
        $this->subscriptionFee = $this->getFee($product['data'], $coupons);
        
        $this->subscriptionPrice = $this->getSubscriptionPriceWithTaxAndFee(
            $product
        );
    }
    
    public function getSubscriptionTaxPersent($product)
    {
        $total = $product['line_total'];
        $taxTotal = $product['line_tax'];
        
        $percent = $taxTotal / ($total / 100);

        return $percent;
    }
    
    public function isSubscriptionInCart($cart)
    {   
        $products = $cart->getProducts();

        foreach ($products as $key => $value) {
            $product = $value['data'];
            
            if (!$this->isSubcribePluginProducts($product)) {
                continue;
            }
            
            if ($this->isSubscriptionRenewal($value)) {
                return false;
            }
            
            $this->subscriptionKey = $key;

            return true;
        }
        
        return false;
    }
    
    public function isSubscriptionRenewal($subscription)
    {
        return !empty($subscription['subscription_renewal']);
    }
    
    public function isTrialSubscription($product)
    {
        return !empty($product->subscription_trial_length);
    }
    
    public function isFeeExist($product) 
    {
        return !empty($product->subscription_sign_up_fee);
    }
    
    public function onDisplayCustomerTotalSavingsFilter($total)
    {
        if (!$this->_hasOptionInSettings('showCustomerSavings')
            || !$this->_isEnabledPageInCustomerSavingsOption('cartTotal')
            || !$this->_isRegisteredUser()) {
            return $total;
        }
        
        $cart = WooCommerceCartFacade::getInstance();

        $userTotal = $cart->getTotal(); 
        $retailTotal = $this->getRetailTotal();
        $isGeneralTotals = $this->mainTotals;
        
        if ($this->isSubscriptionInCart($cart)) {
            $this->setSubscriptionProductOption($cart);
            $userTotal = $this->getUserTotalWithSubscription($userTotal);
            $retailTotal = $this->getTotalRetailWithSubscription(
                $retailTotal, 
                $cart
            );
            $this->mainTotals = false;
        }
        
        if (!$this->_isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)) {
            return $total;
        }

        $totalSavings = $this->getTotalSavings($retailTotal, $userTotal);

        $userTotal = $this->getFormattedPrice($userTotal);
        $retailTotal = $this->getFormattedPrice($retailTotal);
        
        $userPrice = $this->fetchPrice($userTotal, static::TYPE_PRICE_USER);
        
        $vars = array(
            'regularPrice'    => $this->fetchPrice($retailTotal),
            'userPrice'       => $userPrice,
            'userDiscount'    => $this->fetchTotalSavings($totalSavings),
            'isGeneralTotals' => $isGeneralTotals
        );
        
        if ($isGeneralTotals && $this->_hasSubscriptionFee()) {
            $vars['fee'] = $this->getFormattedPrice($this->subscriptionFee);
        }
        
        return $this->fetch('customer_total_savings_price.phtml', $vars);
    } // end onDisplayCustomerTotalSavingsFilter
    
    private function _hasSubscriptionFee()
    {
        return $this->subscriptionFee;
    }
    
    protected function getRetailTotal()
    {
        $retailSubTotal = $this->getRetailSubTotalWithTax();
        $shippingTotal = $this->getShippingTotalWithTax();
        $retailTotal = $retailSubTotal + $shippingTotal;
        return $retailTotal;
    } // end getRetailTotal
    
    protected function getShippingTotalWithTax()
    {
        $cart = WooCommerceCartFacade::getInstance();
        
        $shippingTotal = $cart->getShippingTotal();
        $shippingTaxTotal = $cart->getShippingTaxTotal();

        return $shippingTotal + $shippingTaxTotal;
    } // end getShippingTotalWithTax
    
    protected function getRetailSubTotalWithTax()
    {
        $cart = WooCommerceCartFacade::getInstance();
        
        $subtotal = $cart->getTotalFullPrice(); 
        
        $taxTotal = $cart->getTaxTotal();

        $taxPersent = $this->getTaxTotalPersent($subtotal, $taxTotal);
        
        $this->taxPersent = $taxPersent;
        
        $retailSubTotal = $this->getRetailSubTotal();
        
        $retailSubTotalTax = $retailSubTotal / 100 * $taxPersent;
        
        $retailSubTotalWithTax = $retailSubTotal;
        
        if ($this->isTaxExcludedFromPriceAndDisplaysSeparately($cart)) {        
            $retailSubTotalWithTax += $retailSubTotalTax;
        }
        
        return $retailSubTotalWithTax;
    } // end getRetailSubTotalWithTax
    
    public function isTaxExcludedFromPriceAndDisplaysSeparately($cart)
    {
        return (!$cart->isPricesIncludeTax() 
            && !$cart->isTaxInclusionOptionOn());
    }

    protected function getTaxTotalPersent($subtotal, $taxTotal)
    {
        if ($subtotal == 0) {
            return 0;
        }   
            
        $taxPersent = 100 * $taxTotal / $subtotal;
        
        return $taxPersent;
    } // end getTaxTotalPersent
    
    public function onVariationPriceFilter(
        $price, $product, $priceRangeType, $display
    )
    {
        $product = $this->getProductNewInstance($product);
               
        $userPrice = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            $display
        );
        
        if ($userPrice) {
            $price = $this->getPriceWithFixedFloat($userPrice);
        }

        return $price;
    } // end onVariationPriceFilter
    
    public function getPriceByRangeType($product, $rangeType, $display)
    {
        if ($this->_isMaxPriceRangeType($rangeType)) {
            $price = $this->products->getMaxProductPice($product, $display);
        } else {
            $price = $this->products->getMinProductPice($product, $display);
        }
        
        return $price;
    } // end getPriceByRangeType
    
    private function _isMaxPriceRangeType($rangeType)
    {
        return $rangeType == PRICE_BY_ROLE_MAX_PRICE_RANGE_TYPE;
    } // end _isMaxPriceRangeType
    
    public function getRetailSubTotal()
    {
        $cart = WooCommerceCartFacade::getInstance();
        $products = $cart->getProducts();

        $total = 0;
        $displayMode = ($cart->isPricesIncludeTax()) ? true : false;

        foreach ($products as $key => $product) {
            if ($this->_isVariableProduct($product)) {
                $idProduct = $product['variation_id'];
            } else {
                $idProduct = $product['product_id'];
            }
            
            $productInstance = $this->createProductInstance($idProduct);
            $price = $this->products->getRegularPrice(
                $productInstance,
                $displayMode
            );
            
            $total += $price * $product['quantity'];
        }
        
        return $total;
    } // end getRetailSubTotal
    
    private function _isVariableProduct($product)
    {
        return array_key_exists('variation_id', $product)
               && !empty($product['variation_id']);
    } // end _isVariableProduct
    
    public function fetchTotalSavings($totalSavings)
    {
        $vars = array(
            'discount' => $totalSavings
        );

        return $this->fetch('discount.phtml', $vars);
    } // end fetchTotalSavings
    
    public function fetchPrice($price, $type = self::TYPE_PRICE_REGULAR)
    {
        $vars = array(
            'price' => $price,
            'type'  => $type
        );
        
        return $this->fetch('price.phtml', $vars);
    } // end fetchRegularPrice
    
    protected function getTotalSavings($retailTotal, $userTotal)
    {        
        $savings = round(100 - ($userTotal/$retailTotal * 100), 2);
        
        return $savings;
    } // end getTotalSavings
    
    private function _isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)
    {
        return $retailTotal > $userTotal;
    } // end _isRetailTotalMoreThanUserTotal

    private function _isRoleSalePriceLowerThenRolePrice($product)
    {
        return $this->getSalePrice($product) < $this->getPrice($product);
    }

    /**
     * Display price HTML for all product type like simplae and variable.
     * 
     * @param string $price html content for display price
     * @param WC_Product $product
     * @return string
     */
    public function onDisplayCustomerSavingsFilter(
        $price, $product
    )
    {
        if ($this->_isOneHundredPercentDiscountEnabled()) {
            return $this->fetch('free.phtml');
        }
        
        if (
            $this->_hasRolePriceByVariableProduct($product) ||
            $this->isVariableTypeProduct($product)
        ) {
            return $price;
        }

        $product = $this->getProductNewInstance($product);

        $result = $this->_hasConditionsForDisplayCustomerSavingsInProduct(
            $product
        );
        
        if (!$result) {

            if (
                $this->_isDisplayDefaultPriceForCustomerSavingsFilter($product)
            ) {
                return $this->_fetchUserRolePrice($product);
            }

            if (
                $this->_hasSalePriceForUserRole($product) && 
                $this->_isRoleSalePriceLowerThenRolePrice($product)
            ) {
                $content = $this->_fetchPriceAndSalePriceForUserRole($product);
                return $content;
            }

            return $price;
        }
        $regularPrice = $this->products->getRegularPrice($product, true);
        
        $userPrice = $this->products->getUserPrice($product, true);
        
        $result = $this->_isAvaliablePricesToDisplayCustomerSavings(
            $regularPrice,
            $userPrice
        );
        
        if (!$result) {
            return $price;
        }

        $regularPriceSuffix = $this->_getSuffixForRegularPrice($product);
        
        $userDiscount = $this->fetchUserDiscount(
            $regularPrice,
            $userPrice,
            $product
        );
        $regularPrice = $this->getFormattedPrice($regularPrice);
        $formattedPrice = $this->getFormattedPrice($userPrice);
        
        $userPrice = $this->fetchPrice(
            $formattedPrice,
            static::TYPE_PRICE_USER
        );
        
        $vars = array(
            'regularPrice'       => $this->fetchPrice($regularPrice),
            'userPrice'          => $userPrice,
            'userDiscount'       => $userDiscount,
            'priceSuffix'        => $this->products->getPriceSuffix($product),
            'regularPriceSuffix' => $regularPriceSuffix
        );
        
        if ($this->isSubcribePluginProducts($product)) {
            $content = $this->fetch(
                'customer_subscription_product_savings_price.phtml', 
                $vars
            );
            
            return $content;
        }
        
        if (!$userPrice) {
            return $this->_fetchFreePrice($price);
        }

        return $this->fetch('customer_product_savings_price.phtml', $vars);
    } // end onDisplayPriceContentForSingleProductFilter
    
    private function _getSuffixForRegularPrice($product)
    {
        return $this->products->getPriceSuffix(
            $product,
            $this->_getRegularPriceBeforeAnyTaxCalculationsProcessed($product)
        );
    } // end _getSuffixForRegularPrice
    
    private function _getRegularPriceBeforeAnyTaxCalculationsProcessed($product)
    {
        return $this->products->getRegularPrice($product, false);
    } // end _getRegularPriceBeforeAnyTaxCalculationsProcessed

    private function _isOneHundredPercentDiscountEnabled()
    {
        if ($this->_isRegisteredUser()) {
            return $this->getAmountOfDiscountOrMarkUp() >= 100 &&
                   $this->_isPercentDiscountType();
        }
        return false;
    } // end _isOneHundredPercentDiscountEnabled

    private function _isDisplayDefaultPriceForCustomerSavingsFilter($product)
    {
        $productPrice = $this->getPrice($product);
        
        $emptyPriceSymbol = $this->_facade->getEmptyPriceSymbol();
        
        return $productPrice && !$this->_isDefaultCurrencyActive() &&
               $this->_isRegisteredUser() &&
               $productPrice !== $emptyPriceSymbol;
    } // end _isDisplayDefaultPriceForCustomerSavingsFilter
    
    public function _fetchFreePrice($price)
    {
        if ($this->hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            return $price;
        }
        
        return $this->fetch('free.phtml');
    }

    private function _hasSalePriceForUserRole($product)
    {
        if (!$this->isVariableTypeProduct($product)) {
            $salePrice = $this->getSalePrice($product);
            
            return (bool) $salePrice;
        }
    }

    private function _isDefaultCurrencyActive()
    {
        $defaultCurrency = WooCommerceFacade::getDefaultCurrencyCode();
        $activeCurrency = WooCommerceFacade::getBaseCurrencyCode();

        return $defaultCurrency == $activeCurrency;
    } // end _isDefaultCurrencyActive

    private function _fetchUserRolePrice($product)
    {
        $price = $this->getPrice($product);
        
        $formatPrice = $this->getFormattedPrice($price);
        
        return $this->fetchPrice($formatPrice, static::TYPE_PRICE_USER);
    } // end _fetchUserRolePrice
    
    private function _fetchPriceAndSalePriceForUserRole($product)
    {
        $price = $this->getPrice($product);
        $salePrice = $this->getSalePrice($product);
        
        $vars = array(
            'price' => $this->getFormattedPrice($price),
            'salePrice' => $this->getFormattedPrice($salePrice)
        );
        
        $content = $this->fetch(
            'price_role_width_sale.phtml',
            $vars
        );
        
        return $content;
    }
    
    private function _isAvaliablePricesToDisplayCustomerSavings(
        $regularPrice, $userPrice
    )
    {
        return $userPrice < $regularPrice;
    } // end _isAvaliablePricesToDisplayCustomerSavings
    
    public function fetchUserDiscount($regularPrice, $userPrice, $product)
    {
        $discount = round(100 - ($userPrice/$regularPrice * 100), 2);
        $vars = array(
            'discount' => $discount
        );

        return $this->fetch('discount.phtml', $vars);
    } // end fetchRegularPrice
    
    protected function getFormattedPrice($price)
    {
        return wc_price($price);
    } // end getFormattedPrice
    
    private function _hasConditionsForDisplayCustomerSavingsInProduct(
        $product
    )
    {
        if (!$this->_hasNewPriceForProduct($product)) {
            return false;
        }

        return $this->_hasOptionInSettings('showCustomerSavings')
               && $this->_isRegisteredUser()
               && $this->_isAllowedPageToDisplayCustomerSavings($product)
               && $this->_isAvaliableProductTypeToDispalySavings($product);
    } // end _hasConditionsForDisplayCustomerSavingsInProduct
    
    private function _hasNewPriceForProduct($product)
    {
        if ($this->isVariableTypeProduct($product)) {
            return $this->_hasNewPriceForRangeProduct($product);
        }
        
        $idProduct = $this->products->getProductId($product);
        $rolePrice = $this->getRolePrice($idProduct);
        $hasDiscount = $this->hasDiscountOrMarkUpForUserRoleInGeneralOptions();
        
        return $rolePrice ||
               ($hasDiscount && !$this->isIgnoreDiscountForProduct($idProduct));
    } // end _hasNewPriceForProduct
    
    private function _isAvaliableProductTypeToDispalySavings($product)
    {
        $result =  $this->products->isAvaliableProductTypeToDispalySavings(
            $product
        );
        
        return $result;
    } // end _isAvaliableProductTypeToDispalySavings
    
    private function _isAllowedPageToDisplayCustomerSavings($product)
    {
        $isEnabledProductPage = $this->_isEnabledPageInCustomerSavingsOption(
            'product'
        );
        
        $isEnabledArchivePage = $this->_isEnabledPageInCustomerSavingsOption(
            'archive'
        );
        
        $mainProduct = $this->_isMainProductInSimpleProductPage($product);
        
        $isProductPage = $this->isProductPage();
        
        if ($isProductPage && $isEnabledProductPage && $mainProduct) {
            return true;
        }

        if (!$isProductPage && $isEnabledArchivePage) {
            return true;
        }
        
        if ($this->_isProductParentMainproduct($product, $mainProduct)) {
            return true;
        }

        return false;
    } // end _isAllowedPageToDisplayCustomerSavings
    
    private function _isProductParentMainproduct($product)
    {
        $idParent = $this->_facade->getParentID($product);
        if (!$idParent) {
            return false;
        }
        
        return $idParent == $this->mainProductOnPage;
    } // end _isProductParentMainproduct
    
    private function _isMainProductInSimpleProductPage($product)
    {
        return $this->_facade->getID($product) == $this->mainProductOnPage;
    } // end _isMainProductInSimpleProductPage
    
    private function _isEnabledPageInCustomerSavingsOption($page)
    {
        $settings = $this->getSettings();
        return in_array($page, $settings['showCustomerSavings']);
    } // end _isEnabledPageInCustomerSavingsOption
    
    protected function onHidePrice()
    {
        if (!$this->_hasAvailableRoleToViewPricesInAllProducts()) {
            $this->products->replaceAllPriceToText();
            $this->removeFilter(
                'woocommerce_get_price_html',
                'onDisplayCustomerSavingsFilter'
            );
            
            $this->doHideSubscriptionProductPrice();   
            
        } else {
            $this->products->replaceAllPriceToTextInSomeProduct();
        }
    } // end onHidePrice
    
    protected function doHideSubscriptionProductPrice()
    {
        $this->addFilterListener(
            'woocommerce_subscriptions_product_price_string',
            'onReplaceAllPriceToTextInAllProductFilter',
            10,
            3
        );
        $this->addFilterListener(
            'woocommerce_variable_subscription_price_html',
            'onReplaceAllPriceToTextInAllProductFilter',
            10,
            2
        );
    }
    
    protected function removeFilter($hook, $methodName, $priority = 10)
    {
        remove_filter($hook, array($this, $methodName), $priority);
    } // end removeFilter
    
    protected function onHideAddToCartButton()
    {
        if ($this->_isEnabledHideAddToCartButtonOptionInAllProducts()) {
            $this->removeAllAddToCartButtons();
        } else {
            $this->removeAddToCartButtonsInSomeProduct();
        }
    } // end onHideAddToCartButton
    
    protected function onFilterPriceByRolePrice()
    {
        $this->products->onFilterPriceByRolePrice();
    } // end onFilterPriceByRolePrice
    
    public function onDisplayPriceByRolePriceFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);

        if (!$this->_isRegisteredUser()) {
            return $price;
        }
        
        $this->userPrice = $price;

        if (!$this->_hasUserRoleInActivePLuginRoles()) {
            return $this->getPriceWithFixedFloat($this->userPrice);
        }
       
        $newPrice = $this->getRolePriceOrSale($product);

        if ($newPrice) {
            $idProduct = $this->products->getProductId($product);
            $this->addIdToListOfPruductsWithRolePrice($idProduct);
            $this->userPrice = $newPrice;
            return $this->getPriceWithFixedFloat($this->userPrice);
        }

        if (
            $this->isVariableTypeProduct($product) &&
            $this->_facade->isEnabledTaxCalculation() &&
            $this->_facade->hasPriceDisplaySuffixPriceIncludingOrExcludingTax()
        ) {
            $priceFacade = UserRolePriceFacade::getInstance();

            return $priceFacade->getRolePriceForWoocommercePriceSuffix(
                $product,
                $this->userRole,
                $this,
                $this->_facade
            );
        }
        
        if ($this->_emptyPriceHelper->isDisplayTextInsteadOfPriceEnabled()) {
            return $this->_facade->getEmptyPriceSymbol();
        }

        return $this->userPrice;
    } // end onDisplayPriceByRolePriceFilter
    
    protected function onFilterPriceByDiscountOrMarkup()
    {
        $this->products->onFilterPriceByDiscountOrMarkup();
    } // end onFilterPriceByDiscountOrMarkup
    
    public function onDisplayPriceByDiscountOrMarkupFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);
        
        if (!$this->_isRegisteredUser()) {
            return $price;
        }

        $this->userPrice = $price;

        $newPrice = $this->getPriceWithDiscountOrMarkUp($product, $price);
        
        $idProduct = $this->products->getProductId($product);
        $this->addIdToListOfPruductsWithRolePrice($idProduct);
        $this->userPrice = $this->getPriceWithFixedFloat($newPrice);
       
        return $this->userPrice;
    } // end onDisplayPriceByDiscountOrMarkupFilter
    
    protected function addIdToListOfPruductsWithRolePrice($idProduct)
    {
        if (in_array($idProduct, $this->_listOfProductsWithRolePrice)) {
            return false;
        }
        
        $this->_listOfProductsWithRolePrice[] = $idProduct;
    } // end addIdToListOfPruductsWithRolePrice
    
    public function getListOfPruductsWithRolePrice()
    {
        return $this->_listOfProductsWithRolePrice;
    } // end getListOfPruductsWithRolePrice
    
    private function _hasUserRoleInActivePLuginRoles()
    {
        $roles = $this->getAllUserRoles();
        
        if (!$roles) {
            return false;
        }
        
        $activeRoles = $this->getActiveRoles();

        if (!$activeRoles) {
            return false;
        }
        
        
        $result =  $this->_hasOneOfUserRolesInActivePLuginRoles(
            $activeRoles,
            $roles
        );
        
        return $result;
    } // end _hasUserRoleInActivePLuginRoles
    
    private function _hasOneOfUserRolesInActivePLuginRoles($activeRoles, $roles)
    {
        $result = false;

        foreach ($roles as $key => $role) {
            $result = array_key_exists($role, $activeRoles);
            
            if ($result) {
                return $result;
            }
        }
    } // end _hasOneOfUserRolesInActivePLuginRoles
    
    /**
     * Returns price for product. 
     * @param WC_Product $product
     * @param float $originalPrice
     */
    public function getPriceWithDiscountOrMarkUp($product, $originalPrice)
    {
        //FIXME: Need Refactoring
        
        $amount = $this->getAmountOfDiscountOrMarkUp();
       
        $idPost = $this->_facade->getID($product);
        
        
        if ($this->_facade->getVariationID($product)) {
            $idPost = $this->_facade->getVariationID($product);
        }
        
        if ($this->isIgnoreDiscountForProduct($idPost)) {
            $rolePrice = $this->getRolePrice($idPost);
            return $rolePrice ? $rolePrice : $originalPrice;
        }
        
        $isNotRoleDiscountType = false;
        $price = PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE;
        
        if ($this->_isRolePriceDiscountTypeEnabled()) {
            $price = $this->getPrice($product);
            
            if (!$price) {
                $isNotRoleDiscountType = true;
            }
        }

        if (!$price) {
            $price = $this->products->getRegularPrice($product);
        }
        
        if ($isNotRoleDiscountType) {
            return $price;
        }
        
        if ($this->_isPercentDiscountType()) {
            $amount = $this->getAmountOfDiscountOrMarkUpInPercentage(
                $price,
                $amount
            );
        }

        if ($this->_isDiscountTypeEnabled()) {
            $minimalPrice = PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE;
            $newPrice = ($amount > $price) ? $minimalPrice : $price - $amount;
        } else {
            $newPrice = $price + $amount;
        }
        
        $numberOfDecimals = $this->_facade->getNumberOfDecimals();
        
        if (!$numberOfDecimals) {
            $newPrice = round($newPrice);
        }
                
        return $newPrice;
    } // end getPriceWithDiscountOrMarkUp
    
    public function getAmountOfDiscountOrMarkUpInPercentage($price, $discount)
    {
        $discount = $price / 100 * $discount;
        
        return $discount;
    } // end getAmountOfDiscountOrMarkUpInPercentage
    
    private function _isDiscountTypeEnabled()
    {
        $settings = $this->getSettings();
        return $settings['discountOrMakeUp'] == 'discount';
    } // end _isDiscountTypeEnabled
    
    private function _isPercentDiscountType()
    {
        $settings = $this->getSettings();
        $discountType = $settings['discountByRoles'][$this->userRole]['type'];
        return $discountType == PRICE_BY_ROLE_PERCENT_DISCOUNT_TYPE;
    } // end _isPercentDiscountType
    
    public function getPrice($product)
    {
        return $this->products->getRolePrice($product);
    } // end getPrice
    
    public function getSalePrice($product)
    {
        return $this->products->getRoleSalePrice($product);
    }
    
    public function getRolePriceOrSale($product)
    {
        $salePrice = $this->getSalePrice($product);
        
        if ($salePrice && $this->_isDefaultCurrencyActive()) {
            return $salePrice;
        }
        
        return  $this->getPrice($product);
    }
    
    private function _isRolePriceDiscountTypeEnabled()
    {
        $settings = $this->getSettings();
        $userRole = $this->userRole;
        
        if (!$settings) {
            return false;
        }
        
        if (!isset($settings['discountByRoles'][$userRole]['priceType'])) {
            return false;
        }
        
        $priceType = $settings['discountByRoles'][$userRole]['priceType'];
        
        return $priceType == PRICE_BY_ROLE_DISCOUNT_TYPE_ROLE_PRICE;
    } // end _isRolePriceDiscountTypeEnabled
    
    public function getAmountOfDiscountOrMarkUp()
    {
        $settings = $this->getSettings();
        
        return $settings['discountByRoles'][$this->userRole]['value'];
    } // end getAmountOfDiscountOrMarkUp
    
    
    
    public function onReplaceAllPriceToTextInSomeProductFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            return $this->fetchContentInsteadOfPrices();
        }
        
        return $price;
    } // end onReplaceAllPriceToTextInSomeProductFilter
    
    protected function removeAddToCartButtonsInSomeProduct()
    {
        $this->products->removeLoopAddToCartLinksInSomeProducts();
        $this->removeAddToCartButtonInProductPage();
    } // end removeAddToCartButtonsInSomeProduct
    
    protected function removeAddToCartButtonInProductPage()
    {
        if (!$this->isProductPage()) {
            return false;
        }
        
        $idProduct = get_the_ID();
        $product = $this->createProductInstance($idProduct);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            $type = $this->_facade->getType($product);
            $this->products->removeAddToCartButton($type);
        }
    } // end removeAddToCartButtonInProductPage
    
    // FIXME: PPC
    public function createProductInstance($idProduct, $params = array())
    {
        $wooFactory = new WC_Product_Factory();
        $product = $wooFactory->get_product($idProduct);
        return $product;
    } // end createProductInstance
    
    // FIXME: PPC
    protected function getProductNewInstance($product)
    { 
        $params = array(
            'product_type' => $this->_facade->getType($product)
        );
        
        $idProduct = $this->getProductIdFromProductInstance($product);
       
        if (!$idProduct) {
            throw new Exception('Undefined product Id');
        }

        return $this->createProductInstance($idProduct, $params);
    } // end getProductNewInstance
    
    protected function getProductIdFromProductInstance($product)
    {
        if ($this->_hasVariationIdInProductInstance($product)) {
            $producId = $this->_facade->getVariationID($product);
        } else {
            $producId = $this->_facade->getID($product);//$product->id;
        }
        
        return $producId;
    } // end getProductIdFromProductInstance
    
    private function _hasVariationIdInProductInstance($product)
    {
        return (bool) $this->_facade->getVariationID($product);
    } // end _hasVariationIdInProductInstance
    
    public function isProductPage()
    {
        return is_product();
    } // end isProductPage
    
    public function onRemoveAddToCartButtonInSomeProductsFilter(
        $button, $product
    )
    {
        $product = $this->getProductNewInstance($product);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            return '';
        }

        return $button;
    } // end onRemoveAddToCartButtonInSomeProductsFilter
    
    private function _hasAvailableRoleToViewPricesInProduct($product)
    {
        if ($this->_isChildProduct($product)) {
            $parentID = $this->_facade->getParentID($product);
            $product = $this->createProductInstance($parentID);
        }

        if (!$this->_isAvailablePriceInProductForUnregisteredUsers($product)) {
            $this->setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }
    
        if (!$this->_isAvailablePriceInProductForRegisteredUsers($product)) {
            $this->setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
        }
        
        return true;
    } // end _hasAvailableRoleToViewPricesInProduct
    
    private function _isChildProduct($product)
    {
        return $this->_facade->_isChildProduct($product);
    } // end _isChildProduct
    
    private function _isAvailablePriceInProductForUnregisteredUsers($product)
    {
        return $this->_isRegisteredUser() || (!$this->_isRegisteredUser()
               && !$this->_hasOnlyRegisteredUsersInProductSettings($product));
    } // end _isAvailablePriceInProductForUnregisteredUsers
    
    private function _hasOnlyRegisteredUsersInProductSettings($product)
    {
        if (!$product) {
            return false;
        }
        $produtcId = $this->_facade->getID($product);//$product->id;
        
        if (!$produtcId) {
            return false;
        }

        $options = $this->getMetaOptions(
            $produtcId,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
       
        if (!$options) {
            return false;
        }

        return array_key_exists(
            'onlyRegisteredUsers',
            $options
        );
    } // end _hasOnlyRegisteredUsersInProductSettings
    
    private function _isAvailablePriceInProductForRegisteredUsers($product)
    {
        return !$this->_isRegisteredUser() || ($this->_isRegisteredUser()
           && !$this->_hasHidePriceOptionForRoleInProductSettings($product));
    } // end _isAvailablePriceInProductForRegisteredUsers
    
    public function onReplaceAllPriceToTextInAllProductFilter()
    {
        return $this->fetchContentInsteadOfPrices();
    } //end onReplaceAllPriceToTextInAllProductFilter
    
    public function fetchContentInsteadOfPrices()
    {
        $vars = array(
            'text' => $this->textInsteadPrices
        );
        
        return $this->fetch('custom_text.phtml', $vars);
    } // end fetchContentInsteadOfPrices
    
    private function _hasAvailableRoleToViewPricesInAllProducts()
    {
        if (!$this->_isAvailablePriceInAllProductsForUnregisteredUsers()) {
            $this->setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }

        if (!$this->_isAvailablePriceInAllProductsForRegisteredUsers()) {
            $this->setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
        }

        return true;
    } // end _hasAvailableRoleToViewPricesInAllProducts
    
    private function _isAvailablePriceInAllProductsForRegisteredUsers()
    {
        return !$this->_isRegisteredUser() || ($this->_isRegisteredUser()
               && !$this->_hasHidePriceOptionForRoleInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForRegisteredUsers
    
    public function setValueForContentInsteadOfPrices($optionName)
    {
        $settings = $this->getSettings();
        
        $this->textInsteadPrices = $settings[$optionName];
    } // end getContentInsteadOfPrices
    
    private function _isAvailablePriceInAllProductsForUnregisteredUsers()
    {
        return $this->_isRegisteredUser() || (!$this->_isRegisteredUser()
               && !$this->_hasOnlyRegisteredUsersInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForUnregisteredUsers
    
    private function _hasOnlyRegisteredUsersInGeneralSettings()
    {
        $settings = $this->getSettings();
        return array_key_exists('onlyRegisteredUsers', $settings);
    } // end _hasOnlyRegisteredUsersInGeneralSettings
    
    public function removeAllAddToCartButtons()
    {
        $this->products->removeAllLoopAddToCartLinks();
        $this->products->removeAddToCartButton();
    } //end removeAllAddToCartButtons

    public function removeGroupedAddToCartLinkAction()
    {
        $vars = array(
            'settings' => $this->getSettings(),
        );
        echo $this->fetch('hide_grouped_add_to_cart_buttons.phtml', $vars);
    } // end removeGroupedAddToCartLinkAction
    
    public function removeVariableAddToCartLinkAction()
    {
        $vars = array(
            'settings' => $this->getSettings(),
        );
        echo $this->fetch('hide_variable_add_to_cart_buttons.phtml', $vars);
    } // end removeVariableAddToCartLinkAction

    public function onRemoveAllAddToCartButtonFilter($button, $product)
    {
        if ($this->_hasAddToCartButtonText($product)) {
            $settings = $this->getSettings();
            return $settings['textForNonRegisteredUsers'];
        }
        
        return $button;
    } // end onRemoveAddToCartButtonFilter
    
    private function _hasAddToCartButtonText($product)
    {
        return $this->_facade->isProductPurchasableAndInStock($product);
    } // _hasAddToCartButtonText
    
    public function isAddToCartButtonHiddenAndProductPurchasable($product)
    {
        $settings = $this->getSettings();
        
        return array_key_exists('textForNonRegisteredUsers', $settings) && 
               $this->_facade->isProductPurchasableAndInStock($product);
    } // end isAddToCartButtonHiddenAndProductPurchasable
    
    private function _isEnabledHideAddToCartButtonOptionInAllProducts()
    {
        return (!$this->_isRegisteredUser() 
                  && $this->_hasHideAddToCartButtonOptionInSettings())
               || ($this->_isRegisteredUser() 
                  && $this->_hasHideAddToCartButtonOptionForUserRole());
    } // end _isEnabledHideAddToCartButtonOptionInAllProducts
    
    private function _hasHidePriceOptionForRoleInProductSettings($product)
    {
        if (!$product) {
            return true; //FIX
        }
        
        $produtcId = $this->_facade->getID($product);//$product->id;
        
        if (!$produtcId) {
            return false;
        }
        
        $options = $this->getMetaOptions(
            $produtcId,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
        
        if (!$options) {
            return false;
        }
        
        if (!array_key_exists('hidePriceForUserRoles', $options)) {
            return false;
        }
        
        return $options && array_key_exists(
            $this->userRole,
            $options['hidePriceForUserRoles']
        );
    } // end _hasHidePriceOptionForRoleInProductSettings
    
    private function _hasHidePriceOptionForRoleInGeneralSettings()
    {
        $settings = $this->getSettings();
        $role = $this->userRole;
           
        return array_key_exists('hidePriceForUserRoles', $settings)
               && array_key_exists($role, $settings['hidePriceForUserRoles']);
    } // end _hasHidePriceOptionForRoleInGeneralSettings
    
    private function _hasHideAddToCartButtonOptionForUserRole()
    {
        $key = 'hideAddToCartButtonForUserRoles';
        $settings = $this->getSettings();
        
        return array_key_exists($key, $settings)
               && array_key_exists($this->userRole, $settings[$key]);
    } //end _hasHideAddToCartButtonOptionForUserRole
    
    private function _hasHideAddToCartButtonOptionInSettings()
    {
        $settings = $this->getSettings();
        
        return array_key_exists('hideAddToCartButton', $settings);
    } //end _hasHideAddToCartButtonOptionInSettings
    
    private function _isRegisteredUser()
    {
        return $this->userRole;
    } // end _isRegisteredUser
    
    private function _isSesionStarted()
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE;
            } else {
                return session_id() !== '';
            }
        } else if (defined('WP_TESTS_TABLE_PREFIX')) {
            return true;
        }
        
        return false;
    } // end _isSesionStarted

    public function getPluginTemplatePath($fileName)
    {
        return $this->_pluginTemplatePath.'frontend/'.$fileName;
    } // end getPluginTemplatePath
    
    public function getPluginJsUrl($fileName)
    {
        return $this->_pluginJsUrl.'frontend/'.$fileName;
    } // end getPluginJsUrl
    
    public function getPluginCssUrl($path) 
    {
        return $this->_pluginUrl.$path;
    } // end getPluginCssUrl
    
    public function onInitJsAction()
    {
        $this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-general',
            'general.js',
            'jquery',
            $this->_version
        );
    } // end onInitJsAction
    
    public function onInitCssAction()
    {
        $this->addActionListener(
            'wp_head',
            'appendCssToHeaderForCustomerSavingsCustomize'
        );

        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-styles',
            'static/styles/frontend/style.css',
            array(),
            $this->_version
        );
    } // end onInitCssAction
    
    public function appendCssToHeaderForCustomerSavingsCustomize()
    {
        if (!$this->_hasOptionInSettings('showCustomerSavings')) {
            return false;
        }
        
        $vars = array(
            'settings' => $this->getSettings(),
        );

        echo $this->fetch('customer_savings_customize_style.phtml', $vars);
    } // end appendCssToHeaderForPriceCustomize
    
    private function _hasOptionInSettings($option)
    {
        $settings = $this->getSettings();

        return array_key_exists($option, $settings);
    } // end _hasOptionInSettings
    
    public function isWoocommerceMultiLanguageActive()
    {
        $pluginPath = 'woocommerce-multilingual/wpml-woocommerce.php';
        
        return $this->isPluginActive($pluginPath);
    } // end isWoocommerceMultiLanguageActive

    public function onDisplayOnlyProductStockStatusAction()
    {
        $vars = array(
            'settings' => $this->getSettings(),
        );
        echo $this->fetch('stock_status_for_simple_type_product.phtml', $vars);
    } // end onDisplayOnlyProductStockStatusAction

    private function _getMaxExecutionTime()
    {
        return ini_get('max_execution_time');
    } // end _getMaxExecutionTime

    private function _isMaxExecutionTimeLowerThanConstant()
    {
        $executionTime = WooUserRolePricesFestiPlugin::MAX_EXECUTION_TIME;
        return $this->_getMaxExecutionTime() < $executionTime;
    } // end _isMaxExecutionTimeLowerThanConstant
}