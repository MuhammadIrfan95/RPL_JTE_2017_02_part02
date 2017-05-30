<?php 

require_once dirname(__FILE__).'/PriceByRoleTestCase.php';

class FunctionsPriceByUserRoleTest extends PriceByRoleTestCase
{
    public function testPositiveFunctionsApi()
    {
        $plugin = get_price_by_role_instance();
        $this->assertNotNull($plugin);
        
        //
        $roleName = 'test_group';
        $role = add_role($roleName, 'Test Group');
        $this->assertInstanceOf('WP_Role', $role);
        
        $idProduct = $this->createWooProduct();
        
        $newPrices = array(
            $roleName => 100
        );
        
        update_prices_by_roles($idProduct, $newPrices);
        
        $prices = get_product_prices($idProduct);
        
        $this->assertTrue($newPrices[$roleName] == $prices[$roleName]);
        
        $options = array(
            'role' => $roleName
        );
        
        $idUser = self::factory()->user->create($options);
        
        $price = get_price_by_user_id($idProduct, $idUser);
        
        $this->assertTrue($price == $newPrices[$roleName]);
    } // end testPositiveFunctionsApi
    
}