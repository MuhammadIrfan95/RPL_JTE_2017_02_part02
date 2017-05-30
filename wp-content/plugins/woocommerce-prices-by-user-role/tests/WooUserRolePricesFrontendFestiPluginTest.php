<?php
require_once dirname(__FILE__) . '/PriceByRoleTestCase.php';

class WooUserRolePricesFrontendFestiPluginTest extends PriceByRoleTestCase
{
    public $idPost;
    public $cart;

    public function setUp()
    {
        parent::setUp();

        require_once $this->getPluginPath('WooUserRolePricesFestiPlugin.php');

        $file = 'WooUserRolePricesFrontendFestiPlugin.php';
        require_once $this->getPluginPath($file);
        $file = 'common/festi/woocommerce/product/FestiWooCommerceProduct.php';
        require_once $this->getPluginPath($file);

        $this->cart = $this->getCart();

        $this->_frontend = new WooUserRolePricesFrontendFestiPlugin(
            $this->pluginMainFile
        );

        $this->setFrontendPropertyValue();

        $this->doCreateProduct();

        $this->doCartFill();
    }

    public function getCart()
    {
        $cartController = WooCommerceCartFacade::getInstance();

        return $cartController->getCartInstance();
    }

    public function setFrontendPropertyValue()
    {
        $productController = new FestiWooCommerceProduct($this->_frontend);
        $reflectionClass = new ReflectionClass(
            'WooUserRolePricesFrontendFestiPlugin'
        );
        $reflectionProperty = $reflectionClass->getProperty('products');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($this->_frontend, $productController);
    }

    public function doCreateProduct()
    {
        parent::doCreateProduct();

        $regularPriceKey = WooCommerceProductValuesObject::REGULAR_PRICE_KEY;
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        $this->idPost = $this->getProductId('simple');

        update_post_meta($this->idPost, $regularPriceKey, '100');
        update_post_meta($this->idPost, $priceKey, '100');
    }

    public function doCartFill()
    {
        $this->cart->add_to_cart($this->idPost, 1);
    }

    public function setCartProperties($options)
    {
        $this->cart->subtotal_ex_tax = $options['subtotal_ex_tax'];
        $this->cart->tax_total = $options['tax_total'];
        $this->cart->prices_include_tax = $options['prices_include_tax'];
    }

    public function getCartCasesProperties()
    {
        $properties = array(
            //tax included in price, displays inclusive
            array(
                'subtotal_ex_tax'    => 80,
                'tax_total'          => 20,
                'tax_option_display' => 'incl',
                'prices_include_tax' => 1,
                'expected'           => '100'
            ),
            //tax included in price, displays separately
            array(
                'subtotal_ex_tax'    => 80,
                'tax_total'          => 20,
                'tax_option_display' => 'excl',
                'prices_include_tax' => 1,
                'expected'           => '100'
            ),
            //tax excluded from price, displays inclusive
            array(
                'subtotal_ex_tax'    => 100,
                'tax_total'          => 25,
                'tax_option_display' => 'incl',
                'prices_include_tax' => '',
                'expected'           => '125'
            ),
            //tax excluded from price, displays separately
            array(
                'subtotal_ex_tax'    => 100,
                'tax_total'          => 25,
                'tax_option_display' => 'excl',
                'prices_include_tax' => '',
                'expected'           => '125'
            ),
        );

        return $properties;
    }

    public function getReflectedFunction($class, $name)
    {
        $testedFunction = new ReflectionMethod(
            $class,
            $name
        );

        $testedFunction->setAccessible(true);

        return $testedFunction;
    }

    public function testGetRetailSubTotalWithTax()
    {
        $cases = $this->getCartCasesProperties();

        $testedFunction = $this->getReflectedFunction(
            'WooUserRolePricesFrontendFestiPlugin',
            'getRetailSubTotalWithTax'
        );

        foreach ($cases as $case) {
            $this->setCartProperties($case);

            $currentTotal = $testedFunction->invoke($this->_frontend);

            $this->assertEquals($case['expected'], $currentTotal);
        }
    }

    public function getSimpleProductWithVariationID()
    {
        $product = new WC_Product($this->idPost);

        $product = (array)$product;
        $product['variation_id'] = '';
        $product = (object)$product;

        return $product;
    }

    public function testGetProductNewInstance()
    {
        $testedFunction = $this->getReflectedFunction(
            'WooUserRolePricesFrontendFestiPlugin',
            'getProductNewInstance'
        );

        $product = $this->getSimpleProductWithVariationID();
        $error = null;

        try {
            $result = $testedFunction->invokeArgs(
                $this->_frontend,
                array($product)
            );
        } catch (Exception $e) {
            $error = $e;
        }

        $this->assertEquals(null, $error);
    }

