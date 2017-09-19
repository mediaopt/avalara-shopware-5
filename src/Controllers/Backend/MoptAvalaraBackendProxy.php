<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

use Shopware\Plugins\MoptAvalara\Bootstrap\Form;

/**
 * @extends Shopware_Controllers_Backend_ExtJs
 * @author derksen mediaopt GmbH
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

            if (!is_object($pingResponse)) {
                throw new \RuntimeException('Connection test failed: unknown error.');
            }
            if (empty($pingResponse->authenticated)) {
                throw new \RuntimeException('Connection test failed: please check your credentials.');
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
     *
     * @throws \Exception
     */
    private function testAvaTax()
    {
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        $fileName = $service->isLandedCostEnabled()
            ? 'testTaxAndLandedCostRequest.json'
            : 'testTaxRequest.json'
        ;
        $model = $this->getMockModel($fileName);
        $response = $adapter->getAvaTaxClient()->createTransaction(null, $model);

        if (is_string($response)) {
            $lastResponse = $adapter->getLogSubscriber()->getLastResponseWithError();
            if (!$errorData = json_decode($lastResponse->getBody(), true)) {
                throw new RuntimeException('Unknown error with AvalaraTax API');
            }
            
            if (isset($errorData['error']['message'])) {
                throw new RuntimeException($errorData['error']['message']);
            }
        }
    }
    
    /**
     *
     * @param string $fileName
     * @return \stdClass
     * @throws \Exception
     */
    private function getMockModel($fileName)
    {
        $this->getAdapter()->getBootstrap();
        $filePath = $this->getAdapter()->getBootstrap()->Path() . 'Data/' . $fileName;
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Wrong data file: ' . $filePath);
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
            ->getPluginConfig(Form::COMPANY_CODE_FIELD)
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
