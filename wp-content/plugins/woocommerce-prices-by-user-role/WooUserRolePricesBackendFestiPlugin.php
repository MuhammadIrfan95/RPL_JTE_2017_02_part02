<?php

if (!class_exists('SettingsWooUserRolePrices')) {
    require_once dirname(__FILE__).'/SettingsWooUserRolePrices.php';    
}

if (!class_exists("CsvWooProductsImporter")) {
    $fileName = 'CsvWooProductsImporter.php';
    require_once dirname(__FILE__).'/common/import/'.$fileName;
}

if (!class_exists("ImportWooProductException")) {
    $fileName = 'ImportWooProductException.php';
    require_once dirname(__FILE__).'/common/import/'.$fileName;
}

if (!class_exists("FestiTeamApiClient")) {
    $fileName = 'FestiTeamApiClient.php';
    require_once dirname(__FILE__).'/common/api/'.$fileName;
}

if (!class_exists("PricesHidingRulesWooUserRoleBackendTab")) {
    $fileName = 'PricesHidingRulesWooUserRoleBackendTab.php';
    require_once __DIR__.'/common/backend/tabs/'.$fileName;
}

class WooUserRolePricesBackendFestiPlugin extends WooUserRolePricesFestiPlugin
{
    private $_importManager;
    
    protected $_menuOptions = array(
        'settingsTab'    => 'Settings',
        'hidingRulesTab' => 'Hiding Rules',
        'importPriceTab' => 'Import Products',
        'registration'   => 'Product License'
    );
    
    protected $uploadImportFields;

    protected $_defaultMenuOption = 'settingsTab';
    
    protected $_hidingRulesTab;
    protected $_settingsObject;
    
    protected function onInit()
    {    
        // FIXME: Split display logic and import logic
        $this->getImportManager();
        
        $priority = 100;
        $this->addActionListener('admin_menu', 'onAdminMenuAction', $priority);
        
        $this->addActionListener(
            'wp_ajax_onSetUserIdForAjaxAction',
            'onSetUserIdForAjaxAction'
        );
        
        $this->addActionListener(
            'woocommerce_product_write_panel_tabs',
            'onAppendTabToAdminProductPanelAction'
        );
        
        $this->addActionListener(
            'woocommerce_product_data_panels', //woocommerce_product_write_panels
            'onAppendTabContentToAdminProductPanelAction'
        );
        
        $this->addActionListener(
            'woocommerce_product_options_pricing',
            'onAppendFieldsToSimpleOptionsAction'
        );
        
        $priority = 11;
        $paramsCount = 3;
        
        $this->addActionListener(
            'woocommerce_product_after_variable_attributes',
            'onAppendFieldsToVariableOptionsAction',
            $priority,
            $paramsCount
        );
        
        $this->addActionListener(
            'woocommerce_process_product_meta',
            'onUpdateProductMetaOptionsAction'
        );

        $this->addActionListener(
            'woocommerce_process_product_meta',
            'onUpdateAllTypeProductMetaOptionsAction'
        );
        
        $priority = 10;
        $paramsCount = 2;
        
        $this->addActionListener(
            'woocommerce_save_product_variation',
            'onUpdateVariableProductMetaOptionsAction',
            $priority,
            $paramsCount
        );
        
        $this->addActionListener(
            'admin_print_styles', 
            'onInitCssForWoocommerceProductAdminPanelAction'
        );
        
        $this->addActionListener(
            'admin_print_scripts', 
            'onInitJsForWoocommerceProductAdminPanelAction'
        );
        
        $this->addFilterListener(
            'plugin_action_links_woocommerce-prices-by-user-role/plugin.php',
            'onFilterPluginActionLinks'
        );
        
        $this->addActionListener(
            'bulk_edit_custom_box',
            'onInitHideProductFieldForBulkEdit',
            10,
            2
        );
        
        $this->addActionListener(
            'wp_ajax_onHideProductsByRoleAjaxAction',
            'onHideProductsByRoleAjaxAction'
        );
        
        if ($this->isWpmlMultiCurrencyOptionOn()) {
            $wmplCurrencyManager = new WpmlCurrencyCompabilityManager($this);
            $wmplCurrencyManager->onInitBackendActionListeners();
        }
        
        $this->_hidingRulesTab = 
            new PricesHidingRulesWooUserRoleBackendTab($this);

        $languageDomain = $this->_languageDomain;
        $this->_settingsObject = new SettingsWooUserRolePrices($languageDomain);
        
        $envato = $this->_getEnvatoUtilInstance();
        $envato->displayLicenseNotice();
        
    } // end onInit
    
