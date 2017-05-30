<?php

require_once dirname(__FILE__).'/ImportWooProductValidator.php';

class WooProductsSkuManager
{
    private $_engine;
    private $_mapingOptions;
    private $_columnsToCaptions = array();
    private $_parentProductValuesObject;
    
    public $config;
    public $languageDomain;
    
    protected $rowIndex;
    
    public $newPostId;
    public $newPost = array();
    public $newPostDefaults = array();
    public $newPostMeta = array();
    public $newPostMetaDefaults = array();
    public $newPostTerms = array();
    public $newPostCustomFields = array();
    public $newPostCustomFieldCount = 0;
    public $newPostImageUrls = array();
    public $newPostImagePaths = array();
    public $newPostErrors = array();
    public $newPostMessages = array();
    public $newPricesByUserRole = array();
    public $exsistProduct = null;
    public $uploadImagesDir = '';

    public function __construct($config, $engine, $languageDomain)
    {
        $this->_engine = $engine;
        $this->config = $config;
        
        $this->languageDomain = $languageDomain;
        
        if (!empty($this->config['mapTo'])) {
            $this->_columnsToCaptions = array_combine(
                $this->config['mapTo'],
                $this->config['custom_field_name']
            );
        }
    } // end __construct
    
    protected function onInitDefaultPostValues()
    {
        $this->newPostId = null;
        $this->newPost = array();
        $this->newPostDefaults = $this->_getDefaultPostData();
        $this->newPostMeta = array();
        $this->newPostMetaDefaults = $this->_getDefaultPostMetaData();
        $this->newPostTerms = array();
        $this->newPostCustomFields = array();
        $this->newPostCustomFieldCount = 0;
        $this->newPostImageUrls = array();
        $this->newPostImagePaths = array();
        $this->newPostErrors = array();
        $this->newPostMessages = array();
        $this->newPricesByUserRole = array();
        $this->uploadImagesDir = wp_upload_dir();
        
        $this->_parentProductValuesObject = false;

        $this->mapingOptions = $this->_engine->mapingOptions->get();
        $this->mapingOptions = $this->_deleteMappingOptionsGroup();        
    } // end onInitDefaultPostValues
    
    protected function setValuesThatRequireValidation(
        $mapingActions, $col, $key
    )
    {
        $mapTo = $this->config['mapTo'][$key];
        
        if ($this->_isNotImportColum($mapTo) || !$col) {
            return false;
        }

        if (!$this->_isValidateValue($col, $mapTo)) {
            return false;
        }
        
        // FIXME: 
        foreach ($mapingActions as $ident => $item) {
            if (in_array($mapTo, $item)) {
                $methodName = $ident;
            } else if (strpos($mapTo, '_festi_price')) {
                $methodName = 'setRolePriceValue';
            } else {
                continue;
            }

            if (
                $this->_isValueRequireDecimalSeparatorValidation($methodName) &&
                $this->_hasIncorrectDecimalSeparator($col)
            ) {
                $message = __(
                    "Incorrect decimal separator was provided for {$col}",
                    $this->languageDomain
                );
                $this->newPostErrors[] = $message;
                
                continue;
            }
            
            $method = array($this->_engine->mapingOptions, $methodName);
            call_user_func_array(
                $method,
                array($this, $mapTo, $col, $key)
            );
        }
    } // end setValuesThatRequireValidation
    
    private function _isValueRequireDecimalSeparatorValidation($methodName)
    {
        $floatMethods = array('setRolePriceValue', 'setFloatPostMetaFields');
        
        return in_array($methodName, $floatMethods);
    } // end _isValueRequireDecimalSeparatorValidation
    
    private function _hasIncorrectDecimalSeparator($value)
    {
        $separator = $this->config['decimalSeparator'];
        
        return $this->_isSeparatorNotComma($value, $separator) ||
               $this->_isSeparatorNotDot($value, $separator);
    } // end _hasIncorrectDecimalSeparator

    private function _isSeparatorNotComma($value, $separator)
    {
        return $this->_isValueSeparatedByComma($value) && $separator !== ",";
    } // end _isSeparatorNotComma

    private function _isSeparatorNotDot($value, $separator)
    {
        return $this->_isValueSeparatedByDot($value) && $separator !== ".";
    } // end _isSeparatorNotDot

