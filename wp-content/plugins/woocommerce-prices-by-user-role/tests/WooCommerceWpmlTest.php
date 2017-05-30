<?php
require_once dirname(__FILE__).'/PriceByRoleTestCase.php';

class WooCommerceWpmlTest extends PriceByRoleTestCase
{
    private $_backend;
    
    public function setUp()
    {
        parent::setUp();
        
        $file = '/common/wpml/WooCommerceWpmlFacade.php';
        
        require_once $this->getPluginPath($file);
        
        $file = '/common/wpml/WpmlCurrencyCompabilityManager.php';
        
        require_once $this->getPluginPath($file);
        
        $this->_backend = new WooUserRolePricesBackendFestiPlugin(
            $this->pluginMainFile
        );
    }
    
    /**
     * Bug 2821
     * @link http://localhost.in.ua/issues/2821
     */
    public function testGetWpmlMultiCurrencySupportInstance()
    {
        $name = "woocommerce_wpml";
        $wpml = array(
            'multi_currency' => true
        );
        $GLOBALS[$name] = (object) $wpml;
        $wpmlFacade = new WooCommerceWpmlFacade();
        
        $testedFunction = $this->getReflectedFunction(
            $wpmlFacade,
            '_getWpmlMultiCurrencySupportInstance'
        );
        
        $test = $testedFunction->invoke($wpmlFacade);
        $this->assertTrue($test);
        
        $GLOBALS[$name] = (object) array();
        $wpmlFacade = new WooCommerceWpmlFacade();
        
        $testedFunction = $this->getReflectedFunction(
            $wpmlFacade,
            '_getWpmlMultiCurrencySupportInstance'
        );
        
        try {
            $test = $testedFunction->invoke($wpmlFacade);
            $this->fail(
                "Expected exception ".
                PRICE_BY_ROLE_EXCEPTION_WMPL_CURRENCY.
                " not thrown"
            );
        } catch (Exception $e) {
            $this->assertEquals(
                PRICE_BY_ROLE_EXCEPTION_WMPL_CURRENCY,
                $e->getCode()
            );
        }
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
    
    /**
     * Bug #3075
     * @link http://localhost.in.ua/issues/3075
     */
    public function testOnDisplayFieldsAfterPriceOptionsAction()
    {
        $this->_includeWPFunctions();

        $this->_doCreateSimpleProduct();
        
        $this->_setActiveUserRoles();
        
        $currencies = $this->_getCurrencies();
        
        $mock = $this->getMock(
            'WpmlCurrencyCompabilityManager',
            array(
                'getCurrenciesData',
                'getDefaultCurrencyCode'
            ),
            array(
                $this->_backend
            )
        );
       
        $mock->expects($this->any())
             ->method('getCurrenciesData')
             ->will($this->returnValue($currencies));
        
        $mock->expects($this->any())
             ->method('getDefaultCurrencyCode')
             ->will($this->returnValue('USD'));
        
        $mock->onWoocommerceCurrenciesSetAction();
        
        ob_start();
        $mock->onDisplayFieldsAfterPriceOptionsAction();
        $content = ob_get_contents();
        ob_end_clean();
        
        $regExp = '#'.PRICE_BY_ROLE_PRICE_META_KEY.
                  '\[editor-currency\]\[UAH\]#Umis';

        $this->assertTrue((bool) preg_match($regExp, $content));
    }
    
    private function _getCurrencies()
    {
        $currencies = array(
            'USD' => array(
                'rate' => 1
            ),
            'UAH' => array(
                'rate' => 25
            )
        );
        
        return $currencies;
    }
    
    private function _setActiveUserRoles()
    {
        $settings = array(
            'roles' => array(
                'administrator' => array(
                    'name' => 'Administrator'
                ),
                'editor' => array(
                    'name' => 'Editor'
                )
             )
        );
        $settings = json_encode($settings);
        $prefix = PRICE_BY_ROLE_OPTIONS_PREFIX;
        update_option($prefix.'settings', $settings);
        
        return true;
    }

    private function _doCreateSimpleProduct()
    {
        $product = WC_Helper_Product::create_simple_product();
        $_GET['post'] = $product->id;
        $GLOBALS['post'] = $product;
        
        return true;
    }

    private function _includeWPFunctions()
    {
        $path = $this->getWooCommercePath(
            'includes/admin/wc-meta-box-functions.php'
        );
        
        require_once $path;
    }
    
}
