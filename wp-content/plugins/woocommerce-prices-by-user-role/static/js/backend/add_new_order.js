jQuery(document).ready(function() 
{
    var inputField = ".woocommerce #order_data input[name='customer_user']";
    
    if (jQuery(inputField).length > 0 && isRoleSet()) {
        setUserIdForAjaxAction(jQuery(inputField).val());
    }

    jQuery(inputField).change(function () {
        setUserIdForAjaxAction(jQuery(this).val());
    })
    
    function setUserIdForAjaxAction(userId)
    {
        var data = {
            action: 'onSetUserIdForAjaxAction',
            userId: userId
        };
        
        jQuery.post(fesiWooPriceRole.ajaxurl, data, function(response) {
            if (response.status === false) {
                alert('Woocommerce Price By Role: Error!');
                return false;
            }
            
            return true;
        })
    } // end etUserIdForAjaxAction
    
    function isRoleSet()
    {
        var value = jQuery(inputField).val();
        
        return value !== '';
    }
}); 