    private function _isAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    } // end _isAjax
    
    private function _isSettingsPage()
    {
        return array_key_exists('page', $_GET)
               && $_GET['page'] == PRICE_BY_ROLE_SETTINGS_PAGE_SLUG;
    } // end _isSettingsPage
    
    public function onSetUserIdForAjaxAction()
    {
        $result = array('status' => true);
        
        if (!isset($_POST['userId'])) {
            $result['status'] = false;
        } else {
            $_SESSION['userIdForAjax'] = $_POST['userId'];
        }
 
        wp_send_json($result);
        exit();
    } // end onSetUserIdForAjaxAction
    
    public function onInitCssForWoocommerceProductAdminPanelAction()
    {
        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-product-admin-panel-styles',
            'product_admin_panel.css',
            array(),
            $this->_version
        );
        
        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-product-admin-panel-tooltip',
            'tooltip.css',
            array(),
            $this->_version
        );
    } // end onInitCssForWoocommerceProductAdminPanelAction
    
    public function onInitJsForWoocommerceProductAdminPanelAction()
    {
        $this->onEnqueueJsFileAction('jquery');

        $this->onEnqueueJsFileAction(
            'festi-checkout-steps-wizard-tooltip',
            'tooltip.js',
            'jquery',
            $this->_version
        );
        
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-product-admin-panel-tooltip',
            'product_admin_panel.js',
            'jquery',
            $this->_version
        );
        
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-product-admin-add-new-order',
            'add_new_order.js',
            'jquery',
            $this->_version,
            true
        );

        $vars = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        );

        wp_localize_script(
            'festi-user-role-prices-product-admin-add-new-order',
            'fesiWooPriceRole',
            $vars
        );
    } // end onInitJsForWoocommerceProductAdminPanelAction
    
    public function onAppendTabToAdminProductPanelAction()
    {
        echo $this->fetch('product_tab.phtml');
    } // end onAppendTabToAdminProductPanelAction

    public function onAppendTabContentToAdminProductPanelAction()
    {
        $settings = $this->getOptions('settings');
        if (!is_array($settings)) {
            $settings = array();
        }
        
        $vars = array(
            'onlyRegisteredUsers' => $this->getValueFromProductMetaOption(
                'onlyRegisteredUsers'
            ),
            'hidePriceForUserRoles' => $this->getValueFromProductMetaOption(
                'hidePriceForUserRoles'
            ),
            'settings' => $settings,
            'settingsForHideProduct' => $this->_getSettingsForHideProduct()
        );
        
        echo $this->fetch('product_tab_content.phtml', $vars);
    } // end onAppendTabContentToAdminProductPanelAction
    
    private function _getSettingsForHideProduct()
    {
        $hideProductForUserRoles = $this->getMetaOptionsForProduct(
            false,
            PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY
        );
        
        $settings = array(
            'hideProductForUserRoles' => $hideProductForUserRoles,
            'roles' => $this->getUserRoles()
        );
        
        return $settings;
    }
    
    public function onInitHideProductFieldForBulkEdit($columnName, $postType)
    {
        if (!$this->_isBulkEditForProducts($columnName, $postType)) {
            return true;
        }
        
        $vars = array(
            'roles' => $this->getUserRoles()
        );
        echo $this->fetch('bulk_edit_hide_product.phtml', $vars);
    }
    
    private function _isBulkEditForProducts($columnName, $postType)
    {
        return $columnName == 'price' && $postType == 'product';
    }
    
    public function onHideProductsByRoleAjaxAction()
    {
        if ($this->_hasPostIDsInRequest()) {
            $postIDs = $_POST['postIDs'];    
            $formBulkEdit = array();
            parse_str($_POST['form'], $formBulkEdit);
            $this->_doUpdateHideProductsForBulkEditForm(
                $postIDs,
                $formBulkEdit
            );
        }
    }
    
    private function _doUpdateHideProductsForBulkEditForm(
        $postIDs, $formBulkEdit
    )
    {
        if ($postIDs && is_array($postIDs)) {
            foreach ($postIDs as $id) {
                $this->updateMetaOptions(
                    $id,
                    $formBulkEdit[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY],
                    PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY
                );
                $this->_doUpdateHideProductOptions($id, $formBulkEdit);
            }
        }
    }
    
    private function _hasPostIDsInRequest()
    {
        return array_key_exists('postIDs', $_POST)
               && !empty($_POST['postIDs']);
    }
    
    public function hasOnlyRegisteredUsersOptionInPluginSettings($settings)
    {
        return array_key_exists('onlyRegisteredUsers', $settings);
    } // end _hasOnlyRegisteredUsersOptionInPluginSettings
    
    public function hasRoleInHidePriceForUserRolesOption(
        $settings, $role
    )
    {
        return array_key_exists('hidePriceForUserRoles', $settings)
               && array_key_exists($role, $settings['hidePriceForUserRoles']);
    } // end hasOnlyRegisteredUsersOptionInPluginSettings
    
    public function getValueFromProductMetaOption($optionName)
    {
        $options = $this->getMetaOptionsForProduct(
            false,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );

        if (!$this->_hasItemInOptionsList($optionName, $options)) {
            return false;
        }
        
        return $options[$optionName];
    } //end getValueFromProductMetaOption
    
    private function _hasItemInOptionsList($optionName, $options)
    {
        return array_key_exists($optionName, $options);
    } //end _hasItemInOptionsList

    public function onUpdateVariableProductMetaOptionsAction(
        $variationId, $loop
    )
    {
        $this->_updateIgnoreDiscountMetaOption($variationId);
        
        $metaKey = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        
        if (!$this->_hasVariableItemInRequest($loop)) {
            $_POST[$metaKey][$loop] = array();
        }
        
        $value = $_POST[$metaKey][$loop];
        
        $this->updateProductPrices($variationId, $value);
    } // end onUpdateVariableProductMetaOptionsAction
    
        
    public function onUpdateProductMetaOptionsAction($idPost)
    {
        if (!$this->_hasHidePriceProductOptionsInRequest()) {
            $_POST[PRICE_BY_ROLE_HIDDEN_RICE_META_KEY] = array();
        }
        
        $value = json_encode($_POST[PRICE_BY_ROLE_HIDDEN_RICE_META_KEY]);
        
        $this->updateMetaOptions(
            $idPost,
            $_POST[PRICE_BY_ROLE_HIDDEN_RICE_META_KEY],
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
        
        if (!$this->_hasHideProductOptionsInRequest($_POST)) {
            $_POST[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY] = array();
        }
        $this->updateMetaOptions(
            $idPost,
            $_POST[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY],
            PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY
        );
        
        $this->_doUpdateHideProductOptions($idPost, $_POST);
    } // end onUpdateProductMetaOptionsAction
    
    private function _doUpdateHideProductOptions($idPost, $data)
    {
        $slectedRoles = array();
        $hiddenProductsByRole = $this->getOptions(
            PRICE_BY_ROLE_HIDDEN_PRODUCT_OPTIONS
        );
        
        if (!$hiddenProductsByRole) {
            $hiddenProductsByRole = array();
        }
        
        if ($this->_hasHideProductOptionsInRequest($data)) {
            $slectedRoles = $data[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY];
            
            foreach ($slectedRoles as $key => $item) {    
                $slectedRoles[$key] = array($idPost);
            }
            
            $hiddenProductsByRole = $this->_doRemoveIdPostInHideProductOptions(
                $idPost,
                $hiddenProductsByRole
            );
            
            
            $hiddenProductsByRole = $this->_doPrepareHideProductOptions(
                $slectedRoles,
                $hiddenProductsByRole
            );            
            
        } else {
            $hiddenProductsByRole = $this->_doRemoveIdPostInHideProductOptions(
                $idPost,
                $hiddenProductsByRole
            );
        }

        $this->updateOptions(
            PRICE_BY_ROLE_HIDDEN_PRODUCT_OPTIONS,
            $hiddenProductsByRole
        );
    }
    
    private function _doPrepareHideProductOptions($roles, $options)
    {
        $options = array_merge_recursive($roles, $options);
            
        foreach ($options as $key => $item) {
            if (is_array($item)) {
                $options[$key] = array_unique($item, SORT_NUMERIC);
            }
                
        } 
        return $options;
    }
    
    private function _doRemoveIdPostInHideProductOptions($idPost, $options)
    {
        foreach ($options as $role => $postIDs) {
            if (is_array($postIDs)) {
                foreach ($postIDs as $key => $id) {
                   if ($id == $idPost) {
                       unset($options[$role][$key]);      
                   }
                }
            }
        }
        return $options;
    }
    
    private function _hasHidePriceProductOptionsInRequest()
    {
        return array_key_exists(PRICE_BY_ROLE_HIDDEN_RICE_META_KEY, $_POST)
               && !empty($_POST[PRICE_BY_ROLE_HIDDEN_RICE_META_KEY]);
    } // end _hasHidePriceProductOptionsInRequest
    
    private function _hasHideProductOptionsInRequest($data)
    {
        return array_key_exists(PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY, $data)
               && !empty($data[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY]);
    } // end _hasHidePriceProductOptionsInRequest
    
    private function _hasIgnoreDiscountOptionInRequest($idPost)
    {
        $key = PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY;
        return array_key_exists($key, $_POST) && 
               array_key_exists($idPost, $_POST[$key]) &&
               !empty($_POST[$key][$idPost]);
    } // end _hasIgnoreDiscountOptionInRequest
    
    private function _hasRolePriceProductOptionsInRequest()
    {
        return array_key_exists(PRICE_BY_ROLE_PRICE_META_KEY, $_POST)
               && !empty($_POST[PRICE_BY_ROLE_PRICE_META_KEY]);
    } // end _hasRolePriceProductOptionsInRequest
    
    public function getSelectorClassForDisplayEvent($class)
    {
        $selector = $class.'-visible';
        
        $options = $this->getOptions('settings');
                
        if (!isset($options[$class]) || $options[$class] == 'disable') {
            $selector.=  ' festi-user-role-prices-hidden ';
        }
        
        return $selector;
    } // end getSelectorClassForDisplayEvent
    
    private function _hasVariableItemInRequest($loop)
    {
        $metaKey = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        
        return array_key_exists($metaKey, $_POST)
               && array_key_exists($loop, $_POST[$metaKey]);
    } // end _hasVariableItemInRequest
    
    public function onUpdateAllTypeProductMetaOptionsAction($idPost)
    {
        $this->_updateIgnoreDiscountMetaOption($idPost);
        
        if (!$this->_hasRolePriceProductOptionsInRequest()) {
            $_POST[PRICE_BY_ROLE_PRICE_META_KEY] = array();
        }
        
        $this->updateProductPrices(
            $idPost, 
            $_POST[PRICE_BY_ROLE_PRICE_META_KEY]
        );
    } // end onUpdateAllTypeProductMetaOptionsAction
    
    private function _updateIgnoreDiscountMetaOption($idPost)
    {
        $value = (int) $this->_hasIgnoreDiscountOptionInRequest($idPost);
        $this->updateMetaOptions(
            $idPost,
            $value,
            PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY
        );
    } // end _updateIgnoreDiscountMetaOption
    
    public function onAppendFieldsToSimpleOptionsAction()
    {
        $displayManager = new WooUserRoleDisplayPricesBackendManager($this);
        $displayManager->onAppendFieldsToSimpleOptionsAction();
        
        $this->removeAction(
            'woocommerce_product_options_pricing',
            'onAppendFieldsToSimpleOptionsAction'
        );
    } // end onAppendFieldsToSimpleOptionsAction
    
    private function _onCheckDiscountOrMarkupEnabled()
    {
        $settings = $this->getOptions('settings');
        $flag = false;
        if ($this->_hasDiscountByRolesOptionInSettings($settings)) {
            foreach ($settings['discountByRoles'] as $role => $item) {
                if ($item['value'] > 0) {
                    $flag = true;
                    break;
                }
            }
        }
        $additionalSettings['discountOrMarkupEnabled'] = (int) $flag;
        
        $this->updateOptions('additionalSettings', $additionalSettings);
    }
    
    private function _hasDiscountByRolesOptionInSettings($settings)
    {
        return $settings && array_key_exists('discountByRoles', $settings);
    }
    
    protected function removeAction($hook, $methodName, $priority = 10)
    {        
        remove_action($hook, array($this, $methodName), $priority);
    } // end removeAction
    
    public function onAppendFieldsToVariableOptionsAction($loop, $data, $post)
    {
        $displayManager = new WooUserRoleDisplayPricesBackendManager($this);
        $displayManager->onAppendFieldsToVariableOptionsAction(
            $loop,
            $data,
            $post
        );
    } // end onAppendFildsToVariableOptionsAction

    public function onInstall($refresh = false, $settings = false)
    {        
        if (!$this->_fileSystem) {
            $this->_fileSystem = $this->getFileSystemInstance();
        }
        
        if ($this->_hasPermissionToCreateCacheFolder()) {
            $this->_fileSystem->mkdir($this->_pluginCachePath, 0777);
        }
        
        if (!$refresh) {
            $settings = $this->getOptions('settings');
        }

        if (!$refresh && !$settings) {
            $this->_doInitDefaultOptions('settings');
            $this->updateOptions('roles', array());
        }
        
        FestiTeamApiClient::addInstallStatistics(PRICE_BY_ROLE_PLUGIN_ID);
    } // end onInstall
    
    private function _hasPermissionToCreateCacheFolder()
    {
        return ($this->_fileSystem->is_writable($this->_pluginPath)
               && !file_exists($this->_pluginCachePath));
    } // end _hasPermissionToCreateFolder
    
    public function getPluginTemplatePath($fileName)
    {
        return $this->_pluginTemplatePath.'backend/'.$fileName;
    } // end getPluginTemplatePath
    
    public function getPluginCssUrl($fileName) 
    {
        return $this->_pluginCssUrl.'backend/'.$fileName;
    } // end getPluginCssUrl
    
    public function getPluginJsUrl($fileName)
    {
        return $this->_pluginJsUrl.'backend/'.$fileName;
    } // end getPluginJsUrl
    
    protected function hasOptionPageInRequest()
    {
        return array_key_exists('tab', $_GET)
               && array_key_exists($_GET['tab'], $this->_menuOptions);
    } // end hasOptionPageInRequest
    
    public function _onFileSystemInstanceAction()
    {
        $this->_fileSystem = $this->getFileSystemInstance();
    } // end _onFileSystemInstanceAction
    
    public function onAdminMenuAction() 
    {
        $args = array(
             'parent'     => PRICE_BY_ROLE_WOOCOMMERCE_SETTINGS_PAGE_SLUG,
             'title'      => __('Prices by User Role', $this->_languageDomain),
             'caption'    => __('Prices by User Role', $this->_languageDomain),
             'capability' => 'manage_options',
             'slug'       => PRICE_BY_ROLE_SETTINGS_PAGE_SLUG,
             'method'     => array(&$this, 'onDisplayOptionPage')  
        );

        $page = $this->doAppendSubMenu($args);
        
        $this->addActionListener(
            'admin_print_styles-'.$page, 
            'onInitCssAction'
        );
        
        $this->addActionListener(
            'admin_print_scripts-'.$page, 
            'onInitJsAction'
        );
        
        $this->addActionListener(
            'admin_head-'.$page,
            '_onFileSystemInstanceAction'
        );
    } // end onAdminMenuAction
    
    public function onInitCssAction()
    {
        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-styles',
            'style.css',
            array(),
            $this->_version
        );
        
        $this->onEnqueueCssFileAction(
            'festi-admin-menu',
            'menu.css',
            array(),
            $this->_version
        );
        
        $this->onEnqueueCssFileAction(
            'festi-checkout-steps-wizard-colorpicker',
            'colorpicker.css',
            array(),
            $this->_version
        );
    } // end onInitCssAction
    
    public function onInitJsAction()
    {
        $this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-colorpicker',
            'colorpicker.js',
            'jquery',
            $this->_version
        );
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-general',
            'general.js',
            'jquery',
            $this->_version
        );
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-modal',
            'modal.js',
            'jquery',
            $this->_version
        );
    } // end onInitJsAction
    
    public function doAppendSubMenu($args = array())
    {
        $page = add_submenu_page(
            $args['parent'],
            $args['title'], 
            $args['caption'], 
            $args['capability'], 
            $args['slug'], 
            $args['method']
        );
        
        return $page;  
    } //end doAppendSubMenu
    
    public function onDisplayOptionPage()
    {
        if ($this->_isRefreshPlugin()) {
            $this->onRefreshPlugin();
        }
        
        if ($this->_isRefreshCompleted()) {
            $message = __(
                'Success refresh plugin',
                $this->_languageDomain
            );
            
            $this->displayUpdate($message);   
        }
        
        $this->_displayPluginErrors();
        
        $this->displayOptionsHeader();
        
        if ($this->_menuOptions) {         
            $menu = $this->fetch('menu.phtml');
            echo $menu;
        }
        
        $methodName = 'display';
        
        if ($this->hasOptionPageInRequest()) {
            $postfix = $_GET['tab'];
        } else {
            $postfix = $this->_defaultMenuOption;
        }
        $methodName.= ucfirst($postfix);
        
        $method = array(&$this, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }
        
        call_user_func_array($method, array());
    } // end onDisplayOptionPage
    
    public function displayImportPriceTab()
    {
        $this->getImportManager()->displayPage();
    } // end displayImportPriceTab

    public function displaySettingsTab()
    {
        $vars = array();

        if ($this->_isDeleteRole()) {
            try {
                $this->deleteRole();
                           
                $this->displayOptionPageUpdateMessage(
                    'The role was successfully deleted'
                ); 
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->displayError($message);
            }
        }

        if ($this->isUpdateOptions('save')) {
            try {
                $this->_doUpdateOptions($_POST);
                           
                $this->displayOptionPageUpdateMessage(
                    'Settings are updated successfully'
                );
                
                $this->_onCheckDiscountOrMarkupEnabled();
                
                WooCommerceCacheHelper::doRefreshPriceCache();
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->displayError($message);
            }
        }
        
        if ($this->isUpdateOptions('new_role')) {
            try {
                $this->doAppendNewRoleToWordpressRolesList();
    
                $this->displayOptionPageUpdateMessage(
                    'The role was successfully added'
                );   
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->displayError($message);
            }
        }
        
        $this->onPrepareScreen();
        
        $options = $this->getOptions('settings');
        
        $vars['fieldset'] = $this->getOptionsFieldSet();        
        $vars['currentValues'] = $options;
        
        echo $this->fetch('settings_page.phtml', $vars);
        echo $this->fetch('add_new_role_form.phtml');
    } // end displaySettingsTab

    public function displayHidingRulesTab()
    {
        if ($this->isUpdateOptions('save')) {
            $res = $this->_hidingRulesTab->doUpdateOptions($_POST);
            if (!$res) {
                $this->displayError($this->_hidingRulesTab->getLastError());
            } else {
                $this->displayOptionPageUpdateMessage(
                    'Settings are updated successfully'
                );
            }
        }
        
        $this->_hidingRulesTab->display();
    } // end displayHidingRulesTab

    public function onPrepareScreen()
    {
        $this->addFilterListener(
            'admin_footer_text',
            'onFilterDisplayFooter'
        );
    } // end onPrepareScreen
    
    public function displayOptionsHeader()
    { 
        $vars = array(
            'content' => __(
                'Prices by User Role Options',
                $this->_languageDomain
            )
        );
        
        echo $this->fetch('options_header.phtml', $vars);
    } // end displayOptionsHeader
    
    public function deleteRole()
    {
        $roleKey = $_GET['delete_role'];
        
        $roles = $this->getUserRoles();
        
        if (!$this->_isRoleCreatedOfPlugin($roleKey)) {
            $message = __(
                'Unable to remove a role. Key does not exist.',
                $this->_languageDomain
            );
            throw new Exception($message);
        }
        
        $this->doDeleteWordpressUserRole($roleKey);
    } // end deleteRole
    
    private function _isRoleCreatedOfPlugin($key)
    {
        $roles = $this->getUserRoles();
        $pluginRoles = $this->getCreatedRolesOptionsOfPlugin();
        
        return array_key_exists($key, $roles)
               && array_key_exists($key, $pluginRoles);
    } // end _isRoleCreatedOfPlugin
    
    public function doDeleteWordpressUserRole($key)
    {
        $result = remove_role($key);
    } // end doDeleteWordpressUserRole
    
    private function _isDeleteRole()
    {
        return array_key_exists('delete_role', $_GET)
               && !empty($_GET['delete_role']);
    } // end _isDeleteRole
    
    public function doAppendNewRoleToWordpressRolesList()
    {
        if (!$this->_hasNewRoleInRequest()) {
            $message = __(
                'You have not entered the name of the role',
                $this->_languageDomain
            );
            throw new Exception($message, PRICE_BY_ROLE_EXCEPTION_EMPTY_VALUE);
        }
        
        $key = $this->getKeyForNewRole();
        if (!$key) {
            $message = __(
                'An error has occurred, the Role Name contains unacceptable '.
                'characters. Please use the Role Identifier field to add the '.
                'user role.',
                $this->_languageDomain
            );
            
            throw new Exception(
                $message, 
                PRICE_BY_ROLE_EXCEPTION_INVALID_VALUE
            );
        }
        
        $this->doAddWordpressUserRole($key, $_POST['roleName']);
        
        $this->updateCreatedRolesOptions($key);
        
        if ($this->_hasActiveOptionForNewRoleInRequest()) {
            $this->updateListOfEnabledRoles($key);
        } 
    } // end doAppendNewRoleToWordpressRolesList
    
    public function updateListOfEnabledRoles($key)
    {
        $settings = $this->getOptions('settings');
        
        $settings['roles'][$key] = true;
        
        $this->updateOptions('settings', $settings);
    } // end updatelistOfEnabledRoles
    
    public function updateCreatedRolesOptions($newKey)
    {
        $roleOptions = $this->getCreatedRolesOptionsOfPlugin();

        if (!$roleOptions) {
            $roleOptions = array();
        }
        
        $roleOptions[$newKey] = $_POST['roleName'];

        $this->updateOptions('roles', $roleOptions);
    } // end updateCreatedRolesOptions
    
    public function getCreatedRolesOptionsOfPlugin()
    {
        return $this->getOptions('roles');
    } // end getCreatedRolesOptionsOfPlugin
    
    public function doAddWordpressUserRole($key, $name)
    {
        $capabilities = array(
            'read' => true
        );
        
        $result = add_role($key, $name, $capabilities);
        
        if (!$result) {
            $message = __(
                'Unsuccessful attempt to create a role',
                $this->_languageDomain
            );
            throw new Exception($message);
        }
    } // end doAddWordpressUserRole
    
    public function getKeyForNewRole()
    {
        $roleKey = $_POST['roleName'];
        if (!empty($_POST['roleIdent'])) {
            $roleKey = $_POST['roleIdent'];
        }
        
        if (!preg_match("#^[a-zA-Z0-9_\s]+$#Umis", $roleKey)) {
            return false;
        }
        
        $roleKey = $this->_cleaningExtraCharacters($roleKey);

        $roleKey = $this->getAvailableKeyName($roleKey);
       
        return $roleKey;
    } // end getKeyForNewRole
    
    public function getAvailableKeyName($key)
    {
        $result = false;
        $sufix = '';
        $i = 0;
        
        $rols = $this->getUserRoles();
        
        while ($result === false) {
            $keyName = $key.$sufix;
            
            if (!$this->_hasKeyInExistingRoles($keyName, $rols)) {
                return $keyName;
            }

            $i++;
            $sufix = '_'.$i;
        }
    } // edn getAvailableKeyName
    
    private function _hasKeyInExistingRoles($keyName, $rols)
    {
        return array_key_exists($keyName, $rols);      
    } // end _hasKeyInExistingRoles
    
    private function _cleaningExtraCharacters($string)
    {
        $key = strtolower($string);
        $key = preg_replace('/[^a-z0-9\s]+/', '', $key);
        $key = trim($key);
        $key = preg_replace('/\s+/', '_', $key);
        
        return $key;
    } // end _cleaningExtraCharacters
    
    private function _hasNewRoleInRequest()
    {
        return array_key_exists('roleName', $_POST)
               && !empty($_POST['roleName']);
    } // end _hasNewRoleInRequest
    
    private function _hasActiveOptionForNewRoleInRequest()
    {
        return array_key_exists('active', $_POST);
    } // end _hasActiveOptionForNewRoleInRequest
    
    public function displayOptionPageUpdateMessage($text)
    {
        $message = __(
            $text,
            $this->_languageDomain
        );
            
        $this->displayUpdate($message);   
    } // end displayOptionPageUpdateMessage
    
    public function getOptionsFieldSet()
    {
        $fildset = array(
            'general' => array(),
        );
        
        $settings = $this->loadSettings();
        
        if ($settings) {
            foreach ($settings as $ident => &$item) {
                if (array_key_exists('fieldsetKey', $item)) {
                   $key = $item['fieldsetKey'];
                   $fildset[$key]['filds'][$ident] = $settings[$ident];
                }
            }
            unset($item);
        }
        
        return $fildset;
    } // end getOptionsFieldSet
    
    public function loadSettings()
    {
        $options = $this->_settingsObject->get();

        $values = $this->getOptions('settings');
        if ($values) {
            foreach ($options as $ident => &$item) {
                if (array_key_exists($ident, $values)) {
                    $item['value'] = $values[$ident];
                }
            }
            unset($item);
        }
        
        return $options;
    } // end loadSettings
    
    private function _displayPluginErrors()
    {        
        $caheFolderErorr = $this->_detectTheCacheFolderAccessErrors();

        if ($caheFolderErorr) {
            echo $this->fetch('refresh.phtml');
        }
    } // end _displayPluginErrors
    
    private function _isRefreshPlugin()
    {
        return array_key_exists('refresh_plugin', $_GET);
    } // end _isRefreshPlugin
    
    public function onRefreshPlugin()
    {
        $this->onInstall(true);
    } // end onRefreshPlugin
    
    private function _doInitDefaultOptions($option, $instance = NULL)
    {
        $methodName = $this->getMethodName('load', $option);
        
        if (is_null($instance)) {
            $instance = $this;
        }

        $method = array($instance, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }

        $options = call_user_func_array($method, array());
        foreach ($options as $ident => &$item) {
            if ($this->_hasDefaultValueInItem($item)) {
                $values[$ident] = $item['default'];
            }
        }
        unset($item);
        
        $this->updateOptions($option, $values);
    } // end _doInitDefaultOptions
    
    private function _hasDefaultValueInItem($item)
    {
        return isset($item['default']);
    } //end _hasDefaultValueInItem
    
    public function getMethodName($prefix, $option)
    {
        $option = explode('_', $option);
        
        $option = array_map('ucfirst', $option);
        
        $option = implode('', $option);
        
        $methodName = $prefix.$option;
        
        return $methodName;
    } // end getMethodName
    
    private function _isRefreshCompleted()
    {
        return array_key_exists('refresh_completed', $_GET);
    } // end _isRefreshCompleted
    
    private function _detectTheCacheFolderAccessErrors()
    {
        if (!$this->_fileSystem->is_writable($this->_pluginCachePath)) {

            $message = __(
                "Caching does not work! ",
                $this->_languageDomain
            );
            
            $message .= __(
                "You don't have permission to access: ",
                $this->_languageDomain
            );
            
            $path = $this->_pluginCachePath;
            
            if (!$this->_fileSystem->exists($path)) {
                $path = $this->_pluginPath;
            }
            
            $message .= $path;
            //$message .= $this->fetch('manual_url.phtml');
            
            $this->displayError($message);
            
            return true;
        }
        
        return false;
    } // end _detectTheCacheFolderAccessErrors
    
    public function isUpdateOptions($action)
    {
        return array_key_exists('__action', $_POST)
               && $_POST['__action'] == $action;
    } // end isUpdateOptions
    
    private function _doUpdateOptions($newSettings = array())
    {
        $this->updateOptions('settings', $newSettings);
    } // end _doUpdateOptions
    
    /**
     * @return CsvWooProductsImporter
     */
    public function getImportManager()
    {
        if (!$this->_importManager) {
            $this->_importManager = new CsvWooProductsImporter($this);
        }
        
        return $this->_importManager;
    } // end getImportManager
    
    /**
     * @filter admin_footer_text
     */
    public function onFilterDisplayFooter()
    {
        return $this->fetch('footer.phtml');
    } // end onFilterDisplayFooter
    
    /**
     * @filter plugin_action_links_
     */
    public function onFilterPluginActionLinks($links)
    {
        $link = $this->fetch('settings_link.phtml');
        
        return array_merge($links, array($link));
    } // end onFilterPluginActionLinks
    
    public function displayRegistration()
    {
        $envatoInstance = $this->_getEnvatoUtilInstance();
        
        $apiUrl = $envatoInstance->getApiUrl();

        $licenseInfo = $envatoInstance->doValidateLicense($_REQUEST);
        
        $urlPage = $envatoInstance->getPrepareUrl();
        
        $facade = WordpressFacade::getInstance();
        
        $pluginData = $facade->getPluginData($this->_pluginMainFile);

        $statusFestiAPI = $envatoInstance->getStatusFestiApiService();
        
        $vars = array(
            'pluginData'  => $pluginData,
            'licenseInfo' => $licenseInfo,
            'apiUrl'      => $apiUrl,
            'urlPage'     => $urlPage,
            'statusAPI'   => $statusFestiAPI
        );

        echo $this->fetch('registration.phtml', $vars);
    }
    
    private function _getEnvatoUtilInstance()
    {
        $message = __(
            'HI! Would you like unlock premium support? '.
            'Please activate your copy of Prices by User Role.'
        );
        
        $options = array(
            'id_plugin'   => PRICE_BY_ROLE_PLUGIN_ID,
            'message'     => $message,
            'slug_plugin' => PRICE_BY_ROLE_SETTINGS_PAGE_SLUG
        );
        
        return new EnvatoUtil($this, $options);
    }

}