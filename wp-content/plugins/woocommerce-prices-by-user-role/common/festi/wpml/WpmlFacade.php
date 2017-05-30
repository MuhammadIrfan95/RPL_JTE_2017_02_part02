<?php

class WpmlFacade
{
    private static $_instance = null;
    private $_isInstalled = null;
    
    public static function &getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end &getInstance
    
    public function __construct()
    {
         if (isset(self::$_instance)) {
            $message = 'Instance already defined ';
            $message .= 'use WpmlFacade::getInstance';
            throw new Exception($message);
         }
    } // end __construct
    
    public function isInstalled()
    {
         $pluginPath = 'woocommerce-multilingual/wpml-woocommerce.php';
         
         if ($this->_isInstalled === null) {
            $this->_isInstalled = $this->_isPluginActive($pluginPath);    
         }
         
         return $this->_isInstalled;
    } // end isInstalled
    
    private function _isPluginActive($pluginMainFilePath)
    {
        if (is_multisite()) {
           $activPlugins = get_site_option('active_sitewide_plugins');
           $result =  array_key_exists($pluginMainFilePath, $activPlugins);
           if ($result) {
               return true;
           }
        }
        
        $activPlugins = get_option('active_plugins');
        return in_array($pluginMainFilePath, $activPlugins);
    } // end _isPluginActive
    
    public function getWooCommerceProductIDByPostID($idProduct)
    {
        $originalProductID = apply_filters(
            'wpml_master_post_from_duplicate',
            $idProduct
        );
        
        return $originalProductID;
    } // end getWooCommerceProductIDByPostID
    
}