    private function _isValueSeparatedByComma($value)
    {
        return (bool) preg_match("/^[0-9]+,[0-9]+$/", $value);
    } // end _isValueSeparatedByComma

    private function _isValueSeparatedByDot($value)
    {
        return (bool) preg_match("/^[0-9]+\.[0-9]+$/", $value);
    } // end _isValueSeparatedByDot
    
    public function start($row, $rowIndex) 
    {
        $this->rowIndex = $rowIndex;
        
        $this->onInitDefaultPostValues();
        
        $mapingActions = $this->_engine->mapingOptions->getImportMapingActions(
            $this
        );
        
        foreach ($row as $key => $col) {
            $this->setValuesThatRequireValidation($mapingActions, $col, $key);
        }
        
        $this->exsistProduct = $this->_getExsistProduct();

        if ($this->_isUpdatePriceEnabled()) {
            $this->_doUpdatePriceForCurrentProduct();
        }

        $this->newPostMeta['_manage_stock'] = $this->setManageStockForProduct();
        $this->newPostMeta['_stock_status'] = $this->setStockStatusForProduct();
        
        if ($this->_isUpdateUserRolePriceEnabled()) {
            $this->_doUpdateUserRolePriceForCurrentProduct();
        }
        
        // FIXME: Move to ImportWooProductValidator
        if (!$this->_hasValidateProductNameInData() && !$this->exsistProduct) {
            $this->newPostErrors[] = __(
                'Skipped import of product without a name',
                $this->languageDomain
            );
            
            return $this->getImportReport();
        }
        
        // FIXME: Strip params and combine components
        $validator = new ImportWooProductValidator(
            $this->newPost, 
            $this->newPostMeta,
            $this->exsistProduct,
            $this->_columnsToCaptions,
            $this->_engine
        );
        
        $validator->exec();
        
        $errors = $validator->getErrors();
        if ($errors) {
            $this->newPostErrors = $errors;
            return $this->getImportReport();
        }
        
        try {
            if ($this->exsistProduct) {
                $this->updatePost();
            } else {
                $this->insertPost();
            }
            
            if ($this->_isValidateNewPostId()) {
                $this->updateProduct($key);
            }
            
            if ($this->_parentProductValuesObject) {
                $this->_doSyncVariationProduct();
            }
        } catch (Exception $exp) {
            $this->newPostErrors[] = $exp->getMessage();
        }
        
        return $this->getImportReport();   
    } //end  updateProduct
    
    private function _isUpdateUserRolePriceEnabled()
    {
        return (bool) $this->newPricesByUserRole;
    } // end _isUpdateUserRolePriceEnabled
    
    private function _doUpdateUserRolePriceForCurrentProduct()
    {
        $userRolePrices =  $this->getPreparedUserRolePricesForProduct();
        $this->newPostMeta[PRICE_BY_ROLE_PRICE_META_KEY] = $userRolePrices;
    } // end _doUpdateUserRolePriceForCurrentProduct
    
    private function _doSyncVariationProduct()
    {
        $parentValuesObject = $this->_getParentProductValuesObject();
        
        $facade = WooCommerceFacade::getInstance();
        
        // Insert the attributes (I will be using for variations)
        $attributes = array();
        $attributesValues = array();

        foreach ($this->newPostCustomFields as $attrName => $attrOption) {
            $option = $this->config['attributes'][$attrOption['name']];
            $attrKey = $facade->getAttributeNameByKey($option['ident']);
          
            $attributes[$attrKey] = $this->_getProductAttribute($option);   
            $attributesValues[$attrKey] = $facade->getProductAttributeValues(
                $parentValuesObject->getID(),
                $attrKey
            );
            
            if ($option['is_variation']) {
                update_post_meta(
                    $this->newPostId, 
                    'attribute_'.$attrKey,
                    sanitize_title($attrOption['value'])
                );
            }
            
            $attributesValues[$attrKey][] = $attrOption['value'];
        }
        
        if ($attributes) {
            $facade->updateProductAttributes(
                $parentValuesObject->getID(),
                $attributes
            );
        }
        
       foreach ($attributesValues as $attrName => $attrValues) {
            $attrValues = array_unique($attrValues);
            $facade->updateProductAttributeValues(
                $parentValuesObject->getID(),
                $attrName,
                $attrValues
            );
        }
        
        $facade->setProductTypeToVariable($parentValuesObject->getID());
        
        WC_Product_Variable::sync($parentValuesObject->getID());
    } // end _doSyncVariationProduc

