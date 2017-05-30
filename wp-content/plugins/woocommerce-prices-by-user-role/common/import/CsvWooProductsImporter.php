<?php

class CsvWooProductsImporter extends FestiObject
{
    private $_action = 'upload';
    private $_engine;
    private $_reader;
    private $_skuManager;
    private $_fileSystem;
    
    // FIXME: Change to private
    public $mapingOptions;
    // FIXME: Change to chunkSize
    public $importLimit = 5;
    
    const DEFAULT_IMPORT_FILE_NAME = 'price_by_user_role_import.csv';
    const IMPORT_OPTIONS_KEY = 'import_config';
    
    public function __construct(
        WooUserRolePricesBackendFestiPlugin $engine
    )
    {
        $this->_engine = $engine;

        if ($this->_hasActionInRequest()) {
            $this->_action = $_GET['action'];
        }

        add_action(
            'wp_ajax_importProductData',
            array($this, 'onAjaxImportChunkOfProductsAction')
        );

        add_action(
            'admin_print_scripts',
            array($this, 'onInitJsAction')
        );
        
        add_action(
            'admin_print_styles',
            array($this, 'onInitCssAction')
        );
        
        $this->onInitCsvReader();
        $this->onInitCsvMappingOptions();
    } // end __construct
    
    public function onAjaxImportChunkOfProductsAction()
    {
        $importConfig = $this->_getOptions();
        $importConfig['offset'] = $_POST['offset'];
        $report = $this->doProcessing($importConfig);

        $vars = array(
            'reportData' => $report
        );

        $content = $this->_engine->fetch(
            'import/report_table_part.phtml',
            $vars
        );
        
        $vars = array(
            'content' => $content,
            'errors'  => $this->_getCountImportProductsWithErrros($report)
        );

        wp_send_json($vars);
        exit();
    } // end onAjaxImportChunkOfProductsAction
    
    private function _getPreparedOptions($options)
    {
        $fields = array(
            'offset'                        => static::FIELD_TYPE_INT,
            'isFirstRowHeader'              => static::FIELD_TYPE_INT,
            'mapTo'                         => static::FIELD_TYPE_ARRAY,
            'custom_field_name'             => static::FIELD_TYPE_ARRAY,
            'custom_field_visible'          => static::FIELD_TYPE_ARRAY,
            'product_image_set_featured'    => static::FIELD_TYPE_ARRAY,
            'product_image_skip_duplicates' => static::FIELD_TYPE_ARRAY,
            'custom_field_variation'        => static::FIELD_TYPE_ARRAY,
            'post_meta_key'                 => static::FIELD_TYPE_ARRAY,
            'rowsCount'                     => static::FIELD_TYPE_INT,
            'filePath' => array(
                'type'     => static::FIELD_TYPE_STRING,
                'required' => true
            ),
            'limit' => array(
                'type'    => static::FIELD_TYPE_INT,
                'default' => 5
            ),
            'csvSeparator' => array(
                'type'    => static::FIELD_TYPE_STRING,
                'default' => ';'
            ),
            'categorySeparator' => array(
                'type'    => static::FIELD_TYPE_STRING,
                'default' => '/'
            ),
            'decimalSeparator' => array(
                'type'    => static::FIELD_TYPE_STRING,
                'default' => ','
            )
        );
        
        $errros = array();
        $options = $this->getPreparedData($options, $fields, $errros);
        
        if ($errros) {
            $this->_throwException(
                "Undefined options: ".join(", ", array_keys($errros))
            );
        }
        
        $woocommerceFacade = WooCommerceFacade::getInstance();
        
        foreach ($options['custom_field_name'] as $index => $columnName) {
            if (array_key_exists($index, $options['mapTo'])) {
                $ident = $options['mapTo'][$index];
            }
            
            $options['columns'][$index] = array();
            $row = &$options['columns'][$index];
            
            $row['name'] = $columnName;
            
            if ($this->_isAttributeColumn($ident)) {
                $row['type'] = 'attribute';
                $row['ident'] = $woocommerceFacade->getAttributeIdent(
                    $columnName
                );
                
                $attributeTaxonomyName = $woocommerceFacade->getTaxonomyName(
                    $columnName
                );

                $options['attributes'][$columnName] = array(
                    'index'        => $index,
                    'name'         => $columnName,
                    'ident'        => $attributeTaxonomyName,
                    'is_visible'   => 
                        !empty($options['visible'][$index]),
                    'is_variation' => 
                        !empty($options['custom_field_variation'][$index])
                );
            } else if ($ident == 'do_not_import') {
                $row['type'] = 'ignore';
            } else {
                $row['type'] = 'option';
                $row['ident'] = $ident;
            }
        }
        
        return $options;
    } // end _getPreparedOptions
    
