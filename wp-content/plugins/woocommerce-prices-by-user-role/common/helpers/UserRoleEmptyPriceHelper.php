<?php

class UserRoleEmptyPriceHelper
{
    private $_engine;
    
    public function __construct(&$engine)
    {
        $this->_engine = &$engine;
    } // end __construct
    
    public function isDisplayTextInsteadOfPriceEnabled()
    {
        if (!$this->_engine->getUserRole()) {
            return false;
        }

        $settings = $this->_engine->getOptions('settings');
        
        if (!$settings) {
            $settings = array();
        }
        
        $userRole = $this->_engine->getUserRole();

        return array_key_exists('hideEmptyPrice', $settings) &&
               !empty($settings['hideEmptyPrice']) &&
               array_key_exists($userRole, $settings['hideEmptyPrice']);
    } // end isDisplayTextInsteadOfPriceEnabled
    
    public function onGetTextInsteadOfEmptyPrice()
    {
        $settings = $this->_engine->getOptions('settings');
        $textInsteadOfEmptyPrice = $settings['textForEmptyPrice'];
        $vars = array(
            'text' => $textInsteadOfEmptyPrice
        );
        
        return $this->_engine->fetch('custom_text.phtml', $vars);
    } // end onGetTextInsteadOfEmptyPrice
    
    public function onHideEmptyPrice()
    {
        if ($this->isDisplayTextInsteadOfPriceEnabled()) {
            $this->_engine->addFilterListener(
                'woocommerce_empty_price_html',
                'onGetTextInsteadOfEmptyPrice'
            );
                
            return true;
        }
        
        return false;
    } // end onHideEmptyPrice
}