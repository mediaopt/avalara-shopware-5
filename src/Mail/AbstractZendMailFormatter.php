<?php

namespace Shopware\Plugins\MoptAvalara\Mail;

use Shopware\Models\Mail\Mail;
use Shopware_Plugins_Backend_MoptAvalara_Bootstrap as AvalaraBootstrap;

/**
 * This class will update Mail body based on shipping address attributes and delivery cost
 *
 * @author derksen mediaopt gmbh
 */
abstract class AbstractZendMailFormatter
{
    /**
     * @var string Shipping tag in email template
     */
    const SHIPPING_COST_TAG = '{$sShippingCosts}';
   
    /**
     * @var \Shopware_Components_TemplateMail
     */
    protected $templateMailService;
    
    /**
     * @var \Shopware_Components_Config
     */
    private $config;
    
    /**
     * @param \Shopware_Components_TemplateMail $templateMailService
     * @param \Shopware_Components_Config $config
     */
    public function __construct(\Shopware_Components_TemplateMail $templateMailService, \Shopware_Components_Config $config) {
        $this->templateMailService = $templateMailService;
        $this->config = $config;
    }
    
    /**
     * Will format \Zend_Mail object with a template
     * @param \Zend_Mail $mail
     * @param string $compiledTemplate
     */
    abstract protected function formatMail(\Zend_Mail $mail, $compiledTemplate);

    /**
     * @param Mail $mailModel
     * @return string
     */
    abstract protected function getMailTemplate(Mail $mailModel);
    
    /**
     * @param \Zend_Mail $mail
     * @param array $context
     */
    public function updateMail(\Zend_Mail $mail, $context = [])
    {
        $mailModel = $this->getMailModel();        
        $template = $this->getMailTemplate($mailModel);
        $templateUpdated = $this->addAvalaraDeliveryCost($template, $context);
        
        $stringCompiler = $this->getStringCompiler();
        $stringCompiler->setContext($this->getCombinedContext($context));
        $compiledTemplate = $stringCompiler->compileString($templateUpdated);
        
        $this->formatMail($mail, $compiledTemplate);
    }
    
    /**
     * @param string $modelName
     * @return \Shopware\Models\Mail\Mail
     * @throws \Enlight_Exception
     */
    protected function getMailModel($modelName = 'sORDER') {
        /* @var $mailModel \Shopware\Models\Mail\Mail */
        $mailModel = $this
            ->templateMailService
            ->getModelManager()
            ->getRepository('Shopware\Models\Mail\Mail')
            ->findOneBy(['name' => $modelName])
        ;
        
        if (!$mailModel) {
            throw new \Enlight_Exception("Mail-Template with name '{$modelName}' could not be found.");
        }
        
        $isoCode = $this->getShop()->get('isocode');
        $translation = $this
            ->templateMailService
            ->getTranslationReader()
            ->read($isoCode, 'config_mails', $mailModel->getId())
        ;
        $mailModel->setTranslation($translation);
        
        return $mailModel;
    }
    
    /**
     * 
     * @param array $context
     * @return array
     */
    protected function getCombinedContext($context = [])
    {
        $defaultContext = [
            'sConfig' => $this->config,
        ];

        if ($this->getShop() !== null) {
            $defaultContext = [
                'sConfig' => $this->config,
                'sShop' => $this->config->get('shopName'),
                'sShopURL' => $this->getShopURL(),
            ];
        }
        
        return array_merge($defaultContext, $context);
    }
    
    /**
     * 
     * @return string
     */
    private function getShopURL()
    {
        return $this->getShop()->getAlwaysSecure()
            ? 'https://' . $this->getShop()->getSecureHost() . $this->getShop()->getSecureBasePath()
            : 'http://' . $this->getShop()->getHost() . $this->getShop()->getBasePath()
        ;
    }
    
    /**
     * 
     * @return \Shopware\Models\Shop\Shop
     */
    protected function getShop()
    {
        return $this->templateMailService->getShop();
    }
    
    /**
     * 
     * @return \Shopware_Components_StringCompiler
     */
    protected function getStringCompiler()
    {
        return $this->templateMailService->getStringCompiler();
    }
    
    /**
     * 
     * @param string $snippet
     * @return string
     */
    protected function translateSnippet($snippet)
    {
        return Shopware()
            ->Container()
            ->get('snippets')
            ->getNamespace(AvalaraBootstrap::SNIPPETS_NAMESPACE)
            ->get($snippet)
        ;
    }
    
    /**
     * This method will add a delivery surcharge to existing shipping info
     * @param string $template
     * @param array $context
     * @return string
     */
    protected function addAvalaraDeliveryCost($template, $context = [])
    {
        if ($context['moptAvalaraShippingCostSurcharge'] <= 0) {
            return $template;
        }
        
        $surcharges = [];
        $surcharges[] = $this->getLandedCostSurcharge($context);
        $surcharges[] = $this->getIncuranceSurcharge($context);
        $potentialSurcharges = array_filter($surcharges);
        
        $shippingInfo = sprintf(
            static::AVALARA_DELIVERY_COST_BLOCK, 
            self::SHIPPING_COST_TAG, 
            implode(static::LINE_BREAK, $potentialSurcharges)
        );

        return str_replace(self::SHIPPING_COST_TAG, $shippingInfo, $template);
    }
    
    /**
     * 
     * @param float $value
     * @return string
     */
    private function formatToPriceValue($value) {
        return number_format($value, 2 , ',', '');
    }
    
    /**
     * 
     * @param array $context
     * @return string
     */
    private function getLandedCostSurcharge($context = []) {
        if (empty($context['moptAvalaraLandedCost'])) {
            return '';
        }
        
        return $this->translateSnippet('landedCost') . ': ' . $this->formatToPriceValue($context['moptAvalaraLandedCost']) . ' ' . $context['sCurrency'];
    }
    
    /**
     * 
     * @param array $context
     * @return string
     */
    private function getIncuranceSurcharge($context = []) {
        if (empty($context['moptAvalaraInsuranceCost'])) {
            return '';
        }
        
        return $this->translateSnippet('insurance') . ': ' . $this->formatToPriceValue($context['moptAvalaraInsuranceCost']) . ' ' . $context['sCurrency'];
    }
}