    private function _getParentProductValuesObject()
    {
        return $this->_parentProductValuesObject;
    } // end _getParentProductValuesObject
    
    protected function updateProduct($key)
    {
        $this->updatePostMeta();

        $this->updateProductAttributes();

        $this->updatePostTerms();

        $this->updateProductImagesByUrl();
        
        $this->updateProductAtachmentData($key);
    } // end updateProduct
    
    private function _isEnabledSkipDuplicatesImagesOption($columnIndex)
    {
        $optionKey = 'product_image_skip_duplicates';
        $skipDuplicatesOptionsByColumnIndexes = $this->config[$optionKey];
        
        $isSkipDuplicateImages = false;
        
        if ($this->_isExistsColumnIndex($columnIndex)) {
            $isSkipDuplicateImages = (bool) 
                $skipDuplicatesOptionsByColumnIndexes[$columnIndex];
        }

        return $this->exsistProduct !== null && $isSkipDuplicateImages;
    } // end _isEnabledSkipDuplicatesImagesOption
    
    private function _isExistsColumnIndex($columnIndex)
    {
        $optionKey = 'product_image_skip_duplicates';
        $this->config[$optionKey];
        
        return array_key_exists($columnIndex, $this->config[$optionKey]);
    }
    
    private function _isDuplicateImage($source, $numCol)
    {
        if (!$this->_isEnabledSkipDuplicatesImagesOption($numCol)) {
            return false;
        }
        
        $existingAttachmentQuery = array(
            'numberposts' => 1,
            'meta_key' => '_import_source',
            'post_status' => 'inherit',
            'post_parent' => $this->exsistProduct->ID,
            'meta_query' => array(
                array(
                    'key'=>'_import_source',
                    'value'=> $source,
                    'compare' => '='
                )
            ),
            'post_type' => 'attachment'
        );
        
        $existingAttachments = get_posts($existingAttachmentQuery);
        
        if ($this->_hasImageInProductData($existingAttachments)) {
            $message = __(
                'Skipping import of duplicate image %s.',
                $this->languageDomain
            );
            
            $this->newPostMessages[] = sprintf(
                $message,
                $source
            );
            return true;
        }
        
        return false;
    } // end _isDuplicateImage
    
    private function _isExistsLocalImage($path)
    {
        if (!file_exists($path)) {
            $message = __(
                'Couldn\'t find local file %s.',
                $this->languageDomain
            );
                
            $this->newPostErrors[] = sprintf($message, $path);
            return false;
        }
        
        return true;
    } // end _isExistsLocalImage
    
    protected function doInsertAtachmentImage($path)
    {
        $destUrl = str_ireplace(ABSPATH, home_url('/'), $path);
        
        $pathParts = pathinfo($path);

        $wpFiletype = wp_check_filetype($path);
        
        $postTitle = preg_replace(
            '/\.[^.]+$/',
            '',
            $pathParts['filename']
        );
        
        $idAttachment = $this->_getExistsAttachmentID(
            $path,
            $wpFiletype['type']
        );
        
        if ($idAttachment) {
            return $idAttachment;
        }
        
        $attachment = array(
            'guid' => $destUrl,
            'post_mime_type' => $wpFiletype['type'],
            'post_title' => $postTitle,
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $facade = WordpressFacade::getInstance();
        
        $idAttachment = $facade->addAttachment(
            $this->newPostId,
            $attachment,
            $path
        );
        
        return $idAttachment;
    } // end doInsertAtachmentImage
    
    private function _getExistsAttachmentID($path, $fileType)
    {   
        $facade = WordpressFacade::getInstance();
        $idPostParent = $this->_getCurrentProductID();
        
        $attachments = $facade->getAttachmentsByPostID(
            $idPostParent,
            $fileType
        );
        
        if (is_array($attachments)) {
            foreach ($attachments as $post) {
                $postPath = $facade->getAbsolutePath($post->guid);
                
                if ($this->_isFileEquals($path, $postPath)) {
                    return $post->ID;
                }              
            } 
        }
        
        return false;
    }
    
    private function _getCurrentProductID()
    {
        return $this->exsistProduct->ID;
    }
    
    private function _isFileEquals($pathOne, $pathTwo)
    {
        $hashOne = sha1_file($pathOne);
        $hashTwo = sha1_file($pathTwo);
        
        return $hashOne == $hashTwo;
    }
    
    protected function updateAtachmentMetaData($attachmentId, $path)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
            
        $attachData = wp_generate_attachment_metadata(
            $attachmentId,
            $path
        );
        
        wp_update_attachment_metadata($attachmentId, $attachData);
    } // end updateAtachmentMetaData
    
