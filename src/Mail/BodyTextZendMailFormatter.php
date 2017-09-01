<?php

namespace Shopware\Plugins\MoptAvalara\Mail;

use Shopware\Models\Mail\Mail;

/**
 * This class will update Mail text body based on shipping address attributes and delivery cost
 *
 * @author derksen mediaopt gmbh
 */
class BodyTextZendMailFormatter extends AbstractZendMailFormatter
{
    /**
     * @var string Shipping block in email template
     */
    const AVALARA_DELIVERY_COST_BLOCK = "%s\n%s";

    /**
     * @var string
     */
    const LINE_BREAK = "\n";
    
    /**
     * Will format \Zend_Mail object with a template
     * @param \Zend_Mail $mail
     * @param string $compiledTemplate
     */
    protected function formatMail(\Zend_Mail $mail, $compiledTemplate)
    {
        $mail->setBodyText($compiledTemplate);
    }
    
    /**
     * @param Mail $mailModel
     * @return string
     */
    protected function getMailTemplate(Mail $mailModel)
    {
        return $mailModel->getContent();
    }
}
