<?php

namespace Shopware\Plugins\MoptAvalara\Mail;

use Shopware\Models\Mail\Mail;

/**
 * Will update Mail html body based on shipping address attributes and delivery cost
 *
 * @author bubnov
 */
class BodyTextZendMailFormatter extends AbstractZendMailFormatter
{
    /**
     * @const string Shipping block in email template
     */
    const AVALARA_DELIVERY_COST_BLOCK = "%s\n%s";

    /**
     * @const string
     */
    const LINE_BREAK = "\n";
    
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
        $textTemplate = $mailModel->getContent();
        $textTemplate = $this->addAvalaraDeliveryCost($textTemplate, $context);
        
        $text = $stringCompiler->compileString($textTemplate);
        $mail->setBodyText($text);
    }
}
