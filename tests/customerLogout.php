<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    try {
        $result = $uc->customerLogout();
        
        $uc->printRawCart();
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

