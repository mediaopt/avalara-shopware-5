<?php

use Shopware\Plugins\MoptAvalara\Form\FormCreator;

/**
 * $Id: $
 */
class Shopware_Controllers_Backend_MoptAvalaraBackendProxy extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Connection Test with Avalara API
     */
    public function getConnectionTestAction()
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        try {
            /* @var $pingResponse \Avalara\PingResultModel */
            $pingResponse = $client->ping();

            if (!($pingResponse instanceof \stdClass)) {
                throw new \Exception('Connection test failed: unknown error.');
            }
            if (empty($pingResponse->authenticated)) {
                throw new \Exception('Connection test failed: please check your credentials.');
            }

            $this->testAvaTax();
            
            $result['info'] = 'Connection test successful.';
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        $response = $this->Response();
        $response->setBody(json_encode($result));
        $response->sendResponse();
        
        exit;
    }
    
    /**
     * Make a test request to AvaTax API with or without landed cost
     */
    private function testAvaTax()
    {
        $adapter = $this->getAdapter();
        
        $fileName = ($adapter->getPluginConfig(FormCreator::LANDEDCOST_ENABLED_FIELD))
            ? 'testTaxAndLandedCostRequest.json'
            : 'testTaxRequest.json'
        ;
        $model = $this->loadMockData($fileName);
        $client = $adapter->getAvaTaxClient();
        
        $response = $adapter->getAvaTaxClient()->createTransaction(null, $model);

        if (is_string($response)) {
            $lastResponse = $adapter->getLogSubscriber()->getLastResponseWithError();
            if (!$errorData = json_decode($lastResponse->getBody(), true)) {
                throw new Exception('Unknown error with AvalaraTax API');
            }
            
            if (isset($errorData['error']) && isset($errorData['error']['message'])) {
                throw new Exception($errorData['error']['message']);
            }
        }
    }
    
    /**
     *
     * @param string $fileName
     * @return \stdClass
     * @throws \Exception
     */
    private function loadMockData($fileName)
    {
        $this->getAdapter()->getBootstrap();
        $filePath = $this->getAdapter()->getBootstrap()->Path() . 'Data/' . $fileName;
        if (!file_exists($filePath)) {
            throw new \Exception('Wrong data file: ' . $filePath);
        }
        $json = str_replace('%companyCode%', $this->getCompanyCode(), file_get_contents($filePath));
        
        return json_decode($json, true);
    }
    
    /**
     *
     * @return string
     */
    protected function getCompanyCode()
    {
        return $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::COMPANY_CODE_FIELD)
        ;
    }

    /**
     *
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    private function getAdapter()
    {
        $serviceName = \Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter::SERVICE_NAME;
        
        return Shopware()->Container()->get($serviceName);
    }
}
