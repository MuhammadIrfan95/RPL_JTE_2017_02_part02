<?php
// @codingStandardsIgnoreStart
/**
 * Plugin Name: WooCommerce Prices By User Role
 * Plugin URI: https://festi.team/plugins/woocommerce-prices-by-user-role/
 * Description:  With this plugin  for WooCommerce  Products can be offered different prices for each customer group. Also you can do only product catalog without prices and show custom notification instead price.
 * Version: 3.1
 * Author: Festi-Team
 * Author URI: https://festi.team/
 * Copyright 2014  Festi-Team  https://festi.team/
 */
// @codingStandardsIgnoreEnd

try {
    $festiPriceByRolePath = dirname(__FILE__);
    $festiPriceByRoleCommonPath = dirname(__FILE__).'/common';
    $festiPriceByRoleFestiPath = dirname(__FILE__).'/common/festi';
    
    require_once $festiPriceByRolePath.'/config.php';
    
    if (!class_exists('WooUserRolePricesUtils')) {
        $path = '/WooUserRolePricesUtils.php';
        require_once $festiPriceByRolePath.$path;
    }
    
    WooUserRolePricesUtils::doCheckPhpVersion(
        PRICE_BY_ROLE_MIN_PHP_VERSION
    );
    
    if (!class_exists('WordpressDispatchFacade')) {
        require_once $festiPriceByRoleCommonPath.'/WordpressDispatchFacade.php';
    }
    
    if (!class_exists('WooCommerceCacheHelper')) {
        $path = '/woocommerce/WooCommerceCacheHelper.php';
        require_once $festiPriceByRoleFestiPath.$path;
    }
    
    if (!class_exists('WooCommerceProductValuesObject')) {
        $path = '/common/festi/woocommerce/WooCommerceProductValuesObject.php';
        require_once $festiPriceByRolePath.$path;
    }
    
    if (!class_exists('FestiObject')) {
        require_once $festiPriceByRoleFestiPath.'/FestiObject.php';
    }
    
    if (!class_exists('FestiPlugin')) {
        require_once $festiPriceByRoleFestiPath.'/FestiPlugin.php';
    }
    
    if (!class_exists("WooCommerceFacade")) {
        $path = '/common/festi/woocommerce/WooCommerceFacade.php';
        require_once $festiPriceByRolePath.$path;
    }
    
    if (!class_exists("WordpressFacade")) {
        $path = '/common/festi/wordpress/WordpressFacade.php';
        require_once $festiPriceByRolePath.$path;
    }
    
    if (!class_exists('UserRolePriceFacade')) {
        $path = '/facade/UserRolePriceFacade.php';
        require_once $festiPriceByRoleCommonPath.$path;
    }
    
    if (!class_exists("Object")) {
        $path = '/common/festi/database/Object.php';
        require_once $festiPriceByRolePath.$path;
    }
    
    if (!class_exists('WpmlCompatibleFestiPlugin')) {
        $path = '/wpml/WpmlCompatibleFestiPlugin.php';
        require_once $festiPriceByRoleCommonPath.$path;
    }
    
    if (!class_exists('FestiWpmlManager')) {
        require_once $festiPriceByRoleCommonPath.'/wpml/FestiWpmlManager.php';
    }
    
    if (!class_exists('StringManagerWooUserRolePrices')) {
        require_once $festiPriceByRolePath.
            '/StringManagerWooUserRolePrices.php';
    }
    
    if (!class_exists('WooUserRolePricesFestiPlugin')) {
        require_once $festiPriceByRolePath.'/WooUserRolePricesFestiPlugin.php';
    }
    
    if (!class_exists('EnvatoUtil')) {
        require_once $festiPriceByRoleFestiPath.'/envato/EnvatoUtil.php';
    }
    
    require_once $festiPriceByRolePath.'/functions.php';
    require_once $festiPriceByRoleCommonPath.'/WooUserRolePricesApiFacade.php';
    
    $className = 'wooUserRolePricesFestiPlugin';
    $GLOBALS[$className] = new WooUserRolePricesFestiPlugin(__FILE__);
} catch (Exception $e) {
     WooUserRolePricesUtils::displayPluginError($e->getMessage());
}