    private function _isOptionsPrepared($options)
    {
        return array_key_exists('columns', $options);
    } // end _isOptionsPrepared
    
    
    private function _isAttributeColumn($ident)
    {
        return $ident == 'custom_field';
    }
    
    
    /**
     * Execute processing of csv file.
     * 
     * @param array $options 
     * @return array
     */
    public function doProcessing($options)
    {
        $options = $this->_updateOptions($options);
        
        $this->_reader = new CsvReaderComponent(
            $options['filePath'], 
            $options['csvSeparator']
        );
        
        // FIXME:
        $importData = $this->_getLimitImportData($options);
        
        // FIXME: 
        $this->onInitSkuManager();
        
        // FIXME: _languageDomain
        $this->_skuManager = new WooProductsSkuManager(
            $options,
            $this,
            $this->_engine->_languageDomain
        );
        
        $report = $this->_doProcessImportData($importData);
        WooCommerceCacheHelper::doRefreshPriceCache();
        
        return $report;
    } // end doProcessing
    
    // FIXME:
    private function _getCountImportProductsWithErrros($report)
    {
        $errorsCount = 0;
        foreach ($report as $row) {
            if ($row['has_errors']) {
                $errorsCount++;
            }
        }
        
        return $errorsCount;
    } // end _getCountImportProductsWithErrros
    
    // FIXME: Move to engine
    public function lang()
    {
        $args = func_get_args();
        if (!isset($args[0])) {
            return false;
        }
    
        $word = __($args[0], $this->_engine->_languageDomain);
        if (!$word) {
            $word = $args[0];
        }
    
        $params = array_slice($args, 1);
        if ($params) {
            $word = vsprintf($word, $params);
        }
    
        return $word;
    } // end lang
    
    private function _doProcessImportData($importData)
    {
        $report = array();
        
        foreach ($importData as $key => $row) {
            $result = $this->_skuManager->start($row, $key);
            $report[$key] = $result;
        }

        return $report;
    } // end _doProcessImportData
    
    
    public function onInitSkuManager() 
    {
        if (!class_exists('WooProductsSkuManager')) {
            $filename = 'common/import/WooProductsSkuManager.php';
            require_once $this->_engine->getPluginPath().$filename;
        }
    } // end onInitSkuManager
    
    private function _getLimitImportData($importConfig)
    {
        $offset = $importConfig['offset'];
        $currentRow = 0;
        $importData = array();

        if ($this->_isFirstRowHeader($importConfig)) {
            $offset++; 
        }
        
        $rowNum = 0;
        
        while ($currentRow < ($offset + $importConfig['limit'])) {
            $rowNum++;
            
            $row = $this->_reader->getNextRow();
            
            if ($row === false) {
                break;
            }
            
            if ($currentRow < $offset) {
                $currentRow++;
                continue;
            }
            
            $importData[$rowNum] = $row;
            $currentRow++;
        }

        return $importData;
    } // end _getLimitImportData
    
    public function onInitJsAction()
    {
        $this->_engine->onEnqueueJsFileAction(
            'festi-user-role-prices-admin-import-'.$this->_action,
            'import/'.$this->_action.'.js',
            'jquery',
            $this->_engine->_version,
            true
        );
        
        $vars = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        );

        $options = $this->_getOptions();
        
        if (is_array($options)) {
            $vars = array_merge($vars, $options);
        };
        
