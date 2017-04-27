<?php
# http://avalara.local/shopware/ce436/engine/Shopware/Plugins/Community/Backend/MoptAvalara/Scripts/test.php
set_time_limit(0);
require realpath(__DIR__ . '/../../../../../../../') . '/autoload.php';

$env = 'development';

$kernel = new Shopware\Kernel($env, false);
$kernel->boot();

if (version_compare(\Shopware::VERSION, '4.3.0', '>=')) {
    //init http request object for sAdmin::sSaveRegister - see shopware.php & \Shopware\Kernel::handle()
    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request = $kernel->transformSymfonyRequestToEnlightRequest($request);
    $front = $kernel->getContainer()->get('front');
    $front->setRequest($request);
}

//init plugin (for autoloading)
Shopware()->Plugins()->Backend()->MoptAvalara();

$sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');
try {
    $addressData = array();
    $addressData['City'] = 'Washingtooon';
    $addressData['Country'] = \Mediaopt\Avalara\Sdk\Model\Address::COUNTRY_CODE__US;
    $addressData['Line1'] = 'White House';
    $addressData['Line2'] = '1600 Pennsylvania Ave NW';
    $addressData['PostalCode'] = 20500;
    $addressData['Region'] = 'DC';

    $response = $sdkMain->getService('validateAddress')->validate($addressData);
    if(empty($response['ResultCode']) || $response['ResultCode'] != 'Success') {
        echo 'Validation failed !';
        exit;
    }
    
    //compare address fields
    foreach ($addressData as $key => $value) {
        $sdkMain->getLogger()->info("".$key." : ".$value);
        echo 'compare ' . $key . ': ' . $value . ' == ' . $response['Address'][$key]  . ' => ' . 
                ($value == $response['Address'][$key] ? 'true' : 'false') . '<br />';
    }
    echo 'Validation successful !';
    
} catch (Exception $e) {
    echo 'Validation failed due to technical reasons!';
}