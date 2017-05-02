<?php

namespace Shopware\Plugins\MoptAvalara\Model;

/**
 * Description of DocumentType
 *
 */
class DocumentType extends AbstractModel
{
    const SALES_ORDER = 'SalesOrder';
    const SALES_INVOICE = 'SalesInvoice';
    const PURCHASE_ORDER = 'PurchaseOrder';
    const PURCHASE_INVOICE = 'PurchaseInvoice';
    const RETURN_ORDER = 'ReturnOrder';
    const RETURN_INVOICE = 'ReturnInvoice';
}
