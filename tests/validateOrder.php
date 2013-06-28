<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    try {
        $checks = array(
            'Item Quantity Valid',
            'Billing Address Provided',
            'Billing State Abbreviation Valid',
            'Billing Phone Numbers Provided',
            'Email provided if required',
            'Billing Validate City State Zip',
            'Tax County Specified',
            'Shipping Method Provided',
            'Advertising Source Provided',
            'Referral Code Provided',
            'Shipping Address Provided',
            'Shipping State Abbreviation Valid',
            'Gift Message Length',
            'Shipping Validate City State Zip',
            'Shipping Destination Restriction',
            'One per customer violations',
            'Credit Card Shipping Method Conflict',
            'Payment Information Validate',
            'Payment Method Provided',
            'Quantity requirements met',
            'Items Present',
            'Options Provided',
            'CVV2 Not Required',
            'Electronic Check Confirm Account Number',
            'Customer Profile Does Not Exist.',
            'Valid Ship On Date',
            'Pricing Tier Limits',
            'Shipping Needs Recalculation',
            'Merchant Specific Item Relationships',
            'All'
        );
        $result = $uc->validateCart();
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } catch (Exception $exc) {
        echo "<pre>";
        print_r($uc->errors);
        echo "</pre>";
    }

} catch (Exception $error) {
    echo "<pre>";
    print_r($error);
    echo "</pre>";
}
?>

