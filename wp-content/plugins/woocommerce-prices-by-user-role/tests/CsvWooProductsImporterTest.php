<?php
require_once dirname(__FILE__).'/PriceByRoleTestCase.php';

class CsvWooProductsImporterTest extends PriceByRoleTestCase
{
    private $_backend;
    private $_jsHandlePrefix = 'festi-user-role-prices-admin-import-';
    private $_engine;
    private $_csvImporter;
    
    public function setUp()
    {
        parent::setUp();
                
        $fileName = 'WooUserRolePricesFestiPlugin.php';
        require_once dirname($this->pluginMainFile).'/'.$fileName;
        
        $fileName = 'WooUserRolePricesBackendFestiPlugin.php';
        require_once dirname($this->pluginMainFile).'/'.$fileName;
        $this->_backend = new WooUserRolePricesBackendFestiPlugin(
            $this->pluginMainFile
        );
        
        $fileName = 'CsvWooProductsImporter.php';
        $path = '/common/import/';
        require_once dirname($this->pluginMainFile).$path.$fileName;
        $this->_csvImporter = new CsvWooProductsImporter($this->_backend);
        
    } // end setUp
    
    public function testEnqueueJsInUploadPage()
    {
        $_GET['action'] = 'upload';
        
        $this->_doTestJsFile($_GET['action']);
    } // end testEnqueueJsInUploadPage
    
    public function testEnqueueJsInPreviewPage()
    {
        $_GET['action'] = 'preview';

        $this->_doTestJsFile($_GET['action']);
    } // end testEnqueueJsInPreviewPage
    
    public function testEnqueueJsInResultPage()
    {
        $_GET['action'] = 'result';

        $this->_doTestJsFile($_GET['action']);
    } // end testEnqueueJsInResultPage
    
    private function _getJsFilePath($fileName)
    {
        return $this->getPluginPath('static/js/backend/import/'.$fileName);
    } // end _getJsFilePath
    
    private function _doTestJsFile($actionName)
    {
        $result = $this->_isFileExists($actionName);

        $this->assertTrue($result);
        
        $result = $this->_hasHandleInWpScripts($actionName);
        
        $this->assertTrue($result);
        
        $this->doCleanWpScriptsList();
    } // end _doTestJsFile
    
    private function _isFileExists($actionName)
    {
        $fileName = $actionName.'.js';
        $file = $this->_getJsFilePath($fileName);
        
        return file_exists($file);
    } // end _isFileExists
    
    private function _hasHandleInWpScripts($actionName)
    {
        $importer = new CsvWooProductsImporter($this->_backend);
        
        ob_start();
        do_action('admin_print_scripts');
        ob_get_clean();
        
        $scriptsList = $this->getWpScriptsList();
        
        $handle = $this->_jsHandlePrefix.$actionName;
        
        return in_array($handle, $scriptsList);
    } // end _hasHandleInWpScripts
    
    public function getFilePath()
    {
        $path = 'C:\inetpub\wwwroot\prasa sitio\prasa/wp-content/uploads/';
            
        $pathes = array(
            'options'   => array(
                'filePath' => $path,
                'name'     => 'test \name'
            ),
        );
        
        return $pathes;
    }
    
    
}