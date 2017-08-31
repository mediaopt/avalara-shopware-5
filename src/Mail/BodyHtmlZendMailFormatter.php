<?php

namespace Shopware\Plugins\MoptAvalara\Mail;

use Shopware\Models\Mail\Mail;

/**
 * Will update Mail html body based on shipping address attributes and delivery cost
 *
 * @author bubnov
 */
class BodyHtmlZendMailFormatter extends AbstractZendMailFormatter
{
    /**
     * @const string Shipping block in email template
     */
    const AVALARA_DELIVERY_COST_BLOCK = '%s<br/>%s';
    
    /**
     * @const string
     */
    const LINE_BREAK = '<br/>';

    /**
     * Will format \Zend_Mail object
     * @param \Zend_Mail $mail
     * @param \Shopware\Models\Mail\Mail $mailModel
     * @param array $context
     */
    protected function formatMail(\Zend_Mail $mail, Mail $mailModel, $context = []) {
        if (!$mailModel->isHtml()) {
            return;
        }
        $stringCompiler = $this->getStringCompiler();
        $htmlTemplate = $mailModel->getContentHtml();
        $htmlTemplate = $this->addAvalaraDeliveryCost($htmlTemplate, $context);
        
        $html = $stringCompiler->compileString($htmlTemplate);
        $mail->setBodyHtml($html);
    }
}
