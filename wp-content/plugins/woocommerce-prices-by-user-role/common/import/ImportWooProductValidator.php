<?php 

class ImportWooProductValidator extends FestiObject
{
    private $_values;
    private $_metaValues;
    private $_exsistProduct;
    private $_columnsToCaptions;
    private $_engine;
    private $_errors = array();
    
    public function __construct(
        &$values, &$metaValues, $exsistProduct, $columnsToCaptions, &$engine
    )
    {
        $this->_values = &$values;
        $this->_metaValues = &$metaValues;
        $this->_engine = &$engine;
        $this->_exsistProduct = $exsistProduct;
        $this->_columnsToCaptions = $columnsToCaptions;
    } // end __construct
    
    public function exec()
    {
        foreach ($this->_values as $key => $value) {
            $methodName = 'get'.self::convertToCamelCase($key).'Error';
            
            if (!method_exists($this, $methodName)) {
                continue;
            }
            
            $error = $this->$methodName($key, $value);
            if ($error) {
                //$this->_errors[$key][] = $error;
                $this->_errors[] = $error;
            }
        }
        
        return true;
    } // end exec
    
    public function getErrors()
    {
        return $this->_errors;
    } // end getErrors
    
    protected function getPostStatusError($key, $value)
    {
        $allowedStatuses = array(
            'publish', 
            'future', 
            'draft', 
            'pending', 
            'private', 
            'trash'
        );
        
        if (in_array($value, $allowedStatuses)) {
            return false;
        }
        
        $error = $this->_engine->lang(
            '"%s" field contains incorrect data, expected values are: %s', 
            $this->_getColumnCaption($key), 
            join(", ", $allowedStatuses)
        );
        
        return $error;
    } // end getPostTitleError
    
    private function _getColumnCaption($key)
    {
        if (array_key_exists($key, $this->_columnsToCaptions)) {
            return $this->_columnsToCaptions[$key];
        }
        
        return $key;
    } // end _getColumnCaption
    
}