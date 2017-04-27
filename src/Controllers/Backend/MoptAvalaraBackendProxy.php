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
        $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');
        try {
            $response = $sdkMain->getService('testConnection')->test();
            if (empty($response['ResultCode']) || $response['ResultCode'] != 'Success') {
                $result['error'] = 'Unknown error';
            } else {
                $result['info'] = 'Connection test successful.';
            }
        } catch (Exception $e) {
            $result['error'] = 'Connection test failed: please check your credentials.';
        }
        $response = $this->Response();
        $response->setBody(json_encode($result));
        $response->sendResponse();
        exit;
    }
}
