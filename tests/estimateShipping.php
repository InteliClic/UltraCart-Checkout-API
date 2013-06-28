<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    $shipping = $uc->estimateShipping();
    
    echo "<pre>";
    print_r($shipping);
    echo "</pre>";
    
} catch (Exception $error) {
    echo "<pre>";
    print_r($error);
    echo "</pre>";
}
?>

