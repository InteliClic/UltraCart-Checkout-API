<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    try {
        $request = array('autoResponderName' => 'mailchimp', 'lists' => array('Welcome','Two Week','Three Week'));
        $result = $uc->subscribeToAutoResponder($request);
        
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

