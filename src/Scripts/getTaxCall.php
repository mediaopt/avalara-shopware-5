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
$service = \Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter::SERVICE_NAME;
$adapter = Shopware()->Container()->get($service);
try {
    $address1 = array();
    $address1['AddressCode'] = '01';
    $address1['City'] = 'Washingtooon';
    $address1['Country'] = \Shopware\Plugins\MoptAvalara\Adapter\Factory\AddressFactory::COUNTRY_CODE__US;
    $address1['Line1'] = 'White House';
    $address1['Line2'] = '1600 Pennsylvania Ave NW';
    $address1['PostalCode'] = 20500;
    $address1['Region'] = 'DC';
    $address2['AddressCode'] = '02';
    $address2['City'] = 'Washington';
    $address2['Country'] = \Shopware\Plugins\MoptAvalara\Adapter\Factory\AddressFactory::COUNTRY_CODE__US;
    $address2['Line1'] = 'White House';
    $address2['Line2'] = '1600 Pennsylvania Ave NW';
    $address2['PostalCode'] = 20500;
    $address2['Region'] = 'DC';
    $addressData[]=$address1;
    $addressData[]=$address2;
    $line1 = array();
    $line2 = array();
    $line1 ['LineNo'] = '01';
    $line1 ['ItemCode'] = 'N543';
    $line1 ['Qty'] = '1';
    $line1 ['Amount'] = '10';
    $line1 ['OriginCode'] = '01';
    $line1 ['DestinationCode'] = '01';
    $line1 ['Description'] = 'Red Size 7 Widget';
    $line1 ['TaxCode'] = 'NT';
    $line1 ['CustomerUsageType'] = 'L';
    $line1 ['Discounted'] = 'true';
    $line1 ['TaxIncluded'] = 'true';
    $line1 ['Ref1'] = 'ref123';
    $line1 ['Ref2'] = 'ref456';
    $line2['LineNo'] = '02';
    $line2['ItemCode'] = 'N544';
    $line2['Qty'] = '2';
    $line2['Amount'] = '10';
    $line2['OriginCode'] = '01';
    $line2['DestinationCode'] = '01';
    $line2['Description'] = 'Red Size 7 Widget';
    $line2['TaxCode'] = 'NT';
    $line2['CustomerUsageType'] = 'L';
    $line2['Discounted'] = 'true';
    $line2['TaxIncluded'] = 'true';
    $line2['Ref1'] = 'ref123';
    $line2['Ref2'] = 'ref456';
    $lineData[]=$line1;
    $lineData[]=$line2;
    $userData['CustomerCode'] = '12345';
    $orderData['DocDate'] = '2015-04-23';
    $orderData['DocCode'] = 'DOC33333';
    $avalaraData['DocType'] = 'SalesInvoice';
    $avalaraData['Commit'] = 'true';
    $response = $adapter->getService('getTax')->getTax($userData,$addressData,$lineData,$orderData,$avalaraData);
    if(empty($response['ResultCode']) || $response['ResultCode'] != 'Success') {
        echo 'Validation failed !';
        exit;
    }
    echo 'GETTAX CALL WORKS';
    //compare address fields
    
} catch (Exception $e) {
    echo 'Validation failed due to technical reasons!';
    echo $e->getMessage();
}