<?php

$form = $this->Form();
$parent = $this->Forms()->findOneBy(array('name' => 'Frontend'));
$form->setParent($parent);

$form->setElement('base', 'mopt_avalara__fieldset__credentials', array(
    'xtype' => 'component',
    'autoEl' => array(
        'tag' => 'h2',
        'html' => 'Credentials',
    ),
    'style' => array(
        'marginBottom' => '5px',
        'padding' => '5px',
    ),
    'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
));

$form->setElement('boolean', 'mopt_avalara__is_live_mode', array(
    'label' => 'Live Modus',
));

$form->setElement('text', 'mopt_avalara__account_number', array(
    'label' => 'Account number',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__license_key', array(
    'label' => 'License key',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__company_code', array(
    'label' => 'Company code',
    'required' => true,
));

$form->setElement('base', 'mopt_avalara__fieldset__configuration', array(
    'xtype' => 'component',
    'autoEl' => array(
        'tag' => 'h2',
        'html' => 'Configuration',
    ),
    'style' => array(
        'marginTop' => '20px',
        'marginBottom' => '5px',
        'padding' => '5px',
    ),
    'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
));

$form->setElement('boolean', 'mopt_avalara__tax_enabled', array(
    'label' => 'Enable Avalara SalesTax calculation',
    'description' => 'Choose, if you want to use the Avalara SalesTax Calculation.',
));

$form->setElement('boolean', 'mopt_avalara__doc_commit_enabled', array(
    'label' => 'Enable document committing',
    'description' => 'Disable document committing will result that all calls will be done with DocType=SalesOrder and suppress any non-getTax calls(i.e.canceltax,postTax)',
));

$form->setElement('select', 'mopt_avalara_addressvalidation_countries', array('label' => 'Address-validation for following countries',
    'description' => 'Choose the delivery countries, which should be covered by the Avalara Tax Calculation',
    'value' => 4,
    'store' => array(
        array(1, 'No Validation'),
        array(2, 'USA only'),
        array(3, 'Canada only'),
        array(4, 'USA & Canada')
    ),
));

$form->setElement('select', 'mopt_avalara_loglevel', array(
    'label' => 'Log level',
    'description' => 'Choose the loglevel of Avalara, which will be logged ',
    'value' => 'ERROR',
    'store' => array(
        array('DEBUG', 'DEBUG'),
        array('ERROR', 'ERROR'),
        array('INFO', 'INFO'),
    ),
));

$form->setElement('number', 'mopt_avalara_log_rotation_days', array(
    'label' => 'Log rotation days',
    'description' => 'How many days log files to be stored. 0 means unlimited.',
    'value' => self::LOGGER_DEFAULT_ROTATING_DAYS,
    'minValue' => 0,
    'maxValue' => 365,
    'required' => true,
));

$form->setElement('base', 'mopt_avalara__fieldset__origin_address', array(
    'xtype' => 'component',
    'autoEl' => array(
        'tag' => 'h2',
        'html' => 'Origin address',
    ),
    'style' => array(
        'marginTop' => '20px',
        'marginBottom' => '5px',
        'padding' => '5px',
    ),
    'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
));

$form->setElement('text', 'mopt_avalara__origin_address__line1', array(
    'label' => 'line1',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__origin_address__line2', array(
    'label' => 'line2',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__origin_address__line3', array(
    'label' => 'line3',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__origin_address__postal_code', array(
    'label' => 'postal_code',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__origin_address__city', array(
    'label' => 'city',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__origin_address__region', array(
    'label' => 'region',
    'required' => true,
));

$form->setElement('text', 'mopt_avalara__origin_address__country', array(
    'label' => 'country',
    'required' => true,
));

$form->setElement('base', 'mopt_avalara__fieldset__test', array(
    'xtype' => 'component',
    'autoEl' => array(
        'tag' => 'h2',
        'html' => 'Actions',
    ),
    'style' => array(
        'marginTop' => '20px',
        'marginBottom' => '5px',
        'padding' => '5px',
    ),
    'baseCls' => 'x-panel-header-default-top x-panel-header-default x-window-header-text-default',
));
$container = Shopware()->Container();
/** @var \Shopware\Models\Shop\Repository $repository */
$repository = $container->get('models')->getRepository(\Shopware\Models\Shop\Shop::class);
/** @var $shop \Shopware\Models\Shop\Shop */
$shop = $repository->getActiveDefault();
/** @var $config \Shopware_Components_Config */
$config = $container->get('config');
$context = \Shopware\Components\Routing\Context::createFromShop($shop, $config);

$remoteUrlConnectionTest = Shopware()->Front()->Router()->assemble(
    array("controller" => "MoptAvalaraBackendProxy", "action" => "getConnectionTest"),
    $context
);

$downloadUrlCall = Shopware()->Front()->Router()->assemble(
    array("controller" => "MoptAvalaraLog", "action" => "downloadLogfile"),
    $context
);

$form->setElement('button', 'mopt_avalara__license_check', array(
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
));

$form->setElement('button', 'mopt_avalara__log', array(
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
));
    


//set positions
$elements = array(
    'mopt_avalara__fieldset__credentials',
    'mopt_avalara__is_live_mode',
    'mopt_avalara__account_number',
    'mopt_avalara__license_key',
    'mopt_avalara__company_code',
    'mopt_avalara__fieldset__configuration',
    'mopt_avalara__tax_enabled',
    'mopt_avalara__doc_commit_enabled',
    'mopt_avalara_addressvalidation_countries',
    'mopt_avalara_loglevel',
    'mopt_avalara_log_rotation_days',
    'mopt_avalara__fieldset__origin_address',
    'mopt_avalara__origin_address__line1',
    'mopt_avalara__origin_address__line2',
    'mopt_avalara__origin_address__line3',
    'mopt_avalara__origin_address__postal_code',
    'mopt_avalara__origin_address__city',
    'mopt_avalara__origin_address__region',
    'mopt_avalara__origin_address__country',
    'mopt_avalara__fieldset__test',
    'mopt_avalara__license_check',
    'mopt_avalara__log',
);

/* @var $element \Shopware\Models\Config\Element */
foreach ($form->getElements() as $element) {
    $element->setPosition(array_search($element->getName(), $elements));
}