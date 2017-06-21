//{namespace name="backend/mopt_avalara/order/view/order/detail"}
//{block name="backend/order/view/detail/window" append}
Ext.define('Shopware.apps.moptAvalara.Order.view.order.tabs.avalara_tab', {
    override: 'Shopware.apps.Order.view.detail.Window',
    extend: 'Ext.form.Panel',
    /**
     * @Override
     * Creates the main tab panel which displays the different tabs for the order sections.
     * To extend the tab panel this function can be override.
     *
     * @return Ext.tab.Panel
     */
    createTabPanel: function () {
        var me = this, result;
        result = me.callParent(arguments);
        result.add(me.createAvalaraTab());
        return result;
    },

    createAvalaraTab: function () {
        var me = this,
            exemptionCode = '',
            userId = me.record.getCustomerStore.first().get('id'),
            docCode = '',
            transactionType = '',
            incoterms = '',
            landedCost = ''
        ;
        Ext.Ajax.request({
            url: '{url controller=AttributeData action=loadData}',
            params: {
                _foreignKey: me.record.get('id'),
                _table: 's_order_attributes'
            },
            async: false,
            success: function(responseData, request) {
                var response = Ext.JSON.decode(responseData.responseText);
                docCode = response.data.__attribute_mopt_avalara_doc_code;
                transactionType = response.data.__attribute_mopt_avalara_transaction_type;
                incoterms = response.data.__attribute_mopt_avalara_incoterms;
                landedCost = response.data.__attribute_mopt_avalara_landedcost;
            }
        });

        Ext.Ajax.request({
            url: '{url controller=AttributeData action=loadData}',
            params: {
                _foreignKey: userId,
                _table: 's_user_attributes'
            },
            async: false,
            success: function(responseData, request) {
                var response = Ext.JSON.decode(responseData.responseText);
                exemptionCode = response.data.__attribute_mopt_avalara_exemption_code;
            }
        });
        
        var actionPanel = me.createActionPanel(transactionType);
        var itemsArray = [
            me.createInformationPanel(exemptionCode, transactionType, incoterms, docCode, landedCost)
        ];
        if (actionPanel) {
            itemsArray.push(actionPanel);
        }
        
        return Ext.create('Ext.container.Container', {
            defaults: me.defaults,
            title: 'Avalara',
            layout: {
                type: 'hbox'
            },
            style: 'padding: 5px',
            items: itemsArray
        });
    },
    
    createActionPanel: function (transactionType) {
        if (null === transactionType) {
            return null;
        }
        var me = this;
        var items = [{
            xtype: 'button',
            text: 'Reset update flag',
            width: 130,
            handler: function () {
                Ext.Ajax.request({
                    url: '{url controller="MoptAvalara" action="resetUpdateFlag"}',
                    method: 'POST',
                    params: { id: me.record.get('id')},
                    headers: { 'Accept': 'application/json'},
                    success: function (response)
                    {
                        var jsonData = Ext.JSON.decode(response.responseText);
                        Shopware.Notification.createGrowlMessage(
                                (jsonData.success ? '{s name=success}success{/s}' : '{s name=error}error{/s}'), 
                                jsonData.message, 
                                'Avalara');
                    }

                });
            }
        }];
    
        switch (transactionType) {
            case 'SalesOrder':
                items.push({
                    xtype: 'button',
                    text: 'Commit order to Avalara',
                    width: 130,
                    handler: function () {
                        Ext.Ajax.request({
                            url: '{url controller="MoptAvalara" action="commitOrder"}',
                            method: 'POST',
                            params: { id: me.record.get('id')},
                            headers: { 'Accept': 'application/json'},
                            success: function (response)
                            {
                                var jsonData = Ext.JSON.decode(response.responseText);
                                Shopware.Notification.createGrowlMessage(
                                        (jsonData.success ? '{s name=success}success{/s}' : '{s name=error}error{/s}'), 
                                        jsonData.message, 
                                        'Avalara');
                            }

                        });
                    }
                });
                break;
                
            case 'SalesInvoice':
                items.push({
                    xtype: 'button',
                    text: 'Cancel Tax',
                    width: 130,
                    handler: function () {
                        Ext.Ajax.request({
                            url: '{url controller="MoptAvalara" action="cancelOrder"}',
                            method: 'POST',
                            params: { id: me.record.get('id')},
                            headers: { 'Accept': 'application/json'},
                            success: function (response)
                            {
                                var jsonData = Ext.JSON.decode(response.responseText);
                                Shopware.Notification.createGrowlMessage(
                                        (jsonData.success ? '{s name=success}success{/s}' : '{s name=error}error{/s}'), 
                                        jsonData.message, 
                                        'Avalara');
                            }

                        });
                    }
                });
                break;
        }
        
        return Ext.create('Ext.form.Panel', {
            title: 'Actions',
            items: items,
            width: '45%',
            height: 130,
            bodyPadding: '5px 10px',
            layout: {
                type: 'vbox',
                defaultMargins: '5 0'
            }
        });
    },
    
    createInformationPanel: function (exemptionCode, transactionType, incoterms, docCode, landedCost) {
        return Ext.create('Ext.form.Panel', {
            title: 'Information',
            items: [
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Document code',
                    value: docCode ? docCode : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Transaction status',
                    value: transactionType ? transactionType : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Incoterms',
                    value: incoterms ? incoterms : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Customs duties and fees',
                    value: landedCost ? landedCost : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Exemption code',
                    value: exemptionCode ? exemptionCode : '-',
                    labelWidth: 170
                }
            ],
            width: '50%',
            height: 180,
            style: 'margin-right: 10px;',
            bodyPadding: '5px 10px'
        });
    }
});
//{/block}
