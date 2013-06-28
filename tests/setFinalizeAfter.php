<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    try {
        $result = $uc->setFinalizeAfter(45);
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

