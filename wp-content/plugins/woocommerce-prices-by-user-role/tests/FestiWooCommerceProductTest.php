<?php
require_once dirname(__FILE__).'/PriceByRoleTestCase.php';

class FestiWooCommerceProductTest extends PriceByRoleTestCase
{
    private $_backend;
    private $_notRealFilePath = '/var/test/file.php';
    private $_defaultCsvDelimiter = ',';
    private $_instance;
    private $__variableProduct;
    private $_notRealType = 'not_real';
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
        'subscription_variation'
    );

    public function setUp()
    {
        parent::setUp();
        
        $file = 'FestiWooCommerceProduct.php';
        require_once $this->getPluginPath(
            'common/festi/woocommerce/product/'.$file
        );
        
        $this->_instance = new FestiWooCommerceProduct($this);
        
        $this->_doInitVariableProduct();
        
    } // end setUp
    
    public function testGetProductInstanceByType()
    {
        foreach ($this->_types as $type) {
            $instance = $this->_instance->getInstance($type);
            $result = is_object($instance);
            $this->assertTrue($result);
        }
    } // end testGetProductInstanceByType
    
    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #Plugin do not support product type.*#
     */
    public function testGetProductIdByUdefinedTypeExceptionMessage()
    {
        $product = new stdClass();
        $product->product_type = $this->_notRealType;
        
        $this->_instance->getProductId($product);
    } // end testGetProductIdByUdefinedTypeExceptionMessage
    
    public function _doInitVariableProduct()
    {
        $file = 'FestiWooCommerceVariableProduct.php';
        require_once $this->getPluginPath(
            'common/festi/woocommerce/product/'.$file
        );
        
        $this->_variableProduct = new FestiWooCommerceVariableProduct($this);
    } // end _doInitVariableProduct
    
     /**
     * @ticket 2624 http://localhost.in.ua/issues/2624
     */
    public function testGetRegularPrice()
    {
        $product = WC_Helper_Product::create_variation_product();
        
        $price = $this->_variableProduct->getRegularPrice($product, true);

        $this->assertNotEmpty($price);
        $this->assertNotNull($price);
        
        $productMock = $this->getMock(
            'WC_Product_Variable', 
            array('_isExistsMethodVariationPrices'),
            array($product)
        );
        
        $price = $this->_variableProduct->getRegularPrice($productMock, true);
        
        $this->assertNotEmpty($price);
        $this->assertNotNull($price);
        
    } // end testGetRegularPrice
}