    protected function updateProductImagePostMeta($attachmentId, $source)
    {
        $result = add_post_meta(
            $attachmentId,
            '_import_source',
            $source,
            true
        );
                
        if (!$result) {
            update_post_meta(
                $attachmentId,
                '_import_source',
                $source
            ); 
        }
    } // end updateProductImagePostMeta
    
    private function _isEnabledFirstImageFeaturedOption($columnIndex)
    {
        $setFeatureOptions = $this->config['product_image_set_featured'];
        
        if (array_key_exists($columnIndex, $setFeatureOptions)) {
            
            return (bool) $setFeatureOptions[$columnIndex];
        }
        
        return false;
    } // end _isEnabledFirstImageFeaturedOption
    
    private function _isFirstImage($imageIndex)
    {
        return $imageIndex == 0;
    } // end _isFirstImage

    public function updateProductAtachmentData($numCol)
    {
        $imageGalleryIds = array();
        
        foreach ($this->newPostImagePaths as $imageIndex => $destPathInfo) {
            $path = $destPathInfo['path'];
            $source = $destPathInfo['source'];
            
            if ($this->_isDuplicateImage($source, $numCol)) {
                return false;
            }

            if (!$this->_isExistsLocalImage($path)) {
                return false;
            }
            
            $attachmentId = $this->doInsertAtachmentImage($path);
            
            $this->updateAtachmentMetaData($attachmentId, $path);

            $this->updateProductImagePostMeta($attachmentId, $source);
            
            $setFeatured = $this->_isEnabledFirstImageFeaturedOption($numCol);
        
            if (!$this->_isFirstImage($imageIndex) || !$setFeatured) {
                $imageGalleryIds[] = $attachmentId;
                continue;
            }
            
            update_post_meta($this->newPostId, '_thumbnail_id', $attachmentId);
        }
        
        $this->updateProductImageGallery($imageGalleryIds);
        
    } // end updateProductAtachmentData
    
    public function updateProductImageGallery($imageGalleryIds)
    {
        if ($this->_hasImageGalleryIds($imageGalleryIds)) {
            update_post_meta(
                $this->newPostId,
                '_product_image_gallery',
                implode(',', $imageGalleryIds)
            );
        }
    } // end updateProductImageGallery
    
    private function _hasImageGalleryIds($imageGalleryIds)
    {
        return count($imageGalleryIds) > 0;
    } // end _hasImageGalleryIds
    
    private function _hasImageInProductData($existingAttachments)
    {
        return is_array($existingAttachments)
               && sizeof($existingAttachments) > 0;
    } // end _hasImageInProductData
    
    public function updateProductImagesByUrl()
    {
        if (!$this->newPostImageUrls) {
            return false;
        }
        
        foreach ($this->newPostImageUrls as $imageIndex => $imageUrl) {
            $imageUrl = str_replace(' ', '%20', trim($imageUrl));
            $parsedUrl = parse_url($imageUrl);
            $pathinfo = pathinfo($parsedUrl['path']);
            $imageExt = strtolower($pathinfo['extension']);
            
            if (!$this->_isAllowedImageExtension($imageExt, $imageUrl)) {
                return false;
            }
            
            $destFilename = wp_unique_filename(
                $this->uploadImagesDir['path'],
                $pathinfo['basename']
            );
            
            $destPath = $this->uploadImagesDir['path'].'/'.$destFilename;
            
            $this->copyImageFromUrl($imageUrl, $destPath);
            
            if (!file_exists($destPath)) {
                $message = __(
                    'Couldn\'t download file %s.', 
                    $this->languageDomain
                );
                
                $this->newPostErrors[] = sprintf($message, $imageUrl);
                return false;
            }
    
            $this->newPostImagePaths[] = array(
                'path' => $destPath,
                'source' => $imageUrl
            );
        }
    } // end updateProductImagesByUrl
    
