<?php

if (!interface_exists("IWooCommerce")) {
    require_once dirname(__FILE__).'/IWooCommerce.php';
}

if (!interface_exists("IWooVersion")) {
    require_once __DIR__.'/version/IWooVersion.php';
}

class WooCommerceFacade implements IWooCommerce
{
    private static $_instance = null;
    private $_adapter;
    
    const WOOCOMMERCE_EMPTY_PRICE_SYMBOL = '';

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
            $message .= 'use WooCommerceFacade::getInstance';
            throw new Exception($message);
         }
         
         $this->_factory();
    } // end __construct
    
    private function _factory()
    {
        $wooVersion = $this->_getWooVersionNumber();
        
        $postfix = 'Three';
        
        if ($this->_isOldVersion()) {
            $postfix = 'Two';
        }
        $className = 'WooVersion'.$postfix;
        
        if (!class_exists($className)) {
            require_once __DIR__.'/version/WooVersion'.$postfix.'.php';    
        }
        $this->_adapter = new $className();
        return $this->_adapter;
    }
    
    public function getAttributes($search = array())
    {
        $db = FestiObject::getDatabaseInstance();
        
        $sql = "SELECT 
                    *
                FROM 
                    ".$db->getPrefix()."woocommerce_attribute_taxonomies";
        
        return $db->select($sql, $search);
    } // end getAttributes
    
    public function addAttribute($values)
    {
        $db = FestiObject::getDatabaseInstance();
        
        $tableName = $db->getPrefix()."woocommerce_attribute_taxonomies";
    
        $id = $db->insert($tableName, $values);
        
        delete_transient('wc_attribute_taxonomies');
        
        return $id;
    } // end getAttributes
    
   /* public function addAttributeValues($attributeIdent, $attributesValues)
    {
        if (!is_array($attributesValues)) {
            $attributesValues = array($attributesValues);
        }
        
        $taxonomy  = wc_attribute_taxonomy_name($attributeIdent);
        
        $termsValues = array();
        foreach ($attributesValues as $value) {
            $this->_addAttributeValue($taxonomy, $value);
        }
        
        delete_transient('wc_attribute_taxonomies');
        
        return true;
    } // end addAttributeValues
    
    private function _addAttributeValue($taxonomy, $value)
    {
        $db = FestiObject::getDatabaseInstance();
        
        $values = array(
            'name'       => $value,
            'slug'       => $this->getAttributeIdent($value),
            'term_group' => 0
        );
        
        $tableName = $db->getPrefix()."terms";
        $idTerm = $db->insert($tableName, $values);
        
        //
        $values = array(
            'term_id'     => $idTerm,
            'taxonomy'    => $taxonomy,
            'description' => '',
            'parent'      => 0,
            'count'       => 0
        );
        
        $tableName = $db->getPrefix()."term_taxonomy";
        $db->insert($tableName, $values);
        
        return true; 
    } // end _addAttributeValue*/
    
    public function createAtributtesHelper()
    {
        if (!class_exists('WooCommerceAtributtesHelper')) {
            require_once dirname(__FILE__).'/WooCommerceAtributtesHelper.php';
        }
        
        return new WooCommerceAtributtesHelper();
    } // end createAtributtesHelper
    
    
    public function getNumberOfDecimals()
    {
        return get_option('woocommerce_price_num_decimals');
    } // end getNumberOfDecimals
    
    public function getWooCommerceInstance()
    {
        if (!function_exists("WC")) {
            throw new Exception("Not Found WooCommerce Instance", 1);
        }
        
        return WC();
    } // end getWooComerceInstance
    
    public static function getCurrencies()
    {
        return get_woocommerce_currencies();
    }
    
    public static function getCurrencySymbol($code) 
    {
        return get_woocommerce_currency_symbol($code);
    }
    
    public static function getBaseCurrencyCode()
    {
        return get_woocommerce_currency();
    }

    public static function getDefaultCurrencyCode()
    {
        return get_option('woocommerce_currency');
    }
    
    public static function displayMetaTextInputField($args)
    {
        woocommerce_wp_text_input($args);
    }
    
    public static function displayHiddenMetaTextInputField($args)
    {
        woocommerce_wp_hidden_input($args);
    }
    
    public function getProductAttributeValues($idProduct, $attrName)
    {
        $terms = wp_get_object_terms($idProduct, $attrName);
        
        if (!$terms || $terms instanceof WP_Error) {
            return array();
        }
        
        $result = array();
        foreach ($terms as $term) {
            $result[] = $term->name;
        }
        
        return $result;
    } // end getProductAttributeValues
    
    public function getEmptyPriceSymbol()
    {
        return static::WOOCOMMERCE_EMPTY_PRICE_SYMBOL;
    } // end getEmptyPriceSymbol

    public function isEnabledTaxCalculation()
    {
        return get_option('woocommerce_calc_taxes') === 'yes';
    } // end isEnabledTaxCalculation

    public function hasTaxInPrice()
    {
        return get_option('woocommerce_prices_include_tax') === 'yes';
    } // end hasTaxInPrice
    
    public function getPriceDisplaySuffix()
    {
        return get_option('woocommerce_price_display_suffix');
    } // end getPriceDisplaySuffix
    
    public function hasPriceDisplaySuffixPriceIncludingOrExcludingTax()
    {
        $suffix = $this->getPriceDisplaySuffix();

        return (bool) preg_match(
            "/{price_(including|excluding)_tax}/",
            $suffix
        );
    } // end hasPriceDisplaySuffixPriceIncludingOrExcludingTax
    
    public function doIncludeTaxesToPrice($product, $price)
    {
        $taxRates = $this->_getTaxRates($product);
        
        $taxValues = $this->_doCalculateTaxes($price, $taxRates);
        
        if (!$taxValues) {
            $taxValues = array();
        }

        $priceWithTaxes = $price + $this->_getTaxTotal($taxValues);
        
        return $priceWithTaxes;
    } // end doIncludeTaxesToPrice
    
    private function _getTaxTotal($taxValues)
    {
        return WC_Tax::get_tax_total($taxValues);
    } // end _getTaxTotal
    
    private function _doCalculateTaxes($price, $taxRates)
    {
        return WC_Tax::calc_tax(floatval($price), $taxRates);
    } // end _doCalculateTaxes
    
    private function _getTaxRates($product)
    {
        return WC_Tax::get_rates($product->get_tax_class());
    } // end _getTaxRates
    
    public function getPriceSuffix($product)
    {
        return $product->get_price_suffix();
    } // end getPriceSuffix
    
    public function updateProductAttributeValues($idProduct, $attrName, $values)
    {
        wp_set_object_terms($idProduct, $values, $attrName);
    } // end updateProductAttributeValues
    
    public function setProductTypeToVariable($idProduct)
    {
        wp_set_object_terms($idProduct, 'variable', 'product_type', false);
    }
    
    public function getAttributeIdent($key)
    {
        return str_replace(" ", "_", strtolower($key));
    }
    
    public function getTaxonomyName($name)
    {
        return wc_sanitize_taxonomy_name($name);
    } // end getTaxonomyName
    
    public function getAttributeNameByKey($key)
    {
        return wc_attribute_taxonomy_name($key);
    } // end getAttributeNameByKey
    
    public function updateProductAttributes($idProduct, $attributes)
    {
        update_post_meta($idProduct, '_product_attributes', $attributes);
    } // end updateProductAttributes
    
    public function isProductPurchasableAndInStock($product)
    {
        return $product->is_purchasable() && $product->is_in_stock();
    } // end isProductPurchasableAndInStock
    
    /**
     * Returns values object for woocommerce product.
     *
     * @param string $sku
     * @return WooCommerceProductValuesObject
     */
    public function loadProductValuesObjectBySKU($sku)
    {
        $existingPostQuery = array(
            'numberposts' => 1,
            'meta_key'    => '_sku',
            'post_type'   => 'product',
            'meta_query'  => array(
                array(
                    'key'     =>'_sku',
                    'value'   => $sku,
                    'compare' => '='
                )
            )
        );
    
        $posts = get_posts($existingPostQuery);
        if (!$posts) {
            return false;
        }
        
        return new WooCommerceProductValuesObject($posts[0]);
    } // end loadProductValuesObjectBySKU
    
    public static function getProductByID($id)
    {
        $factory = new WC_Product_Factory();
        
        $product = $factory->get_product($id);
        print_r($product);
        die("dasdasd77----");
    } // end getProductByID
    
    public function getProductsIDsForRangeWidgetFilter()
    {
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        $postIDsQuery = array(
            'numberposts'         => -1,
            'post_meta'           => $priceKey,
            'post_type'           => array('product', 'product_variation'),
            'post_status'         => 'publish',
            'ignore_sticky_posts' => 1,
            'fields'              => 'ids',
            'meta_query'          => array(
                array(
                    'key'     => '_visibility',
                    'value'   => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            ),
        );
        
        $queryObject = get_queried_object();
        
        if ($this->_hasCategoryByQueryObject($queryObject)) {
            $postIDsQuery['product_cat'] = $queryObject->slug;
        }
        
        $productsIDs = get_posts($postIDsQuery);
        
        $postParentIDsQuery = array(
            'numberposts' => -1,
            'post_meta'   => $priceKey,
            'post_type'   => array('product', 'product_variation'),
            'post_status' => 'publish',
            'post_parent__in' => $productsIDs,
            'fields' => 'ids', 
        );
        
        $parentProductsIDs = get_posts($postParentIDsQuery);
        
        $productsIDs = array_merge($productsIDs, $parentProductsIDs);
        return $productsIDs;
    }

    private function _hasCategoryByQueryObject($queryObject)
    {
        return !empty($queryObject->term_id);
    }
    
    public function getProductsByIDsForWidgetFilter($productIDs)
    {
        $products = array();
        
        if ($productIDs) {
                 
             $postQuery = array(
                'numberposts' => -1,    
                'post_type'   => array('product', 'product_variation'),
                'post_status' => 'publish',
                'include' => $productIDs,
            );
    
            $products = get_posts($postQuery);
        }
        return $products;
    }
    
    public function getProductsForWidgetFilter()
    {
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        $postQuery = array(
            'numberposts' => -1,
            'meta_key'    => $priceKey,
            'post_type'   => array('product', 'product_variation'),
            'post_status' => 'publish',
        );

        $products = get_posts($postQuery);

        return $products;                    
    }

    public function doResetAllProductsStatusToPrivate()
    {
        $params = array(
            'post_type' => 'product',
            'post_status' => 'private'
        );

        $privateProducts = WordpressFacade::createQueryInstance($params);

        if ($privateProducts->have_posts()) {
            $this->_doMakeAllPrivateProductsPublic($privateProducts);
            return true;
        }

        return false;
    } // end doResetAllProductsStatusToPrivate

    private function _doMakeAllPrivateProductsPublic($products)
    {
        while ($products->have_posts()) {
            $products->the_post();
            $productStatus['post_status'] = 'publish';
            $productStatus['ID'] = get_the_ID();

            wp_update_post($productStatus);
        }

        return true;
    } // end _doMakeAllPrivateProductsPublic
    
    public function getPricesFromVariationProduct($product)
    {
        return $this->_adapter->getPricesFromVariationProduct($product);
    } // end getPricesFromVariationProduct
    
    public function getType($product)
    {
        return $this->_adapter->getType($product);
    }
    
    public function getVariationID($product)
    {
        return $this->_adapter->getVariationID($product);
    }
    
    public function isPostParent($product)
    {
        return $this->_adapter->isPostParent($product);
    }
    
    public function getPostParent($product)
    {
        return $this->_adapter->getPostParent($product);
    }
    
    public function _isChildProduct($product)
    {
        return $this->_adapter->_isChildProduct($product);
    }
    
    public function getParentID($product)
    {
        return $this->_adapter->getParentID($product);
    }
    
    public function getID($product)
    {
        return $this->_adapter->getID($product);
    }
    
    public function getPriceExcludingTax($product, $quantity = '', $price = '')
    {
        $args = array(
            'qty'   => $quantity,
            'price' => $price
        );
        
        return $this->_adapter->getPriceExcludingTax($product, $args);
    }
    
    public function getPriceIncludingTax($product, $quantity = '', $price = '')
    {
         $args = array(
            'qty'   => $quantity,
            'price' => $price
        );
        
        return $this->_adapter->getPriceIncludingTax($product, $args);
    }
    
    public function getHook($hook)
    {
        return $this->_adapter->getHook($hook);
    }
    
    public function _isOldVersion()
    {
        $wooVersion = $this->_getWooVersionNumber();
        
        return version_compare($wooVersion, '3.0.0', '<');
    }
    
    public function _getWooVersionNumber()
    {
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH.'wp-admin/includes/plugin.php');    
        }
        
        $pluginFolder = get_plugins('/'.'woocommerce');
        $pluginFile = 'woocommerce.php';
        
        if (isset($pluginFolder[$pluginFile]['Version'])) {
            return $pluginFolder[$pluginFile]['Version'];
        }
        
        return false;    
    }
}
