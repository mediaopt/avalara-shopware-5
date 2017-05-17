<?php

namespace Shopware\Plugins\MoptAvalara\Form;

/**
 * Description of formCreator
 *
 * @author bubnov
 */
class FormCreator
{
    
    /**
     * Field names
     */
    const LOGGER_DEFAULT_ROTATING_DAYS = 7;
    
    const LOG_FILE_NAME = 'mo_avalara';

    const LOG_FILE_EXT = '.log';
    
    const IS_LIVE_MODE_FIELD = 'mopt_avalara__is_live_mode';
    
    const ACCOUNT_NUMBER_FIELD = 'mopt_avalara__account_number';
    
    const LICENSE_KEY_FIELD = 'mopt_avalara__license_key';
    
    const COMPANY_CODE_FIELD = 'mopt_avalara__company_code';
    
    const TAX_ENABLED_FIELD = 'mopt_avalara__tax_enabled';
    
    const DOC_COMMIT_ENABLED_FIELD = 'mopt_avalara__doc_commit_enabled';

    const LANDEDCOST_ENABLED_FIELD = 'mopt_avalara__landedcost_enabled';
    
    const INCOTERMS_FIELD = 'mopt_avalara__incoterms';
    
    const ADDRESS_VALIDATION_COUNTRIES_FIELD = 'mopt_avalara_addressvalidation_countries';
    
    const LOG_LEVEL_FIELD = 'mopt_avalara_loglevel';
    
    const LOG_ROTATION_DAYS_FIELD = 'mopt_avalara_log_rotation_days';
    
    const ORIGIN_ADDRESS_LINE_1_FIELD = 'mopt_avalara__origin_address__line1';
    
    const ORIGIN_ADDRESS_LINE_2_FIELD = 'mopt_avalara__origin_address__line2';
    
    const ORIGIN_ADDRESS_LINE_3_FIELD = 'mopt_avalara__origin_address__line3';
    
    const ORIGIN_POSTAL_CODE_FIELD = 'mopt_avalara__origin_address__postal_code';
    
    const ORIGIN_CITY_FIELD = 'mopt_avalara__origin_address__city';
    
    const ORIGIN_REGION_FIELD = 'mopt_avalara__origin_address__region';
    
    const ORIGIN_COUNTRY_FIELD = 'mopt_avalara__origin_address__country';
    
    
    /**
     * Values and options
     */
    const DELIVERY_COUNTRY_NO_VALIDATION = 1;
    const DELIVERY_COUNTRY_USA = 2;
    const DELIVERY_COUNTRY_CANADA = 3;
    const DELIVERY_COUNTRY_USA_AND_CANADA = 4;
    
    /**
     * Incoterms
     */
    const INCOTERMS_DEFAULT = 'Use default';
    const INCOTERMS_DAP = 'DAP';
    const INCOTERMS_DAP_LABEL = 'Delivered at Place (DAP)';
    const INCOTERMS_DDP = 'DDP';
    const INCOTERMS_DDP_LABEL = 'Delivered Duty Paid (DDP)';
    