    protected function copyImageFromUrl($imageUrl, $destPath)
    {
        if ($this->_isAllowUrlFopen()) {
            $result = @copy($imageUrl, $destPath);
            
            if (!$result) {
                $message = __(
                    'Error Encountered while attempting to download %s',
                    $this->languageDomain
                );
                $this->newPostErrors[] = sprintf($message, $imageUrl);
            }
        } elseif (function_exists('curl_init')) {
            $this->_copyImagesWithCurlFunction($imageUrl, $destPath);
        }
    } // end copyImageFromUrl
    
    private function _copyImagesWithCurlFunction($imageUrl, $destPath)
    {
        $ch = curl_init($imageUrl);
        $fp = fopen($destPath, "wb");

        $options = array(
            CURLOPT_FILE => $fp,
            CURLOPT_HEADER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_TIMEOUT => 60
        );

        curl_setopt_array($ch, $options);
        curl_exec($ch);
        
        $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $httpStatus = intval($result);
        
        curl_close($ch);
        
        fclose($fp);

        if ($httpStatus == 200) {
            return true;    
        }
        
        unlink($destPath);
        
        $message = __(
            'HTTP status %s encountered while attempting to download %s',
            'woo-product-importer'
        );
        
        $this->newPostErrors[] = sprintf($message, $httpStatus, $imageUrl);
    } // end _copyImagesWithCurlFunction
    
    private function _isAllowUrlFopen()
    {
        return ini_get('allow_url_fopen');
    } // end _isAllowUrlFopen
    private function _isAllowedImageExtension($imageExt, $imageUrl)
    {
        $allowedExtensions = array('jpg', 'jpeg', 'gif', 'png');
        
        if (in_array($imageExt, $allowedExtensions)) {
            return true;
        }
        
        $message = __(
            'A valid file extension wasn\'t found in %s. Extension '.
            'found was %s. Allowed extensions are: %s.', 
            $this->languageDomain
        );
        
        $this->newPostErrors[] = sprintf(
            $message,
            $imageUrl,
            $imageExt,
            implode(',', $allowedExtensions)
        );

        return false;
    } // end _isAllowedImageExtension
    
    public function updatePostTerms()
    {
        foreach ($this->newPostTerms as $tax => $termIds) {
            wp_set_object_terms($this->newPostId, $termIds, $tax);
        }
    } // end updatePostTerms
    
    
    public function updateProductAttributes()
    { 
        if ($this->exsistProduct !== null) {
            $this->_loadProductExistsAttributes();
        }
        
        $facade = WooCommerceFacade::getInstance();
        
        $attributes = array();
        $attributesValues = array();

        foreach ($this->newPostCustomFields as $attrName => $attrOption) {
            $option = $this->config['attributes'][$attrOption['name']];
            $attrKey = $facade->getAttributeNameByKey($option['ident']);
          
            $attributes[$attrKey] = $this->_getProductAttribute($option);
            
            $attributesValues[$attrKey] = $facade->getProductAttributeValues(
                $this->newPostId,
                $attrKey
            );
            
            $attributesValues[$attrKey][] = $attrOption['value'];
        }
        
        if ($attributes) {
            $facade->updateProductAttributes($this->newPostId, $attributes);
        }
        
        $this->_doUpdateProductAttributesValues($attributesValues);
        
        return true;
    } // end updateProductAttributes
    
    private function _getProductAttribute($option)
    {
        $facade = WooCommerceFacade::getInstance();
        
        $isVisible = $this->_isProductAttributeVisible($option['index']);
        $attrKey = $facade->getAttributeNameByKey($option['ident']);

        $productAttribute = array(
            'name'         => $attrKey,
            'value'        => '',
            'is_visible'   => $isVisible,
            'is_variation' => $option['is_variation'],
            'is_taxonomy'  => '1',
            'position'     => 0
        );
        
        return $productAttribute;
    } // end _getProductAttribute

    private function _doUpdateProductAttributesValues($attributesValues)
    {
        $facade = WooCommerceFacade::getInstance();

        foreach ($attributesValues as $attrName => $attrValues) {
            $attrValues = array_unique($attrValues);
            $facade->updateProductAttributeValues(
                $this->newPostId,
                $attrName,
                $attrValues
            );
        }

        return true;
    } // end _doUpdateProductAttributesValues
    
