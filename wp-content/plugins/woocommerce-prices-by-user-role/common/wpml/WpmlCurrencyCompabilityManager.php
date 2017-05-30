<?php

class WpmlCurrencyCompabilityManager
{
    private $_woocommerceCurrencies;
    private $_plugin;
    
    const DEFAULT_CURRENCY_RATE = 1;
    const AUTO_CALCULATION_OPTION_VALUE = "0";
    const ACTION_PRIORITY = 13;
    const ACTION_ARGUMENTS_COUNT = 3;
       
    public function __construct($plugin)
    {
        $this->_plugin = $plugin;    
        
        $this->_doIncludeFacadeFiles();    
        
        $this->_plugin->addActionListener(
            'woocommerce_loaded',
            array($this, 'onWoocommerceCurrenciesSetAction')
        );  
    } 
    
    public function onInitBackendActionListeners()
    {           
        $this->_plugin->addActionListener(
            'woocommerce_product_options_pricing',
            array($this, 'onDisplayFieldsAfterPriceOptionsAction'),
            self::ACTION_PRIORITY
        );
        
        $this->_plugin->addActionListener(
            'woocommerce_product_after_variable_attributes',
            array($this, 'onDisplayFieldsAfterVariableAttributesAction'),
            self::ACTION_PRIORITY,
            self::ACTION_ARGUMENTS_COUNT
        ); 
    }
    
    private function _doIncludeFacadeFiles()
    {
        $woocommercePath = PRICE_BY_ROLE_PLUGIN_DIR.'/common/woocommerce/';
        $wpmlPath = PRICE_BY_ROLE_PLUGIN_DIR.'/common/wpml/';
        $files = array(
            'WooCommerceFacade'     => $woocommercePath.'WooCommerceFacade.php',
            'WooCommerceWpmlFacade' => $wpmlPath.'WooCommerceWpmlFacade.php'
        );
        
        foreach ($files as $key => $file) {
            if (class_exists($key)) {
                unset($files[$key]);
            }
        }

        $this->_plugin->doIncludeFiles($files);
    }
    
    public function onWoocommerceCurrenciesSetAction()
    {
        $this->_woocommerceCurrencies = WooCommerceFacade::getCurrencies();
    }
    
    public function getCurrenciesData() 
    {
        $wpml = new WooCommerceWpmlFacade();
        
        return $wpml->getActiveCurrenciesData();
    }  
    
    public function onDisplayFieldsAfterVariableAttributesAction(
        $loop, $data, $item
    )
    {
        $this->_displayPriceFieldsForCurrencyInVariableProduct($loop, $item);
    }
    
    private function _displayPriceFieldsForCurrencyInVariableProduct(
        $loop, $item
    )
    {
        $wpmlCurrencies = $this->getCurrenciesData();
        
        $values = $this->_getRolesPrices($item->ID);
        
        $roles = $this->_plugin->getActiveRoles();
        
        $vars = array(
            'wpmlCurrencies'        => $wpmlCurrencies,
            'woocommerceCurrencies' => $this->_woocommerceCurrencies,
            'roles'                 => $roles,
            'price'                 => $values,
            'loop'                  => $loop,
            'idPost'                => $item->ID,
            'compabilityManager'    => $this
        );

        echo $this->_plugin->fetch('wpml_currency_fields.phtml', $vars);
    }
    
    public function isVariationLoop($loop)
    {
        return !($loop === false);
    }

    private function _getRolesPrices($idPost)
    {        
        $values = $this->_plugin->getPostMeta(
            $idPost, 
            PRICE_BY_ROLE_PRICE_META_KEY, 
            true
        );
        
        if (!$values) {
            return false;
        }

        return json_decode($values, true);
    }
    
    private function _isRolePriceForCurrencyExist($prices, $role, $currency)
    {
        return $prices && $this->_isPriceExistForRole($prices, $role) &&
               $this->_isRolePriceExistForCurrency($prices, $role, $currency);
    }
    
    public function getRolePriceForChosenCurrency($prices, $role, $currency)
    {
        if (!$this->_isRolePriceForCurrencyExist($prices, $role, $currency)) {
            return null;
        }
        
        return $prices[$role][$currency];     
    }
    
    public function getRolePriceForDefaultCurrency($prices, $role)
    {
        if (!$prices || !$this->_isPriceExistForRole($prices, $role)) {     
            return null;       
        }
            
        return $prices[$role];
    }
    
    private function _isPriceExistForRole($prices, $role) 
    {
        return array_key_exists($role, $prices);          
    }
    
    private function _isRolePriceExistForCurrency($prices, $role, $currency)
    {
        return array_key_exists($currency, $prices[$role]);
    }
    
    public function displayInputField($args)
    {
        WooCommerceFacade::displayMetaTextInputField($args);
    }
    
    public function displayHiddenInputField($args)
    {
        WooCommerceFacade::displayHiddenMetaTextInputField($args);
    }
   
    public function onDisplayFieldsAfterPriceOptionsAction()
    {    
        $this->_displayPriceFieldsForCurrencyInSimpleProduct();
    }
    
