<?php
try {
    
    include_once('../includes/config/api.php');
    $uc = new UltraCart_Checkout();
    
    $uc->cart->ipAddress = $_SERVER['REMOTE_ADDR'];
    $uc->cart->paymentMethod = 'Credit Card';
    $uc->cart->updateShippingOnAddressChange = true;
    $uc->cart->leastCostRoute = true;
    $uc->cart->shippingMethod = 'USPS: First Class';
    $uc->cart->needShipping = true;
    $uc->cart->page = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
    $uc->cart->shipToFirstName = 'Test';
    $uc->cart->shipToLastName = 'Test';
    $uc->cart->shipToAddress1 = '123 Fake St';
    $uc->cart->shipToAddress2 = '';
    $uc->cart->shipToCity = 'Denver';
    $uc->cart->shipToState = 'AZ';
    $uc->cart->shipToPostalCode = '80202';
    $uc->cart->shipToCountry = 'US';
    $uc->cart->shipToPhone = '123-123-1234';
    $uc->cart->email = 'test@text.com';
    $uc->cart->billToFirstName = 'Test';
    $uc->cart->billToLastName = 'Test';
    $uc->cart->billToAddress1 = '123 Fake St';
    $uc->cart->billToAddress2 = '';
    $uc->cart->billToCity = 'Denver';
    $uc->cart->billToState = 'AZ';
    $uc->cart->billToPostalCode = '80202';
    $uc->cart->billToCountry = 'US';
    $uc->cart->billToPhone = '123-123-1234';
    // $uc->cart->creditCardNumber = '4012888888881881';
    $uc->cart->creditCardExpirationMonth = '02';
    $uc->cart->creditCardExpirationYear = '2015';
    $uc->cart->creditCardVerificationNumber = '123';
    $uc->cart->creditCardType = 'visa';
    
    $uc->updateCart();

    $items = array();
    $item = (object) array('itemId' => 'BASEBALL', 'quantity' => '1');
    $items[] = $item;

    $uc->addCartItems($items);
    
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

