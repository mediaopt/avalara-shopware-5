<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\VoidTransactionModel;
use Avalara\VoidReasonCode;
use Avalara\DocumentType;
use Shopware\Models\Order\Order;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
class CancelTax extends AbstractService
{
    /**
     *
     * @param Order $order
     * @throws \RuntimeException
     */
    public function cancel(Order $order)
    {
        $adapter = $this->getAdapter();
        try {
            if (!$attr = $order->getAttribute()) {
                throw new \RuntimeException('Cannot cancel an order without attributes.');
            }
            
            $docCode = $attr->getMoptAvalaraDocCode();
            $transactionType = $attr->getMoptAvalaraTransactionType();
            
            if (DocumentType::C_SALESINVOICE !== $transactionType) {
                throw new \RuntimeException('Cannot cancel uncommitted transaction.');
            }
            
            if (empty($docCode)) {
                throw new \RuntimeException('Cannot cancel transaction with empty DocCode');
            }
            $client = $this->getAdapter()->getAvaTaxClient();
            $companyCode = $this->getAdapter()->getPluginConfig(Form::COMPANY_CODE_FIELD);

            $model = new VoidTransactionModel();
            $model->code = VoidReasonCode::C_DOCVOIDED;
            if (!$response = $client->voidTransaction($companyCode, $docCode, $model)) {
                throw new \RuntimeException('Empty response from Avalara on void transaction ' . $docCode);
            }

            $order
                ->getAttribute()
                ->setMoptAvalaraTransactionType(VoidReasonCode::C_DOCVOIDED)
            ;
            Shopware()->Models()->persist($order);
            Shopware()->Models()->flush();

            $adapter
                ->getLogger()
                ->info('Order with docCode: ' . $docCode . ' has been voided')
            ;

        } catch (\Exception $e) {
            $adapter->getLogger()->error('CancelTax call failed: ' . $e->getMessage());
            throw new \RuntimeException('Avalara: Cancel Tax call failed: ' . $e->getMessage());
        }
    }
}