    private function _displayPriceFieldsForCurrencyInSimpleProduct()
    {
        $wpmlCurrencies = $this->getCurrenciesData();
        
        if (!$this->_hasPostIdInRequest()) {
            return false;
        }
        
        $idPost = $_GET['post'];
        
        if (!$this->_isValidID($idPost)) {
            return false;
        }
        
        $values = $this->_getRolesPrices($idPost);
        
        $roles = $this->_plugin->getActiveRoles();

        $vars = array(
            'wpmlCurrencies'        => $wpmlCurrencies,
            'woocommerceCurrencies' => $this->_woocommerceCurrencies,
            'roles'                 => $roles,
            'price'                 => $values,
            'idPost'                => $idPost,
            'loop'                  => false,
            'compabilityManager'    => $this
        );

        echo $this->_plugin->fetch('wpml_currency_fields.phtml', $vars);
    }
    
    private function _hasPostIdInRequest()
    {
        return array_key_exists('post', $_GET);
    }
    
    private function _isValidID($id)
    {
        return (!filter_var($id, FILTER_VALIDATE_INT) === false);
    }
    
    public function getRoleNameWithCurrencyCode($code, $roleKey, $loop)
    {
        $wpmlRole = '['.$roleKey.'-currency'.']';   
        $currency = '['.$code.']';
          
        if (!$this->isVariationLoop($loop)) {
            $prefix = PRICE_BY_ROLE_PRICE_META_KEY;
            
            return $prefix.$wpmlRole.$currency;
        }
        
        $prefix = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        $loop = '['.$loop.']';
        
        return $prefix.$loop.$wpmlRole.$currency;
    }
    
    public function getRoleIdWithCurrencyCode($code, $roleKey, $loop)
    {
        $wpmlRole = '_'.$roleKey.'-currency';
        $code = '_'.$code;    
            
        if (!$this->isVariationLoop($loop)) {
            $prefix = PRICE_BY_ROLE_PRICE_META_KEY;
             
            return $prefix.$wpmlRole.$code;
        } 
        
        $prefix = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        $loop = '_'.$loop;
        
        return $prefix.$loop.$wpmlRole.$code;
    }
    
    private function _getPostCurrencyCalculationOption($id)
    {
        $name = '_wcml_custom_prices_status';
        $option = $this->_plugin->getPostMeta($id, $name, true);
        
        if ($this->_isOptionEmpty($option)) {
            return false;
        }
        
        return $option;
    }
    
    private function _isOptionEmpty($option)
    {
        return $option === "";
    }

    private function _isPriceCalculatedAutomatically($id)
    {          
        $option = $this->_getPostCurrencyCalculationOption($id);
        
        return $option === self::AUTO_CALCULATION_OPTION_VALUE;
    } 
    
    public function getDefaultCurrencyCode()
    {       
        return WooCommerceFacade::getBaseCurrencyCode();
    }
    
    private function _getCurrencyRate($currency, $code) 
    {            
        if ($this->_isRateExist($currency, $code)) {
            return $currency[$code]['rate'];
        } 
        
        return self::DEFAULT_CURRENCY_RATE;
    }
    
    private function _isRateExist($currency, $code)
    {
        if (!is_array($currency)) {
            return false;
        }
            
        return array_key_exists($code, $currency);
    }
    
    public function getCurrencySymbol($code) 
    {
        return WooCommerceFacade::getCurrencySymbol($code);
    }
    
    public function getPrices($priceList, $roles, $id)
    {
        $code = WooCommerceFacade::getBaseCurrencyCode();
        $prices = array();

        foreach ($roles as $key => $role) {
            $wpmlRole = $role.'-currency';
            if (
                !$this->_hasRolePrice($priceList, $role) &&
                !$this->_hasRolePrice($priceList, $wpmlRole)
                ) {
                continue;
            }
            
            if ($this->_isPriceCalculatedAutomatically($id)) {
                $currencies = $this->getCurrenciesData();   
                $rate = $this->_getCurrencyRate($currencies, $code);   
                $price = $priceList[$role] * $rate;   
            } else {
                if ($this->_hasRolePrice($priceList, $wpmlRole)) {
                    $price = $priceList[$wpmlRole][$code];    
                }
            }
            
            if (!$this->_hasPriceInCurrency($priceList, $wpmlRole, $code)) {
                $price = $priceList[$role];
            }
            
            if ($this->_isPriceEqualsZero($price)) {
                $facade = WooCommerceFacade::getInstance();
                $prices[] = $facade->getEmptyPriceSymbol();
                continue;
            }
            
            $prices[] = $this->_getPriceWithFixedFloat($price);
        }

        return $prices;
    }
    
    private function _isPriceEqualsZero($price)
    {
        return $price === '0';
    } // end _isPriceEqualsZero
    
    private function _hasRolePrice($priceList, $role)
    {        
        return array_key_exists($role, $priceList) &&
               !empty($priceList[$role]);
    }
    
    private function _hasPriceInCurrency($priceList, $wpmlRole, $code)
    {
        return array_key_exists($wpmlRole, $priceList) &&
        array_key_exists($code, $priceList[$wpmlRole]) &&
        !empty($priceList[$wpmlRole][$code]);
    }
    
    private function _getPriceWithFixedFloat($price)
    {
        $price = str_replace(',', '.', $price);
        $price = floatval($price);
        return strval($price);
    }
}