    /**
     *
     * @var \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    private $bootstrap;
    
    /**
     *
     * @param \Shopware_Plugins_Backend_MoptAvalara_Bootstrap $bootstrap
     */
    public function __construct(\Shopware_Plugins_Backend_MoptAvalara_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Will return array of arrays ['iso', 'Country name']
     * @return array
     */
    public function getCountriesISO()
    {
        $countries = [];
        $filePath = $this->bootstrap->Path() . 'Data/countries.json';
        if (!$json = json_decode(file_get_contents($filePath), true)) {
            return $countries;
        }
        
        foreach ($json as $line) {
            $countries[] = [$line['code'], ucfirst(strtolower($line['name']))];
        }
        
        usort($countries, function ($a, $b) {
            return ($a[1] > $b[1])
                ? 1
                : -1
            ;
        });
        
        return $countries;
    }
    
    /**
     * Will return array of arrays ['iso', 'Region name']
     * @param string $countryIso
     * @return array
     */
    public function getRegionsISO($countryIso = null)
    {
        $regions = [];
        $filePath = $this->bootstrap->Path() . 'Data/regions.json';
        if (!$json = json_decode(file_get_contents($filePath), true)) {
            return $regions;
        }
        
        foreach ($json as $line) {
            if ($countryIso && $line['countryCode'] != $countryIso) {
                continue;
            }
            $regions[] = [$line['code'], ucfirst(strtolower($line['name']))];
        }
        
        usort($regions, function ($a, $b) {
            return ($a[1] > $b[1])
                ? 1
                : -1
            ;
        });
        
        return $regions;
    }
    
    /**
     * Will create all plugin config forms and fields
     */
    public function createForms()
    {
        $form = $this->bootstrap->Form();
        $parent = $this->bootstrap->Forms()->findOneBy(['name' => 'Frontend']);
        $form->setParent($parent);

        $form->setElement('base', 'mopt_avalara__fieldset__credentials', [
            'xtype' => 'component',
            'autoEl' => [
                'tag' => 'h2',
                'html' => 'Credentials',
            ],
            'style' => [
                'marginBottom' => '5px',
                'padding' => '5px',
            ],
            'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
        ]);

        $form->setElement('boolean', self::IS_LIVE_MODE_FIELD, [
            'label' => 'Live Modus',
        ]);

        $form->setElement('text', self::ACCOUNT_NUMBER_FIELD, [
            'label' => 'Account number',
            'required' => true,
        ]);

        $form->setElement('text', self::LICENSE_KEY_FIELD, [
            'label' => 'License key',
            'required' => true,
        ]);
        
        $form->setElement('text', self::COMPANY_CODE_FIELD, [
            'label' => 'Company code',
            'required' => true,
        ]);

        $form->setElement('base', 'mopt_avalara__fieldset__configuration', [
            'xtype' => 'component',
            'autoEl' => [
                'tag' => 'h2',
                'html' => 'Configuration',
            ],
            'style' => [
                'marginTop' => '20px',
                'marginBottom' => '5px',
                'padding' => '5px',
            ],
            'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
        ]);

        $form->setElement('boolean', self::TAX_ENABLED_FIELD, [
            'label' => 'Enable Avalara SalesTax calculation',
            'description' => 'Choose, if you want to use the Avalara SalesTax Calculation.',
        ]);

        $form->setElement('boolean', self::DOC_COMMIT_ENABLED_FIELD, [
            'label' => 'Enable document committing',
            'description' => 'Disable document committing will result that all calls will be done with DocType=SalesOrder and suppress any non-getTax calls(i.e.canceltax,postTax)',
        ]);
        
        $form->setElement('boolean', self::LANDEDCOST_ENABLED_FIELD, [
            'label' => 'Enable Avalara Landed cost calculation',
            'description' => 'Choose, if you want to use the Avalara Landed cost calculation.',
        ]);

        $form->setElement('select', self::INCOTERMS_FIELD, [
            'label' => 'Default incoterms for Landed cost',
            'description' => 'Terms of sale. Used to determine buyer obligations for a landed cost.',
            'value' => self::INCOTERMS_DAP,
            'store' => [
                [self::INCOTERMS_DAP, self::INCOTERMS_DAP_LABEL],
                [self::INCOTERMS_DDP, self::INCOTERMS_DDP_LABEL],
            ],
        ]);
        
        $form->setElement('select', self::ADDRESS_VALIDATION_COUNTRIES_FIELD, [
            'label' => 'Address-validation for following countries',
            'description' => 'Choose the delivery countries, which should be covered by the Avalara Tax Calculation',
            'value' => self::DELIVERY_COUNTRY_USA_AND_CANADA,
            'store' => [
                [self::DELIVERY_COUNTRY_NO_VALIDATION, 'No Validation'],
                [self::DELIVERY_COUNTRY_USA, 'USA only'],
                [self::DELIVERY_COUNTRY_CANADA, 'Canada only'],
                [self::DELIVERY_COUNTRY_USA_AND_CANADA, 'USA & Canada']
            ],
        ]);

        $form->setElement('select', self::LOG_LEVEL_FIELD, [
            'label' => 'Log level',
            'description' => 'Choose the loglevel of Avalara, which will be logged ',
            'value' => 'ERROR',
            'store' => [
                ['DEBUG', 'DEBUG'],
                ['ERROR', 'ERROR'],
                ['INFO', 'INFO'],
            ],
        ]);

        $form->setElement('number', self::LOG_ROTATION_DAYS_FIELD, [
            'label' => 'Log rotation days',
            'description' => 'How many days log files to be stored. 0 means unlimited.',
            'value' => self::LOGGER_DEFAULT_ROTATING_DAYS,
            'minValue' => 0,
            'maxValue' => 365,
            'required' => true,
        ]);

        $form->setElement('base', 'mopt_avalara__fieldset__origin_address', [
            'xtype' => 'component',
            'autoEl' => [
                'tag' => 'h2',
                'html' => 'Origin address',
            ],
            'style' => [
                'marginTop' => '20px',
                'marginBottom' => '5px',
                'padding' => '5px',
            ],
            'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
        ]);

        $form->setElement('text', self::ORIGIN_ADDRESS_LINE_1_FIELD, [
            'label' => 'Line1',
            'required' => true,
        ]);

        $form->setElement('text', self::ORIGIN_ADDRESS_LINE_2_FIELD, [
            'label' => 'Line2',
            'required' => false,
        ]);

        $form->setElement('text', self::ORIGIN_ADDRESS_LINE_3_FIELD, [
            'label' => 'Line3',
            'required' => false,
        ]);

        $form->setElement('text', self::ORIGIN_POSTAL_CODE_FIELD, [
            'label' => 'Postal code (zipcode)',
            'required' => true,
        ]);

        $form->setElement('select', self::ORIGIN_COUNTRY_FIELD, [
            'label' => 'Country (ISO 3166 country code)',
            'required' => true,
            'store' => $this->getCountriesISO(),
        ]);

        $form->setElement('text', self::ORIGIN_REGION_FIELD, [
            'label' => 'Region (ISO 3166 region code)',
            'required' => true,
        ]);
        
        $form->setElement('text', self::ORIGIN_CITY_FIELD, [
            'label' => 'City',
            'required' => true,
        ]);

        $form->setElement('base', 'mopt_avalara__fieldset__test', [
            'xtype' => 'component',
            'autoEl' => [
                'tag' => 'h2',
                'html' => 'Actions',
            ],
            'style' => [
                'marginTop' => '20px',
                'marginBottom' => '5px',
                'padding' => '5px',
            ],
            'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
        ]);

        $context = $this->getContext();

        $remoteUrlConnectionTest = Shopware()->Front()->Router()->assemble(
            ["module" => "backend", "controller" => "MoptAvalaraBackendProxy", "action" => "getConnectionTest"],
            $context
        );

        $downloadUrlCall = Shopware()->Front()->Router()->assemble(
            ["module" => "backend", "controller" => "MoptAvalaraLog", "action" => "downloadLogfile"],
            $context
        );

        $form->setElement('button', 'mopt_avalara__license_check', [
            'label' => 'Connection-Test',
            'maxWidth' => '150',
            'handler' => 'function (){
                var token = Ext.CSRFService.getToken();
                var urlConnectionTest = "' . $remoteUrlConnectionTest . '?__csrf_token=" + token;
                Ext.Ajax.request({
                   scope:this,
                   url: urlConnectionTest,
                   success: function(result,request) {
                   var jsonResponse = Ext.JSON.decode(result.responseText);
                   var successPrefixHtml = "<div class=\"sprite-tick-small\"  style=\"width: 25px; height: 25px; float: left;\">&nbsp;</div><div style=\"float: left;\">";
                   var failurePrefixHtml = "<div class=\"sprite-cross-small\"  style=\"width: 25px; height: 25px; float: left;\">&nbsp;</div><div style=\"float: left;\">";
                   var htmlConnectionTest;
                   if (jsonResponse.info){
                   htmlConnectionTest = successPrefixHtml + jsonResponse.info + "</div>";
                   }
                   else {
                   htmlConnectionTest = failurePrefixHtml + jsonResponse.error + "</div>";
                   }
                   var connectionTestForm = new Ext.form.Panel({
                        width: 600,
                        bodyPadding: 5,
                        title: "Avalara connection test",
                        floating: true,
                        closable : true,
                        draggable : true,
                        items: [
                          {  
                            bodyPadding: 5,
                            title: "Connection test",
                            html: htmlConnectionTest
                          }, 
                        ]
                    });
                    connectionTestForm.show();
                    },
                     failure: function() {
                          Ext.MessageBox.alert(\'Oops\',\'Connection test failed, please check if the plugin is activated!\');
                     }
                  });
            }'
        ]);

        $form->setElement('button', 'mopt_avalara__log', [
            'label' => 'Download logfile',
            'maxWidth' => '150',
            'handler' => 'function (){
                var token = Ext.CSRFService.getToken();
                var url = "' . $downloadUrlCall . '?__csrf_token=" + token;

                var manualDownloadForm = new Ext.form.Panel({
                  width: 400,
                  bodyPadding: 5,
                  title: "Avalara",
                  floating: true,
                  closable : true,
                  draggable : true,
                  items: [
                    {  
                      bodyPadding: 5,
                      title: "Log",
                      html: "The module has to be active to download the logfile.<br />'
                . '<div class=\"sprite-drive-download\" style=\"width: 25px; height: 25px; float: left;\">&nbsp;</div>'
                . '<div style=\"float: left;\"><a href=\'" + url + "\'  target=\'_blank\'>Download</a></div>"
                    } 
                  ]
                });
                manualDownloadForm.show();
            }',
        ]);



        //set positions
        $elements = [
            'mopt_avalara__fieldset__credentials',
            self::IS_LIVE_MODE_FIELD,
            self::ACCOUNT_NUMBER_FIELD,
            self::LICENSE_KEY_FIELD,
            self::COMPANY_CODE_FIELD,
            'mopt_avalara__fieldset__configuration',
            self::TAX_ENABLED_FIELD,
            self::DOC_COMMIT_ENABLED_FIELD,
            self::LANDEDCOST_ENABLED_FIELD,
            self::INCOTERMS_FIELD,
            self::ADDRESS_VALIDATION_COUNTRIES_FIELD,
            self::LOG_LEVEL_FIELD,
            self::LOG_ROTATION_DAYS_FIELD,
            'mopt_avalara__fieldset__origin_address',
            self::ORIGIN_ADDRESS_LINE_1_FIELD,
            self::ORIGIN_ADDRESS_LINE_2_FIELD,
            self::ORIGIN_ADDRESS_LINE_3_FIELD,
            self::ORIGIN_POSTAL_CODE_FIELD,
            self::ORIGIN_COUNTRY_FIELD,
            self::ORIGIN_REGION_FIELD,
            self::ORIGIN_CITY_FIELD,
            'mopt_avalara__fieldset__test',
            'mopt_avalara__license_check',
            'mopt_avalara__log',
        ];

        /* @var $element \Shopware\Models\Config\Element */
        foreach ($form->getElements() as $element) {
            $element->setPosition(array_search($element->getName(), $elements));
        }
    }
    
    /**
     *
     * @return \Shopware\Components\Routing\Context
     */
    private function getContext()
    {
        $container = Shopware()->Container();
        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = $container->get('models')->getRepository(\Shopware\Models\Shop\Shop::class);
        /** @var $shop \Shopware\Models\Shop\Shop */
        $shop = $repository->getActiveDefault();
        /** @var $config \Shopware_Components_Config */
        $config = $container->get('config');
        
        return \Shopware\Components\Routing\Context::createFromShop($shop, $config);
    }
}