    private function _isProductAttributeVisible($fieldIndex)
    {
        $visibleCustomFields = $this->config['custom_field_visible'];

        if (array_key_exists($fieldIndex, $visibleCustomFields)) {
            return true;
        }
        
        return false;
    } // end _isProductAttributeVisible
    
    private function _loadProductExistsAttributes()
    {
        $existingProductAttributes = get_post_meta(
            $this->newPostId,
            '_product_attributes',
            true
        );
        
        if (!is_array($existingProductAttributes)) {
            return false;
        }
        
        $maxPosition = 0;
        foreach ($existingProductAttributes as $fieldSlug => $fieldData) {
            $position = intval($fieldData['position']);
            $maxPosition = max($position, $maxPosition);
        }
        
        foreach ($this->newPostCustomFields as $fieldSlug => $fieldData) {
            $result = $this->_hasValueInDataArray(
                $fieldSlug,
                $existingProductAttributes
            );
            
            if ($result) {
                continue;
            }
            
            $this->newPostCustomFields[$fieldSlug]['position'] = ++$maxPosition;
        }
        
        $this->newPostCustomFields = array_merge(
            $existingProductAttributes,
            $this->newPostCustomFields
        );
        
        
    } // end _loadProductExistsAttributes
    
    public function updatePostMeta()
    {
        foreach ($this->newPostMeta as $key => $value) {
            $result = add_post_meta($this->newPostId, $key, $value, true);

            if (!$result) {
               update_post_meta($this->newPostId, $key, $value); 
            }
        }
    } // end updatePostMeta
    
    private function _isValidateNewPostId()
    {
        if (is_wp_error($this->newPostId)) {
            $message = __(
                'Couldn\'t insert product with name %s.',
                $this->languageDomain
            );
            
            $this->newPostErrors = sprintf(
                $message,
                $this->newPost['post_title']
            );
            return false;
        }
        
        if ($this->newPostId == 0) {
            $message = __(
                'Couldn\'t update product with ID %s.',
                $this->languageDomain
            );
            
            $this->newPostErrors = sprintf(
                $message,
                $this->newPost['ID']
            );
            return false;
        }
        
        return true;        
    } // _isValidateNewPostId
    
    public function insertPost()
    {
        $this->newPost = array_merge(
            $this->newPostDefaults,
            $this->newPost
        );
        
        $this->newPostMeta = array_merge(
            $this->newPostMetaDefaults,
            $this->newPostMeta
        );

        $this->_onPrepareProductValues();
        
        $this->_createSimpleProduct();
        
        $this->newPostMessages[] = sprintf(
            __('Insert product with ID %s.', $this->languageDomain),
            $this->newPostId
        );
    } // end insertPost
    
    private function _createSimpleProduct()
    {
        $this->newPostId = wp_insert_post($this->newPost, true);
        
    } // end _createSimpleProduct
    
    
    public function updatePost()
    {
        $this->newPostMessages[] = sprintf(
            __('Updating product with ID %s.', $this->languageDomain),
            $this->exsistProduct->ID
        );
    
        $this->newPost['ID'] = $this->exsistProduct->ID;
        
        $this->_onPrepareProductValues();
        
        $this->_updateSimpleProduct();
    } // end updatePost
    
    private function _updateSimpleProduct()
    {
        $this->newPostId = wp_update_post($this->newPost);
    } // end _updateSimpleProduct
    
    /**
     * This method called every time before product values insert or update.
     */
    private function _onPrepareProductValues()
    {
        if ($this->_isVariationProduct()) {
            $this->_parentProductValuesObject = $this->_getParentProduct();
            
            $idParent = $this->_parentProductValuesObject->getID();
            $this->newPost['post_parent'] = $idParent;
            $this->newPost['post_title'] = 'Product #'.$idParent.' Variation';
            $this->newPost['post_type'] = 'product_variation';
        }
        
    } // end _onPrepareProductValues
    
