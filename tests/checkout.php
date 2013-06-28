<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    try {
        $result = $uc->checkout();
        echo 'This is the checkout handoff method so you will need to redirect the visitor to the following link.<br />';
        echo '<a href="'.$result->redirectToUrl.'">Complete Checkout</a>';
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

