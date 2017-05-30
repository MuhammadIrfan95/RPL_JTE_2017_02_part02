<?php

class FestiTeamApiClient
{
    public static function addInstallStatistics($idPlugin)
    {
        $url = "/statistics/plugin/".$idPlugin."/install/";
        
        $params = array();
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $params['ip'] = $_SERVER['SERVER_ADDR'];
        }
        
        if (!empty($_SERVER['SERVER_NAME'])) {
            $params['host'] = $_SERVER['SERVER_NAME'];
        }
        
        if (function_exists('get_option')) {
            $params['admin_email'] = get_option('admin_email');
        }
        
        static::_api($url, $params);
    } // end addInstallStatictics
    
    private static function _api($url, $params)
    {
        $url = 'http://api.festi.team'.$url;
        
        $params = array('http' => array(
            'method' => 'POST',
            'content' => http_build_query($params)
        ));
        
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if ($fp) {
            @stream_get_contents($fp);
        }
    } // end _api
    
}