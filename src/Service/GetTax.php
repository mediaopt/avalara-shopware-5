<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\TransactionBuilder;
use Avalara\TransactionAddressType;
use Avalara\DocumentType;
use Shopware\Plugins\MoptAvalara\Model\GetTaxRequest;

/**
 * Description of GetTax
 *
 */
class GetTax extends AbstractService
{
    /**
     * 
     * @param GetTaxRequest $taxRequest
     * @return \stdClass
     */
    public function calculate(GetTaxRequest $taxRequest)
    {
        $client = $this->getAdapter()->getClient();
        /* @var $originAddress \Shopware\Plugins\MoptAvalara\Model\Address */
        $originAddress = $this->getAdapter()->getFactory('Address')->buildOriginAddress();
        /* @var $address \Shopware\Plugins\MoptAvalara\Model\Address */
        $address = $taxRequest->getAddresses()[0];
        $tb = new TransactionBuilder($client, null, $taxRequest->getDocType(), $taxRequest->getCustomerCode());
        $tb
            ->withAddress(
                TransactionAddressType::C_SHIPFROM, 
                $originAddress->line1, 
                null, 
                null, 
                $originAddress->city, 
                $originAddress->region,
                $originAddress->postalCode, 
                $originAddress->country
            )
            ->withAddress(
                TransactionAddressType::C_SHIPTO, 
                $address->line1, 
                null, 
                null, 
                $address->city, 
                $address->region,
                $address->postalCode, 
                $address->country
            )
        ;
        
        foreach($taxRequest->getLines() as $line) {
            /* @var $line \Shopware\Plugins\MoptAvalara\Model\Line */
            $tb
                ->withLine($line->getAmount(), $line->getQty(), $line->getTaxCode(), $line->getItemCode())
                ->withLineParameter('itemCode', $line->getItemCode())
            ;
        }
        
        $response = $tb->create();
        $this->fixItemCodes($taxRequest, $response);

        return $response;
    }
    
    /**
     * There is a bug in Avalara API.
     * If we use type SalesOrder and provide itemCode - we got empty itemCode in response.
     * We are hoping that Avalara whould return lines in the same order.
     * 
     * @param GetTaxRequest $taxRequest
     * @param \stdClass $taxInformation
     */
    private function fixItemCodes(GetTaxRequest $taxRequest, \stdClass $taxInformation)
    {
        if (DocumentType::C_SALESINVOICE === $taxRequest->getDocType()) {
            return;
        }
        
        foreach($taxRequest->getLines() as $i => $line) {
            $lineNo = $i+1;
            /* @var $line \Shopware\Plugins\MoptAvalara\Model\Line */
            foreach ($taxInformation->lines as $taxLineInformation) {
                if (empty($taxLineInformation->itemCode) && $lineNo == $taxLineInformation->lineNumber) {
                    $taxLineInformation->itemCode = $line->getItemCode();
                }
            }
        }
    }
}
