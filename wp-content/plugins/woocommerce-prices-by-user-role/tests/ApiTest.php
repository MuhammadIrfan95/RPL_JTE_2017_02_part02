<?php 

$wcPath = dirname(__FILE__).'/framework/';

require_once $wcPath.'factories/'.
             'class-wc-unit-test-factory-for-webhook-delivery.php';
require_once $wcPath.'factories/class-wc-unit-test-factory-for-webhook.php';
require_once $wcPath.'class-wc-unit-test-factory.php';
require_once $wcPath.'class-wc-unit-test-case.php';
require_once $wcPath.'class-wc-api-unit-test-case.php';

require_once dirname(__FILE__).'/framework/helpers/class-wc-helper-product.php';

class ApiTest extends WC_API_Unit_Test_Case
{
    public function testApiGetSimpleProduct()
    {
        $plugin = get_price_by_role_instance();
        $apiFacade = new WooUserRolePricesApiFacade($plugin);
        $apiFacade->init();
        
        $product = WC_Helper_Product::create_simple_product();
        
        $roleName = 'test_group2';
        $role = add_role($roleName, 'Test Group');
        $this->assertInstanceOf('WP_Role', $role);
        
        $newPrices = array(
            $roleName => 100
        );
        
        update_prices_by_roles($product->id, $newPrices);
        
        $productsApi = WC()->api->WC_API_Products;
        
        $data = $productsApi->get_product($product->id);
        $data = $data['product'];
        
        $this->assertNotNull($data);
        $this->assertNotEmpty($data['prices_by_user_roles']);
        $this->assertTrue(
            $data['prices_by_user_roles'][$roleName] == $newPrices[$roleName]
        );
    }
    
}