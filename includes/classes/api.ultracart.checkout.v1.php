<?php

/**
 * UltraCart Checkout REST API PHP wrapper
 *
 * @package UltraCart_Checkout
 * @author InteliClic <info@inteliclic.com>
**/

function ucCompareItemsById($a, $b) {
    if (strtoupper($a->itemId) == strtoupper($b->itemId)) {
        return 0;
    }
    return (strtoupper($a->itemId) < strtoupper($b->itemId)) ? -1 : 1;
}

class UltraCart_Checkout {

    public $cart = null;
    public $hasCart = false;
    public $hasItems = false;
    public $loggedIn = false;
    public $errors = array();
    public $curl = null;
    public $response = null;
    public $request = null;

    public function __construct() {
        global $config;
        $this->credentials = array('merchantId' => $config['ultracart']['merchantId'], 'login' => $config['ultracart']['login'], 'password' => $config['ultracart']['pass']);
        $this->initialize();
    }
    
    private function initialize() {
        global $config;
        // Set cart fields
        $this->cart = new stdClass();
        $this->cart->merchantId = $this->credentials['merchantId'];
        $this->detectCartId();
        // Lets open our curl class to send the request eficiently
        $this->curl = new Curl;
        $this->curl->options = array('CURLOPT_TIMEOUT' => $config['api_timeout']);
        $this->curl->headers = array('X-UC-Merchant-Id' => $this->credentials['merchantId'], 'cache-control' => 'no-cache');
        // Create the request
        $this->request = new stdClass();
        $this->request->server = $config['ultracart']['server'];
        $this->request->vars = $this->cart;
        $this->request->type = 'get';
        // Lets pull the most recent cart
        $this->getCart();
    }
    
    private function updateItemState() {
        $this->hasItems = $this->hasCart && property_exists($this->cart, 'items') && count($this->cart->items) > 0;
        if ($this->hasItems) {
            usort($this->cart->items, "ucCompareItemsById");
        }
    }

    private function updateLoginState() {
        if ($this->hasCart && property_exists($this->cart, 'loggedIn')) {
            $this->loggedIn = $this->cart->loggedIn;
        }
    }

    public function printRawCart() {
        echo '<pre>';
        echo 'Cart Object:<br />';
        echo "<hr />";
        ob_start();
        var_dump($this->cart);
        $a = ob_get_contents();
        ob_end_clean();
        echo htmlspecialchars($a, ENT_QUOTES);
        echo "<hr />";
        echo '</pre>';
    }

    public function detectCartId() {
        if (isset($_REQUEST['cartId'])) {
            $this->cart->cartId = filter_var($_REQUEST['cartId'], FILTER_SANITIZE_STRING);
        } else if (isset($_SESSION['cartId'])) {
            $this->cart->cartId = filter_var($_SESSION['cartId'], FILTER_SANITIZE_STRING);
        } else if (isset($_COOKIE['cartId'])) {
            $this->cart->cartId = filter_var($_COOKIE['cartId'], FILTER_SANITIZE_STRING);
        }
    }

    public function detectErrors() {
        global $lang;
        if($this->response->headers['Status-Code'] == '100' OR $this->response->headers['Status-Code'] == '200'){
            $response = json_decode($this->response->body);
            if (!empty($this->response->headers['UC-REST-ERROR'])){
                $this->errors = array($this->response->headers['UC-REST-ERROR']);
                throw new Exception($lang['ultracart']['cart']['containsErrors'], 2001);
            } else if(count($response->errors) > 0){
                $this->errors = $response->errors;
                throw new Exception($lang['ultracart']['cart']['containsErrors'], 2001);
            } else if (count($response->errorMessages) > 0) {
                $this->errors = $response->errorMessages;
                throw new Exception($lang['ultracart']['cart']['containsErrors'], 2001);
            }
        } else {
            if($this->curl->error()){
                throw new Exception($this->curl->error());
            } else if(count($this->response->headers) > 0){
                throw new Exception($this->response->headers['Status'], $this->response->headers['Status-Code']);
            } else {
                throw new Exception($lang['ultracart']['api']['responseEmpty'], 2003);
            }
        }
    }
    
