<?php

require_once dirname(__FILE__).'/PriceByRoleTestCase.php';

class ImportsTest extends PriceByRoleTestCase
{
    const ATTRIBUTE_FIELD_INDEX = 7;

    public function testImportProductsWithIncorrectStatus()
    {
        $options = array(
            'offset' => 0,
            'isFirstRowHeader' => 1,
            'filePath' => $this->_getCsvFilePath('import-incorrect-status.csv'),
            'mapTo' => array(
                0 => 'post_title',
                1 => '_sku',
                2 => 'do_not_import',
                3 => WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
                4 => 'post_status'
            ),
            'custom_field_name' => array(
                0 => 'Name',
                1 => 'SKU',
                2 => 'Admin Price',
                3 => 'Regular Price',
                4 => 'Status'
            )
        );
        
        $pluginInstance = $this->getBackendPluginInstance();
        
        $report = $pluginInstance->getImportManager()->doProcessing($options);
        
        $countProductsWithIncorrectStatuses = 4;
        $countProductsWithErrors = 0;
        foreach ($report as $item) {
            if ($item['has_errors']) {
                $countProductsWithErrors++;
            }
        }
        
        $this->assertTrue(
            $countProductsWithErrors == $countProductsWithIncorrectStatuses
        );
    } // end testImportProductsWithWronStatus

    /**
     * @ticket 1888 http://localhost.in.ua/issues/1888
     */
    public function testImportWithVariations()
    {
        $fileName = 'import-variations-parent-sku.csv';
        $options = array(
            'offset' => 0,
            'isFirstRowHeader' => 1,
            'filePath' => $this->_getCsvFilePath($fileName),
            'csvSeparator' => ',',
            'categorySeparator' => '/',
            'decimalSeparator' => ',',
            'mapTo' => array(
                0 => 'post_title',
                1 => '_sku',
                2 => '_parent_sku',
                3 => WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
                4 => 'custom_field',
                5 => 'post_content'
            ),
            'custom_field_name' => array(
                0 => 'Product Title',
                1 => 'SKU',
                2 => 'Parent SKU',
                3 => 'Price',
                4 => 'Color',
                5 => 'Description'
            )
        );
        
        $pluginInstance = $this->getBackendPluginInstance();
        
        $report = $pluginInstance->getImportManager()->doProcessing($options);
        
        foreach ($report as $item) {
            $this->assertTrue($item['success'] == 1);
        }
        
        $facade = WooCommerceFacade::getInstance();
        $sku = '999-X';
        $productValuesObject = $facade->loadProductValuesObjectBySKU($sku);
        
        $this->assertTrue($productValuesObject->getType() == "variable");
    }
    
    private function _getCsvFilePath($fileName)
    {
        return dirname(__FILE__).DIRECTORY_SEPARATOR.'resources'.
               DIRECTORY_SEPARATOR.$fileName;
    } // end _getCsvFilePath
    
     /**
     * @ticket 3008 http://localhost.in.ua/issues/3008
     */
    public function testImportWithAttributes()
    {
        $fileName = 'import-custom-field-taxonomi.csv';
        $options = array(
            'offset' => 0,
            'isFirstRowHeader' => 1,
            'filePath' => $this->_getCsvFilePath($fileName),
            'csvSeparator' => ',',
            'categorySeparator' => '/',
            'decimalSeparator' => '.',
            'mapTo' => array(
                0 => '_sku',
                1 => '_parent_sku',
                2 => 'post_title',
                3 => WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
                4 => 'subscriber_festi_price',
                5 => 'editor_festi_price',
                6 => 'author_festi_price',
                7 => 'custom_field',
                8 => 'post_content',
                9 => 'product_cat_by_name',
                10 => 'product_tag_by_name'
            ),
            'custom_field_name' => array(
                0 => 'SKU',
                1 => 'Parent SKU',
                2 => 'Product Title',
                3 => 'Regular Price',
                4 => 'Administartor Price',
                5 => 'Editor Price',
                6 => 'Author Price',
                7 => 'Custom Field Attribute',
                8 => 'Description',
                9 => 'Categories',
                10 => 'Tags By Name'
            )
        );
        
        $pluginInstance = $this->getBackendPluginInstance();
        
        $report = $pluginInstance->getImportManager()->doProcessing($options);
        
        foreach ($report as $item) {
            $this->assertTrue($item['success'] == 1);
        }
        
        ob_start();
        $pluginInstance->getImportManager()->displayImportResultPage();
        ob_get_clean();
        
        $attributeTaxonomies = wc_get_attribute_taxonomies();
        $productAttributes = array();
        
        foreach ($attributeTaxonomies as $tax) {
            $productAttributes[$tax->attribute_name] = $tax;                
        }

        $facade = WooCommerceFacade::getInstance();
        $columnNames = $options['custom_field_name'];
        $attributeLabel = $columnNames[ImportsTest::ATTRIBUTE_FIELD_INDEX];
        $attributeSlug = $facade->getTaxonomyName($attributeLabel);

        $this->assertArrayHasKey(
            $attributeSlug,
            $productAttributes
        );

    }

    /**
     * @ticket 3207 http://localhost.in.ua/issues/3207
     */
    public function testImportTwoAttributes()
    {
        $fileName = 'import-variation-product-two-attributes.csv';
        $options = array(
            'offset' => 0,
            'isFirstRowHeader' => 1,
            'filePath' => $this->_getCsvFilePath($fileName),
            'csvSeparator' => ',',
            'categorySeparator' => '/',
            'decimalSeparator' => '.',
            'mapTo' => array(
                0 => 'post_title',
                1 => '_sku',
                2 => '_parent_sku',
                3 => WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
                4 => 'custom_field',
                5 => 'custom_field',
                6 => 'post_content'
            ),
            'custom_field_name' => array(
                0 => 'Product Title',
                1 => 'SKU',
                2 => 'Parent SKU',
                3 => 'Regular Price',
                4 => 'Color',
                5 => 'Size',
                6 => 'Description'
            )
        );

        $pluginInstance = $this->getBackendPluginInstance();

        $report = $pluginInstance->getImportManager()->doProcessing($options);

        foreach ($report as $item) {
            $this->assertTrue($item['success'] == 1);
        }

        ob_start();
        $pluginInstance->getImportManager()->displayImportResultPage();
        ob_get_clean();

        $attributeTaxonomies = wc_get_attribute_taxonomies();
        $productAttributes = array();

        foreach ($attributeTaxonomies as $tax) {
            $productAttributes[] = $tax->attribute_name;
        }
        
        $productAttributes = array_unique($productAttributes);
        
        $this->assertCount(2, $productAttributes);
    } // end testImportTwoAttributes
    
}