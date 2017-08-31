<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Service;

use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
class CommitTax extends AbstractService
{
    /**
     *
     * @var \Shopware\Plugins\MoptAvalara\Service\GetTax 
     */
    private $getTaxService;
    
    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
        $this->getTaxService = $adapter->getService('GetTax');
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @return boolean
     */
    public function commitOrder(\Shopware\Models\Order\Order $order)
    {
        $adapter = $this->getAdapter();
        try {
            $docCommitEnabled = $adapter->getPluginConfig(Form::DOC_COMMIT_ENABLED_FIELD);
            if (!$docCommitEnabled) {
                throw new \RuntimeException('Doc commit is not enabled.');
            }
            
            if (!$attr = $order->getAttribute()) {
                throw new \RuntimeException('Order has no attributes.');
            }
            
            if ($attr->getMoptAvalaraDocCode()) {
                throw new \RuntimeException('Order has already been commited to Avalara.');
            }
            
            $model = $adapter
                ->getFactory('InvoiceTransactionModelFactory')
                ->build($order)
            ;

            $taxResult = $this->getTaxService->calculate($model);
            if (!$taxResult->code) {
                 throw new \RuntimeException('No docCode on order commiting to Avalara.');
            }
            $this->updateOrderAttributes($order, $taxResult);
            $adapter->getLogger()->info('Order ' . $order->getId() . ' has been commited with docCode: ' . $taxResult->code);
        } catch (\Exception $e) {
            $adapter->getLogger()->error('Commiting order to Avalara failed: '. $e->getMessage());
            throw new \RuntimeException('Avalara: Update order call failed: ' . $e->getMessage());
        }
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @param \stdClass $taxResult
     * @return boolean
     */
    protected function updateOrderAttributes(\Shopware\Models\Order\Order $order, $taxResult)
    {
        $attr = $order->getAttribute();
        $attr->setMoptAvalaraDocCode($taxResult->code);
        $attr->setMoptAvalaraTransactionType(\Avalara\DocumentType::C_SALESINVOICE);
        
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Detail $detail
     * @param \stdClass $taxResult
     * @return float
     * @throws \Exception
     */
    protected function getTaxRateForOrderDetail(\Shopware\Models\Order\Detail $detail, $taxResult)
    {
        $taxRate = $this->getTaxService->getTaxRateForOrderBasketId($taxResult, $detail->getId());
        if (!$taxRate) {
            throw new \Exception('Avalara: no tax information found for line ' . $detail->getId());
        }
        
        return $taxRate;
        
    }
}