    private function doCall(){
        global $lang;
        
        if(!is_null($this->request->method)){
            $url = $this->request->server . $this->request->method;
            switch ($this->request->type) {
                case 'put':
                    $this->curl->headers['Content-Type'] = 'application/json';
                    $this->response = $this->curl->put($url, json_encode($this->request->vars));
                    break;
                case 'post':
                    $this->curl->headers['Content-Type'] = 'application/json';
                    $this->response = $this->curl->post($url, json_encode($this->request->vars));
                    break;
                case 'delete':
                    $this->response = $this->curl->delete($url, json_encode($this->request->vars));
                    break;
                case 'get':
                    $this->response = $this->curl->get($url);
                    break;
                default:
                    throw new Exception($lang['ultracart']['api']['invalidRequest'], 1001);
                    break;
            }
            
            $this->response = $response;
            $this->detectErrors();

        } else {
            throw new Exception($lang['ultracart']['api']['methodEmpty'], 1002);
        }
    }
    
/* 
 * CART
 */
    
    /**
     *  Get Cart object from UltraCart
     *  This function gets the most recent cart object from UltraCart and sets it.
     * 
     *  Alternative Methods for sending the shopping CartID. However as outlined url parameters are prefered.
     *      $this->curl->headers['X-UC-Shopping-Cart-Id'] = $this->cart->cartId;
     *      $cart_url = '?_mid=' . $this->credentials['merchantId'] . '&_cid' . $this->cart->cartId;
     */
    public function getCart(){
        $this->request->type = 'get';
        $this->request->method = '/rest/cart';
        if (!is_null($this->cart->cartId)) 
            $this->request->method .= '/' . $this->cart->cartId;
        $this->doCall();
        $this->setCart();
    }
    
    /**
     *  Update Cart object to UltraCart
     *  This function pushes the most recent cart object to UltraCart.
     */
    public function updateCart(){
        $this->request->vars = $this->cart;
        $this->request->type = 'put';
        $this->request->method = '/rest/cart';
        if (!is_null($this->cart->cartId)) 
            $this->request->method .= '/' . $this->cart->cartId;
        $this->doCall();
        $this->setCart();
    }
    
    /**
     * Set Cart object
     * @global array $lang
     * @return boolean
     * @throws Exception
     */
    private function setCart(){
        global $lang;
        $cart = json_decode($this->response->body);
        $this->hasCart = !is_null($cart);
        if ($this->hasCart) {
            $cart = (array) $cart;
            ksort($cart);
            $this->cart = (object) $cart;
            $cookie = array('expiry' => '', 'path' => '', 'domain' => '');
            setcookie('cartId', $this->cart->cartId, $cookie['cookie_expiry'], $cookie['cookie_path'], $cookie['cookie_domain']);
            $_SESSION['cartId'] = $this->cart->cartId;
            $this->updateItemState();
            $this->updateLoginState();
            return true;
        } else {
            throw new Exception($lang['ultracart']['api']['missingCart'], 100);
        }
    }

    /**
     * Destroy Cart
     * Removes cart object locally and creates a new cart
     * @global type $config
     */
    public function destroyCart() {
        unset($this->cart);
        $this->cart->merchantId = $this->credentials['merchantId'];
        $this->updateCart();
    }
    
/* 
 * CART ITEMS 
 */
    
    /**
     * Add Cart Item
     * @param array $item
     */
    public function addCartItem($item) {
        $this->cart->items = array($item);
        $this->updateCart();
    }

    /**
     * Add Cart Items
     * @param array $items
     * @throws Exception
     */
    public function addCartItems($items) {
        if(count($items) > 0){
            $this->cart->items = $items;
            $this->updateCart();
        }  else {
            throw new Exception($lang['ultracart']['cart']['missingParameter'], 202);
        }
    }
    
