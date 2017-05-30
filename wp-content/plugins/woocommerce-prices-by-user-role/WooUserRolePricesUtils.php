<?php

class WooUserRolePricesUtils
{
    public static function doCheckPhpVersion($minVersion)
    {
        if (version_compare(phpversion(), $minVersion, '<')) {
            $message = 'The minimum PHP version required for this plugin is '.
                       $minVersion.'. Please contact your hosting company to '.
                       'upgrade PHP version on your server.';
                    
            throw new Exception(
                $message,
                PRICE_BY_ROLE_EXCEPTION_INVALID_PHP_VERSION
            );
        }
    }
    
    public static function displayPluginError($message)
    {
        set_transient(PRICE_BY_ROLE_EXCEPTION_MESSAGE, $message);
        add_action(
            'admin_notices',
            array(
                new WooUserRolePricesUtils(),
                "fetchExceptionMessage"
            )
        );
    }
    
    public function fetchExceptionMessage()
    {
        $message = get_transient(PRICE_BY_ROLE_EXCEPTION_MESSAGE);
        echo '<div class="error"> <p>'.$message.'</p></div>';
    }
    
}
