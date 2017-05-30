<?php

class PricesHidingRulesWooUserRoleBackendTab
{
    private $_plugin;
    private $_errorMessage;
    private $_hasErrors = false;

    public function __construct(&$backend)
    {
        $this->_plugin = &$backend;
    }

    public function display()
    {
        $this->_plugin->onPrepareScreen();

        $vars = $this->_getCurrentValues();

        echo $this->_plugin->fetch('settings_page.phtml', $vars);
    } // end display

    public function getOptionsFieldSet()
    {
        $fieldset = array(
            'hide' => array(),
        );

        $settings = $this->_loadSettings();

        if ($settings) {
            foreach ($settings as $ident => &$item) {
                if (array_key_exists('fieldsetKey', $item)) {
                    $key = $item['fieldsetKey'];
                    $fieldset[$key]['filds'][$ident] = $settings[$ident];
                }
            }
            unset($item);
        }

        return $fieldset;
    } // end getOptionsFieldSet

    private function _loadSettings()
    {
        $languageDomain = $this->_plugin->_languageDomain;
        $settings = new SettingsWooUserRolePrices($languageDomain);

        $options = $settings->get();

        $values = $this->_plugin->getOptions('settings');
        if ($values) {
            foreach ($options as $ident => &$item) {
                if (array_key_exists($ident, $values)) {
                    $item['value'] = $values[$ident];
                }
            }
            unset($item);
        }

        return $options;
    } // end _loadSettings

    public function doUpdateOptions($params)
    {
        try {
            $this->_plugin->updateOptions('settings', $params);
        } catch (Exception $e) {
            $this->_hasErrors = true;
            $this->_errorMessage = $e->getMessage();

            return false;
        }

        return true;
    } // end doUpdateOptions

    private function _getCurrentValues()
    {
        $options = $this->_plugin->getOptions('settings');

        $vars['fieldset'] = $this->getOptionsFieldSet();
        $vars['currentValues'] = $options;

        return $vars;
    } // end _getCurrentValues

    public function getLastError()
    {
        if ($this->_hasErrors) {
            return $this->_errorMessage;
        }

        return false;
    } // end getLastError

}