<?php

class WooUserRolePricesFestiPlugin extends WpmlCompatibleFestiPlugin
{
    public $_languageDomain = PRICE_BY_ROLE_LANGUAGE_DOMAIN;
    protected $_optionsPrefix = PRICE_BY_ROLE_OPTIONS_PREFIX;
    public $_version = PRICE_BY_ROLE_VERSION;
    
    const ENABLED_MULTI_CURRENCY_OPTION_VALUE = 2;
    const MAX_EXECUTION_TIME = 180;
    
    protected function onInit()
    {
        $this->addActionListener('plugins_loaded', 'onLanguagesInitAction');

        if ($this->_isWoocommercePluginNotActiveWhenFestiPluginActive()) {
            $this->addActionListener(
                'admin_notices',
                'onDisplayInfoAboutDisabledWoocommerceAction' 
            );
            
            return false;
        }
        
        $this->onInitCompatibilityManager();

        $this->oInitWpmlManager();
   
        $this->addActionListener('wp_loaded', 'onInitStringHelperAction');
        
        if ($this->isWmplCurrenciesPluginActive()) {
            $this->_doIncludeWpmlCurrencyCompabilityManager();
        }

        parent::onInit();
        
        if (defined('DOING_AJAX')) {
            $this->onBackendInit();
        }
    } // end onInit
    
    protected function isWmplCurrenciesPluginActive()
    {
        $plugin = 'woocommerce-multilingual/wpml-woocommerce.php';
        
        return $this->isPluginActive($plugin);
    }
    
    private function _doIncludeWpmlCurrencyCompabilityManager()
    {
        $path = $this->_pluginPath.'/common/wpml/';
        $name = 'WpmlCurrencyCompabilityManager.php';
        $file = $path.$name;       
        $files = array($file);
       
        $this->doIncludeFiles($files);
    }
    
    protected function isWpmlMultiCurrencyOptionOn()
    {
        $options = get_option('_wcml_settings');
        $key = 'enable_multi_currency';
        
        if (!$this->_isOptionExist($options, $key)) {
            return false;
        }
        
        $option = $options[$key];
        
        return $option == self::ENABLED_MULTI_CURRENCY_OPTION_VALUE &&
               $this->isWmplCurrenciesPluginActive();
    }
    
    private function _isOptionExist($options, $key)
    {
        if (!is_array($options)) {
            return false;
        }
        
        return array_key_exists($key, $options);
    }
    