    /**
     * Get Cart Item 
     * @param string $itemId
     * @return array
     * @throws Exception
     */
    public function getCartItem($itemId){
        if ($this->hasItem AND !empty($itemId)) {
            foreach ($this->cart->items as $row => $cartItem) {
                if ($cartItem->itemId == $itemId) {
                    return $cartItem;
                }
            }
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }
    
    /**
     * Get Cart Items
     * @return object
     * @throws Exception
     */
    public function getCartItems(){
        if ($this->hasItems) {
            return $this->cart->items;
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }
    
    /**
     * Update Cart Item
     * @param array $item
     * @throws Exception
     */
    public function updateCartItem($item) {
        if ($this->hasItems AND is_array($item)) {
            foreach($this->cart->items as $key => $cartItem){
                if ($cartItem->itemId == $item['itemId']) {
                    $this->cart->items[$key] = (object) array_merge((array) $this->cart->items[$key], $item);
                    break;
                }
            }
            $this->updateCart();
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }

    /**
     * Update Cart Items
     * @param array $items
     * @throws Exception
     */
    public function updateCartItems($items) {
        if ($this->hasItems AND count((array) $items) > 0) {
            foreach ($items as $row => $item) {
                foreach($this->cart->items as $key => $cartItem){
                    if ($cartItem->itemId == $item['itemId']) {
                        $this->cart->items[$key] = (object) array_merge((array) $this->cart->items[$key], $item);
                        break;
                    }
                }
            }
            $this->updateCart();
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }
    
    /**
     * Remove Cart Item
     * @global array $lang
     * @param string $itemId
     * @throws Exception
     */
    public function removeCartItem($itemId) {
        global $lang;
        if ($this->hasItems AND !empty($itemId)) {
            foreach ($this->cart->items as $row => $cartItem) {
                if ($cartItem->itemId == $itemId) {
                    unset($this->cart->items[$row]);
                    break;
                }
            }
            $this->cart->items = array_values((array) $this->cart->items);
            $this->hasItems = (count((array) $this->cart->items) > 0)? true : false;
            $this->updateCart();
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }
    
    /**
     * Clear Cart Items
     * This removes all items from the cart
     */
    public function clearItems() {
        if ($this->hasItems) {
            unset($this->cart->items);
            $this->hasItems = false;
            $this->updateCart();
        }
    }
    
/* 
 * SHIPPING
 */
 
    /**
     * Estimate Shipping
     * @global array $lang
     * @return object
     * @throws Exception
     */
    public function estimateShipping() {
        global $lang;
        if ($this->hasItems) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/estimateShipping';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }
    
    /**
     * Set Shipping 
     * Sets Method and cost in Cart for Shipping.
     */
    public function setShipping() {
        $methods = $this->estimateShipping();
        if(count($methods) > 0){
            foreach($methods as $method){
                if($method->name == $this->cart->shippingMethod){
                    $this->cart->shippingHandling = $method->costBeforeDiscount;
                    $this->cart->shippingHandlingDiscount = $method->discount;
                    $this->cart->shippingHandlingWithDiscount = $method->cost;
                    break;
                }
            }
        }
    }
   
/* 
 * CHECKOUT
 */

    /**
     * Checkout Cart
     * This checkout method returns a checkout URL where you must forward your visitor to.
     * @global array $lang
     * @return object
     * @throws Exception
     */
    public function checkout() {
        global $lang;
        if ($this->hasItems) {
            $request = new stdClass();
            $request->cart = $this->cart;
            $request->errorParameterName = 'error';
            $request->errorReturnUrl = $_SERVER['REQUEST_URI'];
            $request->secureHostName = null;
            $this->request->vars = $request;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/checkout';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }

    /**
     * Finalize Cart
     * This checkout method will complete and return the orderId 
     * @global array $lang
     * @return type
     * @throws Exception
     */
    public function finalizeCart() {
        global $lang;
        if ($this->hasItems) {
            $this->setShipping();
            $options = array();
            $options['noRealtimePaymentProcessing'] = false; // boolean
            $options['skipPaymentProcessing'] = false; // boolean
            $options['autoApprovePurchaseOrder'] = true; // boolean
            $options['storeIfPaymentDeclines'] = true; // boolean
//            $options['creditCardAuthorizationReferenceNumber'] = '1234565'; // string, if CC auth was already done elsewhere
//            $options['creditCardAuthorizationAmount'] = '45.45'; // decimal/float
//            $options['creditCardAuthorizationDate'] = '20130603T170226-0400'; // string, ISO8601 format.
//            $options['channelPartnerOid'] = '54'; // integer, channel partner identifier
//            $options['channelPartnerOrderId'] = 'CP-12345'; // string, the order id the channel partner is using.
//            $options['storeCompleted'] = true; // boolean, if true, the order is marked as 'completed' -- bypassing Accounts Receivable and Shipping
            $request = new stdClass();
            $request->cart = $this->cart;
            $request->credentials = $this->credentials;
            $request->options = $options;
            $this->request->vars = $request;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/finalizeOrder';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }

    /**
     * Validate Cart
     * Runs validations on the cart
     * @global array $lang
     * @param array $checks
     * @throws Exception
     */
    public function validateCart($checks = array()) {
        global $lang;
        if ($this->hasItems) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/validate';
            if (count($checks) > 0) {
                foreach($checks as $check){
                    $vars .= "&check=".rawurlencode($check);
                }
                $vars = ltrim($vars, '&');
                $this->request->method .= '?' . $vars;
            }
            $this->doCall();
            $response = json_decode($this->response->body);
            if(count($response) > 0){
                $this->errors = $response;
                throw new Exception($lang['ultracart']['cart']['containsErrors'], 2001);
            }
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }

    /**
     * Set Finalize After
     * @global array $lang
     * @param int $minutes
     * @return boolean
     * @throws Exception
     */
    public function setFinalizeAfter($minutes) {
        global $lang;
        if ($this->hasItems) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/setFinalizeAfter';
            if(!empty($minutes))
                $this->request->method .= '?minutes='.$minutes;
            $this->doCall();
            $response = json_decode($this->response->body);
            return is_null($response);
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }

    /**
     * Clear Finalize After
     * @global array $lang
     * @return boolean
     * @throws Exception
     */
    public function clearFinalizeAfter() {
        global $lang;
        if ($this->hasItems) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/clearFinalizeAfter';
            $this->doCall();
            $response = json_decode($this->response->body);
            return is_null($response);
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }

/*
 * CUSTOMER MANAGEMENT
 */

    /**
     * Customer Login
     * You must have at least $this->cart->email and $this->cart->password set
     * @global array $lang
     * @return boolean
     * @throws Exception
     */
    public function customerLogin() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/login';
            $this->doCall();
            $this->setCart();
            return true;
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        }
    }

    /**
     * Customer Logout
     * @global array $lang
     * @return boolean
     * @throws Exception
     */
    public function customerLogout() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/logout';
            $this->doCall();
            $this->setCart();
            return true;
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        }
    }

    /**
     * Customer Register
     * @global array $lang
     * @return boolean
     * @throws Exception
     */
    public function customerRegister() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/register';
            $this->doCall();
            $this->setCart();
            return true;
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        }
    }

    /**
     * Customer Reset Password
     * @global array $lang
     * @return boolean
     * @throws Exception
     */
    public function customerResetPassword() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/logout';
            $this->doCall();
            return (strtolower($this->response->body) == 'success')?true:false;
        } else {
            throw new Exception($lang['ultracart']['cartNotReady'], 2000);
        }
    }
    
/*
 * MARKETING
 */
    
    /**
     * Subscribe Email to Auto Responder
     * The name of the auto responder should be one of the following:
     * icontact, madmimi, silverpop, mailchimp, lyris, lyrishq, campaignMonitor, getResponse
     * @global array $lang
     * @param array $vars
     * @throws Exception
     */
    public function subscribeToAutoResponder($vars) {
        global $lang;
        if ($this->hasItems) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/subscribeToAutoResponder';
            if (count($vars['lists']) > 0) {
                foreach($vars['lists'] as $list){
                    $lists .= "&lists=".rawurlencode($list);
                }
                $vars = "autoResponderName=" . $vars['autoResponderName'] . $lists;
                $this->request->method .= '?' . $vars;
            }
            $this->doCall();
            $this->setCart();
        } else {
            throw new Exception($lang['ultracart']['cart']['empty'], 2000);
        }
    }
    
    /**
     * Apply Coupon
     * @param string $coupon
     */
    public function applyCoupon($coupon){
        global $lang;
        if (!empty($coupon)) {
            $this->cart->coupons = array(array('couponCode' => $coupon));
            $this->updateCart();
        } else {
            throw new Exception($lang['ultracart']['cart']['missingParameter'], 2000);
        }
    }
    
    /**
     * Remove Coupon
     * @param string $coupon
     */
    public function removeCoupon($coupon){
        if (count($this->cart->coupons) > 0) {
            foreach($this->cart->coupons as $k => $coupon){
                if($coupon->couponCode == $coupon){
                    unset($this->cart->coupons[$k]);
                    break;
                }
            }
            
            $this->cart->coupons = array_values((array) $this->cart->coupons);
            $this->updateCart();
        }
    }
    
    /**
     * Apply Coupons
     * @param array $coupons
     */
    public function applyCoupons($coupons){
        if (is_array($coupons)) {
            $this->cart->coupons = $coupons;
            $this->updateCart();
        } else {
            throw new Exception($lang['ultracart']['cart']['missingParameter'], 2000);
        }
    }
    
    /**
     * Remove Coupons
     */
    public function removeCoupons(){
        if ($this->hasCart) {
            unset($this->cart->coupons);
            $this->updateCart();
        }
    }
    
    /**
     * Get Cart Coupons
     * @return array
     */
    public function getCartCoupons(){
        return $this->cart->coupons;
    }
    
    /**
     * Gift Settings
     * @global array $lang
     * @return type
     * @throws Exception
     */
    public function giftSettings() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/giftSettings';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        } 
    }
    
