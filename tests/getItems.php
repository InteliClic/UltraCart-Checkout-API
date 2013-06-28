<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    $items = $uc->getCartItems();
    
    echo "<pre>";
    print_r($items);
    echo "</pre>";
    
} catch (Exception $error) {
    echo "<pre>";
    print_r($error);
    echo "</pre>";
}    

/*
?>
    // The way their system handles the REQUEST TYPE PUT|GET will determine if the cart is updated or just pulled.
    
    $cartId = '256BD12AC1F856013F6F9831D1051500';
    
    $cart_url = 'https://secure.ultracart.com/rest/cart/'.$cartId.'?_mid=CLIC';
    
    //        $response = $si->curl->post($method_url, $vars);
    //        $si->response = $response;

    $ch = curl_init($cart_url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $response = curl_exec($ch);
    $cart = json_decode($response);
    
    echo "<pre>";
    print_r($cart);
    echo "</pre>";
 
 */