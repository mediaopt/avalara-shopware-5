<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\VoidTransactionModel;
use Avalara\VoidReasonCode;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;

/**
 * Description of CancelTax
 *
 */
class CancelTax extends AbstractService
{
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @return \stdClass
     */
    public function cancel(\Shopware\Models\Order\Order $order)
    {
        $adapter = $this->getAdapter();
        try {
            $docCode = $order->getAttribute()->getMoptAvalaraDocCode();
            if (empty($docCode)) {
                throw new \Exception('Cannot cancel transaction with empty DocCode');
            }
            $client = $this->getAdapter()->getAvaTaxClient();
            $companyCode = $this->getAdapter()->getPluginConfig(Form::COMPANY_CODE_FIELD);

            $model = new VoidTransactionModel();
            $model->code = VoidReasonCode::C_DOCVOIDED;
            if (!$response = $client->voidTransaction($companyCode, $docCode, $model)) {
                throw new \Exception('Empty response from Avalara on void transaction ' . $docCode);
            }

            $order
                ->getAttribute()
                ->setMoptAvalaraTransactionType(\Avalara\VoidReasonCode::C_DOCVOIDED)
            ;
            Shopware()->Models()->persist($order);
            Shopware()->Models()->flush();

            $adapter
                ->getLogger()
                ->info('Order with docCode: ' . $docCode . ' has been voided')
            ;

        } catch (\Exception $e) {
            $adapter->getLogger()->error('CancelTax call failed: ' . $e->getMessage());
            throw new \Exception('Avalara: Cancel Tax call failed: ' . $e->getMessage());
        }
    }
}