        wp_localize_script(
            'festi-user-role-prices-admin-import-'.$this->_action,
            'fesiImportOptions',
            $vars
        );
    } // end onInitJsAction
    
    public function onInitCssAction()
    {
        $this->_engine->onEnqueueCssFileAction(
            'festi-user-role-prices-admin-import-'.$this->_action,
            'import/'.$this->_action.'.css',
            array(),
            $this->_engine->_version
        );
    } // end onInitCssAction
    
    public function displayPage()
    {
        $this->_engine->onPrepareScreen();
        
        $this->_fileSystem = $this->_engine->getFileSystemInstance();

        $methodName = 'displayImport'.ucfirst($this->_action).'Page';

        $method = array($this, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }
        
        try {
            call_user_func_array($method, array());
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->_engine->displayError($message);
            $this->_action = 'upload';
            $this->displayPage();
        }
    } // end displayPage
    
    private function _hasCompleteOptionInImportConfig()
    {
        $importConfig = $this->_getOptions();

        return in_array('complete', $importConfig);
    } // end _hasCompleteOptionInImportConfig
    
    private function _hasActionInRequest()
    {
        return array_key_exists('action', $_GET)
               && !empty($_GET['action']);
    } // end _hasActionInRequest
    
    public function onInitCsvReader()
    {
        if (!class_exists('CsvReaderComponent')) {
            $filename = 'common/import/CsvReaderComponent.php';
            require_once $this->_engine->getPluginPath().$filename;
        }
    } // end onInitCsvReader
    
    public function onInitCsvMappingOptions()
    {
        if (!class_exists('WooMappingImportOptions')) {
            $filename = 'import/WooMappingImportOptions.php';
            require_once $this->_engine->getPluginPath().'common/'.$filename;
        }
        
        $this->mapingOptions = new WooMappingImportOptions(
            $this->_engine->_languageDomain
        );
    } // end onInitCsvMappingOptions

    public function displayImportUploadPage()
    {
        $params = array(
            'refresh_completed' => '',
            'refresh_plugin' => '',
            'delete_role' => '',
            'action' => 'preview'
        );
        
        $vars = array(
            'url'    => $this->_engine->getUrl($params),
            'fields' => $this->_getUploadFields()
        );
        
        echo $this->_engine->fetch('import/import_upload_page.phtml', $vars);
    } // end displayImportUploadPage
    
    public function displayImportResultPage()
    {
        if ($this->_isUpdateOptionsRequestForResultPage()) {
            $oprions = $this->_getOptionsFromRequest();
            $this->_updateOptions($oprions);
        }
        
        $options = $this->_getOptions();
        
        if ($this->_hasAttributesInOptions($options)) {
            $this->_updateAttributes($options);
        }
        
        $vars = array(
            'rowsCount' => $options['rowsCount']
        );
                  
        echo $this->_engine->fetch('import/import_result_page.phtml', $vars);
    } // end  displayImportResultPage
    
    private function _updateAttributes($options)
    {
        $woocommerceFacade = WooCommerceFacade::getInstance();
        
        $atributtesHelper = $woocommerceFacade->createAtributtesHelper();
        
        $atributtesHelper->sync($options['attributes']);
    } // end _updateAttributes
    
    private function _hasAttributesInOptions($options)
    {
        return !empty($options['attributes']);
    } // end _hasAttributesInOptions
    
    private function _isUpdateOptionsRequestForResultPage()
    {
        return !empty($_POST['mapTo']);
    } // end _isUpdateOptionsRequest
    
    
    private function _getUploadFields()
    {
        if (!class_exists('ImportUploadFields')) {
            $filename = 'common/import/ImportUploadFields.php';
            require_once $this->_engine->getPluginPath().$filename;
        }
        
        $importFields = new ImportUploadFields($this->_engine->_languageDomain);
        
        $options = $this->_getOptions();
        
        return $importFields->get($options);  
    } // end _getUploadFields
    
    public function displayImportPreviewPage()
    {
        $filePath         = $this->_getFilePathFromRequest();
        $delimiter        = $this->_getDelimiter();
        $decimalSeparator = $this->_getDecimalSeparator();
        $this->_reader    = new CsvReaderComponent($filePath, $delimiter);
        
        if (!$this->_isValidateDelimiter($delimiter)) {
            $this->_throwException("Is not a valid CSV field separator");
        }

        $this->_reader->resetHandle();
                
        // FIXME: Danger logic load all rows from csv file.
        $importData = $this->_getImportData();
        
        $extendOptions = array(
            'filePath'  => $filePath,
            'rowsCount' => $this->_getCountRowsOfImportData($importData),
            'limit'     => $this->importLimit,
        );
        
        $options = $this->_getOptionsFromRequest($extendOptions);
        $this->_updateOptions($options, true);
        
        $vars = array(
            'url'              => $this->_getUrlToResultPage(),
            'cancelUrl'        => $this->_getCancelUrlForPreviewPage(),
            'isFirstRowHeader' => $options['isFirstRowHeader'],
            'importData'       => $importData,
            'mapingOptions'    => $this->mapingOptions->get(),
            'options'          => $options
        );
        
        echo $this->_engine->fetch('import/import_preview_page.phtml', $vars);
    } // end displayImportPreviewPage
    
    private function _getUrlToResultPage()
    {
        $params = array(
            'action' => 'result'
        );
    
        return $this->_engine->getUrl($params);
    } // end _getCancelUrlForPreviewPage
    
    private function _getCancelUrlForPreviewPage()
    {
        $params = array(
            'tab'    => 'importPriceTab',
            'action' => false
        );
        
        return $this->_engine->getUrl($params);
    } // end _getCancelUrlForPreviewPage
    
    private function _getOptionsFromRequest($extendedOptions = array())
    {
        $_POST += $extendedOptions;
        
        if ($this->_isUpdateOptionsRequestInPreviewPage()) {
            $isFirstRowHeader = !empty($_POST['isFirstRowHeader']);
            $_POST['isFirstRowHeader'] = (int) $isFirstRowHeader;
            $_POST['offset'] = 0;
        } else if ($this->_isUpdateOptionsRequestForResultPage()) {
            $fields = array(
                'custom_field_visible'       => static::FIELD_TYPE_ARRAY,
                'custom_field_variation'     => static::FIELD_TYPE_ARRAY,
                'product_image_set_featured' => static::FIELD_TYPE_ARRAY
            );
            
            $requredOptions = $this->getPreparedData($_POST, $fields);
            $_POST += $requredOptions;
        }
        
        $previousImportOptions = $this->_getOptions();
        
        if ($previousImportOptions) {
            $_POST += $previousImportOptions;
        }
        
        return $this->_getPreparedOptions($_POST);
    } // end _getOptionsFromRequest
    
    private function _isUpdateOptionsRequestInPreviewPage()
    {
        return array_key_exists('csvSeparator', $_POST);
    } // end _isUpdateOptionsRequestInPreviewPage
    
    /**
     * If we have saved options and they valid for current import then 
     * return this options.
     * 
     * @param array $importData
     * @return bool|array
     */
    /*
    private function _getPreviousImportOptions($importData)
    {
        $options = $this->_getOptions();
        
        if (!$this->_isPreviousFileHaveSameStructure($importData, $options)) {
            return false;
        }
        
        return $options;
    } // end _getPreviousImportOptions
    */
    
    /*
    private function _isPreviousFileHaveSameStructure($importData, $options)
    {
        if (
            !$options || 
            empty($options['columns']) || 
            empty($options['custom_field_name']) ||
            empty($importData[0])
        ) {
            return false;
        }
        
        $firstImportRow = $importData[0];
        
        if (count($options['columns']) != count($firstImportRow)) {
            return false;
        }
        
        foreach ($options['custom_field_name'] as $idnex => $columnName) {
            
            if ($columnName != $firstImportRow[$idnex]) {
                return false;
            }
        }
        
        return true;
    } // end _isPreviousFileHaveSameStructure
    */
    
    private function _getCountRowsOfImportData($importData)
    {
        $count = count($importData);
        
        if ($this->_isFirstRowHeader($_POST)) {
            $count--;
        }
        
        return $count;
    } // end _getCountRowsOfImportData
    
    /**
     * Update or replace options for import. If you need replace current options
     * use $isReplaceAllOptions = true.
     * 
     * @param array $values
     * @param bool $isReplaceAllOptions
     * @return array Prepared and mapped options
     */
    private function _updateOptions($values, $isReplaceAllOptions = false)
    {
        $options = $this->_getOptions();
        
        if (!$isReplaceAllOptions && $options) {
            $values = array_merge($options, $values);
        }
        
        $values = $this->_getPreparedOptions($values);
        
        $this->_engine->updateOptions(static::IMPORT_OPTIONS_KEY, $values);
        
        return $values;
    } // end _updateOptions
    
    private function _getImportData()
    {
        $data = array();

        $count = 0;
        
        while (($row = $this->_reader->getNextRow()) !== false) {
            $count++;
            $data[] = $row;
        }
        
        if ($this->_isFirstRowHeader($_POST)) {
            $count--;
        }
        
        if (!$count) {
            $this->_throwException("No data to import");
        }
        
        return $data;
    } // end _getImportData
    
    private function _isFirstRowHeader($options)
    {
        if (array_key_exists('csvSeparator', $options)) {
            return !empty($options['isFirstRowHeader']);
        }
        
        $isFirstRowHeader = $this->_getOption('isFirstRowHeader');
        return !empty($isFirstRowHeader);
    } // end isFirstRowIsHeader
    
    private function _isValidateDelimiter($delimiter)
    {
        if (strlen($delimiter) > 1) {
            return false;
        }

        $row = $this->_reader->getNextRow();
        
        return count($row) > 1;
    } // end _isValidateDelimiter
    
    private function _getDelimiter()
    {
        // FIXME:
        if (!$this->_hasDelimiterInRequest()) {
            $delimiter = $this->_getOption('csvSeparator');
            if ($delimiter) {
                return $delimiter;
            }
            
            $this->_throwException("CSV field separator can not be empty");
        }
        
        $delimiter = $_POST['csvSeparator'];
        
        return $delimiter;
    } // end _getDelimiter
    
    private function _getDecimalSeparator()
    {
        if (!$this->_hasDecimalSeparatorInRequest()) {
            
            $decimalSeparator = $this->_getOption('csvSeparator');
            if ($decimalSeparator) {
                return $decimalSeparator;
            }
            
            $this->_throwException("Decimal Separator field can not be empty");
        }
        
        $decimalSeparator = $_POST['csvSeparator'];
        
        return $decimalSeparator;
    } // end _getDecimalSeparator
    
    private function _hasDelimiterInRequest()
    {
        return array_key_exists('csvSeparator', $_POST)
               && !empty($_POST['csvSeparator']);
    } // end _hasDelimiterInRequest
    
    private function _hasDecimalSeparatorInRequest()
    {
        return array_key_exists('decimalSeparator', $_POST)
               && !empty($_POST['decimalSeparator']);
    } // end _hasDecimalSeparatorInRequest
    
    private function _getFilePathFromRequest()
    {
        if (!$this->_hasFileInRequest() && !$this->_hasFileUrlInRequest()) {
            
            $filePath = $this->_getOption('filePath');
            if ($filePath) {
                return $filePath;
            }
            
            $this->_throwException(
                "You have not selected a file or insert url to Import"
            );
        }
        
        if ($this->_hasFileUrlInRequest()) {
            $url = $_POST['importUrl'];
            $fileName = $url;
            $file = $url;
        } else {
            $fileName = $_FILES['importFile']['name'];
            $file = $_FILES['importFile']['tmp_name'];
        }
        
        if (!$this->_isAllowedImportFileExtension($fileName)) {
            $this->_throwException(
                "Sorry, your file extension is not correct!"
            );
        }

        $uploadDir = $this->getUploadDir();
        
        if (!$this->_fileSystem->exists($uploadDir)) {
            $result = $this->_fileSystem->mkdir($uploadDir, 0777);
            
            if (!$result) {
                $this->_throwException(
                    "Could not create upload directory ".$uploadDir
                );
            }
        }
        
        $filePath = $this->getUploadDir(static::DEFAULT_IMPORT_FILE_NAME);
        
        $result = $this->_fileSystem->move(
            $file,
            $filePath,
            true
        );

        if (!$result) {
            $this->_throwException("Could not move file to folder ".$uploadDir);
        }

        return $filePath;
    } // end _getFilePathFromRequest
    
    private function _throwException($text)
    {
        $message = __(
            $text,
            $this->_engine->_languageDomain
        );
        
        throw new Exception( 
            $message
        );
    } // end _throwException
    
    private function _isAllowedImportFileExtension($fileName)
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        return in_array($ext, array('csv'));
    } // end _isAllowedImportFileExtension
    

    private function _hasFileInRequest()
    {
        return isset($_FILES)
        && array_key_exists("importFile", $_FILES)
        && $_FILES['importFile']['name'];
    } // end _hasFileInRequest
    
    private function _hasFileUrlInRequest()
    {
        return array_key_exists('importUrl', $_POST)
               && !empty($_POST['importUrl']);
    } // end _hasFileUrlInRequest
    
    protected function getUploadDir($fileName = '')
    {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'].'/'.$fileName;
    } // end getUploadDir
    
    private function _getOptions()
    {
        $options = $this->_engine->getOptions(static::IMPORT_OPTIONS_KEY);
        
        // XXX: This need for compatibility after update old version plugin.
        if ($options && !$this->_isOptionsPrepared($options)) {
            $options = $this->_getPreparedOptions($options);
        }
        
        return $options;
    } // end _getOptions
    
    private function _getOption($name)
    {
        $options = $this->_getOptions();
        
        if (array_key_exists($name, $options)) {
            return $options[$name];
        }
        
        return null;
    } // end _getOptions
    
}