    public function doIncludeFiles($files)
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $message = "File does not exist: ".$file;
                throw new Exception($message);
            }
            
            require_once($file);
        }
    }
    
    protected function onInitCompatibilityManager()
    {
        $fileName = 'CompatibilityManagerWooUserRolePrices.php';
        require_once $this->_pluginPath.'common/'.$fileName;
        $pluginMainFile = $this->_pluginMainFile;
        $backend = new CompatibilityManagerWooUserRolePrices($pluginMainFile);
    } // end onInitCompatibilityManager
    
    protected function oInitWpmlManager()
    {
        new FestiWpmlManager(PRICE_BY_ROLE_WPML_KEY, $this->_pluginMainFile);
    } // end oInitWpmlManager
    
    public function onInitStringHelperAction()
    {
        StringManagerWooUserRolePrices::start();
    } // end onInitStringHelperAction
    
    public function onInstall()
    {
        if (!$this->_isWoocommercePluginActive()) {
            $message = 'WooCommerce not active or not installed.';
            $this->displayError($message);
            exit();
        } 

        $plugin = $this->onBackendInit();
        
        $plugin->onInstall();
    } // end onInstall
    
    public function onBackendInit()
    {
        $fileName = 'WooUserRolePricesBackendFestiPlugin.php';
        require_once $this->_pluginPath.$fileName;
        
        if (!class_exists("WooUserRoleDisplayPricesBackendManager")) {
            $fileName = 'WooUserRoleDisplayPricesBackendManager.php';
            require_once __DIR__.'/common/backend/'.$fileName;
        }
        
        $pluginMainFile = $this->_pluginMainFile;
        $backend = new WooUserRolePricesBackendFestiPlugin($pluginMainFile);
        return $backend;
    } // end onBackendInit
    
    protected function onFrontendInit()
    {
        $fileName = 'WooUserRolePricesFrontendFestiPlugin.php';
        require_once $this->_pluginPath.$fileName;
        $pluginMainFile = $this->_pluginMainFile;
        $frontend = new WooUserRolePricesFrontendFestiPlugin($pluginMainFile);
        return $frontend;
    } // end onFrontendIn
    
    private function _isWoocommercePluginNotActiveWhenFestiPluginActive()
    {
        return $this->_isFestiPluginActive()
               && !$this->_isWoocommercePluginActive();
    } // end _isWoocommercePluginNotActiveWhenFestiPluginActive
    
    private function _isFestiPluginActive()
    {        
        return $this->isPluginActive('woocommerce-woocartpro/plugin.php'); 
    } // end _isFestiPluginActive
    
    private function _isWoocommercePluginActive()
    {        
        return $this->isPluginActive('woocommerce/woocommerce.php');
    } // end _isWoocommercePluginActive
    
    public function onLanguagesInitAction()
    {
        load_plugin_textdomain(
            $this->_languageDomain,
            false,
            $this->_pluginLanguagesPath
        );
    } // end onLanguagesInitAction
    
    public function getMetaOptions($id, $optionName)
    {
        $value = $this->getPostMeta($id, $optionName);
        
        if (!$value) {
            return false;
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        $value = json_decode($value, true);
        
        return $value;
    } // end getMetaOptions
    
    public function getActiveRoles()
    {
        $options = $this->getOptions('settings');
        
        if (!$this->_hasActiveRoleInOptions($options)) {
            return false;
        }

        $wordpressRoles = $this->getUserRoles();
        
        $diff = array_diff_key($wordpressRoles, $options['roles']);
        $roles = array_diff_key($wordpressRoles, $diff);
        
        return $roles;
    } // end getActiveRoles
    
    private function _hasActiveRoleInOptions($options)
    {
        return array_key_exists('roles', $options);
    } // end _hasActiveRoleInOptions
    
    public function getUserRoles()
    {
        if (!$this->_hasRolesInGlobals()) {
            return false;
        }
        
        $roles = $GLOBALS['wp_roles'];

        return $roles->roles; 
    } // getUserRoles
    
    private function _hasRolesInGlobals()
    {
        return array_key_exists('wp_roles', $GLOBALS);   
    } // end _hasWordpessPostTypeInGlobals
    
    public function onDisplayInfoAboutDisabledWoocommerceAction()
    {        
        $message = 'WooCommerce Prices By User Role: ';
        $message .= 'WooCommerce not active or not installed.';
        $this->displayError($message);
    } //end onDisplayInfoAboutDisabledWoocommerceAction
    
    public function updateMetaOptions($idPost, $value, $optionName)
    {
        $value = json_encode($value);
    
        return update_post_meta($idPost, $optionName, $value);
    } // end updateMetaOptions
    
    public function updateProductPrices($idPost, $prices)
    {
        $this->updateMetaOptions(
            $idPost, 
            $prices,
            PRICE_BY_ROLE_PRICE_META_KEY
        );
    } // end updateProductPrices
    
    public function getProductPrices($idProduct)
    {
        return $this->getMetaOptionsForProduct(
            $idProduct, 
            PRICE_BY_ROLE_PRICE_META_KEY
        );
    } // end getProductPrices
    
    public function isIgnoreDiscountForProduct($idProduct = false)
    {
        return (bool) $this->getMetaOptionsForProduct(
            $idProduct,
            PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY
        );
    } // end isIgnoreDiscountForProduct
    
    public function getMetaOptionsForProduct($productId, $optionName)
    {
        if (!$productId) {
            $post = $this->getWordpressPostInstance();
            $productId = $post->ID;
        }
    
        $values = $this->getMetaOptions($productId, $optionName);
        
        if (!$values) {
            $values = array();
        }
    
        return $values;
    } // end getMetaOptionsForProduct
    
    public function &getWordpressPostInstance()
    {
        return $GLOBALS['post'];
    } // end getWoocommerceInstance
    
    public function getPostMeta($postId, $key, $single = true)
    {
        return get_post_meta($postId, $key, $single);
    } // end getPostMeta
    
    public function getUserRole($idUser = false)
    {
        $roles = $this->getAllUserRoles($idUser);
    
        if (!$roles) {
            return false;
        }
    
        return array_shift($roles);
    } // end getUserRole
    
    public function getAllUserRoles($idUser = false)
    {
        if (!$idUser) {
            $idUser = $this->getUserId();
        }
    
        if (!$idUser) {
            return false;
        }
    
        $userData = get_userdata($idUser);
    
        return $userData->roles;
    } // end getAllUserRoles
    
    public function getUserId()
    {
        if (defined('DOING_AJAX') && $this->_hasUserIdInSessionArray()) {
            return $_SESSION['userIdForAjax'];
        }
    
        $userId = get_current_user_id();
    
        return $userId;
    } // end getUserId
    
    private function _hasUserIdInSessionArray()
    {
        return isset($_SESSION['userIdForAjax']);
    } // end _hasUserIdInSessionArray
    
    public function getRolePrice($idProduct, $idUser = false)
    {
        $roles = $this->getAllUserRoles($idUser);
    
        if (!$roles) {
            return false;
        }
        
        $priceList = $this->getProductPrices($idProduct);
    
        if (!$priceList) {
            return false;
        }
    
        $prices = $this->_getUserPrices($priceList, $roles, $idProduct);

        if (!$prices) {
            return false;
        }

        return min($prices);
    } // end getRolePrice
    
    private function _getUserPrices($priceList, $roles, $id)
    {
        if ($this->isWpmlMultiCurrencyOptionOn()) {
            $wpmlCurrencyManager = new WpmlCurrencyCompabilityManager($this);
            return $wpmlCurrencyManager->getPrices($priceList, $roles, $id);
        }
    
        return $this->getAllRolesPrices($priceList, $roles);
    }
    
    protected function getAllRolesPrices($priceList, $roles)
    {
        $prices = array();

        foreach ($roles as $key => $role) {
            if (!$this->_hasRolePriceInProductOptions($priceList, $role)) {
                continue;
            }
        
            $prices[]= $this->getPriceWithFixedFloat($priceList[$role]);
        }
        
        return $prices;
    } // end getAllRolesPrices
    
    public function getRoleSalePrice($idProduct, $idUser=false)
    {
        $roles = $this->getAllUserRoles($idUser);
        
        if (!$roles) {
            return false;
        }
        
        $priceList = $this->getProductPrices($idProduct);
            
        $prices = array();
        
        foreach ($roles as $key => $role) {            
            if ($this->_hasSalePriceForUserRole($priceList, $role)) {
                $prices[] = $this->getPriceWithFixedFloat(
                    $priceList['salePrice'][$role]
                );
            }
        }
        
        if ($prices) {
            return min($prices);    
        }
        
        return false;
    }
    
    private function _hasSalePriceForUserRole($priceList, $role)
    {
        return $this->_hasRolePriceInProductOptions($priceList, $role)
               && !$this->isDiscountOrMarkupEnabled()
               && $this->_hasExistSalePriceForUserRole($priceList, $role)
               && $this->_hasScheduleForSalePriceRole($priceList, $role);
    }
    
    private function _hasExistSalePriceForUserRole($priceList, $role)
    {
        return array_key_exists('salePrice', $priceList)
               && array_key_exists($role, $priceList['salePrice'])
               && !empty($priceList['salePrice'][$role]);
    }
    
    private function _hasScheduleForSalePriceRole($priceList, $role)
    {
        if ($this->_hasScheduleFiledForSalePrice($priceList, $role)) {
            $dateNow = time();
            
            $dateFrom = $this->_getTimeSalePrice(
                $priceList,
                $role,
                'date_from'
            );

            $dateTo = $this->_getTimeSalePrice($priceList, $role, 'date_to');
            
            if ($dateFrom && $dateTo) {
                return ($dateNow >= $dateFrom && $dateNow <= $dateTo);
            } else if ($dateFrom && !$dateTo) {
                return ($dateNow >= $dateFrom);    
            } else if (!$dateFrom && $dateTo) {
                return ($dateNow <= $dateTo);
            }
        }
        
        return true;
    }
    
    private function _getTimeSalePrice($priceList, $role, $dateName)
    {
        $date = 0;
        if (array_key_exists($dateName, $priceList['schedule'][$role])) {
            $date = strtotime($priceList['schedule'][$role][$dateName]);
        }
        
        return $date; 
    }
    
    private function _hasScheduleFiledForSalePrice($priceList, $role)
    {
        return array_key_exists('schedule', $priceList)
               && array_key_exists($role, $priceList['schedule']);
    }
    
    protected function getPriceWithFixedFloat($price)
    {
        $price = str_replace(',', '.', $price);
        $price = floatval($price);
        return strval($price);
    } // end getPriceWithFixedFloat
    
    private function _hasRolePriceInProductOptions($priceList, $role)
    {
        return array_key_exists($role, $priceList) && $priceList[$role];
    } // end _hasRolePriceInProductOptions

    public function hasDiscountOrMarkUpForUserRoleInGeneralOptions(
        $userRole = false, $idUser = false
    )
    {
        if (!$userRole) {
            $userRole = $this->getUserRole($idUser);
        }
        
        if (!$userRole) {
            return false;
        }
    
        $settings = $this->getSettings();
    
        return array_key_exists('discountByRoles', $settings) && 
               array_key_exists($userRole, $settings['discountByRoles']) && 
               $settings['discountByRoles'][$userRole]['value'] != false;
    } // end hasDiscountOrMarkUpForUserRoleInGeneralOptions
    
    public function isDiscountOrMarkupEnabled()
    {
        $setting = $this->getOptions('additionalSettings');
        
        $key = 'discountOrMarkupEnabled';
        
        if (!$setting || !array_key_exists($key, $setting)) {
            return false;
        }
        
        return (bool) $setting[$key];
    }
    
    public function getRolePricesVariableProductByPriceType($product, $type)
    {
        if (!$this->isVariableTypeProduct($product)) {
            return false;
        }
        $productsIDs = $product->get_children();
        
        if (!$productsIDs) {
            return false;
        }
        $wooFacade = WooCommerceFacade::getInstance();
        $prices = array();
        
        foreach ($productsIDs as $id) {
            $productChild = $this->createProductInstance($id);
            if (!$this->hasProductID($productChild)) {
                continue;
            }

            if ($this->_isRolePriceTypeRegular($type)) {
                $price = $this->getPrice($productChild);
            } else {
                $price = $this->getSalePrice($productChild); 
            }
            
            if (!$price) {
                continue;
            }
            
            if (
                $wooFacade->isEnabledTaxCalculation() &&
                !$wooFacade->hasTaxInPrice()
            ) {
                $price = $wooFacade->doIncludeTaxesToPrice($product, $price);
            }
            
            $prices[] = $price;
        }
        
        return $prices;
    }
    
    private function _isRolePriceTypeRegular($type)
    {
        return $type == PRICE_BY_ROLE_TYPE_PRODUCT_REGULAR_PRICE;
    }
    
    public function hasRoleRegularPriceByVariableProduct($product)
    {
        $rolePrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            PRICE_BY_ROLE_TYPE_PRODUCT_REGULAR_PRICE
        );
        return (bool) $rolePrices;
    }
    
    public function hasRoleSalePriceByVariableProduct($product)
    {
        $rolePrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            PRICE_BY_ROLE_TYPE_PRODUCT_SALE_PRICE
        );
        return (bool) $rolePrices;
    }
}