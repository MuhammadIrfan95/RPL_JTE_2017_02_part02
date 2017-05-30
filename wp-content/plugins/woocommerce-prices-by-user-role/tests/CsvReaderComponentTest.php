<?php
require_once dirname(__FILE__).'/PriceByRoleTestCase.php';

class CsvReaderComponentTest extends PriceByRoleTestCase
{
    private $_backend;
    private $_notRealFilePath = '/var/test/file.php';
    private $_defaultCsvDelimiter = ',';

    public function setUp()
    {
        parent::setUp();
        
        $file = 'CsvReaderComponent.php';
        require_once $this->getPluginPath('common/import/'.$file);
    } // end setUp
    
  
    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #Not exists file.*#
     */
    public function testNotExistsFileMessage()
    {
        new CsvReaderComponent(
            $this->_notRealFilePath,
            $this->_defaultCsvDelimiter
        );
    } // end testNotExistsFileMessage
}