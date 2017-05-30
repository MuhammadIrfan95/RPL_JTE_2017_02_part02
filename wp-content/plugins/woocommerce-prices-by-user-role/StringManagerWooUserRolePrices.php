<?php

class StringManagerWooUserRolePrices extends WordpressDispatchFacade
{
    protected $languageDomain = PRICE_BY_ROLE_LANGUAGE_DOMAIN;
    private static $_instance = null;
    
    private function __construct()
    {
        if (self::$_instance !== null) {
            throw new Exception(
                "Instance already defined."
            );
        }

        $this->_doRegisterStringList();
    } // end __construct
    
    public static function start()
    {
        return self::getInstance();
    } // end getInstance
    
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end getInstance
    
    private function _doRegisterStringList()
    {
        $stringsList = $this->getStrings();
        
        
        foreach ($stringsList as $key => $string) {
            $this->doRegisterString($string, $key);
        }
    } // end _doRegisterStringList
    
    public function doRegisterString($string, $key)
    {
        $this->dispatchAction(
            FESTI_ACTION_REGISTER_STATIC_STRING,
            $string,
            $key
        );
    } // end doRegisterString
    
    public function getStrings()
    {
        $strings = array(
            'cartTotalSavingsRetail' => __(
                "Total retail",
                $this->languageDomain
            ),
            
            'cartTotalSavingsUserPriceTitle' => __(
                "Your Price",
                $this->languageDomain
            ),
            
            'cartTotalSavingsDiscountTitle' => __(
                "Total savings",
                $this->languageDomain
            ),
            
            'productSavingsRegularPrice' => __(
                "Regular Price",
                $this->languageDomain
            ),
            
            'productSavingsUserPriceTitle' => __(
                "Your Price",
                $this->languageDomain
            ),
            
            'productSavingsDiscountTitle' => __(
                "Savings",
                $this->languageDomain
            ),
            
            'subscriptionSignFee' => __(
                "Sign-up Fee",
                $this->languageDomain
            ),
            
            'subscriptionPerMonth' => __(
                "/ month",
                $this->languageDomain
            ),

            'freeProduct' => __(
	            "Free!",
	            $this->languageDomain
            ),
        );
        
        return $strings;
    } // end getStrings
    
    public function getString($key)
    {
        $strings = $this->getStrings();
        
        if (!array_key_exists($key, $strings)) {
            return $key;
        }
        
        $string = $strings[$key];
        
        $string = $this->getStringTranslation($string, $key);

        return $string;
    } // end getString
    
    public function getStringTranslation($string, $key)
    {
        $string = $this->dispatchFilter(
            FESTI_FILTER_GET_STATIC_STRING,
            $string,
            $key
        );
        
        return $string;
    } // end getStringTranslation
    
    public function displayString($key)
    {
        echo $this->getString($key);
    } // end displayString
}
