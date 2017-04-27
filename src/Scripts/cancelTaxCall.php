<?php
# http://avalara.local/shopware/ce436/engine/Shopware/Plugins/Community/Backend/MoptAvalara/Scripts/getTaxCall.php
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
    $orderData['DocCode'] = 'DOC21345';
    $avalaraData['DocType'] = 'SalesInvoice';
    $response = $sdkMain->getService('cancelTax')->cancelTax($orderData, $avalaraData);
    //compare address fields
    
} catch (Exception $e) {
    echo 'Validation failed due to technical reasons!';
    echo $e->getMessage();
}