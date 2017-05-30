<?php 

class EnvatoUtil
{
    private $_url = 'http://api.festi.team';
    private $_facade = null;
    private $_idPlugin;
    private $_options;
    
    const OPTION_KEY_FESTI_LICENSE = 'festi_license';
    const STATUS_API_SERVER_ERROR = 'error';
    const STATUS_API_SERVER_SUCCESS = 'success';
    /*
     * @param object &$facade
     * @param array $options
     * */
    public function __construct(&$facade, $options)
    {
        $this->_facade = $facade;
        
        $this->_setOptions($options);
        
        $this->_idPlugin = $this->_getOption('id_plugin');
    }
    
    private function _setOptions($options)
    {
        $allowedOptions  = array('id_plugin', 'message', 'slug_plugin');
        
        foreach ($options as $key => $value) {
            if (!in_array($key, $allowedOptions)) {
                throw new Exception('Undefined option: '.$key);
            }
            
            $this->_options[$key] = $value;
        }
        
        return true;
    }
    
    private function _getOption($key)
    {
        if ($this->_hasOption($key)) {
            return $this->_options[$key];
        }
       
        throw new Exception('Undefined option: '.$key);
    }
    
    private function _hasOption($key)
    {
        return array_key_exists($key, $this->_options);
    }
    
    private function _getDefaultMessage()
    {
        $message = _(
            'HI! Would you like unlock premium support? '.
            'Please activate your copy of Festi-Team plugins.'
        );
        
        return $message;
    }
    
    public function getApiUrl()
    {
        return $this->_url."/envato/plugin/".$this->_idPlugin."/licence/";
    }
    
    public function displayLicenseNotice()
    {
        if ($this->_isAllowedPages() && !$this->_isActiveLicenseByPluginID()) {
            $this->_addActionListener('admin_notices', 'onDisplayNotice');
        }
        
        return true;
    }

    private function _isAllowedPages()
    {
        return (array_key_exists('page', $_GET) && 
                $_GET['page'] == $this->_getOption('slug_plugin')) || 
               (array_key_exists('pagenow', $GLOBALS) && 
                $GLOBALS['pagenow'] == 'plugins.php');
    }
    
    private function _isActiveLicenseByPluginID()
    {
        $data = $this->_facade->getOptions(static::OPTION_KEY_FESTI_LICENSE);
        
        return $this->_isExistsPluginID($data);
    }
    
    public function onDisplayNotice()
    {   
        $message = $this->_getOption('message');
        
        if (!$message) {
            $message = $this->_getDefaultMessage();
        }
        
        $this->_facade->displayMessage($message, 'updated');
        
        return true;
    }
    
    public function doValidateLicense($request)
    {
        $data = $this->_facade->getOptions(static::OPTION_KEY_FESTI_LICENSE);
        
        if ($this->_isExistsPluginID($data)) {
            return $data[$this->_idPlugin];
        }
        
        if (!$this->_hasUserDataInRequest($request)) {
            return false;   
        }
        
        $params['code'] = $request['code'];
        $params['user'] = $request['user'];
        
        $responseData = $this->_doPluginVerification($params);

        if (!$this->_isPluginID($responseData)) {
            return false;
        }
        
        $data[$this->_idPlugin] = array(
            'id'            => $responseData['id_plugin'],
            'purchase_code' => $responseData['purchase_code']
        );
        
        $this->_facade->updateOptions(static::OPTION_KEY_FESTI_LICENSE, $data);
        
        return $responseData;
    }
    
    private function _hasUserDataInRequest($request)
    {
        return !empty($request['code']) && !empty($request['user']);
    }
    
    private function _isPluginID($data)
    {
        return !empty($data['id_plugin']) &&
               $data['id_plugin'] == $this->_idPlugin;  
    }

    private function _doPluginVerification($params)
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $params['ip'] = $_SERVER['SERVER_ADDR'];
        }
        
        if (!empty($_SERVER['SERVER_NAME'])) {
            $params['host'] = $_SERVER['SERVER_NAME'];
        }
        
        $url = $this->_url."/envato/plugin/verification/";
        
        $params = array('http' => array(
            'method' => 'POST',
            'content' => http_build_query($params)
        ));
        
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        
        if (!$fp) {
            return false;
        }
        
        $response = @stream_get_contents($fp);
        
        fclose($fp);
        
        $data = json_decode($response, true);
        
        if (!$this->_isExistsContent($data)) {
            return false;
        }
        
        return $data['content'];
    }
    
    private function _isExistsContent($data)
    {
        return array_key_exists('content', $data);
    }
    
    private function _isExistsPluginID($data)
    {
        return is_array($data) && array_key_exists($this->_idPlugin, $data);
    }
    
    private function _getCleanQueryString($keys)
    {
        if (is_scalar($keys)) {
            $dataKeys = array($keys => "");    
        } else {
            $dataKeys = array_fill_keys($keys, "");    
        }
        
        parse_str($_SERVER['QUERY_STRING'], $vars);
        
        $query = http_build_query(
            array_diff_key(
                $vars,
                $dataKeys
            )
        );
        
        return $query; 
    }

    public function getPrepareUrl()
    {
        $keys = array('code', 'user', 'errors');
        
        return $this->_getCleanQueryString($keys);
    }
    
    private function _addActionListener($hook, $method)
    {
        if (!is_array($method)) {
            $method = array(&$this, $method);
        }
        
        $this->_facade->addActionListener($hook, $method);
        
        return true;
    }

    public function getStatusFestiApiService()
    {  
        $params = array('http' => array(
            'method' => 'POST'
        ));
        
        $ctx = stream_context_create($params);
        try{
            $fp = @fopen($this->getApiUrl(), 'rb', false, $ctx);
        
            if (!$fp) {
                throw new Exception('Connection with Festi API, Error');
            }
            
            $response = @stream_get_contents($fp);
            
            fclose($fp);
            
            if ($this->_hasErrorInResponse($response)) {
                throw new Exception('Error In Plugin Festi API');
            }
        
        } catch(Exception $exp) {
            $data = array(
                'status'  => static::STATUS_API_SERVER_ERROR,
                'message' => $exp->getMessage()
            );    
            
            return $data;
        }
        
        return array('status' => static::STATUS_API_SERVER_SUCCESS);
    }

    private function _hasErrorInResponse($response)
    {
        $responseData = json_decode($response, true);
        
        return !empty($responseData['error']);
    }
}
