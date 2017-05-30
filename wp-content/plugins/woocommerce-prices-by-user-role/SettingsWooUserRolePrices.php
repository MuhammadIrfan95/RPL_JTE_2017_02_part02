<?php
class SettingsWooUserRolePrices
{
    const GENERAL_SETTINGS_CSS_CLASS = 'general-settings';
    const HIDING_SETTINGS_CSS_CLASS = 'hiding-settings';
    
    private $_languageDomain = '';

    public function __construct($languageDomain)
    {
        $this->_languageDomain = $languageDomain;
    } // end __construct

    public function get()
    {
        $settings = array(
            'roles' => array(
                'caption' => __(
                    'User Roles for Special Pricing',
                    $this->_languageDomain
                ),
                'type' => 'multicheck',
                'default' => array(),
                'fieldsetKey' => 'general',
                'classes' => 'festi-user-role-prices-top-border '.
                    static::GENERAL_SETTINGS_CSS_CLASS,
                'deleteButton' => true,
                'hint' => __(
                    'Select user roles which should be active on '.
                    'product page for special prices',
                    $this->_languageDomain
                ),
            ),
            'discountOrMakeUp' => array(
                'caption' => __(
                    'Discount or Markup for Products',
                    $this->_languageDomain
                ),
                'type' => 'input_select',
                'values' => array(
                    'discount' => __('discount', $this->_languageDomain),
                    'markup' => __('markup', $this->_languageDomain)
                ),
                'default' => 'discount',
                'fieldsetKey' => 'general',
                'classes' => 'festi-user-role-prices-top-border '.
                    static::GENERAL_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Provide discount or markups in fixed or percentage terms 
                    for all products on shop',
                    $this->_languageDomain
                ),
            ),
            'discountByRoles' => array(
                'caption' => __(
                    '',
                    $this->_languageDomain
                ),
                'type' => 'multidiscount',
                'default' => array(),
                'fieldsetKey' => 'general',
                'deleteButton' => false,
                'classes' => static::GENERAL_SETTINGS_CSS_CLASS
            ),
            'showCustomerSavings' => array(
                'caption' => __(
                    'Display Price Savings on',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Display to customer regular price, the user role price '.
                    'with label &quot;Your Price&quot;, and the percent saved '.
                    'with label &quot;Savings&quot;',
                    $this->_languageDomain
                ),
                'type' => 'multi_select',
                'values' => array(
                    'product' => __('Product Page', $this->_languageDomain),
                    'archive' => __(
                        'Products Archive Page (for Simple product)',
                        $this->_languageDomain
                    ),
                    'cartTotal' => __(
                        'Cart Page (for Order Total)',
                        $this->_languageDomain
                    ),
                ),
                'default' => array(),
                'fieldsetKey' => 'general',
                'classes' => 'festi-user-role-prices-top-border '.
                    static::GENERAL_SETTINGS_CSS_CLASS
            ),
            'customerSavingsLableColor' => array(
                'caption' => __(
                    'Color for Savings Labels',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'fieldsetKey' => 'general',
                'default' => '#ff0000',
                'classes' => static::GENERAL_SETTINGS_CSS_CLASS,
                'eventClasses' => 'showCustomerSavings',
                'hint' => __(
                    'Select color for text labels about customer savings '.
                    'Regular Price, Your Price, Savings',
                    $this->_languageDomain
                )
            ),

            'rulesForNonRegistered' => array(
                'caption' => __(
                    '<h2>Rules for Non-Registered Users</h2>  ',
                    $this->_languageDomain
                ),
                'type' => 'text',
                'fieldsetKey' => 'hide',
                'classes' => 'festi-h2 '.static::HIDING_SETTINGS_CSS_CLASS,
                'text' => ''
            ),
            'hideAddToCartButton' => array(
                'caption' => __(
                    'Hide the &quot;Add to Cart&quot; Button',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'fieldsetKey' => 'hide',
                'classes' => 'festi-case-hide-add-to-cart-button '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'lable' => __(
                    'Enable hidden the &quot;Add to Cart&quot; button',
                    $this->_languageDomain
                ),
            ),
            'textForNonRegisteredUsers' => array(
                'caption' => __(
                    'Text Instead of '.
                    '&quot;Add to Cart&quot; button',
                    $this->_languageDomain
                ),
                'type' => 'textarea',
                'fieldsetKey' => 'hide',
                'classes' =>
                    'festi-case-text-instead-button-for-non-registered-users '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Provide written text which '.
                    'will be displayed on instead of &quot;Add to Cart&quot; 
                    button',
                    $this->_languageDomain
                )
            ),
            'onlyRegisteredUsers' => array(
                'caption' => __(
                    'Hide the Prices',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'fieldsetKey' => 'hide',
                'classes' => 'festi-user-role-prices-top-border '.
                    'festi-case-only-registered-users  festi-padding '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'lable' => __(
                    'Enable hidden prices for all products',
                    $this->_languageDomain
                ),
            ),
            'textForUnregisterUsers' => array(
                'caption' => __(
                    'Text Instead of Price',
                    $this->_languageDomain
                ),
                'type' => 'textarea',
                'default' => __(
                    'Please login or register to see price',
                    $this->_languageDomain
                ),
                'fieldsetKey' => 'hide',
                'classes' => 'festi-case-text-for-unregistered-users '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Provide written text which '.
                    'will be displayed on instead of the price',
                    $this->_languageDomain
                ),
            ),
            'rulesForRegistered' => array(
                'caption' => __(
                    '<h2>Rules for Registered Users</h2>  ',
                    $this->_languageDomain
                ),
                'type' => 'text',
                'fieldsetKey' => 'hide',
                'classes' => 'festi-border-top-hiding-rules-tab festi-h2 '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'text' => ''
            ),
            'hideAddToCartButtonForUserRoles' => array(
                'caption' => __(
                    'Hide the &quot;Add to Cart&quot; Button',
                    $this->_languageDomain
                ),
                'type' => 'multicheck',
                'default' => array(),
                'fieldsetKey' => 'hide',
                'deleteButton' => false,
                'classes' => static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Enable hidden the &quot;Add to Cart&quot;
                     button from certain user roles',
                    $this->_languageDomain
                ),
            ),
            'hidePriceForUserRoles' => array(
                'caption' => __(
                    'Hide the Prices',
                    $this->_languageDomain
                ),
                'type' => 'multicheck',
                'default' => array(),
                'fieldsetKey' => 'hide',
                'deleteButton' => false,
                'classes' => 'festi-user-role-prices-top-border '.
                    'festi-case-hide-price-for-user-roles '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Enable hidden prices from certain user roles',
                    $this->_languageDomain
                ),
            ),
            'textForRegisterUsers' => array(
                'caption' => __(
                    'Text with Hidden Price',
                    $this->_languageDomain
                ),
                'type' => 'textarea',
                'default' => __(
                    'Price for your role is hidden',
                    $this->_languageDomain
                ),
                'fieldsetKey' => 'hide',
                'classes' => 'festi-case-text-for-registered-users '.
                    'festi-hint-upper '. static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Provide written text with certain '.
                    'roles which will be shown instead of the product price',
                    $this->_languageDomain
                )
            ),
            'hideEmptyPrice' => array(
                'caption' => __(
                    'Hide Empty Price',
                    $this->_languageDomain
                ),
                'type' => 'multicheck',
                'default' => array(),
                'deleteButton' => false,
                'fieldsetKey' => 'hide',
                'classes' =>
                    'festi-case-hide-empty-price '.
                    'festi-user-role-prices-top-border '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Enable hidden empty price from certain user roles',
                    $this->_languageDomain
                ),
            ),
            'textForEmptyPrice' => array(
                'caption' => __(
                    'Text Instead of Empty Price',
                    $this->_languageDomain
                ),
                'type' => 'textarea',
                'fieldsetKey' => 'hide',
                'classes' =>
                    'festi-case-text-instead-empty-price '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => __(
                    'Provide written text which '.
                    'will be displayed on instead of empty price',
                    $this->_languageDomain
                )
            )
        );

        return $settings;
    } // end get
}