    /**
     * Apply Gift Certificate
     * @global array $lang
     * @param string $giftCertificate
     * @throws Exception
     */
    public function applyGiftCertificate($giftCertificate){
        global $lang;
        if (!empty($giftCertificate)) {
            if($this->validateGiftCertificate($giftCertificate)){
                $this->cart->giftCertificate = array('couponCode' => $coupon);
                $this->updateCart();
            } else {
                throw new Exception($lang['ultracart']['cart']['containsErrors'], 2001);
            }
        } else {
            throw new Exception($lang['ultracart']['cart']['missingParameter'], 2000);
        }
    }

    /**
     * Remove Gift Certificate
     */
    public function removeGiftCertificate(){
        unset($this->cart->giftCertificate);
        $this->updateCart();
    }
    
    /**
     * Validate Gift Certificate
     * @global array $lang
     * @param type $giftCertificate
     * @return type
     * @throws Exception
     */
    public function validateGiftCertificate($giftCertificate) {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/validateGiftCertificate';
            $this->request->method = '?giftCertficiate='.rawurlencode($giftCertificate);
            $this->doCall();
            if(is_null($this->response->body)){
                return true;
            } else {
                $this->errors = json_decode($this->response->body);
                return false;
            }
            return ;
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        } 
    }
    
/*
 * CONTENT
 */
    
    /**
     * Checkout Terms
     * @global array $lang
     * @return object
     * @throws Exception
     */
    public function checkoutTerms() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/checkoutTerms';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        }
    }

    /**
     * Related Items
     * @global array $lang
     * @return object
     * @throws Exception
     */
    public function relatedItems() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/relatedItems';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        }
    }

    /**
     * Tax Countries
     * @global array $lang
     * @return object
     * @throws Exception
     */
    public function taxCounties() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/taxCounties';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        }
    }

    /**
     * City State
     * Compares to the zip to the city and state. If they don't match, it returns back the correct 
     * city and state.
     * @global array $lang
     * @return object
     * @throws Exception
     */
    public function cityState() {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/cityState';
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        } 
    }
    
    /**
     * Host Link
     * This call is useful for sites with multiple urls. This call links them all together on the back end.
     * @global array $lang
     * @param string $secureHostName
     * @return object
     * @throws Exception
     */
    public function hostLink($secureHostName) {
        global $lang;
        if ($this->hasCart) {
            $this->request->vars = $this->cart;
            $this->request->type = 'post';
            $this->request->method = '/rest/cart/hostLink';
            $this->request->method = '?secureHostName='.rawurlencode($secureHostName);
            $this->doCall();
            return json_decode($this->response->body);
        } else {
            throw new Exception($lang['ultracart']['cart']['notReady'], 2000);
        } 
    }
    
}

?>