<?php

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
        $client = $this->getAvalaraSDKClient();
        try {
            /* @var $pingResponse \Avalara\PingResultModel */
            $pingResponse = $client->ping();

            if (!($pingResponse instanceof \stdClass)) {
                throw new \Exception('Connection test failed: unknown error.');
            }
            if (empty($pingResponse->authenticated)) {
                throw new \Exception('Connection test failed: please check your credentials.');
            }
            
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
     * 
     * @return \Avalara\AvaTaxClient
     */
    private function getAvalaraSDKClient()
    {
        /* @var $avalaraSdkAdapter \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface */
        $avalaraSdkAdapter = Shopware()->Container()->get('AvalaraSdkAdapter');
        
        return $avalaraSdkAdapter->getClient();
    }
}
