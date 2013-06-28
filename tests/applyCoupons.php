<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    $uc->applyCoupon('DEMO');

    $uc->printRawCart();
    
} catch (Exception $error) {
    if(count($uc->errors) > 0){
        echo "<pre>";
        print_r($uc->errors);
        echo "</pre>";
    } else {
        echo "<pre>";
        print_r($error);
        echo "</pre>";
    }
}
?>