    /**
     * Returns parent product for current variation product
     * @return WooCommerceProductValuesObject
     * @throws ImportWooProductException
     */
    private function _getParentProduct()
    {
        if ($this->_isEmptyParentSku()) {
            throw new ImportWooProductException(
                "Undefined _parent_sku for variation product."
            );
        }
        $key = WooMappingImportOptions::FIELD_PARENT_SKU;
        $parentSKU = $this->newPostMeta[$key];
        
        $facade = WooCommerceFacade::getInstance();
        
        $parentProduct = $facade->loadProductValuesObjectBySKU($parentSKU);
        
        if (!$parentProduct) {
            throw new ImportWooProductException(
                "Not found parent for variation product.",
                ImportWooProductException::ERROR_CODE_NOT_FOUND_PARENT
            );
        }
        
        return $parentProduct;
    } // end _getParentProduct
    
    private function _isEmptyParentSku()
    {
        return empty(
            $this->newPostMeta[WooMappingImportOptions::FIELD_PARENT_SKU]
        );
    } // end _isEmptyParentSku
    
    private function _isVariationProduct()
    {
        return array_key_exists(
            WooMappingImportOptions::FIELD_PARENT_SKU, 
            $this->newPostMeta
        );
    } // end _isVariationProduct
    
    public function getImportReport()
    {
        $reportData  = array(
            'post_id' => '',
            'name' => '',
            'sku' => '',
            'has_errors' => false,
            'errors' => $this->newPostErrors,
            'has_messages' => false,
            'messages' => $this->newPostMessages
        );
        
        if ($this->newPostId) {
            $reportData['post_id'] = $this->newPostId;
        }
        
        if (isset($this->newPost['post_title'])) {
            $reportData['name'] = $this->newPost['post_title'];    
        }

        if (!empty($this->newPostMeta['_sku'])) {
            $reportData['sku'] = $this->newPostMeta['_sku'];    
        }
        
        if (sizeof($this->newPostErrors) > 0) {
            $reportData['success'] = false;
            $reportData['has_errors'] = true;    
        } else {
            $reportData['success'] = true;
        }
        
        if (sizeof($this->newPostMessages) > 0) {
            $reportData['has_messages'] = true;    
        }
        
        return $reportData;
    } // end getImportReport
    
    private function _hasValidateProductNameInData()
    {
        return array_key_exists('post_title', $this->newPost)
               && strlen($this->newPost['post_title']) > 0;
    } // end _hasValidateProductNameInData
    
    public function getPreparedUserRolePricesForProduct()
    {
        $festiValue = array();
        
        if ($this->exsistProduct !== null) {
            
            $productId = $this->exsistProduct->ID;
            
            $festiValue = get_post_meta(
                $productId,
                PRICE_BY_ROLE_PRICE_META_KEY,
                true
            );

            if (!is_null($festiValue) && !is_array($festiValue)) {
                $festiValue = json_decode($festiValue, true);
            }
        }
        
        if (!is_array($festiValue)) {
            $festiValue = array();
        }
        
        $this->newPricesByUserRole = array_merge(
            $festiValue,
            $this->newPricesByUserRole
        );
        
        return json_encode($this->newPricesByUserRole);
    } // end getPreparedUserRolePricesForProduct
    
    private function _getExsistProduct()
    {
        $var = $this->newPostMeta;

        if (
            !$this->_hasValueInDataArray('_sku', $var) || 
            empty($var['_sku'])
        ) {
            return null;
        }
            
        $postType = 'product';
        if ($this->_isVariationProduct()) {
            $postType = 'product_variation';
        }
        
        $existingPostQuery = array(
            'numberposts' => 1,
            'meta_key'    => '_sku',
            'post_type'   => $postType,
            'meta_query'  => array(
                array(
                    'key'     =>'_sku',
                    'value'   => $this->newPostMeta['_sku'],
                    'compare' => '='
                )
            )
        );
        
        $existingPosts = get_posts($existingPostQuery);
        
        if (!is_array($existingPosts) || sizeof($existingPosts) <= 0) {
            return null;
        }
        
        return array_shift($existingPosts);
    } // end _getExsistProduct
    
    public function setStockStatusForProduct()
    {
        $var = $this->newPostMeta;
        
        if ($this->_hasValueInDataArray('_stock', $var)) {
            return (intval($var['_stock']) > 0) ? 'instock' : 'outofstock';
        }
        
        if ($this->_hasValueInDataArray('_stock_status', $var)) {
            $result = $var['_stock_status'] == 'instock';    
            return ($result) ? 'instock' : 'outofstock';
        }
        
        if ($this->exsistProduct) {
            return $this->getStockOptionOfExsistingProduct();
        }
        
        return 'instock';
        
    } // end setStockStatusForProduct
    