    public function doCreateUser()
    {
        $name = 'testUser';
        $password = '123';
        $email = 'email@test.com';

        $this->idUser = wp_create_user($name, $password, $email);

        wp_set_current_user($this->idUser);
        
        $_SESSION['userIdForAjax'] = $this->idUser;
    }

    public function doRemoveUserRoles()
    {
        $user = get_userdata($this->idUser);
        $user->set_role('');
    }

    /**
     * Used to simulate User Role Editor plugin bug.
     *
     * Adding capability to user before adding a role changes indexes
     * in the result of get_userdata($id)->roles
     *
     * @link https://ru.wordpress.org/plugins/user-role-editor/
     */
    public function doEmulateUserRoleEditorBug()
    {
        $role = 'testRole';
        $capability = 'testCap';

        $this->testRole = $role;

        add_role($role, $role);

        $user = get_userdata($this->idUser);

        $user->add_cap($capability);
        $user->add_role($role);
    }

    /**
     * Bug #2590
     * @link http://localhost.in.ua/issues/2590
     */
    public function testGetUserRole()
    {
        $this->doCreateUser();
        $this->doRemoveUserRoles();
        $this->doEmulateUserRoleEditorBug();

        $role = $this->_frontend->getUserRole();

        $this->assertEquals($role, $this->testRole);
    }

    /**
     * Bug #2714
     * @link http://localhost.in.ua/issues/2714
     */
    public function testGetRolePriceWidthAjax()
    {
        $this->doCreateUser();

        define('DOING_AJAX', true);
        $_SESSION['userIdForAjax'] = $this->idUser;
        $role = 'testRole';
        $priceForRole = 30;

        $user = get_userdata($this->idUser);
        $user->add_role($role);

        $product = WC_Helper_Product::create_simple_product();

        update_post_meta(
            $product->id,
            'festiUserRolePrices',
            array(
                $role                => $priceForRole,
                'twoPriceTestRole'   => '100',
                'threePriceTestRole' => '5'
            )
        );

        $instance = new WooUserRolePricesFestiPlugin($this->pluginMainFile);

        $rolePrice = $instance->getRolePrice($product->id);

        $this->assertEquals($priceForRole, $rolePrice);
    }

    /**
     * Bug #2704 #2705
     * @link http://localhost.in.ua/issues/2704
     * @link http://localhost.in.ua/issues/2705
     */
    public function testCartTotalWithSubscription()
    {
        $this->_setPreparedPropertiesCart();
        $properties = $this->_getCartProperties();

        $retailTotal = $this->_getPrepareRetailTotal();

        $cart = WooCommerceCartFacade::getInstance();

        $this->_setSubscriptionProductOption($cart);

        $retailTotal = $this->_frontend->getTotalRetailWithSubscription(
            $retailTotal, $cart
        );

        $this->assertEquals($properties['expectedRetailTotal'], $retailTotal);

        $reflectionClass = new ReflectionClass($this->_frontend);
        $reflectionProperty = $reflectionClass->getProperty('mainTotals');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->_frontend, true);

        $userTotal = $this->_frontend->getUserTotalWithSubscription(
            $this->cart->total
        );

