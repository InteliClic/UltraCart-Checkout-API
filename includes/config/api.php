<?php
    session_start();
    
    // Language Vars
    $lang['ultracart']['api']['responseEmpty'] = 'API reponse empty.';
    $lang['ultracart']['api']['invalidRequest'] = 'API request invalid.';
    $lang['ultracart']['api']['missingCart'] = 'API result did not contain a cart.';
    $lang['ultracart']['cart']['containsErrors'] = 'Cart contains errors.';
    $lang['ultracart']['cart']['notReady'] = 'Cart is not ready.';
    $lang['ultracart']['cart']['empty'] = 'Cart is empty.';
    $lang['ultracart']['cart']['missingParameter'] = 'Missing parameters, please review and try again.';
    $lang['ultracart']['site']['countryEmpty'] = 'Country is required, please try again.';

    // CRM: UltraCart
    $config['ultracart']['server'] = 'https://secure.ultracart.com';
    $config['ultracart']['merchantId'] = 'DEMO';
    $config['ultracart']['login'] = 'DEMO';
    $config['ultracart']['pass'] = 'DoNotRunInBrowser';
    
    // Classes
    $config['base_classes'] = '/server_path_to_includes/classses';
    include_once($config['base_classes'] . '/api.ultracart.checkout.v1.php');
    include_once($config['base_classes'] . '/curl.request.php');
    include_once($config['base_classes'] . '/curl.response.php');
?>