    public function getStockOptionOfExsistingProduct()
    {
        $idProduct = $this->exsistProduct->ID;
        $optionName = '_stock_status';
        $option = get_post_meta($idProduct, $optionName);
        return $option[0];
    }
    
    public function setManageStockForProduct()
    {
        $var = $this->newPostMeta;

        if (!$this->_hasValueInDataArray('_manage_stock', $var)) {
            return false;
        }
   
        $result = $this->_hasValueInDataArray('_stock', $var);

        return ($result) ? 'yes' : 'no';
    }// end setManageStockForProduct

    private function _isUpdatePriceEnabled()
    {
        return $this->_isSalePriceExists() || $this->_isRegularPriceExists();
    } // end _isUpdatePriceEnabled

    /**
     * @throws ImportWooProductException
     */
    private function _doUpdatePriceForCurrentProduct()
    {
        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        $regularPriceKey = WooCommerceProductValuesObject::REGULAR_PRICE_KEY;

        if ($this->_isSalePriceExists()) {
            $this->_setPriceForProduct(
                $this->newPostMeta[$salePriceKey]
            );
        } else if ($this->_isRegularPriceExists()) {
            $this->_setPriceForProduct(
                $this->newPostMeta[$regularPriceKey]
            );
        } else {
            throw new ImportWooProductException(
                "Undefined error with product price."
            );
        }
    } // end _doUpdatePriceForCurrentProduct

    private function _isSalePriceExists()
    {
        return $this->_hasValueInDataArray(
            WooCommerceProductValuesObject::SALE_PRICE_KEY,
            $this->newPostMeta
        );
    } // end _isSalePriceExists

    private function _isRegularPriceExists()
    {
        return $this->_hasValueInDataArray(
            WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
            $this->newPostMeta
        );
    } // end _isRegularPriceExists

    private function _setPriceForProduct($price)
    {
        $this->newPostMeta[WooCommerceProductValuesObject::PRICE_KEY] = $price;
    } // end setPriceForProduct
    
    private function _hasValueInDataArray($value, $data) 
    {
        return array_key_exists($value, $data);
    } // _hasValueInDataArray
    
    private function _deleteMappingOptionsGroup()
    {
        $onlyOptions = array();
        
        foreach ($this->mapingOptions as $item) {
            $onlyOptions = array_merge($onlyOptions, $item['options']);
        }
        
        return $onlyOptions;
    } // end _deleteMappingOptionsGroup
    
    // FIXME:
    private function _isValidateValue($value, $mapTo)
    {
        if (!array_key_exists($mapTo, $this->mapingOptions)) {
            return true;
        }
        
        $result = array_key_exists(
            'validationValues',
            $this->mapingOptions[$mapTo]
        );
        
        if (!$result) {
            return true;
        }
        
        $methodName = $this->mapingOptions[$mapTo]['validationValues'];

        $method = array($this->_engine->mapingOptions, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }

        $values = call_user_func_array($method, array());
        
        if (!$values) {
            throw new Exception($methodName."Not return Values");
        }

        return in_array($value, $values);
    } // end _isValidateValue
    
    private function _isNotImportColum($key)
    {
        return $key == 'do_not_import';
    } // end _isNotImportColum
    
    private function _getDefaultPostData()
    {
        $postData = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'post_title' => '',
            'post_content' => '',
            'menu_order' => 0,
        );
        
        return $postData;
    } // end _getDefaultPostData
    
    private function _getDefaultPostMetaData()
    {
        $postMetaData = array(
            '_visibility' => 'visible',
            '_featured' => 'no',
            '_weight' => 0,
            '_length' => 0,
            '_width' => 0,
            '_height' => 0,
            '_sku' => '',
            '_stock' => '',
            WooCommerceProductValuesObject::SALE_PRICE_KEY => '',
            '_sale_price_dates_from' => '',
            '_sale_price_dates_to' => '',
            '_tax_status' => 'taxable',
            '_tax_class' => '',
            '_purchase_note' => '',
            '_downloadable' => 'no',
            '_virtual' => 'no',
            '_backorders' => 'no',
            PRICE_BY_ROLE_PRICE_META_KEY => ''
        );
        
        return $postMetaData;
    } // end _getDefaultPostMetaData
}