        $this->assertEquals($properties['expectedUserTotal'], $userTotal);

    }

    private function _setSubscriptionProductOption($cart)
    {
        $testedFunction = $this->getReflectedFunction(
            'WooUserRolePricesFrontendFestiPlugin',
            'setSubscriptionProductOption'
        );

        $testedFunction->invokeArgs($this->_frontend, array($cart));
    }

    private function _getPrepareRetailTotal()
    {
        $testedFunction = $this->getReflectedFunction(
            'WooUserRolePricesFrontendFestiPlugin',
            'getRetailTotal'
        );

        return $testedFunction->invoke($this->_frontend);
    }

    private function _setPreparedPropertiesCart()
    {
        $file = 'FestiWooCommerceProduct.php';
        require_once $this->getPluginPath(
            'common/festi/woocommerce/product/'.$file
        );

        if (!defined('WOOCOMMERCE_CHECKOUT')) {
            define('WOOCOMMERCE_CHECKOUT', true);
        }

        $properties = $this->_getCartProperties();

        $product = WC_Helper_Product::create_simple_product();

        update_post_meta(
            $product->id,
            WooCommerceProductValuesObject::PRICE_KEY,
            $properties['price']
        );
        update_post_meta($product->id, '_tax_status', 'taxable');
        update_post_meta(
            $product->id,
            '_subscription_price',
            $properties['subscriptionPrice']
        );
        update_post_meta(
            $product->id,
            WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
            $properties['subscriptionPrice']
        );
        update_post_meta(
            $product->id,
            '_subscription_sign_up_fee',
            $properties['signUpFee']
        );

        $this->cart->empty_cart();
        $this->cart->add_to_cart($product->id, 1);
        $this->cart->tax_total = $properties['lineTax'];

        $key = array_keys($this->cart->cart_contents);
        $key = array_pop($key);
        $this->_frontend->subscriptionKey = $key;

        $this->cart->cart_contents[$key]['line_tax'] = $properties['lineTax'];
        $this->cart->cart_contents[$key]['line_total'] =
            $properties['lineTotal'];
    }

    private function _getCartProperties()
    {
        $properties = array(
            'subscriptionPrice'   => 1000,
            'price'               => 1080,
            'signUpFee'           => 100,
            'lineTax'             => 180,
            'lineTotal'           => 900,
            'expectedUserTotal'   => 960,
            'expectedRetailTotal' => 1200
        );

        return $properties;
    }

    /**
     * Bug 2738
     * @link http://localhost.in.ua/issues/2738
     */
    public function testOnProductPriceOnlyRegisteredUsers()
    {
        $product = WC_Helper_Product::create_simple_product();
        $this->cart->empty_cart();
        $this->cart->add_to_cart($product->id, 1);

        $settings = array(
            'textForUnregisterUsers' => 'Please login or register to see price',
            'onlyRegisteredUsers' => 1
        );

        $this->_setValueReflectionProperty('settings', $settings);
        $this->_setValueReflectionProperty('userRole', 'administrator');

        $price = $this->_frontend->onProductPriceOnlyRegisteredUsers(
            $this->cart->total
        );

        $this->assertEquals($price, $this->cart->total);

        $this->_setValueReflectionProperty('userRole', '');
        $price = $this->_frontend->onProductPriceOnlyRegisteredUsers(
            $this->cart->total
        );

        $this->assertEquals($price, $settings['textForUnregisterUsers']);
    }

    private function _setValueReflectionProperty($property, $value)
    {
        $reflectionClass = new ReflectionClass($this->_frontend);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->_frontend, $value);
    }

    /**
     * Bug 2735
     * @link http://localhost.in.ua/issues/2735
     */
    public function testOnProductPriceRangeFilter()
    {
        $this->doCreateUser();
        $_SESSION['userIdForAjax'] = $this->idUser;

        $settings = array(
            'onlyRegisteredUsers' => 1
        );

        $this->_setValueReflectionProperty('settings', $settings);

        $product = WC_Helper_Product::create_variation_product();

        $price = $this->_frontend->onProductPriceRangeFilter(
            $product->get_price_html(),
            $product
        );

        $this->assertEquals($price, $product->get_price_html());

    }

    /**
     * Bug 2757
     * @link http://localhost.in.ua/issues/2757
     */
    public function testGetPriceWithDiscountOrMarkUp()
    {
        $this->doCreateUser();
        $_SESSION['userIdForAjax'] = $this->idUser;

        $testUserPrice = 90;
        $price = 100;
        $discountUser = 10;

        $product = $this->_getPrepareProductForPriceDiscount(
            $price,
            $discountUser
        );

        $newPrice = $this->_frontend->getPriceWithDiscountOrMarkUp(
            $product,
            $price
        );

        $this->assertEquals($newPrice, $testUserPrice);

    }

    private function _getPrepareProductForPriceDiscount($price, $discountUser)
    {
        $settings = array(
            'onlyRegisteredUsers' => 1,
            'discountOrMakeUp' => 'discount',
            'discountByRoles' => array(
                'subscriber' => array(
                    'value' => $discountUser,
                    'type' => 0,
                    'priceType' => 'regular'
                ),
            ),
        );

        $this->_setValueReflectionProperty('settings', $settings);
        $this->_setValueReflectionProperty('userRole', 'subscriber');

        $product = WC_Helper_Product::create_variation_product();

        $variationIDs = $product->get_children();

        foreach ($variationIDs as $id) {
            update_post_meta(
                $id,
                WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
                $price
            );
            update_post_meta(
                $id,
                WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
                $price
            );
        }

        return $product;
    }

    /**
     * Bug 2807
     * @link http://localhost.in.ua/issues/2807
     */
    public function testOnHideSelectorSaleForProduct()
    {
        $this->doCreateUser();

        $this->_setValueReflectionProperty('userRole', 'tester');

        $product = WC_Helper_Product::create_variation_product();

        $pricesWithoutRole = array(
            'price' => 10,
            'salePrice' => 8,
            'rolePrice' => 0,
        );
        $pricesWithRole = array(
            'price' => 10,
            'salePrice' => 8,
            'rolePrice' => 9
        );

        $saleLabel = 'Display label if not role price';

        $this->_doPrepareProductForHideSaleFlash($product, $pricesWithoutRole);
        $content = $this->_frontend->onHideSelectorSaleForProduct(
            $saleLabel,
            $product->post,
            $product
        );
        $this->assertEquals($saleLabel, $content);

        $this->_doPrepareProductForHideSaleFlash($product, $pricesWithRole);
        $content = $this->_frontend->onHideSelectorSaleForProduct(
            $saleLabel,
            $product->post,
            $product
        );
        $this->assertFalse($content);
    }

    private function _doPrepareProductForHideSaleFlash($product, $prices)
    {
        $variationIDs = $product->get_children();

        $regularPriceKey = WooCommerceProductValuesObject::REGULAR_PRICE_KEY;
        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        foreach ($variationIDs as $id) {
            update_post_meta($id, $priceKey, $prices['salePrice']);
            update_post_meta($id, $salePriceKey, $prices['salePrice']);
            update_post_meta($id, $regularPriceKey, $prices['price']);
            update_post_meta(
                $id,
                'festiUserRolePrices',
                array(
                    'tester' => $prices['rolePrice'],
                )
            );
        }
    }

    /**
     * Bug 2826
     * @link http://localhost.in.ua/issues/2826
     */
    public function testOnDisplayCustomerTotalSavingsFilter()
    {
        $this->_doPrepareProductForDisplay();

        $stringManager = StringManagerWooUserRolePrices::getInstance();
        $stringSubscriptionFee = $stringManager->getString(
            'subscriptionSignFee'
        );

        $content = $this->_frontend->onDisplayCustomerTotalSavingsFilter(100);
        $this->assertFalse((bool) strripos($content, $stringSubscriptionFee));

        $this->_setValueReflectionProperty('subscriptionFee', 10);
        $content = $this->_frontend->onDisplayCustomerTotalSavingsFilter(100);
        $this->assertTrue((bool) strripos($content, $stringSubscriptionFee));
    }

    private function _doPrepareProductForDisplay()
    {
        $product = WC_Helper_Product::create_simple_product();

        $regularPriceKey = WooCommerceProductValuesObject::REGULAR_PRICE_KEY;
        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        update_post_meta($product->id, $priceKey, 100);
        update_post_meta($product->id, $salePriceKey, 100);
        update_post_meta($product->id, $regularPriceKey, 200);

        $this->cart->empty_cart();
        $this->cart->add_to_cart($product->id, 1);
        $settings = array(
            'onlyRegisteredUsers' => 1,
            'discountOrMakeUp' => 'discount',
            'showCustomerSavings' => array(
                'page',
                'cartTotal'
            ),
            'discountByRoles' => array(
                'subscriber' => array(
                    'value' => 1,
                    'type' => 0,
                    'priceType' => 'regular'
                ),
            ),
        );

        $this->_setValueReflectionProperty('settings', $settings);
        $this->_setValueReflectionProperty('userRole', 'administrator');
        $this->_setValueReflectionProperty('mainTotals', true);
    }

    /**
     * Bug #2919
     * @link http://localhost.in.ua/issues/2919
     */
    public function testOnDisplayFreePriceForRole()
    {
        $this->doCreateUser();
        $_SESSION['userIdForAjax'] = $this->idUser;

        $this->_doPrepareSettingsForAdminRole();
        $this->_setValueReflectionProperty('userRole', 'administrator');

        $price = 900;
        $product = WC_Helper_Product::create_variation_product();
        $userPrice = $this->_frontend->getPriceWithDiscountOrMarkUp(
            $product,
            $price
        );

        $price = $this->_frontend->onDisplayCustomerSavingsFilter(
            $userPrice,
            $product
        );
        $regExp = '/Free!/';

        $this->assertTrue(
            (bool)preg_match($regExp, $price),
            'Your price must be Free!'
        );
    } // end testOnDisplayFreePriceForRole

    private function _doPrepareSettingsForAdminRole()
    {
        $settings = array(
            'onlyRegisteredUsers' => 1,
            'discountOrMakeUp' => 'discount',
            'discountByRoles' => array(
                'administrator' => array(
                    'value' => 100,
                    'type' => 0,
                    'priceType' => 'regular'
                ),
            ),
            'showCustomerSavings' => array(
                'product',
                'archive'
            )
        );

        return $this->_setValueReflectionProperty('settings', $settings);
    } // end _doPrepareSettingsForAdminRole
    
    /**
     * Bug #3019
     * @link http://localhost.in.ua/issues/3019
     */
    public function testOnDisplaySinglePrice()
    {
        $product = WC_Helper_Product::create_simple_product();
        
        $this->_doPrepareSettingsForAdminRole();
        $price = $product->get_price();
        
        $isPrice = $this->_frontend->onRemovePriceForUnregisteredUsers(
            $price,
            $product
        );
        
        $this->assertFalse((bool) $isPrice);
        
        $this->doCreateUser();
        $_SESSION['userIdForAjax'] = $this->idUser;
        $this->_setValueReflectionProperty('userRole', 'administrator');

        $isPrice = $this->_frontend->onRemovePriceForUnregisteredUsers(
            $price,
            $product
        );
        
        $this->assertTrue((bool) $isPrice);
    }
    
    /**
     * Bug #2985
     * @link http://localhost.in.ua/issues/2985
     */
    public function testOnSalePriceToNewPriceTemplateFilter()
    {
        $this->doCreateUser();
        
        $this->_setValueReflectionProperty('userRole', 'administrator');
        
        $product = WC_Helper_Product::create_simple_product();
        $prices = array(
            'price' => 300,
            'rolePrice' => 200
            );
        
        $this->_setPrepareProduct($product, $prices);
        
        $priceHtml = $product->get_price_html();
        
        $content = $this->_frontend->onSalePriceToNewPriceTemplateFilter(
            $priceHtml, $prices['price'], $prices['rolePrice'], $product
        );
        
        $regExp = '#'.wc_price($prices['rolePrice']).'#Umis';
        
        $this->assertTrue(
            (bool) preg_match($regExp, $content),
            'Price and user price display but we have expected user price'
        );
    }
    
    private function _setPrepareProduct($product, $prices)
    {
        update_post_meta(
            $product->id,
            WooCommerceProductValuesObject::PRICE_KEY,
            $prices['price']
        );
        update_post_meta(
            $product->id,
            'festiUserRolePrices',
            array(
                'administrator' => $prices['rolePrice']
            )
        );
        
        return true;
    }
    
    /*
     * Bug #3022
     * @link http://localhost.in.ua/issues/3022
     */
    public function testOnDisplayCustomerSavingsFilter()
    {
        $this->doCreateUser();
        $_SESSION['userIdForAjax'] = $this->idUser;
    
        $this->_setUserRoleSettings();

        $product = WC_Helper_Product::create_variation_product();

        $priceHtml = $product->get_price_html();
        
        $this->_setUserPriceByAdministratorRole($product);
        
        $price = $this->_frontend->onDisplayCustomerSavingsFilter(
            $priceHtml, $product
        );
        
        $regExp = '/Free!/';
        
        $this->assertFalse(
            (bool)preg_match($regExp, $price),
            'Display Free! but we have expected user role price'
        );
    }
    
    private function _setUserRoleSettings()
    {
        $settings = array(
            'onlyRegisteredUsers' => 1,
            'discountOrMakeUp' => 'discount',
            'discountByRoles' => array(
                'administrator' => array(
                    'value' => 0,
                    'type' => 0,
                    'priceType' => 'regular'
                ),
        ),
            'showCustomerSavings' => array(
                'product',
                'archive'
            )
        );
        
        $this->_setValueReflectionProperty('settings', $settings);
        
        $this->_setValueReflectionProperty('userRole', 'administrator');
        
        return true;
    }
    
    private function _setUserPriceByAdministratorRole($product)
    {
        $variationIDs = $product->get_children();
        
        foreach ($variationIDs as $id) {
            update_post_meta(
                $id,
                'festiUserRolePrices',
                array(
                    'administrator' => 15,
                )
            );
        }
        
        return true;
    }
    
    /*
     * Bug #3110
     * @link http://localhost.in.ua/issues/3110
     */
     public function testOnDisplayCustomerSavingsFilterByDiscountProducts()
     {
        $this->doCreateUser();
         
        $_SESSION['userIdForAjax'] = $this->idUser;
        
        $product = WC_Helper_Product::create_variation_product();
        
        $this->_setUserRoleSettingsWithDiscountPrice();        
        
        $this->_setValueReflectionProperty('userRole', 'subscriber');
        $testPrice = 10;
        $userPrice = $this->_frontend->getPriceWithDiscountOrMarkUp(
            $product,
            $testPrice
        );
         
        $price = $this->_frontend->onDisplayCustomerSavingsFilter(
            $userPrice, $product
        );
        
        $regExp = '/Free!/';
         
        $this->assertFalse(
            (bool)preg_match($regExp, $price),
            'Display Free! but we have expected user role price'
        );
     }

     private function _setUserRoleSettingsWithDiscountPrice()
     {
         $settings = array(
            'onlyRegisteredUsers' => 1,
            'discountOrMakeUp' => 'discount',
            'discountByRoles' => array(
                'subscriber' => array(
                    'value' => 50,
                    'type' => 0,
                    'priceType' => 'regular'
                ),
            ),
                'showCustomerSavings' => array(
                    'product',
                    'archive'
                )
         );
        
        $this->_setValueReflectionProperty('settings', $settings);
     }
     
     /*
      * Bug #3119
      * @link http://localhost.in.ua/issues/3119
      */
     public function testOnPriceFilterWidgetResults()
     {
        $products = $this->_getPrepareVariationProductsByFilter();

        $this->_setValueReflectionProperty('userRole', 'administrator');

        $error = null;

        try {
             $minPrice = 0;
             $maxPrice = 50;
             $this->_frontend->onPriceFilterWidgetResults(
                 $products, $minPrice, $maxPrice
             );
        } catch (Exception $e) {
             $error = $e;
        }
        
        $this->assertEquals(null, $error, "You have empty product");
     }
     
     private function _getPrepareVariationProductsByFilter()
     {
         $product = WC_Helper_Product::create_variation_product();
         $variations = $product->get_children();
         $stdProducts = array();
         
         foreach ($variations as $id) {
             $stdClass = new stdClass();
             $stdClass->ID = $id;
             $stdClass->post_parent = $product->id;
             $stdClass->post_type = 'product_variation';
             $stdProducts[$id] = $stdClass;
             
             $my_post = array(
                 'ID'          => $id,
                 'post_parent' => 0
             );
             
             wp_update_post($my_post);
         }

         return $stdProducts;
     }
     
     /*
      * Bug #3119
      * @link http://localhost.in.ua/issues/3119
      */
     public function testonPriceFilterWidgetMinMaxAmount()
     {
         WC_Helper_Product::create_variation_product();
         
         $this->_setValueReflectionProperty('userRole', 'administrator');
         
         $maxUserPrice = 100;
         $minUserPrice = 10;
         
         $testMinPrice = 0;
         $testMaxPrice = 50;
         
         $price = $this->_frontend->onPriceFilterWidgetMinAmount($testMinPrice);
         $this->assertEquals($price, $minUserPrice);
         
         $price = $this->_frontend->onPriceFilterWidgetMaxAmount($testMaxPrice);
         $this->assertEquals($price, $maxUserPrice);
     }
     
    /**
     * Bug #3126
     * @link http://localhost.in.ua/issues/3126
     */
    public function testOnProductPriceRangeFilterWithSalePrice()
    {
        $this->doCreateUser();
        
        $this->_setUserRoleSettings();
        $this->_setValueReflectionProperty('userRole', 'subscriber');
        
        $rolePrices = array(
            'rolePrice'     => 11,
            'roleSalePrice' => 4
        );
        
        $product = $this->_getPrepareVariationProductsByRangeFilter(
            $rolePrices
        );
        
        $price = $this->_frontend->onProductPriceRangeFilter(
            $product->get_price_html(),
            $product
        );
        
        $regExp = '#<del>(.*?)</del>#Umis';

        $this->assertTrue(
            (bool)preg_match($regExp, $price, $maches),
            'Your price must be crossed'
        );
        
        $contentSalePrice = $maches[0];
        $regExp = '#'.$rolePrices['rolePrice'].'#Umis';
        $this->assertTrue(
            (bool)preg_match($regExp, $contentSalePrice)
        );
    }

    private function _getPrepareVariationProductsByRangeFilter($rolePrices)
    {
        $product = WC_Helper_Product::create_variation_product();
        
        $variationIDs = $product->get_children();
        
        foreach ($variationIDs as $id) {
            update_post_meta(
                $id,
                'festiUserRolePrices',
                array(
                    'subscriber' => $rolePrices['rolePrice'],
                    'salePrice'  => array(
                        'subscriber' => $rolePrices['roleSalePrice']
                    )
                )
            );
        }
        
        return $product;
    }
    
    /*
     * Bug #3142
     * @link http://localhost.in.ua/issues/3142
     */
    public function testOnHiddenAndRemoveAction()
    {
        $this->doCreateUser();
        
        $this->_setValueReflectionProperty('userRole', 'administrator');
        
        $this->_testHidePriceForUserRoles();
        $this->_testHideAddToCartButtonForUserRoles();
    }
    
    private function _testHidePriceForUserRoles()
    {
        $product = WC_Helper_Product::create_simple_product();
        $settings = array(
            'roles' => array(
                'administrator' => 1
            ),
            'hidePriceForUserRoles' => array(
                'administrator' => 1
            ),
            'textForRegisterUsers' => 'test'
        );
        
        $this->_setValueReflectionProperty('settings', $settings);
        
        $this->_frontend->onHiddenAndRemoveAction();
        
        $textButton = 'add to Cart';
        
        $testTextButton = apply_filters(
            'woocommerce_loop_add_to_cart_link',
            $textButton,
            $product
        );
        
        $this->assertEquals($textButton, $testTextButton);
    }
    
    private function _testHideAddToCartButtonForUserRoles()
    {
        $product = WC_Helper_Product::create_simple_product();
        $textForNonRegisteredUsers = 'Test Text';
        $textButton = 'add to Cart';
        
        $settings = array(
            'hideAddToCartButtonForUserRoles' => array(
                'administrator' => 1
            ),
            'textForNonRegisteredUsers' => $textForNonRegisteredUsers
        );
        
        $this->_setValueReflectionProperty('settings', $settings);
        
        $this->_frontend->onHiddenAndRemoveAction();
        
        $testTextButton = apply_filters(
            'woocommerce_loop_add_to_cart_link',
            $textButton,
            $product
        );
        
        $this->assertEquals($textForNonRegisteredUsers, $testTextButton);
    }
    
    /*
     * Bug #3154
     * @link http://localhost.in.ua/issues/3154
     */
    public function testSalePriceByUnregisteredUser()
    {
        $product = WC_Helper_Product::create_simple_product();
        $salePrice = 5;
        
        update_post_meta($product->id, '_sale_price', $salePrice);
        
        $isSale = false;
        
        $isSale = $this->_frontend->onSalePriceCheck($isSale, $product);
        
        $this->assertTrue($isSale);
    }
    
    /*
     * Bug #3156
     * @link http://localhost.in.ua/issues/3156
     */
    public function testOnHideAddToCartButton()
    {
        $this->doCreateUser();
        
        $this->_setValueReflectionProperty('userRole', 'administrator');
        
        $product = WC_Helper_Product::create_simple_product();

        $textButton = '<button>add to Cart</button>';
        
        $settings = array(
            'hideAddToCartButtonForUserRoles' => array(
                'administrator' => 1
            ),
            'textForNonRegisteredUsers' => ''
        );
        
        $this->_setValueReflectionProperty('settings', $settings);
        
        $this->_frontend->onHiddenAndRemoveAction();
        
        ob_start();
        $fragment = apply_filters(
            'woocommerce_grouped_add_to_cart',
            array()
        );
        $fragment = ob_get_contents();
        ob_end_clean();
        
        $regExp = '#class="groupedHideAddToCartButton"#Umis';
        
        $this->assertTrue(
            (bool) preg_match($regExp, $fragment)
        );
        
        $testTextButton = apply_filters(
            'woocommerce_loop_add_to_cart_link',
            $textButton,
            $product
        );
        
        $this->assertEmpty($testTextButton);
    }

    /**
     * @ticket 3196 http://localhost.in.ua/issues/3196
     */
    public function testVariationProductRegularPriceInsteadOfFree()
    {
        $this->doCreateUser();

        $this->_setUserRoleSettings();

        $product = WC_Helper_Product::create_variation_product();

        $priceHtml = $product->get_price_html();

        $price = $this->_frontend->onDisplayCustomerSavingsFilter(
            $priceHtml, $product
        );

        $regExp = '/Free!/';

        $this->assertFalse(
            (bool)preg_match($regExp, $price),
            'Display Free! but we have expected user role price'
        );
    } // end testVariationProductRegularPriceInsteadOfFree
    
    /**
     * @ticket 3196 http://localhost.in.ua/issues/3196
     */
    public function testFrontendPluginNotProduceWarnings()
    {
        $this->doCreateUser();

        $this->_setUserRoleSettings();
        
        $test = $this->_frontend->getAmountOfDiscountOrMarkUp();

        $this->assertNotNull($test);
    } // end testFrontendPluginNotProduceWarnings
    
    /**
     * @ticket 3245 http://localhost.in.ua/issues/3245
     */
    public function testRangePriceWithSuffixDisplaysCorrectly()
    {
        $this->doCreateUser();

        $product = WC_Helper_Product::create_variation_product();

        $this->_setUserRoleSettings();
        $this->_setValueReflectionProperty('userRole', 'subscriber');
        $this->_setWoocommerceSettingsToAddTaxToPrice();
        $this->_setPriceDisplaySuffix();

        $taxRateID = $this->_doPrepareAndInsertTax();
        
        $this->_doPrepareVariationProduct($product);
        
        $priceHtml = $product->get_price_html();

        $price = $this->_frontend->onProductPriceRangeFilter(
            $priceHtml, $product
        );

        $regExp = '#<del>(.*?)</del>#Umis';

        $this->assertTrue(
            (bool) preg_match($regExp, $price),
            'Your regular price must be crossed'
        );
        
        $regExp = '#<ins>(.*?)</ins>#Umis';
        
        preg_match($regExp, $price, $matches);
        
        $salePrice = $matches[0];
        $priceExcludingTax = $product->get_price();
        
        $this->assertTrue(
            strpos($salePrice, $priceExcludingTax) !== false,
            'Your price suffix must contain price excluding tax'
        );
        
        $priceIncludingTax = $product->get_price_including_tax();
        
        $this->assertTrue(
            strpos($salePrice, (string) $priceIncludingTax) !== false,
            'Your price must contain tax'
        );
        
        $this->_doDeleteTax($taxRateID);
    } // end testRangePriceWithSuffixDisplaysCorrectly
    
    /**
     * @ticket 3246 http://localhost.in.ua/issues/3246
     */
    public function testPriceSuffixGetsCorrectPrice()
    {
        $product = WC_Helper_Product::create_simple_product();
        
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;
        $price = 120;
        
        update_post_meta($product->id, $priceKey, $price);
        
        $this->doCreateUser();
        $this->_setUserRoleSettings();
        
        $this->_setPriceDisplaySuffix();
        $this->_setWoocommerceSettingsToAddTaxToPrice();
        $taxRateID = $this->_doPrepareAndInsertTax();
        
        $priceHTML = $product->get_price_html();
        $regExp = '/'.$price.'/';

        $this->assertTrue(
            (bool) preg_match($regExp, $priceHTML),
            'Your price suffix must contain price without tax'
        );
        
        $this->_doDeleteTax($taxRateID);
    } // end testPriceSuffixGetsCorrectPrice

    private function _setWoocommerceSettingsToAddTaxToPrice()
    {
        update_option('woocommerce_calc_taxes', 'yes');
        update_option('woocommerce_prices_include_tax', 'no');
        update_option('woocommerce_tax_based_on', 'base');
        update_option('woocommerce_tax_display_shop', 'incl');
    } // end _setWoocommerceSettingsToAddTaxToPrice
    
    private function _setPriceDisplaySuffix()
    {
        update_option(
            'woocommerce_price_display_suffix',
            '{price_excluding_tax} test'
        );
    } // end _setPriceDisplaySuffix
    
    private function _doPrepareAndInsertTax()
    {
        $taxRate = array(
            'tax_rate_country'  => '',
            'tax_rate_state'    => '',
            'tax_rate'          => '50.0000',
            'tax_rate_name'     => 'VAT',
            'tax_rate_priority' => '1',
            'tax_rate_compound' => '0',
            'tax_rate_shipping' => '1',
            'tax_rate_order'    => '1',
            'tax_rate_class'    => '',
        );

        $taxRateID = WC_Tax::_insert_tax_rate($taxRate);
        
        return $taxRateID;
    } // end _doPrepareAndInsertTax
    
    private function _doDeleteTax($taxRateID)
    {
        WC_Tax::_delete_tax_rate($taxRateID);
    } // end _doDeleteTax
    
    private function _doPrepareVariationProduct(&$product)
    {
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;
        
        $price = 80;
        $salePrice = 40;
        $increment = 20;

        $children = $product->get_children();

        update_post_meta($product->id, $priceKey, $salePrice);
        update_post_meta($product->id, '_min_variation_price', $salePrice);
        update_post_meta(
            $product->id,
            '_max_variation_price',
            $salePrice + $increment
        );
        update_post_meta($product->id, '_min_variation_regular_price', $price);
        update_post_meta(
            $product->id,
            '_max_variation_regular_price',
            $price + $increment
        );

        foreach ($children as $childId) {
            $priceFestiOptions = array(
                'subscriber' => $price,
                'salePrice' => array(
                    'subscriber' => $salePrice
                )
            );

            update_post_meta(
                $childId,
                PRICE_BY_ROLE_PRICE_META_KEY,
                json_encode($priceFestiOptions)
            );

            $price += $increment;
            $salePrice += $increment;
        };
    } // end _doPrepareVariationProduct
    
    /**
     * @ticket 3325 http://localhost.in.ua/issues/3325
     */
    public function testOnDisplayCustomerSavingsFilterByCurrency()
    {
        $this->doCreateUser();
        $_SESSION['userIdForAjax'] = $this->idUser;
    
        $this->_setUserRoleSettings();

        $product = WC_Helper_Product::create_simple_product();

        $priceHtml = $product->get_price_html();
        
        $this->_setUserPriceByAdministratorRole($product);
        
        update_option('woocommerce_currency', 'GBP');
        
        add_filter(
            "woocommerce_currency",
            function() {
                return 'USD';
            }
        );
        
        $price = $this->_frontend->onDisplayCustomerSavingsFilter(
            $priceHtml, $product
        );
        
        $this->assertEquals($priceHtml, $price);
    }
    
    /**
     * @ticket 3325 http://localhost.in.ua/issues/3344
     */
    public function testRemoveGroupedAddToCartLinkAction()
    {
        $_SESSION['userIdForAjax'] = false;

        $product = WC_Helper_Product::create_grouped_product();
        $textForNonRegisteredUsers = 'Test Text';
        $textButton = 'add to Cart';
        
        $settings = array(
            'textForNonRegisteredUsers' => $textForNonRegisteredUsers,
            'hideAddToCartButton' => 1
        );
        
        $this->_setValueReflectionProperty('settings', $settings);
        
        $this->_frontend->onHiddenAndRemoveAction();
        
        ob_start();
        $fragment = apply_filters(
            'woocommerce_grouped_add_to_cart',
            array()
        );
        $fragment = ob_get_contents();
        ob_end_clean();
        
        $regExp = '#'.$textForNonRegisteredUsers.'#Umis';
        
        $this->assertTrue((bool) preg_match($regExp, $fragment));
       
        return true;
    }
}
